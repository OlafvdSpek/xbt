#pragma once

#include "client.h"

class connection_t : public client_t, boost::noncopyable
{
public:
	int run();
	void read(std::string_view);
	int recv();
	int send();
	virtual void process_events(int);
	int pre_select(fd_set& read, fd_set& write);
	int post_select(fd_set& read, fd_set& write);
	connection_t(const Csocket&, const sockaddr_in6&);
private:
	sockaddr_in6 addr_;
	time_t ctime_;
	int state_ = 0;
	str_ref r_;
	mutable_str_ref w_;
	shared_data write_b_;
	std::array<char, 4 << 10> read_b_;
};
