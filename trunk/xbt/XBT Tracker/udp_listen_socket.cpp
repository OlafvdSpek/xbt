#include "stdafx.h"
#include "udp_listen_socket.h"

#include "transaction.h"

void Cudp_listen_socket::process_events(int events)
{
	if (events & EPOLLIN)
		Ctransaction(*m_server, m_s).recv();
}
