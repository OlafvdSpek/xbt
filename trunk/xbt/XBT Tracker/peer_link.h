// peer_link.h: interface for the Cpeer_link class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_PEER_LINK_H__55B9FC9B_26A7_42D7_A950_691FBA0B4910__INCLUDED_)
#define AFX_PEER_LINK_H__55B9FC9B_26A7_42D7_A950_691FBA0B4910__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cserver;

class Cpeer_link  
{
public:
	int pre_select(fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_write_set, fd_set* fd_except_set);
	Cpeer_link();
	Cpeer_link(int h, int p, Cserver* server, const string& file_id, int peer_id);

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}
private:
	Csocket m_s;
	int m_ctime;
	Cserver* m_server;
	string m_file_id;
	int m_peer_id;
};

#endif // !defined(AFX_PEER_LINK_H__55B9FC9B_26A7_42D7_A950_691FBA0B4910__INCLUDED_)
