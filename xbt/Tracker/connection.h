#pragma once

#include <vector>
#include "client.h"

class Cserver;

class Cconnection: public Cclient
{
public:
	Cclient::s;
	int run();
	void read(const std::string&);
	int recv();
	int send();
	virtual void process_events(int);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set);
	Cconnection();
	Cconnection(Cserver*, const Csocket&, const sockaddr_in&);
private:
	typedef std::vector<char> t_read_b;
	typedef std::vector<char> t_write_b;

	sockaddr_in m_a;
	time_t m_ctime;
	int m_state;
	t_read_b m_read_b;
	t_write_b m_write_b;
	int m_r;
	int m_w;
};
