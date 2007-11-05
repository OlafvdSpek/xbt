#if !defined(AFX_CONNECTION_H__25A5EB47_F65E_4DF0_AD18_8D4AE720A297__INCLUDED_)
#define AFX_CONNECTION_H__25A5EB47_F65E_4DF0_AD18_8D4AE720A297__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "../BT Test/ring_buffer.h"

class Cconnection_handler;
class Cserver;

class Cconnection
{
public:
	typedef Cring_buffer t_read_b;
	typedef Cring_buffer t_write_b;

	void connection_handler(Cconnection_handler*);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set);
	int recv();
	int send();
	Cconnection();
	Cconnection(Cserver*, const Csocket&, const sockaddr_in&, Cconnection_handler*);
	~Cconnection();

	t_read_b& read_b()
	{
		return m_read_b;
	}

	Csocket& s()
	{
		return m_s;
	}

	Cserver* server()
	{
		return m_server;
	}

	t_write_b& write_b()
	{
		return m_write_b;
	}
private:
	// Cconnection(const Cconnection&);

	sockaddr_in m_a;
	bool m_can_read;
	bool m_can_write;
	Cconnection_handler* m_connection_handler;
	t_read_b m_read_b;
	t_write_b m_write_b;
	Csocket m_s;
	Cserver* m_server;
};

#endif // !defined(AFX_CONNECTION_H__25A5EB47_F65E_4DF0_AD18_8D4AE720A297__INCLUDED_)
