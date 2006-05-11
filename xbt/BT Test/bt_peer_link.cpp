#include "stdafx.h"
#include "bt_peer_link.h"

#include <algorithm>
#include "bt_file.h"
#include "bt_strings.h"
#include "server.h"

Cbt_peer_link::Cbt_peer_link()
{
	m_can_recv = false;
	m_can_send = false;
	m_f = NULL;
	m_state = 1;
}

Cbt_peer_link::~Cbt_peer_link()
{
}

int Cbt_peer_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 1:
		if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
		{
			close();
			return 0;
		}
		if (server()->bind_before_connect() && !m_s.setsockopt(SOL_SOCKET, SO_REUSEADDR, true))
			m_s.bind(htonl(INADDR_ANY), htons(m_f->local_port()));
		if (m_s.connect(m_a.sin_addr.s_addr, m_a.sin_port) && WSAGetLastError() != WSAEINPROGRESS && WSAGetLastError() != WSAEWOULDBLOCK)
		{
			alert(Calert::debug, "Peer: connect failed: " + Csocket::error2a(WSAGetLastError()));
			close();
			return 0;
		}
		write_handshake();
		m_state = 2;
	case 2:
		FD_SET(m_s, fd_except_set);
	case 3:
	case 4:
		if (!m_can_recv)
			FD_SET(m_s, fd_read_set);
		if (!m_can_send)
			FD_SET(m_s, fd_write_set);
		return m_s;
	}
	return 0;
}

int Cbt_peer_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 2:
		if (FD_ISSET(m_s, fd_except_set))
		{
			int e = 0;
			socklen_t size = sizeof(int);
			m_s.getsockopt(SOL_SOCKET, SO_ERROR, &e, size);
			if (e == WSAEADDRINUSE)
			{
				if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
					return 1;
				if (m_s.connect(m_a.sin_addr.s_addr, m_a.sin_port) && WSAGetLastError() != WSAEINPROGRESS && WSAGetLastError() != WSAEWOULDBLOCK)
				{
					alert(Calert::debug, "Peer: connect failed: " + Csocket::error2a(WSAGetLastError()));
					return 1;
				}
				return 0;
			}
			if (server()->log_peer_connect_failures())
				alert(Calert::debug, "Peer: connect failed: " + Csocket::error2a(e));
			return 1;
		}
	case 3:
	case 4:
		if (FD_ISSET(m_s, fd_write_set))
			m_can_send = true;
		if (FD_ISSET(m_s, fd_read_set))
			m_can_recv = true;
		if (m_can_recv)
		{
			if (recv())
				return 1;
			switch (m_state)
			{
			case 2:
				if (m_read_b.cb_r() < hs_size)
					break;
				if (read_handshake(m_read_b.r()))
					return 1;
				m_read_b.cb_r(hs_size);
				m_f->insert_old_peer(m_a.sin_addr.s_addr, m_a.sin_port);
				m_state = 4;
			case 4:
				if (m_read_b.cb_r() < 20)
					break;
				m_remote_peer_id.assign(m_read_b.r(), 20);
				if (m_remote_peer_id == m_f->peer_id())
					return 1;
				m_read_b.cb_r(20);
				m_remote_pieces.resize(m_f->m_pieces.size());
				write_extended_handshake();
				write_get_peers();
				if (!m_f->m_info.size())
					write_get_info();
				write_bitfield();
				m_state = 3;
			case 3:
				while (1)
				{
					while (m_read_b.cb_r() >= 4)
					{
						unsigned int cb_m = read_int(4, m_read_b.r());
						if (cb_m)
						{
							if (cb_m < 0 || cb_m > 64 << 10)
								return 1;
							if (m_read_b.cb_r() < 4 + cb_m)
								break;
							const char* s = m_read_b.r() + 4;
							m_read_b.cb_r(4 + cb_m);
							if (read_message(const_memory_range(s, cb_m)))
								return 1;
						}
						else
							m_read_b.cb_r(4);
					}
					if (m_read_b.cb_r() == m_read_b.cb_read())
						break;
					m_read_b.combine();
				}
				if (!m_read_b.cb_r())
					m_read_b.size(0);
				break;
			}
		}
		if (m_state == 3)
		{
			if (time() - m_check_pieces_time > (m_f->end_mode() ? 5 : 30))
			{
				check_pieces();
				if (!m_local_interested && m_f->next_invalid_piece(*this) != -1)
					interested(true);
			}
			write_choke(m_local_choked_goal);
			write_interested(m_local_interested_goal);
			/*
			if (m_local_requests.empty() || time() - m_local_requests.back().stime > 120)
				mc_local_requests_pending = 0;
			*/
			while (m_local_interested && m_f->state() == Cbt_file::s_running && !m_remote_choked && mc_local_requests_pending < c_max_requests_pending())
			{
				int a = m_f->next_invalid_piece(*this);
				if (a < 0)
				{
					interested(false);
					break;
				}
				Cbt_piece& piece = m_f->m_pieces[a];
				for (int b; mc_local_requests_pending < c_max_requests_pending() && (b = piece.next_invalid_sub_piece(this)) != -1; )
				{
					t_local_request request(m_f->mcb_piece * a + piece.cb_sub_piece() * b, piece.cb_sub_piece(b), time());
					m_local_requests.push_back(request);
					if (m_f->m_merkle)
						write_merkle_request(request.offset, 127);
					else
						write_request(request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, request.size);
				}
			}
			if (time() - m_stime > 120)
			{
				write_haves();
				if (m_write_b.empty())
					write_keepalive();
			}
		}
		break;
	}
	if (!m_left && !m_f->m_left)
	{
		if (server()->log_peer_connection_closures())
			alert(Calert::debug, "Peer: seeder to seeder link closed (" + peer_id2a(m_remote_peer_id) + ")");
		return 1;
	}
	return 0;
}

