// server.h: interface for the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
#define AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_admin_link.h"
#include "bt_file.h"
#include "bt_tracker_link.h"

class Cserver  
{
public:
	typedef list<Cbt_admin_link> t_admins;
	typedef list<Cbt_file> t_files;

	void run();
	Cserver();
	~Cserver();

	int admin_port() const
	{
		return m_admin_port;
	}

	int peer_port() const
	{
		return m_peer_port;
	}
private:
	t_admins m_admins;
	t_files m_files;

	int m_admin_port;
	int m_peer_port;
};

#endif // !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
