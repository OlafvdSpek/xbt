// http_link.cpp: implementation of the Chttp_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "http_link.h"

#include "server.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Chttp_link::Chttp_link(Cserver* server)
{
	m_server = server;
}

int Chttp_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (!m_read_b.cb_w())
		return 0;
	FD_SET(m_s, fd_read_set);
	return m_s;
}

int Chttp_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	return m_read_b.cb_w() && FD_ISSET(m_s, fd_read_set) && recv();
}

int Chttp_link::recv()
{
	for (int r; r = m_s.recv(m_read_b.w(), m_read_b.cb_w()); )
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
	}
	return 1;
}

void Chttp_link::alert(Calert::t_level, const string&)
{
}