void Cbt_peer_link::close()
{
	m_s.close();
	clear_local_requests();
	m_read_b.size(0);
	m_state = -1;
	for (size_t i = 0; i < m_remote_pieces.size(); i++)
		m_f->m_pieces[i].mc_peers -= m_remote_pieces[i];
	m_remote_pieces.clear();
}

void Cbt_peer_link::write(const Cvirtual_binary& s, bool user_data)
{
	m_write_b.push_back(Cbt_pl_write_data(s, user_data));
}

int Cbt_peer_link::cb_write_buffer() const
{
	int cb = 0;
	for (t_write_buffer::const_iterator i = m_write_b.begin(); i != m_write_b.end(); i++)
		cb += i->m_s.size();
	if (!m_local_choked)
	{
		for (t_remote_requests::const_iterator i = m_remote_requests.begin(); i != m_remote_requests.end(); i++)
			cb += i->size + (m_f->m_merkle ? 10 : 13);
	}
	if (cb)
		cb += 9 * m_have_queue.size();
	return cb;
}

int Cbt_peer_link::recv()
{
	if (m_can_recv && !m_read_b.size())
		m_read_b.size(65 << 10);
	for (int r; m_can_recv && m_read_b.cb_w() && (r = m_s.recv(m_read_b.w(), m_read_b.cb_w())); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
			{
				m_can_recv = false;
				return 0;
			}
			if (server()->log_peer_recv_failures())
				alert(Calert::debug, "Peer: recv failed: " + Csocket::error2a(e));
			return 1;
		}
		if (r != m_read_b.cb_w())
			m_can_recv = false;
		m_downloaded += r;
		m_down_counter.add(r, time());
		m_rtime = time();
		m_read_b.cb_w(r);
		m_f->m_downloaded_l5 += r;
	}
	if (!m_can_recv || !m_read_b.cb_w())
		return 0;
	if (server()->log_peer_connection_closures())
		alert(Calert::debug, m_local_link ? "Peer: local link closed" : "Peer: remote link closed");
	return 1;
}

