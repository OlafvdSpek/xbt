// bt_admin_link.cpp: implementation of the Cbt_admin_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_admin_link.h"

#include "bt_strings.h"
#include "server.h"
#include "stream_writer.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_admin_link::Cbt_admin_link()
{
}

Cbt_admin_link::Cbt_admin_link(Cserver* server, const sockaddr_in& a, const Csocket& s)
{
	m_a = a;
	m_s = s;
	m_server = server;
	m_close = false;
	m_ctime = m_mtime = time(NULL);

	m_read_b.size(4 << 10);
	m_write_b.size(64 << 10);
}

int Cbt_admin_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (m_read_b.cb_w())
		FD_SET(m_s, fd_read_set);
	if (m_write_b.cb_r())
		FD_SET(m_s, fd_write_set);
	return m_s;
}

void Cbt_admin_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (m_read_b.cb_w() && FD_ISSET(m_s, fd_read_set))
	{
		recv();
#if 1
		strstream str;
		str << "HTTP/1.0 200\r\ncontent-type: text/html\r\n\r\n"
			<< *m_server;
		m_write_b.write(str.str(), min(str.pcount(), m_write_b.size()));
		m_close = true;
#endif
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
	}
	if (m_write_b.cb_r() && FD_ISSET(m_s, fd_write_set))
		send();
	if (0 && time(NULL) - m_ctime > 60)
		close();
}

void Cbt_admin_link::recv()
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
				alert(Calert(Calert::debug, m_a, "Admin: connection aborted/reset"));
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				alert(Calert(Calert::debug, m_a, "Admin: recv failed:" + n(e)));
				close();
			}
			return;
		}
		m_read_b.cb_w(r);
		m_mtime = time(NULL);
	}
	close();
}

void Cbt_admin_link::send()
{
	for (int r; r = m_s.send(m_write_b.r(), m_write_b.cb_r()); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				alert(Calert(Calert::debug, m_a, "Admin: connection aborted/reset"));
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				alert(Calert(Calert::debug, m_a, "Admin: send failed"));
				close();
			}
			return;
		}
		m_write_b.cb_r(r);
		m_mtime = time(NULL);
	}
	if (m_close)
		close();
}

void Cbt_admin_link::close()
{
	m_s.close();
}

static byte* write(byte* w, int v)
{
	*reinterpret_cast<__int32*>(w) = htonl(v);
	return w + 4;
}

void Cbt_admin_link::read_message(const char* r, const char* r_end)
{
	switch (*r++)
	{
	case bti_get_status:
		{
			Cvirtual_binary d;
			Cstream_writer w(d.write_start(5 + m_server->pre_dump(0)));
			w.write_int32(d.size());
			w.write_int8(bti_status);
			m_server->dump(w, 0);
			assert(w.w() == d.data_end());
		}
		break;
	}
}

void Cbt_admin_link::alert(const Calert& v)
{
}
