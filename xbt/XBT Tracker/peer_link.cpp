// peer_link.cpp: implementation of the Cpeer_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "peer_link.h"

#include "server.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cpeer_link::Cpeer_link()
{
}

Cpeer_link::Cpeer_link(int h, int p, Cserver* server, const string& file_id, const string& peer_id)
{
	m_s.connect(htonl(h), htons(p));
	m_server = server;
	m_file_id = file_id;
	m_peer_id = peer_id;
}

int Cpeer_link::pre_select(fd_set* fd_read_set, fd_set* fd_except_set)
{
	FD_SET(m_s, fd_read_set);
	FD_SET(m_s, fd_except_set);
	return m_s;
}

void Cpeer_link::post_select(fd_set* fd_read_set, fd_set* fd_except_set)
{
	if (FD_ISSET(m_s, fd_read_set))
	{
		m_server->update_peer(m_file_id, m_peer_id, true);
		m_s.close();
	}
	else if (FD_ISSET(m_s, fd_except_set))
		m_s.close();
}