int Cbt_peer_link::send(int& send_quota)
{
	while (m_can_send && send_quota)
	{
		if (m_write_b.empty())
		{
			if (!m_local_choked && !m_remote_requests.empty())
			{
				const t_remote_request& request = m_remote_requests.front();
				Cvirtual_binary d(request.size);
				if (!m_f->read_data(request.offset, d))
				{
					if (m_f->m_merkle)
						write_merkle_piece(request.offset, d, m_f->get_hashes(request.offset, request.c_hashes));
					else
						write_piece(request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, d);
				}
				m_remote_requests.pop_front();
			}
			if (m_write_b.empty())
				return 0;
		}
		write_haves();
		Cbt_pl_write_data& d = m_write_b.front();
		int r = m_s.send(d.m_s, min(d.m_s.size(), send_quota));
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
			{
				m_can_send = false;
				return 0;
			}
			if (server()->log_peer_send_failures())
				alert(Calert::debug, "Peer: send failed: " + Csocket::error2a(e));
			return 1;
		}
		else if (!r)
			return 0;
		if (r != min(d.m_s.size(), send_quota))
			m_can_send = false;
		if (d.m_user_data)
		{
			m_uploaded += r;
			m_up_counter.add(r, time());
			m_f->m_uploaded += r;
			m_f->m_up_counter.add(r, time());
			m_f->m_total_uploaded += r;
		}
		send_quota -= r;
		m_stime = time();
		d.m_s += r;
		if (!d.m_s.size())
			m_write_b.pop_front();
		m_f->m_uploaded_l5 += r;
	}
	return 0;
}

void Cbt_peer_link::remote_has(unsigned int v)
{
	if (v < m_f->m_pieces.size() && !m_remote_pieces[v])
	{
		m_f->m_pieces[v].mc_peers++;
		m_left -= m_f->m_pieces[v].size();
		m_remote_pieces[v] = true;
		if (!m_local_interested && !m_f->m_pieces[v].valid())
			interested(true);
	}
}

void Cbt_peer_link::remote_requests(int piece, int offset, int size)
{
	if (piece < 0 || piece >= m_f->c_pieces() || offset < 0 || size < 0 || size > min(m_f->m_pieces[piece].size(), 1 << 15)
		|| m_remote_requests.size() >= 256 || !m_f->m_pieces[piece].valid() || m_local_choked)
		return;
	m_remote_requests.push_back(t_remote_request(m_f->mcb_piece * piece + offset, size, 0));
}

void Cbt_peer_link::remote_merkle_requests(long long offset, int c_hashes)
{
	unsigned int piece = offset / m_f->mcb_piece;
	if (offset < 0 || m_remote_requests.size() >= 256 || piece >= m_f->m_pieces.size() || !m_f->m_pieces[piece].valid() || m_local_choked)
		return;
	m_remote_requests.push_back(t_remote_request(offset, min(m_f->m_pieces[piece].size() - offset % m_f->mcb_piece, 32 << 10), c_hashes));
}

void Cbt_peer_link::remote_cancels(int piece, int offset, int size)
{
	for (t_remote_requests::iterator i = m_remote_requests.begin(); i != m_remote_requests.end(); i++)
	{
		if (i->offset != m_f->mcb_piece * piece + offset)
			continue;
		m_remote_requests.erase(i);
		return;
	}
}

void Cbt_peer_link::remote_merkle_cancels(long long offset)
{
	for (t_remote_requests::iterator i = m_remote_requests.begin(); i != m_remote_requests.end(); i++)
	{
		if (i->offset != offset)
			continue;
		m_remote_requests.erase(i);
		return;
	}
}

int Cbt_peer_link::read_handshake(const char* h)
{
	if (std::string(h + hs_info_hash, 20) != m_f->m_info_hash)
	{
		alert(Calert::warn, "Peer: handshake failed");
		return 1;
	}
	m_get_info_extension = h[hs_reserved + 7] & 1;
	m_get_peers_extension = h[hs_reserved + 7] & 2;
	m_extended_extension = h[hs_reserved + 5] & 0x10;
	m_ut_pex_extension = 0;
	return 0;
}

