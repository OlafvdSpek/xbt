// bt_peer_link.cpp: implementation of the Cbt_peer_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_peer_link.h"

#include "bt_file.h"
#include "../misc/bt_strings.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_peer_link::Cbt_peer_link()
{
	m_piece = NULL;
	m_state = 1;
}

Cbt_peer_link::~Cbt_peer_link()
{
}

int Cbt_peer_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	unsigned long p = 1;
	switch (m_state)
	{
	case 1:
		if ((m_s.open(SOCK_STREAM)) == INVALID_SOCKET)
		{
			m_state = -1;
			return 0;
		}
		if (ioctlsocket(m_s, FIONBIO, &p))
			cerr << "ioctlsocket failed" << endl;
		if (m_s.connect(m_a.sin_addr.S_un.S_addr, m_a.sin_port) && WSAGetLastError() != WSAEWOULDBLOCK)
		{
			close();
			return 0;
		}
		write_handshake();
		m_state = 2;
	case 2:
		FD_SET(m_s, fd_except_set);
	case 3:
		if (!m_local_choked && !m_remote_requests.empty() && m_write_b.empty())
		{
			const t_remote_request& request = m_remote_requests.front();
			int a = request.offset / m_f->mcb_piece;
			int b = request.offset % m_f->mcb_piece;
			Cbt_piece& piece = m_f->m_pieces[a];
			Cvirtual_binary d;
			if (!m_f->read_piece(a, d.write_start(piece.mcb_d)))
				write_piece(a, b, request.size, d.data() + b);
			m_remote_requests.erase(m_remote_requests.begin());
		}
		if (!m_remote_choked && !m_piece)
		{
			int a = m_f->next_invalid_piece(m_remote_pieces);
			if (a >= 0)
			{
				m_piece = &m_f->m_pieces[a];
				m_piece->m_peer = this;
				for (int b = 0; b < m_piece->c_sub_pieces(); b++)
				{
					if (m_piece->m_sub_pieces.empty() || !m_piece->m_sub_pieces[b])
						write_request(a, m_piece->mcb_sub_piece * b, m_piece->cb_sub_piece(b));
				}
			}
		}
		FD_SET(m_s, fd_read_set);
		if (m_write_b.empty() && time(NULL) - m_stime > 120)
			write_keepalive();
		if (!m_write_b.empty())
			FD_SET(m_s, fd_write_set);
		return m_s;
	}
	return 0;
}

struct t_bt_handshake
{
	unsigned char cb_name;
	char name[19];
	char reserved[8];
	char info_hash[20];
	char peer_id[20];
};

void Cbt_peer_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 2:
		if (FD_ISSET(m_s, fd_except_set) || time(NULL) - m_piece_rtime > 15)
		{
			close();
			return;
		}
	case 3:
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
						|| memcmp(m.info_hash, m_f->m_info_hash.c_str(), 20))
					{
						close();
						return;
					}
					m_remote_peer_id = string(m.peer_id, 20);
					m_read_b.cb_r(sizeof(t_bt_handshake));
					m_remote_pieces.resize(m_f->m_pieces.size());
					if (m_f->c_valid_pieces())
						write_bitfield();
					choked(false);
					interested(m_f->c_invalid_pieces());
					m_state = 3;
				}
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
}

void Cbt_peer_link::close()
{
	m_s.close();
	if (m_piece)
		m_piece->m_peer = NULL;
	m_read_b.size(0);
	m_state = -1;
}

void Cbt_peer_link::write(const Cvirtual_binary& s)
{
	m_write_b.push_back(Cbt_pl_write_data(s));
}

void Cbt_peer_link::write(const void* s, int cb_s)
{
	m_write_b.push_back(Cbt_pl_write_data(reinterpret_cast<const char*>(s), cb_s));
}

void Cbt_peer_link::recv()
{
	for (int r; r = m_s.recv(m_read_b.w(), m_read_b.cb_w()); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				cerr << "recv failed: " << e << endl;
				close();
			}
			return;
		}
		m_rtime = time(NULL);
		ofstream((static_cast<string>("d:/temp/xbt/") + inet_ntoa(m_a.sin_addr)).c_str(), ios::app | ios::binary).write(m_read_b.w(), r);
		m_read_b.cb_w(r);
	}
	close();
}

void Cbt_peer_link::send()
{
	while (!m_write_b.empty())
	{
		Cbt_pl_write_data& d = m_write_b.front();
		int r = m_s.send(d.m_r, d.m_s_end - d.m_r);
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				cerr << "send failed: " << e << endl;
				close();
			}
			return;
		}
		else if (!r)
			return;
		m_stime = time(NULL);
		d.m_r += r;
		if (d.m_r == d.m_s_end)
			m_write_b.erase(m_write_b.begin());
	}
}

void Cbt_peer_link::remote_has(int v)
{
	if (v < m_f->m_pieces.size())
		m_remote_pieces[v] = true;
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
	write("\x13""BitTorrent protocol\0\0\0\0\0\0\0\0", 28);
	write(m_f->m_info_hash.c_str(), 20);
	write(m_f->m_peer_id.c_str(), 20);
	m_local_choked = true;
	m_local_interested = false;
	m_piece = NULL;
	m_remote_choked = true;
	m_remote_interested = false;
	m_downloaded = m_uploaded = 0;
	m_read_b.size(1 << 20);
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
	Cvirtual_binary d;
	byte* w = d.write_start(9);
	w = write(w, d.size() - 4);
	*w++ = bti_have;
	w = write(w, a);
	write(d);
}

void Cbt_peer_link::write_bitfield()
{
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
	m_f->m_uploaded += size;
	m_f->m_up_counter.add(size);
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

void Cbt_peer_link::read_piece(int piece, int offset, int size, const char* s)
{
	m_f->write_data(m_f->mcb_piece * piece + offset, s, size);
	m_downloaded += size;
	m_f->m_downloaded += size;
	m_f->m_down_counter.add(size);
	m_piece_rtime = time(NULL);
}

void Cbt_peer_link::read_message(const char* r, const char* r_end)
{
	switch (*r++)
	{
	case bti_choke:
		m_remote_choked = true;
		if (m_piece)
			m_piece->m_peer = NULL;
		m_piece = NULL;
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
	}
}