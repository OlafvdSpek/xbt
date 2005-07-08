// bt_link.h: interface for the Cbt_link class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_LINK_H__E306F9D6_A5E5_474E_A85F_88BEF876F3B8__INCLUDED_)
#define AFX_BT_LINK_H__E306F9D6_A5E5_474E_A85F_88BEF876F3B8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "ring_buffer.h"
#include "socket.h"

class Cserver;

class Cbt_link
{
public:
	void alert(Calert::t_level, const string&);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int recv();
	Cbt_link();
	Cbt_link(Cserver* server, const sockaddr_in& a, const Csocket& s);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:	
	Cring_buffer m_read_b;
	sockaddr_in m_a;
	Csocket m_s;
	Cserver* m_server;
	int m_ctime;
	int m_mtime;
};

#endif // !defined(AFX_BT_LINK_H__E306F9D6_A5E5_474E_A85F_88BEF876F3B8__INCLUDED_)