void Cbt_peer_link::write_handshake()
{
	if (1)
		write(const_memory_range("\x13""BitTorrent protocol\0\0\0\0\0\x10\0\2", 28));
	else
		write(const_memory_range("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\16\0\2", 28));
	write(Cvirtual_binary(m_f->m_info_hash));
	write(Cvirtual_binary(m_f->peer_id()));
	m_local_choked = true;
	m_local_choked_goal = true;
	m_local_interested = false;
	m_local_interested_goal = false;
	m_remote_choked = true;
	m_remote_interested = false;
	m_downloaded = m_uploaded = 0;
	m_left = m_f->size();
	m_get_peers_stime = 0;
	mc_local_requests_pending = 0;
	mc_max_requests_pending = 1;
	m_peers_stime = 0;
	m_check_pieces_time = m_rtime = m_stime = time();
}

void Cbt_peer_link::write_extended_handshake()
{
	if (!m_extended_extension)
		return;
	Cbvalue d0;
	d0.d("m", Cbvalue().d("ut_pex", bti_extended_ut_pex));
	Cvirtual_binary d;
	byte* w = d.write_start(6 + d0.pre_read());
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_extended;
	*w++ = bti_extended_handshake;
	d0.read(w);
	write(d);
}

void Cbt_peer_link::write_keepalive()
{
	Cvirtual_binary d;
	byte* w = d.write_start(4);
	w = write_int(4, w, d.size() - 4);
	write(d);
}

void Cbt_peer_link::queue_have(int a)
{
	m_have_queue.insert(a);
}

void Cbt_peer_link::write_have(int a)
{
	if (m_remote_pieces.empty() || m_remote_pieces[a])
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_have;
	w = write_int(4, w, a);
	write(d);
}

void Cbt_peer_link::write_haves()
{
	for (t_have_queue::const_iterator i = m_have_queue.begin(); i != m_have_queue.end(); i++)
		write_have(*i);
	m_have_queue.clear();
}

void Cbt_peer_link::write_bitfield()
{
	if (!m_f->c_valid_pieces())
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(5 + (m_f->m_pieces.size() + 7 >> 3));
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_bitfield;
	int j = 0;
	for (Cbt_file::t_pieces::const_iterator i = m_f->m_pieces.begin(); i != m_f->m_pieces.end(); i++, j++)
	{
		if (!j)
			*w = 0;
		if (i->valid())
			*w |= 0x80 >> j;
		if (j == 7)
		{
			j = -1;
			w++;
		}
	}
	write(d);
}

void Cbt_peer_link::write_piece(int piece, int offset, const Cvirtual_binary& s)
{
	Cvirtual_binary d;
	byte* w = d.write_start(13);
	w = write_int(4, w, d.size() + s.size() - 4);
	*w++ = bti_piece;
	w = write_int(4, w, piece);
	w = write_int(4, w, offset);
	write(d);
	write(s, true);
}

void Cbt_peer_link::write_merkle_piece(long long offset, const_memory_range s, const std::string& hashes)
{
	Cvirtual_binary d;
	byte* w = d.write_start(10 + s.size() + hashes.size());
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_piece;
	w = write_int(4, w, offset >> 15);
	*w++ = hashes.size() / 20;
	memcpy(w, s, s.size());
	w += s.size();
	memcpy(w, hashes.data(), hashes.size());
	w += hashes.size();
	assert(w == d.end());
	write(d, true);
}

void Cbt_peer_link::choked(bool v)
{
	m_local_choked_goal = v;
}

void Cbt_peer_link::write_choke(bool v)
{
	if (m_local_choked == v)
		return;
	m_local_choked = v;
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_unchoke - v;
	write(d);
	if (v)
		m_remote_requests.clear();
}

void Cbt_peer_link::interested(bool v)
{
	m_local_interested_goal = v;
}

