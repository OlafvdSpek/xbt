#include "stdafx.h"
#include "tcp_listen_socket.h"

#include "server.h"

int Ctcp_listen_socket::process_events(int events)
{
	m_server->accept(m_s);
	return 0;
}
