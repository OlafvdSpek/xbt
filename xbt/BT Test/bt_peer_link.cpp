// bt_peer_link.cpp: implementation of the Cbt_peer_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_peer_link.h"

#include <algorithm>
#include "bt_file.h"
#include "bt_strings.h"
#include "server.h"

#define for if (0) {} else for

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_peer_link::Cbt_peer_link()
{
	m_f = NULL;
	m_send_quota = 0;
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
		if (m_f->m_server->bind_before_connect() && !m_s.setsockopt(SOL_SOCKET, SO_REUSEADDR, true))
			m_s.bind(htonl(INADDR_ANY), htons(m_f->local_port()));
		if (m_s.connect(m_a.sin_addr.s_addr, m_a.sin_port) && WSAGetLastError() != WSAEINPROGRESS && WSAGetLastError() != WSAEWOULDBLOCK)
		{
			alert(Calert(Calert::debug, m_a, "Peer: connect failed: " + Csocket::error2a(WSAGetLastError())));
			close();
			return 0;
		}
		write_handshake();
		m_state = 2;
	case 2:
		FD_SET(m_s, fd_except_set);
	case 3:
	case 4:
		if (!m_read_b.size() || m_read_b.cb_w())
			FD_SET(m_s, fd_read_set);
		if (m_send_quota && !m_write_b.empty())
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
			getsockopt(m_s, SOL_SOCKET, SO_ERROR, reinterpret_cast<char*>(&e), &size);
			if (e == WSAEADDRINUSE)
			{
				if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
					return 1;
				if (m_s.connect(m_a.sin_addr.s_addr, m_a.sin_port) && WSAGetLastError() != WSAEINPROGRESS && WSAGetLastError() != WSAEWOULDBLOCK)
				{
					alert(Calert(Calert::debug, m_a, "Peer: connect failed: " + Csocket::error2a(WSAGetLastError())));
					return 1;
				}
				return 0;
			}
			if (m_f->m_server->log_peer_connect_failures())
				alert(Calert(Calert::debug, m_a, "Peer: connect failed: " + Csocket::error2a(e)));
			return 1;
		}
	case 3:
	case 4:
		if (FD_ISSET(m_s, fd_read_set))
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
				if (m_remote_peer_id == m_f->m_peer_id)
					return 1;
				m_read_b.cb_r(20);
				m_remote_pieces.resize(m_f->m_pieces.size());
				write_get_peers();
				if (0)
					write_get_info(-1);
				write_bitfield();
				m_state = 3;
			case 3:
				while (1)
				{
					while (m_read_b.cb_r() >= 4)
					{
						int cb_m = ntohl(*reinterpret_cast<const __int32*>(m_read_b.r()));
						if (cb_m)
						{
							if (cb_m < 0 || cb_m > 64 << 10)
								return 1;
							if (m_read_b.cb_r() < 4 + cb_m)
								break;
							const char* s = m_read_b.r() + 4;
							m_read_b.cb_r(4 + cb_m);
							read_message(s, s + cb_m);
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
		if (!m_write_b.empty() && FD_ISSET(m_s, fd_write_set) && send())
			return 1;
		if (m_state == 3)
		{
			if (!m_local_choked && !m_remote_requests.empty() && m_write_b.size() < 2)
			{
				const t_remote_request& request = m_remote_requests.front();
				Cvirtual_binary d;
				if (!m_f->read_data(request.offset, d.write_start(request.size), request.size))
				{
					if (m_f->m_merkle)
						write_merkle_piece(request.offset, request.size, d, m_f->get_hashes(request.offset, request.c_hashes));
					else
						write_piece(request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, request.size, d);
				}
				m_remote_requests.pop_front();
			}
			if (!m_pieces.empty() && time(NULL) - m_piece_rtime > 600)
				clear_local_requests();
			while (m_local_interested && m_f->m_run && !m_remote_choked && mc_local_requests_pending < 8)
			{
				if (m_local_requests.empty())
				{
					int a = m_f->next_invalid_piece(*this);
					if (a >= 0)
					{
						Cbt_piece* piece = &m_f->m_pieces[a];
						if (m_pieces.empty())
							m_piece_rtime = time(NULL);
						m_pieces.insert(piece);
						piece->m_peers.insert(this);
						vector<int> sub_pieces;
						for (int b = 0; b < piece->c_sub_pieces(); b++)
						{
							if (piece->m_sub_pieces.empty() || !piece->m_sub_pieces[b])
								sub_pieces.push_back(b);
						}
						if (piece->m_peers.size() > 1)
							random_shuffle(sub_pieces.begin(), sub_pieces.end());
						for (vector<int>::const_iterator i = sub_pieces.begin(); i != sub_pieces.end(); i++)
							m_local_requests.push_back(t_local_request(m_f->mcb_piece * a + piece->mcb_sub_piece * *i, piece->cb_sub_piece(*i)));
					}
					else
						interested(false);
				}
				if (m_local_requests.empty())
					break;
				const t_local_request& request = m_local_requests.front();
				logger().request(m_f->m_info_hash, inet_ntoa(m_a.sin_addr), false, request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, request.size);
				if (m_f->m_merkle)
					write_merkle_request(request.offset, 127);
				else
					write_request(request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, request.size);
				m_local_requests.pop_front();
			}
			if (m_write_b.empty())
			{
				if (!m_local_interested && time(NULL) - m_stime > 30 && m_f->next_invalid_piece(*this) != -1)
				{
					write_haves();
					interested(true);
				}
				else if (time(NULL) - m_stime > 120)
				{
					write_haves();
					if (m_write_b.empty())
						write_keepalive();
				}
			}
			else
				write_haves();
		}
		break;
	}
	if (!m_left && !m_f->m_left)
	{
		alert(Calert(Calert::debug, m_a, "Peer: seeder to seeder link closed"));
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
	for (int i = 0; i < m_remote_pieces.size(); i++)
		m_f->m_pieces[i].mc_peers -= m_remote_pieces[i];
	m_remote_pieces.clear();
}

void Cbt_peer_link::write(const Cvirtual_binary& s)
{
	m_write_b.push_back(Cbt_pl_write_data(s));
}

void Cbt_peer_link::write(const void* s, int cb_s)
{
	m_write_b.push_back(Cbt_pl_write_data(reinterpret_cast<const char*>(s), cb_s));
}

int Cbt_peer_link::cb_write_buffer() const
{
	int cb = 0;
	for (t_write_buffer::const_iterator i = m_write_b.begin(); i != m_write_b.end(); i++)
		cb += i->m_s_end - i->m_r;
	return cb;
}

int Cbt_peer_link::recv()
{
	if (!m_read_b.size())
		m_read_b.size(65 << 10);
	for (int r; m_read_b.cb_w() && (r = m_s.recv(m_read_b.w(), m_read_b.cb_w())); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
				return 0;
			if (m_f->m_server->log_peer_recv_failures())
				alert(Calert(Calert::debug, m_a, "Peer: recv failed: " + Csocket::error2a(e)));
			return 1;
		}
		m_rtime = time(NULL);
		m_read_b.cb_w(r);
		m_f->m_downloaded_l5 += r;
	}
	if (!m_read_b.cb_w())
		return 0;
	if (m_f->m_server->log_peer_connection_closures())
		alert(Calert(Calert::debug, m_a, m_local_link ? "Peer: local link closed" : "Peer: remote link closed"));
	return 1;
}

int Cbt_peer_link::send()
{
	while (m_send_quota && !m_write_b.empty())
	{
		Cbt_pl_write_data& d = m_write_b.front();
		int r = m_s.send(d.m_r, min(d.m_s_end - d.m_r, m_send_quota));
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
				return 0;
			if (m_f->m_server->log_peer_send_failures())
				alert(Calert(Calert::debug, m_a, "Peer: send failed: " + Csocket::error2a(e)));
			return 1;
		}
		else if (!r)
			return 0;
		if (d.m_vb.size() > 5 && d.m_s[4] == bti_piece)
		{
			m_uploaded += r;
			m_up_counter.add(r);
			m_f->m_uploaded += r;
			m_f->m_up_counter.add(r);
			m_f->m_total_uploaded += r;
		}
		m_send_quota -= r;
		m_stime = time(NULL);
		d.m_r += r;
		if (d.m_r == d.m_s_end)
			m_write_b.pop_front();
		m_f->m_uploaded_l5 += r;
	}
	return 0;
}

