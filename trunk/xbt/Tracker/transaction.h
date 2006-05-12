#if !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
#define AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

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

#endif // !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
