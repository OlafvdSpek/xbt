// bt_peer_link.cpp: implementation of the Cbt_peer_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_peer_link.h"

#include <algorithm>
#include "bt_file.h"
#include "bt_strings.h"

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
		{
			BOOL v = true;
			if (!setsockopt(m_s, SOL_SOCKET, SO_REUSEADDR, reinterpret_cast<const char*>(&v), sizeof(BOOL)))
				m_s.bind(htonl(INADDR_ANY), htons(m_f->m_local_port));
		}
		if (m_s.connect(m_a.sin_addr.s_addr, m_a.sin_port) && WSAGetLastError() != WSAEWOULDBLOCK)
		{
			close();
			return 0;
		}
		write_handshake();
		m_state = 2;
	case 2:
		FD_SET(m_s, fd_except_set);
	case 3:
	case 4:
		if (!m_local_choked && !m_remote_requests.empty() && m_write_b.empty())
		{
			const t_remote_request& request = m_remote_requests.front();
			int a = request.offset / m_f->mcb_piece;
			int b = request.offset % m_f->mcb_piece;
			Cbt_piece& piece = m_f->m_pieces[a];
			Cvirtual_binary d;
			if (!m_f->read_piece(a, d.write_start(piece.mcb_d)))
				write_piece(a, b, request.size, d.data() + b);
			m_remote_requests.pop_front();
		}
		if (!m_pieces.empty() && time(NULL) - m_piece_rtime > 120)
			clear_local_requests();
		while (!m_remote_choked && mc_local_requests_pending < 8)
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
			}
			if (m_local_requests.empty())
				break;
			const t_local_request& request = m_local_requests.front();
			write_request(request.offset / m_f->mcb_piece, request.offset % m_f->mcb_piece, request.size);
			m_local_requests.pop_front();
		}
		if (m_read_b.cb_w())
			FD_SET(m_s, fd_read_set);
		if (m_write_b.empty() && time(NULL) - m_stime > 120)
			write_keepalive();
		if (m_send_quota && !m_write_b.empty())
			FD_SET(m_s, fd_write_set);
		return m_s;
	}
	return 0;
}

void Cbt_peer_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 2:
		if (FD_ISSET(m_s, fd_except_set))
		{
			alert(Calert(Calert::debug, m_a, "Peer: connect failed"));
			close();
			return;
		}
	case 3:
	case 4:
		if (FD_ISSET(m_s, fd_read_set))
		{
			recv();
			switch (m_state)
			{
			case 2:
				if (m_read_b.cb_r() < sizeof(t_bt_handshake))
					break;
				{
					const t_bt_handshake& m = *reinterpret_cast<const t_bt_handshake*>(m_read_b.r());
					if (m.cb_name != 19
						|| memcmp(m.name, "BitTorrent protocol", 19)
						|| m.info_hash() != m_f->m_info_hash)
					{
						alert(Calert(Calert::warn, "Peer: handshake failed"));
						close();
						return;
					}
					m_get_info_extension = m.reserved[7] & 1;
					m_get_peers_extension = m.reserved[7] & 2;
					m_remote_peer_id = m.peer_id();
					m_read_b.cb_r(sizeof(t_bt_handshake));
				}
			case 4:
				m_remote_pieces.resize(m_f->m_pieces.size());
				write_get_peers();
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
				break;
			}					
		}
		if (!m_write_b.empty() && FD_ISSET(m_s, fd_write_set))
			send();
		break;
	}
	if (!m_left && !m_f->m_left)
	{
		alert(Calert(Calert::debug, m_a, "Peer: seeder to seeder link closed"));
		close();
	}
}

void Cbt_peer_link::close()
{
	m_s.close();
	clear_local_requests();
	m_read_b.size(0);
	m_state = -1;
	for (int i = 0; i < m_remote_pieces.size(); i++)
		m_f->m_pieces[i].mc_peers -= m_remote_pieces[i];
}

void Cbt_peer_link::write(const Cvirtual_binary& s)
{
	m_write_b.push_back(Cbt_pl_write_data(s));
}

