// bt_admin_link.cpp: implementation of the Cbt_admin_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_admin_link.h"

#include "server.h"

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

Cbt_admin_link::~Cbt_admin_link()
{
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
		strstream str;
		str << "HTTP/1.0 200\r\ncontent-type: text/html\r\n\r\n";
		m_server->dump(str);
		m_write_b.write(str.str(), str.pcount());
		m_close = true;
	}
	if (m_write_b.cb_r() && FD_ISSET(m_s, fd_write_set))
		send();
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
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				cerr << "recv failed: " << e << endl;
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
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				cerr << "send failed: " << e << endl;
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