void Cbt_peer_link::write_interested(bool v)
{
	if (m_local_interested == v)
		return;
	m_local_interested = v;
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_uninterested - v;
	write(d);
}

void Cbt_peer_link::write_request(int piece, int offset, int size)
{
	Cvirtual_binary d;
	byte* w = d.write_start(17);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_request;
	w = write_int(4, w, piece);
	w = write_int(4, w, offset);
	w = write_int(4, w, size);
	write(d);
	mc_local_requests_pending++;
}

void Cbt_peer_link::write_merkle_request(long long offset, int c_hashes)
{
	Cvirtual_binary d;
	byte* w = d.write_start(10);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_request;
	w = write_int(4, w, offset >> 15);
	*w++ = c_hashes;
	write(d);
	mc_local_requests_pending++;
}

void Cbt_peer_link::write_cancel(int piece, int offset, int size)
{
	Cvirtual_binary d;
	byte* w = d.write_start(17);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_cancel;
	w = write_int(4, w, piece);
	w = write_int(4, w, offset);
	w = write_int(4, w, size);
	write(d);
}

void Cbt_peer_link::write_merkle_cancel(long long offset)
{
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_cancel;
	w = write_int(4, w, offset >> 15);
	write(d);
}

void Cbt_peer_link::write_get_info()
{
	if (!m_get_info_extension || m_f->m_info.size())
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_get_info;
	write(d);
}

void Cbt_peer_link::read_info(const_memory_range r)
{
	if (m_f->m_info.size() || Csha1(r).read() != m_f->m_info_hash)
		return;
	m_f->info(Cbvalue(r));
	m_f->open();
}

void Cbt_peer_link::write_info()
{
	Cvirtual_binary d;
	byte* w = d.write_start(5 + m_f->m_info.size());
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_info;
	m_f->m_info.read(w);
	write(d);
}

void Cbt_peer_link::write_get_peers()
{
	if (!m_get_peers_extension)
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(7);
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_get_peers;
	w = write_int(2, w, m_f->local_port());
	write(d);
	m_get_peers_stime = time();
}

void Cbt_peer_link::write_peers()
{
	Cvirtual_binary d;
	byte* w = d.write_start(7 + 6 * m_f->m_peers.size());
	w = write_int(4, w, d.size() - 4);
	*w++ = bti_peers;
	w = write_int(2, w, m_f->local_port());
	for (Cbt_file::t_peers::const_iterator i = m_f->m_peers.begin(); i != m_f->m_peers.end(); i++)
	{
		w = write_int(4, w, ntohl(i->m_a.sin_addr.s_addr));
		w = write_int(2, w, ntohs(i->m_a.sin_port));
	}
	write(d);
	m_peers_stime = time();
}

int Cbt_peer_link::read_piece(int piece, int offset, const_memory_range s)
{
	while (!m_local_requests.empty() && m_local_requests.front().offset != m_f->mcb_piece * piece + offset)
	{
		m_f->m_pieces[m_local_requests.front().offset / m_f->mcb_piece].erase_peer(this, m_local_requests.front().offset % m_f->mcb_piece);
		m_local_requests.pop_front();
	}
	if (m_local_requests.empty())
	{
		alert(Calert::warn, "No matching request found, piece: " + n(piece) + ", offset: " + n(offset) + ", size: " + b2a(s.size(), "b") + " (" + peer_id2a(m_remote_peer_id) + ")");
		return 1;
	}
	mc_local_requests_pending--;
	t_local_requests::iterator i = m_local_requests.begin();
	int t = time() - i->stime;
	mc_max_requests_pending = t ? max(1, min(min(120 / t, mc_max_requests_pending + 1), 8)) : 8;
	m_f->m_downloaded += s.size();
	m_f->m_down_counter.add(s.size(), time());
	m_f->m_total_downloaded += s.size();
	m_local_requests.erase(i);
	write_data(m_f->mcb_piece * piece + offset, s, t);
	return 0;
}

