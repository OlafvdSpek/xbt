#pragma once

#include "client.h"

class Cserver;

class Cconnection: public Cclient, boost::noncopyable
{
public:
	using Cclient::s;
	int run();
	void read(const std::string&);
	int recv();
	int send();
	virtual void process_events(int);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set);
	Cconnection(Cserver*, const Csocket&, const sockaddr_in&);
private:
	Cserver* m_server;
	sockaddr_in m_a;
	time_t m_ctime;
	int m_state;
	std::array<char, 4 << 10> m_read_b;
	shared_data m_write_b;
	str_ref m_r;
	mutable_str_ref m_w;
};
