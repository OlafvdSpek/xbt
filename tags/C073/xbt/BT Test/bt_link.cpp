#include "stdafx.h"
#include "bt_link.h"

#include "server.h"

Cbt_link::Cbt_link()
{
}

Cbt_link::Cbt_link(Cserver* server, const sockaddr_in& a, const Csocket& s)
{
	m_a = a;
	m_s = s;
	m_server = server;
	m_ctime = m_mtime = server->time();

	m_read_b.size(48);
}

int Cbt_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (!m_read_b.cb_w())
		return 0;
	FD_SET(m_s, fd_read_set);
	return m_s;
}

int Cbt_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	return m_read_b.cb_w() && FD_ISSET(m_s, fd_read_set) && recv()
		|| m_server->time() - m_ctime > 5;
}

int Cbt_link::recv()
{
	for (int r; r = m_s.recv(m_read_b.w()); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
				return 0;
			alert(Calert::debug, "Link: recv failed: " + Csocket::error2a(e));
			return 1;
		}
		m_read_b.cb_w(r);
		m_mtime = m_server->time();
		if (m_read_b.cb_r() >= hs_size)
		{
			m_server->insert_peer(m_read_b.r(), m_a, m_s);
			return 1;
		}
	}
	return 1;
}

void Cbt_link::alert(Calert::t_level, const std::string&)
{
}
