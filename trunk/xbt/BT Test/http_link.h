// http_link.h: interface for the Chttp_link class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_)
#define AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "ring_buffer.h"
#include "socket.h"

class Cserver;

class Chttp_link  
{
public:
	void alert(Calert::t_level, const string&);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int recv();
	Chttp_link(Cserver* server = NULL);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:	
	Cring_buffer m_read_b;
	Csocket m_s;
	Cserver* m_server;
};

#endif // !defined(AFX_HTTP_LINK_H__3D0CB646_2C1F_427A_A561_F1C21E61CB90__INCLUDED_)
