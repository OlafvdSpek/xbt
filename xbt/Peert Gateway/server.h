#if !defined(AFX_SERVER_H__E88E0E42_5E1C_4D56_A7A9_7B8477A84F63__INCLUDED_)
#define AFX_SERVER_H__E88E0E42_5E1C_4D56_A7A9_7B8477A84F63__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "connection.h"

class Cserver  
{
public:
	int http_get(const string& url, string& d);
	void accept(const Csocket&);
	int run();
	void sig_handler(int);
	static void term();
	Cserver();
private:
	typedef list<Cconnection> t_connections;

	t_connections m_connections;
	Csocket m_s;
	time_t m_time;
};

#endif // !defined(AFX_SERVER_H__E88E0E42_5E1C_4D56_A7A9_7B8477A84F63__INCLUDED_)
