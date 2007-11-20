#pragma once

#include "server.h"

class Ctransaction
{
public:
	Cserver::t_user* authenticate(const_memory_range) const;
	long long connection_id() const;
	void recv();
	void send(const_memory_range);
	void send_announce(const_memory_range);
	void send_connect(const_memory_range);
	void send_scrape(const_memory_range);
	void send_error(const_memory_range, const std::string& msg);
	Ctransaction(Cserver& server, const Csocket& s);
private:
	Cserver& m_server;
	Csocket m_s;
	sockaddr_in m_a;
};