void Cbt_peer_link::write(const void* s, int cb_s)
{
	m_write_b.push_back(Cbt_pl_write_data(reinterpret_cast<const char*>(s), cb_s));
}

int Cbt_peer_link::cb_write_buffer()
{
	int cb = 0;
	for (t_write_buffer::const_iterator i = m_write_b.begin(); i != m_write_b.end(); i++)
		cb += i->m_s_end - i->m_s;
	return cb;
}

void Cbt_peer_link::recv()
{
	for (int r; m_read_b.cb_w() && (r = m_s.recv(m_read_b.w(), m_read_b.cb_w())); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				alert(Calert(Calert::debug, m_a, "Peer: connection aborted/reset"));
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				alert(Calert(Calert::debug, m_a, "Peer: recv failed:" + n(e)));
				close();
			}
			return;
		}
		m_rtime = time(NULL);
		m_read_b.cb_w(r);
	}
	if (!m_read_b.cb_w())
		return;
	alert(Calert(Calert::debug, m_a, "Peer: connection closed"));
	close();
}

void Cbt_peer_link::send()
{
	while (m_send_quota && !m_write_b.empty())
	{
		Cbt_pl_write_data& d = m_write_b.front();
		int r = m_s.send(d.m_r, min(d.m_s_end - d.m_r, m_send_quota));
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				alert(Calert(Calert::debug, m_a, "Peer: connection aborted/reset"));
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				alert(Calert(Calert::debug, m_a, "Peer: send failed:" + n(e)));
				close();
			}
			return;
		}
		else if (!r)
			return;
		m_send_quota -= r;
		m_stime = time(NULL);
		d.m_r += r;
		if (d.m_r == d.m_s_end)
			m_write_b.pop_front();
	}
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
		|| m_remote_requests.size() >= 256 || !m_f->m_pieces[piece].m_valid)
		return;
	m_remote_requests.push_back(t_remote_request(m_f->mcb_piece * piece + offset, size));
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

