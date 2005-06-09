#if !defined(AFX_TCP_LISTEN_SOCKET_H__716D929B_2F70_4C51_B559_4B207CAC334F__INCLUDED_)
#define AFX_TCP_LISTEN_SOCKET_H__716D929B_2F70_4C51_B559_4B207CAC334F__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "client.h"

class Cserver;

class Ctcp_listen_socket: public Cclient
{
public:
	virtual void process_events(int);
	Cclient::s;
	Ctcp_listen_socket();
	Ctcp_listen_socket(Cserver*, const Csocket&);
};

#endif // !defined(AFX_TCP_LISTEN_SOCKET_H__716D929B_2F70_4C51_B559_4B207CAC334F__INCLUDED_)