void Cbt_peer_link::read_merkle_piece(long long offset, const_memory_range s, const std::string& hashes)
{
	mc_local_requests_pending--;
	if (!m_f->test_and_set_hashes(offset, Cmerkle_tree::compute_root(s), hashes))
	{
		alert(Calert::warn, "Chunk " + n(offset >> 15) + ": invalid");
		return;
	}
	write_data(offset, s, 0);
	m_f->m_downloaded += s.size();
	m_f->m_down_counter.add(s.size(), time());
	m_f->m_total_downloaded += s.size();
}

int Cbt_peer_link::write_data(long long o, const_memory_range s, int latency)
{
	unsigned int a = o / m_f->mcb_piece;
	if (a >= m_f->m_pieces.size())
		return 0;
	Cbt_piece& piece = m_f->m_pieces[a];
	if (!m_f->write_data(o, s, this))
		return 0;
	int b = o % m_f->mcb_piece / piece.cb_sub_piece();
	if (o % piece.cb_sub_piece())
		alert(Calert::debug, "Piece " + n(a) + ", offset " + n(o % m_f->mcb_piece) + ", size: " + b2a(s.size()) + ": invalid offset (" + peer_id2a(m_remote_peer_id) + ")");
	else if (s.size() != piece.cb_sub_piece(b))
		alert(Calert::debug, "Piece " + n(a) + ", chunk " + n(b) + ", size: " + b2a(s.size()) + ": invalid size (" + peer_id2a(m_remote_peer_id) + ")");
	else
		alert(Calert::debug, "Piece " + n(a) + ", chunk " + n(b) + ", latency: " + n(latency) + " s: rejected (" + peer_id2a(m_remote_peer_id) + ")");
	return 1;
}

int Cbt_peer_link::read_message(const_memory_range s)
{
	switch (*s++)
	{
	case bti_choke:
		m_remote_choked = true;
		// clear_local_requests();
		// m_local_requests.clear();
		mc_local_requests_pending = 0;
		mc_max_requests_pending = min(mc_max_requests_pending, 2);
		break;
	case bti_unchoke:
		m_remote_choked = false;
		break;
	case bti_interested:
		m_remote_interested = true;
		break;
	case bti_uninterested:
		m_remote_interested = false;
		break;
	case bti_have:
		if (s.size() >= 4)
			remote_has(read_int(4, s));
		break;
	case bti_bitfield:
		if (!m_f->m_info.size())
			m_remote_pieces.resize(s.size() << 3);
		for (int i = 0; s < s.end; s++)
		{
			for (int j = 0; j < 8; i++, j++)
			{
				if (*s & 0x80 >> j)
					remote_has(i);
			}
		}
		break;
	case bti_request:
		if (m_f->m_merkle)
		{
			if (s.size() >= 5)
			{
				remote_merkle_requests(static_cast<long long>(read_int(4, s)) << 15, s[4]);
			}
		}
		else if (s.size() >= 12)
		{
			remote_requests(read_int(4, s), read_int(4, s + 4), read_int(4, s + 8));
		}
		break;
	case bti_piece:
		if (m_f->m_merkle)
		{
			if (s.size() >= 5 && s.size() >= 20 * s[4] + 5)
			{
				s += 5;
				read_merkle_piece(static_cast<long long>(read_int(4, s - 4)) << 15, const_memory_range(s, s.end - 20 * s[-1]), std::string(reinterpret_cast<const char*>(s.end - 20 * s[-1]), 20 * s[-1]));
			}
		}
		else if (s.size() >= 8)
		{
			s += 8;
			return read_piece(read_int(4, s - 8), read_int(4, s - 4), s);
		}
		break;
	case bti_cancel:
		if (s.size() >= 12)
		{
			remote_cancels(read_int(4, s), read_int(4, s + 4), read_int(4, s + 8));
		}
		break;
	case bti_get_info:
		write_info();
		break;
	case bti_info:
		read_info(s);
		break;
	case bti_get_peers:
		alert(Calert::debug, "Peer: get_peers");
		if (s.size() >= 2 && time() - m_peers_stime > 300)
			write_peers();
		break;
	case bti_peers:
		alert(Calert::debug, "Peer: " + n((s.size() - 2) / 6) + " peers");
		if (s.size() >= 2 && time() - m_get_peers_stime < 60)
		{
			m_f->insert_peers(s += 2);
		}
		break;
	case bti_extended:
		if (s.size() >= 1)
		{
			switch (*s++)
			{
			case bti_extended_handshake:
				{
					Cbvalue v(s);
					const Cbvalue& m = v.d("m");
					m_ut_pex_extension = m.d("ut_pex").i();
				}
				break;
			case bti_extended_ut_pex:
				{
					Cbvalue v(s);
					m_f->insert_peers(v.d("added").s());
				}
				break;
			}
		}
		break;
	}
	return 0;
}

