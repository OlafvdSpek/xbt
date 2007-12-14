#pragma once

#include <boost/array.hpp>
#include <vector>
#include "client.h"
#include "const_memory_range.h"

class Cserver;

class Cconnection: public Cclient, boost::noncopyable
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
	typedef boost::array<char, 4 << 10> t_read_b;
	typedef std::vector<char> t_write_b;

	sockaddr_in m_a;
	time_t m_ctime;
	int m_state;
	t_read_b m_read_b;
	t_write_b m_write_b;
	const_memory_range m_r;
	memory_range m_w;
};