void Cbt_peer_link::remote_has(int v)
{
	if (v >= 0 && v < m_f->m_pieces.size() && !m_remote_pieces[v])
	{
		m_f->m_pieces[v].mc_peers++;
		m_left -= m_f->m_pieces[v].mcb_d;
		m_remote_pieces[v] = true;
		if (!m_local_interested && !m_f->m_pieces[v].m_valid)
			interested(true);
	}
}

void Cbt_peer_link::remote_requests(int piece, int offset, int size)
{
	if (piece < 0 || piece >= m_f->c_pieces() || offset < 0 || size < 0 || size > min(m_f->m_pieces[piece].mcb_d, 1 << 17) 
		|| m_remote_requests.size() >= 256 || !m_f->m_pieces[piece].m_valid || m_local_choked)
		return;
	m_remote_requests.push_back(t_remote_request(m_f->mcb_piece * piece + offset, size, 0));
}

void Cbt_peer_link::remote_merkle_requests(__int64 offset, int c_hashes)
{
	int piece = offset / m_f->mcb_piece;
	if (offset < 0 || m_remote_requests.size() >= 256 || piece >= m_f->m_pieces.size() || !m_f->m_pieces[piece].m_valid || m_local_choked)
		return;
	m_remote_requests.push_back(t_remote_request(offset, min(m_f->m_pieces[piece].mcb_d - offset % m_f->mcb_piece, 32 << 10), c_hashes));
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

void Cbt_peer_link::remote_merkle_cancels(__int64 offset)
{
	for (t_remote_requests::iterator i = m_remote_requests.begin(); i != m_remote_requests.end(); i++)
	{
		if (i->offset != offset)
			continue;
		m_remote_requests.erase(i);
		return;
	}
}

byte* Cbt_peer_link::write16(byte* w, int v)
{
	*reinterpret_cast<__int16*>(w) = htons(v);
	return w + 2;
}

byte* Cbt_peer_link::write(byte* w, int v)
{
	*reinterpret_cast<__int32*>(w) = htonl(v);
	return w + 4;
}

int Cbt_peer_link::read_handshake(const char* h)
{
	if (h[hs_name_size] != 19
		|| memcmp(h + hs_name, "BitTorrent protocol", 19)
		|| string(h + hs_info_hash, 20) != m_f->m_info_hash)
	{
		alert(Calert(Calert::warn, m_a, "Peer: handshake failed"));
		return 1;
	}
	m_get_info_extension = h[hs_reserved + 7] & 1;
	m_get_peers_extension = h[hs_reserved + 7] & 2;
	return 0;
}

void Cbt_peer_link::write_handshake()
{
	write("\x13""BitTorrent protocol\0\0\0\0\0\0\0\3", 28);
	write(m_f->m_info_hash.c_str(), 20);
	write(m_f->m_peer_id.c_str(), 20);
	m_local_choked = true;
	m_local_interested = false;
	m_remote_choked = true;
	m_remote_interested = false;
	m_downloaded = m_uploaded = 0;
	m_left = m_f->size();
	m_get_peers_stime = 0;
	mc_local_requests_pending = 0;
	m_peers_stime = 0;
	m_piece_rtime = m_rtime = m_stime = time(NULL);
}

void Cbt_peer_link::write_keepalive()
{
	Cvirtual_binary d;
	byte* w = d.write_start(4);
	w = write(w, d.size() - 4);
	write(d);
}

void Cbt_peer_link::queue_have(int a)
{
	m_have_queue.insert(a);
}

void Cbt_peer_link::write_have(int a)
{
	if (m_remote_pieces.empty())
		return;
	if (m_local_interested && m_pieces.empty() && m_remote_pieces[a])
		interested(m_f->next_invalid_piece(*this) != -1);
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write(w, d.size() - 4);
	*w++ = bti_have;
	w = write(w, a);
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
	w = write(w, d.size() - 4);
	*w++ = bti_bitfield;
	int j = 0;
	for (Cbt_file::t_pieces::const_iterator i = m_f->m_pieces.begin(); i != m_f->m_pieces.end(); i++, j++)
	{
		if (!j)
			*w = 0;
		if (i->m_valid)
			*w |= 0x80 >> j;
		if (j == 7)
		{
			j = -1;
			w++;
		}
	}
	write(d);
}

void Cbt_peer_link::write_piece(int piece, int offset, int size, const void* s)
{
	Cvirtual_binary d;
	byte* w = d.write_start(13 + size);
	w = write(w, d.size() - 4);
	*w++ = bti_piece;
	w = write(w, piece);
	w = write(w, offset);
	memcpy(w, s, size);
	write(d);
}

void Cbt_peer_link::write_merkle_piece(__int64 offset, int size, const void* s, const string& hashes)
{
	Cvirtual_binary d;
	byte* w = d.write_start(10 + size + hashes.size());
	w = write(w, d.size() - 4);
	*w++ = bti_piece;
	w = write(w, offset >> 15);
	*w++ = hashes.size() / 20;
	memcpy(w, s, size);
	w += size;
	memcpy(w, hashes.c_str(), hashes.size());
	w += hashes.size();
	assert(w == d.data_end());
	write(d);
}

void Cbt_peer_link::choked(bool v)
{
	if (m_local_choked == v)
		return;
	m_local_choked = v;
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write(w, d.size() - 4);
	*w++ = bti_unchoke - v;
	write(d);
	if (v)
		m_remote_requests.clear();
}

void Cbt_peer_link::interested(bool v)
{
	if (m_local_interested == v)
		return;
	m_local_interested = v;
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write(w, d.size() - 4);
	*w++ = bti_uninterested - v;
	write(d);
}

void Cbt_peer_link::write_request(int piece, int offset, int size)
{
	Cvirtual_binary d;
	byte* w = d.write_start(17);
	w = write(w, d.size() - 4);
	*w++ = bti_request;
	w = write(w, piece);
	w = write(w, offset);
	w = write(w, size);
	write(d);
	mc_local_requests_pending++;
}

void Cbt_peer_link::write_merkle_request(__int64 offset, int c_hashes)
{
	Cvirtual_binary d;
	byte* w = d.write_start(10);
	w = write(w, d.size() - 4);
	*w++ = bti_request;
	w = write(w, offset >> 15);
	*w++ = c_hashes;
	write(d);
	mc_local_requests_pending++;
}

void Cbt_peer_link::write_cancel(int piece, int offset, int size)
{
	Cvirtual_binary d;
	byte* w = d.write_start(17);
	w = write(w, d.size() - 4);
	*w++ = bti_cancel;
	w = write(w, piece);
	w = write(w, offset);
	w = write(w, size);
	write(d);
}

void Cbt_peer_link::write_merkle_cancel(__int64 offset)
{
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write(w, d.size() - 4);
	*w++ = bti_cancel;
	w = write(w, offset >> 15);
	write(d);
}

void Cbt_peer_link::write_get_info(int i)
{
	if (!m_get_info_extension)
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write(w, d.size() - 4);
	*w++ = bti_get_info;
	w = write(w, i);
	write(d);
}

void Cbt_peer_link::read_info(const char* r, const char* r_end)
{
}

void Cbt_peer_link::write_info(int i)
{
	if (i < -1 || 4096 * i >= m_f->m_info.size())
		return;
	Cvirtual_binary d;
	if (i != -1)
	{
		int cb = min(m_f->m_info.size() - 4096 * i, 4096);
		byte* w = d.write_start(9 + cb);
		w = write(w, d.size() - 4);
		*w++ = bti_info;
		w = write(w, i);
		memcpy(w, m_f->m_info + 4096 * i, cb);
	}
	write(d);
}

void Cbt_peer_link::write_get_peers()
{
	if (!m_get_peers_extension)
		return;
	Cvirtual_binary d;
	byte* w = d.write_start(7);
	w = write(w, d.size() - 4);
	*w++ = bti_get_peers;
	w = write16(w, m_f->local_port());
	write(d);
	m_get_peers_stime = time(NULL);
}

void Cbt_peer_link::write_peers()
{
	Cvirtual_binary d;
	byte* w = d.write_start(7 + 6 * m_f->m_peers.size());
	w = write(w, d.size() - 4);
	*w++ = bti_peers;
	w = write16(w, m_f->local_port());
	for (Cbt_file::t_peers::const_iterator i = m_f->m_peers.begin(); i != m_f->m_peers.end(); i++)
	{
		w = write(w, ntohl(i->m_a.sin_addr.s_addr));
		w = write16(w, ntohs(i->m_a.sin_port));
	}
	write(d);
	m_peers_stime = time(NULL);
}

void Cbt_peer_link::read_piece(int piece, int offset, int size, const char* s)
{
	logger().piece(m_f->m_info_hash, inet_ntoa(m_a.sin_addr), true, piece, offset, size);
	m_f->write_data(m_f->mcb_piece * piece + offset, s, size);
	m_downloaded += size;
	m_down_counter.add(size);
	m_f->m_downloaded += size;
	m_f->m_down_counter.add(size);
	m_f->m_total_downloaded += size;
	mc_local_requests_pending--;
	m_piece_rtime = time(NULL);
}

void Cbt_peer_link::read_merkle_piece(__int64 offset, int size, const char* s, const string& hashes)
{
	mc_local_requests_pending--;
	if (!m_f->test_and_set_hashes(offset, Cmerkle_tree::compute_root(s, s + size), hashes))
	{
		alert(Calert(Calert::warn, m_a, "Chunk " + n(offset >> 15) + ": invalid"));
		return;
	}
	m_f->write_data(offset, s, size);
	m_downloaded += size;
	m_down_counter.add(size);
	m_f->m_downloaded += size;
	m_f->m_down_counter.add(size);
	m_f->m_total_downloaded += size;
	m_piece_rtime = time(NULL);
}

void Cbt_peer_link::read_message(const char* r, const char* r_end)
{
	switch (*r++)
	{
	case bti_choke:
		logger().choke(m_f->m_info_hash, inet_ntoa(m_a.sin_addr), true, true);
		m_remote_choked = true;
		// clear_local_requests();
		m_local_requests.clear();
		mc_local_requests_pending = 0;
		break;
	case bti_unchoke:
		logger().choke(m_f->m_info_hash, inet_ntoa(m_a.sin_addr), true, false);
		m_remote_choked = false;
		break;
	case bti_interested:
		m_remote_interested = true;
		break;
	case bti_uninterested:
		m_remote_interested = false;
		break;
	case bti_have:
		if (r_end - r >= 4)
			remote_has(ntohl(*reinterpret_cast<const __int32*>(r)));
		break;
	case bti_bitfield:
		{
			for (int i = 0; r < r_end; r++)
			{
				for (int j = 0; j < 8; i++, j++)
				{
					if (*r & 0x80 >> j)
						remote_has(i);
				}
			}
		}
		break;
	case bti_request:
		if (m_f->m_merkle)
		{
			if (r_end - r >= 5)
			{
				const __int32* a = reinterpret_cast<const __int32*>(r);
				remote_merkle_requests(static_cast<__int64>(ntohl(a[0])) << 15, r[4]);
			}
		}
		else if (r_end - r >= 12)
		{
			const __int32* a = reinterpret_cast<const __int32*>(r);
			remote_requests(ntohl(a[0]), ntohl(a[1]), ntohl(a[2]));
		}
		break;
	case bti_piece:
		if (m_f->m_merkle)
		{
			if (r_end - r >= 5 && r[4] >= 0 && r_end - r >= 20 * r[4] + 5)
			{
				const __int32* a = reinterpret_cast<const __int32*>(r);
				r += 5;
				read_merkle_piece(static_cast<__int64>(ntohl(a[0])) << 15, r_end - r - 20 * r[-1], r, string(r_end - 20 * r[-1], 20 * r[-1]));
			}
		}
		else if (r_end - r >= 8)
		{
			const __int32* a = reinterpret_cast<const __int32*>(r);
			r += 8;
			read_piece(ntohl(a[0]), ntohl(a[1]), r_end - r, r);
		}
		break;
	case bti_cancel:
		if (r_end - r >= 12)
		{
			const __int32* a = reinterpret_cast<const __int32*>(r);
			remote_cancels(ntohl(a[0]), ntohl(a[1]), ntohl(a[2]));
		}
		break;
	case bti_get_info:
		if (r_end - r >= 4)
			write_info(ntohl(*reinterpret_cast<const __int32*>(r)));
		break;
	case bti_info:
		read_info(r, r_end);
		break;
	case bti_get_peers:
		alert(Calert(Calert::debug, m_a, "Peer: get_peers"));
		if (r_end - r >= 2 && time(NULL) - m_peers_stime > 300)
			write_peers();
		break;
	case bti_peers:
		alert(Calert(Calert::debug, m_a, "Peer: " + n((r_end - r - 2) / 6) + " peers"));
		if (r_end - r >= 2 && time(NULL) - m_get_peers_stime < 60)
		{
			for (r += 2; r + 6 <= r_end; r += 6)
				m_f->insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
		}
		break;
	}
}

int Cbt_peer_link::pre_dump() const
{
	int size = m_remote_peer_id.length() + 49;
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
	w.write_int(4, m_down_counter.rate());
	w.write_int(4, m_up_counter.rate());
	w.write_int(1, m_local_link);
	w.write_int(1, m_local_choked);
	w.write_int(1, m_local_interested);
	w.write_int(1, m_remote_choked);
	w.write_int(1, m_remote_interested);
}

void Cbt_peer_link::alert(const Calert& v)
{
	m_f->alert(v);
}

void Cbt_peer_link::clear_local_requests()
{
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		(*i)->m_peers.erase(this);
	m_pieces.clear();
	m_local_requests.clear();
	mc_local_requests_pending = 0;
}

Cbt_logger& Cbt_peer_link::logger()
{
	return m_f->logger();
}
