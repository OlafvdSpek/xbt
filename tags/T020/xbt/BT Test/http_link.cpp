#include "stdafx.h"
#include "http_link.h"

#include "server.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Chttp_link::Chttp_link(Cserver* server)
{
	m_server = server;
	m_state = 0;
}

int Chttp_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 1:
		FD_SET(m_s, fd_except_set);
	case 2:
		FD_SET(m_s, fd_read_set);
		if (m_write_b.cb_r())
			FD_SET(m_s, fd_write_set);
		return m_s;
	}	
	return 0;
}

int Chttp_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (m_s == INVALID_SOCKET)
		return 1;
	if (FD_ISSET(m_s, fd_except_set))
	{
		int e = 0;
		m_s.getsockopt(SOL_SOCKET, SO_ERROR, e);
		alert(Calert::error, "connect failed: " + Csocket::error2a(e));
		return 1;
	}
	return FD_ISSET(m_s, fd_read_set) && recv()
		|| m_write_b.cb_r() && FD_ISSET(m_s, fd_write_set) && send();
}

int Chttp_link::recv()
{
	m_state = 2;
	if (!m_read_b.size())
		m_read_b.size(65 << 10);
	for (int r; r = m_s.recv(m_read_b.w(), m_read_b.cb_w()); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
				return 0;
			alert(Calert::debug, "recv failed: " + Csocket::error2a(e));
			return 1;
		}
		m_read_b.cb_w(r);
	}
	if (m_response_handler)
		m_response_handler->handle(string(m_read_b.r(), m_read_b.cb_r()));
	return 1;
}

int Chttp_link::send()
{
	m_state = 2;
	for (int r; r = m_s.send(m_write_b.r(), m_write_b.cb_r()); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			if (e == WSAEWOULDBLOCK)
				return 0;
			alert(Calert::debug, "send failed: " + Csocket::error2a(e));
			return 1;
		}
		m_write_b.cb_r(r);
	}
	return 0;
}

void Chttp_link::alert(Calert::t_level level, const string& message)
{
	if (m_response_handler)
		m_response_handler->alert(Calert(level, message));

}

int Chttp_link::set_request(int h, int p, const string& v, Chttp_response_handler* response_handler)
{
	assert(m_state == 0);
	if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
		return 1;
	if (m_s.connect(h, p) && WSAGetLastError() != WSAEINPROGRESS && WSAGetLastError() != WSAEWOULDBLOCK)
		return 1;
	m_state = 1;
	m_response_handler = response_handler;
	m_write_b.size(v.size() + 1);
	m_write_b.write(v.c_str(), v.size());
	return 0;
}

void Chttp_link::cancel()
{
	close();
}

void Chttp_link::close()
{
	m_s.close();
	m_state = 0;
}