int Cbt_peer_link::pre_dump() const
{
	int size = m_remote_peer_id.length() + debug_string().length() + 73;
	return size;
}

void Cbt_peer_link::dump(Cstream_writer& w) const
{
	w.write_int(4, ntohl(m_a.sin_addr.s_addr));
	w.write_int(4, ntohs(m_a.sin_port));
	w.write_string(m_remote_peer_id);
	w.write_int(8, m_downloaded);
	w.write_int(8, m_left);
	w.write_int(8, m_uploaded);
	w.write_int(4, m_down_counter.rate(time()));
	w.write_int(4, m_up_counter.rate(time()));
	w.write_int(1, m_local_link);
	w.write_int(1, m_local_choked);
	w.write_int(1, m_local_interested);
	w.write_int(4, mc_local_requests_pending);
	w.write_int(1, m_remote_choked);
	w.write_int(1, m_remote_interested);
	w.write_int(4, m_remote_requests.size());
	w.write_int(4, 0);
	w.write_int(4, m_rtime);
	w.write_int(4, m_stime);
	w.write_string(debug_string());
}

void Cbt_peer_link::alert(Calert::t_level level, const std::string& message)
{
	m_f->alert(Calert(level, std::string(inet_ntoa(m_a.sin_addr)) + ':' + n(ntohs(m_a.sin_port)), message));
}

void Cbt_peer_link::clear_local_requests()
{
	for (t_local_requests::const_iterator i = m_local_requests.begin(); i != m_local_requests.end(); i++)
		m_f->m_pieces[i->offset / m_f->mcb_piece].erase_peer(this, i->offset % m_f->mcb_piece);
	m_local_requests.clear();
	mc_local_requests_pending = 0;
}

void Cbt_peer_link::check_pieces()
{
	for (t_local_requests::const_iterator i = m_local_requests.begin(); i != m_local_requests.end(); i++)
		m_f->m_pieces[i->offset / m_f->mcb_piece].check_peer(this, m_f->m_allow_end_mode && m_f->end_mode() ? 15 : 600);
	m_check_pieces_time = time();
}

int Cbt_peer_link::c_max_requests_pending() const
{
	return min(mc_max_requests_pending, m_f->c_max_requests_pending());
}

std::string Cbt_peer_link::debug_string() const
{
	std::string d;
	for (t_local_requests::const_iterator i = m_local_requests.begin(); i != m_local_requests.end(); i++)
		d += "lr: " + n(i->offset / m_f->mcb_piece) + "; ";
	if (m_read_b.cb_read())
		d += "rb: " + n(m_read_b.cb_read()) + "; ";
	if (cb_write_buffer())
		d += "wb: " + n(cb_write_buffer()) + "; ";
	if (m_can_send)
		d += "w: 1; ";
	return d;
}

Cserver* Cbt_peer_link::server()
{
	return m_f->server();
}

const Cserver* Cbt_peer_link::server() const
{
	return m_f->server();
}

time_t Cbt_peer_link::time() const
{
	return server()->time();
}
