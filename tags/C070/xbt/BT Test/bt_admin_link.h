#if !defined(AFX_BT_ADMIN_LINK_H__C5AA1CA0_8023_42E9_A747_00CF206F5833__INCLUDED_)
#define AFX_BT_ADMIN_LINK_H__C5AA1CA0_8023_42E9_A747_00CF206F5833__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "ring_buffer.h"
#include "socket.h"

class Cserver;

class Cbt_admin_link  
{
public:
	void alert(Calert::t_level, const string&);
	void close();
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void read_message(const char* r, const char* r_end);
	int recv();
	int send();
	Cbt_admin_link();
	Cbt_admin_link(Cserver* server, const sockaddr_in& a, const Csocket& s);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:	
	Cring_buffer m_read_b;
	Cring_buffer m_write_b;
	sockaddr_in m_a;
	Csocket m_s;
	Cserver* m_server;
	bool m_close;
	time_t m_ctime;
	time_t m_mtime;
};

#endif // !defined(AFX_BT_ADMIN_LINK_H__C5AA1CA0_8023_42E9_A747_00CF206F5833__INCLUDED_)
