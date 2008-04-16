#pragma once

#include "alerts.h"
#include "ring_buffer.h"
#include <socket.h>

class Cserver;

class Cbt_link
{
public:
	void alert(Calert::t_level, const std::string&);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int recv();
	Cbt_link();
	Cbt_link(Cserver* server, const sockaddr_in& a, const Csocket& s);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:
	Cring_buffer m_read_b;
	sockaddr_in m_a;
	Csocket m_s;
	Cserver* m_server;
	time_t m_ctime;
	time_t m_mtime;
};
