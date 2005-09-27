#if !defined(AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_)
#define AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "http_response_handler.h"
#include "ring_buffer.h"
#include "socket.h"

class Cserver;

class Chttp_link  
{
public:
	void close();
	void cancel();
	int set_request(int h, int p, const string&, Chttp_response_handler*);
	void alert(Calert::t_level, const string&);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int recv();
	int send();
	Chttp_link(Cserver* server = NULL);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:	
	Chttp_response_handler* m_response_handler;
	Cring_buffer m_read_b;
	Cring_buffer m_write_b;
	Csocket m_s;
	Cserver* m_server;
	int m_state;
};

#endif // !defined(AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_)
