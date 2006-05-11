#if !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
#define AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "server.h"

struct t_udp_tracker_input;
struct t_udp_tracker_input_announce;
struct t_udp_tracker_input_connect;
struct t_udp_tracker_input_scrape;

class Ctransaction
{
public:
	Cserver::t_user* authenticate(const void* s, const char* s_end) const;
	long long connection_id() const;
	void recv();
	void send(const void* d, int cb_d);
	void send_announce(const char* r, const char* r_end);
	void send_connect(const char* r, const char* r_end);
	void send_scrape(const char* r, const char* r_end);
	void send_error(const char* r, const char* r_end, const std::string& msg);
	Ctransaction(Cserver& server, const Csocket& s);
private:
	Cserver& m_server;
	Csocket m_s;
	sockaddr_in m_a;
};

#endif // !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