byte* Cbt_peer_link::write(byte* w, int v)
{
	*reinterpret_cast<__int32*>(w) = htonl(v);
	return w + 4;
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
	m_read_b.size(128 << 10);
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

void Cbt_peer_link::write_have(int a)
{
	if (m_remote_pieces.empty())
		return;
	if (m_remote_pieces[a])
	{
		if (m_local_interested && m_pieces.empty())
			interested(m_f->next_invalid_piece(*this) != -1);
		return;
	}
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write(w, d.size() - 4);
	*w++ = bti_have;
	w = write(w, a);
	write(d);
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
	m_uploaded += size;
	m_up_counter.add(size);
	m_f->m_uploaded += size;
	m_f->m_up_counter.add(size);
	m_f->m_total_uploaded += size;
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

void Cbt_peer_link::write_get_info()
{
	Cvirtual_binary d;
	byte* w = d.write_start(5);
	w = write(w, d.size() - 4);
	*w++ = bti_get_info;
	write(d);
}

void Cbt_peer_link::write_info(int i)
{
	if (i < 0 || 4096 * i >= m_f->m_info.size())
		return;
	Cvirtual_binary d;
	if (i)
	{
		int cb = min(m_f->m_info.size() - 4096 * i, 4096);
		byte* w = d.write_start(5 + cb);
		w = write(w, d.size() - 4);
		*w++ = bti_info;
		memcpy(w, m_f->m_info + 4096 * i, cb);
	}
	else
	{
		byte* w = d.write_start(5 + m_f->m_info_hashes.size());
		w = write(w, d.size() - 4);
		*w++ = bti_info;
		m_f->m_info_hashes.read(w);
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
	*w++ = bti_get_info;
	*reinterpret_cast<__int16*>(w) = htons(m_f->m_local_port);
	w += 2;
	write(d);
	m_get_peers_stime = time(NULL);
}

void Cbt_peer_link::write_peers()
{
	Cvirtual_binary d;
	byte* w = d.write_start(7 + 6 * m_f->m_peers.size());
	w = write(w, d.size() - 4);
	*w++ = bti_peers;
	*reinterpret_cast<__int16*>(w) = htons(m_f->m_local_port);
	w += 2;
	for (Cbt_file::t_peers::const_iterator i = m_f->m_peers.begin(); i != m_f->m_peers.end(); i++)
	{
		*reinterpret_cast<__int32*>(w) = i->m_a.sin_addr.s_addr;
		w += 4;
		*reinterpret_cast<__int16*>(w) = i->m_a.sin_port;
		w += 2;
	}
	write(d);
	m_peers_stime = time(NULL);
}

void Cbt_peer_link::read_piece(int piece, int offset, int size, const char* s)
{
	m_f->write_data(m_f->mcb_piece * piece + offset, s, size);
	m_downloaded += size;
	m_down_counter.add(size);
	m_f->m_downloaded += size;
	m_f->m_down_counter.add(size);
	m_f->m_total_downloaded += size;
	mc_local_requests_pending--;
	m_piece_rtime = time(NULL);
}

void Cbt_peer_link::read_message(const char* r, const char* r_end)
{
	switch (*r++)
	{
	case bti_choke:
		m_remote_choked = true;
		clear_local_requests();
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
		if (r_end - r >= 12)
		{
			const __int32* a = reinterpret_cast<const __int32*>(r);
			remote_requests(ntohl(a[0]), ntohl(a[1]), ntohl(a[2]));
		}
		break;
	case bti_piece:
		if (r_end - r >= 8)
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
		break;
	case bti_info:
		break;
	case bti_get_peers:
		if (r_end - r >= 2 && time(NULL) - m_peers_stime < 300)
			write_peers();
		break;
	case bti_peers:
		if (r_end - r >= 2 && time(NULL) - m_get_peers_stime < 60)
		{
			for (r += 2; r + 6 <= r_end; r += 6)
				m_f->insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
		}
		break;
	}
}

ostream& Cbt_peer_link::dump(ostream& os) const
{
	os << "<tr><td>" << inet_ntoa(m_a.sin_addr) 
		<< "<td>" << ntohs(m_a.sin_port) 
		<< "<td align=right>" << static_cast<int>(m_downloaded >> 10) 
		<< "<td align=right>" << static_cast<int>(m_uploaded >> 10)
		<< "<td>" << (m_local_link ? 'L' : 'R') 
		<< "<td>" << (m_local_choked ? 'C' : ' ') 
		<< "<td>" << (m_local_interested ? 'I' : ' ')
		<< "<td>" << (m_remote_choked ? 'C' : ' ') 
		<< "<td>" << (m_remote_interested ? 'I' : ' ')
		<< "<td align=right>" << m_read_b.cb_read()
		<< "<td align=right>" << m_write_b.size()
		<< "<td align=right>" << m_remote_requests.size()
		<< "<td align=right>" << time(NULL) - m_piece_rtime
		<< "<td align=right>";
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		os << *i - m_f->m_pieces.begin() << ' ';
	os << "<td align=right>";
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		os << ((*i)->m_sub_pieces.empty() ? (*i)->c_sub_pieces() : (*i)->mc_sub_pieces_left) << ' ';
	os << "<td>" << peer_id2a(m_remote_peer_id);
	return os;
}

ostream& operator<<(ostream& os, const Cbt_peer_link& v)
{
	return v.dump(os);
}

int Cbt_peer_link::pre_dump() const
{
	int size = m_remote_peer_id.length() + 49;
	return size;
}

void Cbt_peer_link::dump(Cstream_writer& w) const
{
	w.write_int32(ntohl(m_a.sin_addr.s_addr));
	w.write_int32(ntohs(m_a.sin_port));
	w.write_string(m_remote_peer_id);
	w.write_int64(m_downloaded);
	w.write_int64(m_left);
	w.write_int64(m_uploaded);
	w.write_int32(m_down_counter.rate());
	w.write_int32(m_up_counter.rate());
	w.write_int8(m_local_link);
	w.write_int8(m_local_choked);
	w.write_int8(m_local_interested);
	w.write_int8(m_remote_choked);
	w.write_int8(m_remote_interested);
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
