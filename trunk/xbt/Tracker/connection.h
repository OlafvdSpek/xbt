#if !defined(AFX_CONNECTION_H__CB2C61F4_A0ED_4996_9CFE_D6694BE24CC9__INCLUDED_)
#define AFX_CONNECTION_H__CB2C61F4_A0ED_4996_9CFE_D6694BE24CC9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "client.h"

class Cserver;

class Cconnection: public Cclient
{
public:
	Cclient::s;
	int run();
	void read(const string&);
	int recv();
	int send();
	virtual void process_events(int);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set);
	Cconnection();
	Cconnection(Cserver* server, const Csocket& s, const sockaddr_in& a, bool log_access);
private:
	typedef vector<char> t_read_b;
	typedef vector<char> t_write_b;

	sockaddr_in m_a;
	time_t m_ctime;
	int m_state;
	t_read_b m_read_b;
	t_write_b m_write_b;
	int m_r;
	int m_w;
	bool m_log_access;
};

#endif // !defined(AFX_CONNECTION_H__CB2C61F4_A0ED_4996_9CFE_D6694BE24CC9__INCLUDED_)
