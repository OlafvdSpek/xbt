// transaction.h: interface for the Ctransaction class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
#define AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_misc.h"

class Cserver;

class Ctransaction  
{
public:
	__int64 connection_id() const;
	void recv();
	void send(const void* d, int cb_d);
	void send_announce(const t_udp_tracker_input_announce&);
	void send_connect(const t_udp_tracker_input_connect&);
	void send_scrape(const t_udp_tracker_input_scrape&);
	void send_error(const t_udp_tracker_input&, const string& msg);
	Ctransaction(Cserver& server, const Csocket& s);
private:
	Cserver& m_server;
	Csocket m_s;
	sockaddr_in m_a;
};

#endif // !defined(AFX_TRANSACTION_H__4AEAFC18_CCA7_41C7_A3C1_71D871C7DA04__INCLUDED_)
