#include "stdafx.h"
#include "connection.h"

#include "connection_handler.h"

Cconnection::Cconnection()
{
	m_connection_handler = NULL;
}

Cconnection::Cconnection(Cserver* server, const Csocket& s, const sockaddr_in& a, Cconnection_handler* con)
{
	m_server = server;
	m_s = s;
	m_a = a;

	m_can_read = false;
	m_can_write = false;
	m_connection_handler = con;
}

Cconnection::~Cconnection()
{
	// delete m_connection_handler;
}

void Cconnection::connection_handler(Cconnection_handler* con)
{
	m_connection_handler = con;
}

int Cconnection::pre_select(fd_set* fd_read_set, fd_set* fd_write_set)
{
	if (!m_can_read)
		FD_SET(m_s, fd_read_set);
	if (!m_can_write)
		FD_SET(m_s, fd_write_set);
	return m_s;
}

int Cconnection::post_select(fd_set* fd_read_set, fd_set* fd_write_set)
{
	if (FD_ISSET(m_s, fd_read_set))
		m_can_read = true;
	if (FD_ISSET(m_s, fd_write_set))
		m_can_write = true;
	return m_can_read && recv()
		|| m_can_write && send()
		; // || m_state == 5 && m_write_b.empty();
}

int Cconnection::recv()
{
	if (!m_can_read)
		return 0;
	if (!m_read_b.size())
		m_read_b.size(65 << 10);
	for (int r; m_can_read && m_read_b.cb_w() && (r = m_s.recv(m_read_b.w(), m_read_b.cb_w())); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				return 1;
			case WSAEWOULDBLOCK:
				return 0;
			}
			cerr << "recv failed: " << Csocket::error2a(e) << endl;
			return 1;
		}
		if (r != m_read_b.cb_w())
			m_can_read = false;
		m_read_b.cb_w(r);
		if (m_connection_handler)
		{
			if (int result = m_connection_handler->post_recv(this))
				return result;
		}
	}
	// if (m_can_read && !r
	return 0;
}

int Cconnection::send()
{
	return 0;
}
