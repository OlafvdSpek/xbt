#include "stdafx.h"
#include "tcp_listen_socket.h"

#include "server.h"

void Ctcp_listen_socket::process_events(int events)
{
	m_server->accept(m_s);
}
