// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cserver::Cserver()
{
	m_admin_port = 6880;
	m_peer_port = 6889;
}

Cserver::~Cserver()
{
}

static string new_peer_id()
{
	string v;
	v = "XBT00";
	v.resize(20);
	for (int i = 5; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

void Cserver::run()
{
	Csocket l, la;
	if (l.open(SOCK_STREAM) == INVALID_SOCKET
		|| la.open(SOCK_STREAM) == INVALID_SOCKET)
		cerr << "socket failed" << endl;
	if (l.bind(htonl(INADDR_ANY), htons(peer_port()))
		|| la.bind(htonl(INADDR_LOOPBACK), htons(admin_port())))
		cerr << "bind failed" << endl;
	else if (listen(l, SOMAXCONN)
		|| listen(la, SOMAXCONN))
		cerr << "listen failed" << endl;
	else
	{
		m_files.push_back(Cbt_file());
		Cbt_file& f = m_files.front();
		if (f.info(Cvirtual_binary("//hwima/f$/archives/bt/old/when.vivid.girls.do.orgies.xxx-spice[1].torrent")))
			return;
		if (f.open("c:/temp/xbt/")) // "d:/temp/kz/special forces 2003.avi"))
			return;
		f.m_peer_id = new_peer_id();
#ifndef WIN32
		if (daemon(true, false))
			cerr << "daemon failed" << endl;
		ofstream("xbt.pid") << getpid() << endl;
#endif
		fd_set fd_read_set;
		fd_set fd_write_set;
		fd_set fd_except_set;
		while (1)
		{
			FD_ZERO(&fd_read_set);
			FD_ZERO(&fd_write_set);
			FD_ZERO(&fd_except_set);
			FD_SET(l, &fd_read_set);
			FD_SET(la, &fd_read_set);
			int n = max(l, la);
			{
				for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); i++)
				{
					int z = i->pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
					n = max(n, z);
				}
			}
			{
				for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
				{
					int z = i->pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
					n = max(n, z);
				}
			}
			TIMEVAL tv;
			tv.tv_sec = 1;
			tv.tv_usec = 0;
			if (select(n, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			{
				cerr << "select failed: " << WSAGetLastError() << endl;
				break;
			}
			else
			{
				if (FD_ISSET(l, &fd_read_set) && !m_files.empty())
				{
					sockaddr_in a;
					socklen_t cb_a = sizeof(sockaddr_in);
					Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
					if (s == SOCKET_ERROR)
						cerr << "accept failed: " << WSAGetLastError() << endl;
					else
					{
						if (s.blocking(false))
							cerr << "ioctlsocket failed" << endl;
						m_files.front().insert_peer(a, s);
					}
				}
				if (FD_ISSET(la, &fd_read_set))
				{
					sockaddr_in a;
					socklen_t cb_a = sizeof(sockaddr_in);
					Csocket s = accept(la, reinterpret_cast<sockaddr*>(&a), &cb_a);
					if (s == SOCKET_ERROR)
						cerr << "accept failed: " << WSAGetLastError() << endl;
					else
					{
						if (s.blocking(false))
							cerr << "ioctlsocket failed" << endl;
						m_admins.push_back(Cbt_admin_link(a, s));
					}
				}
				{
					for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); )
					{
						i->post_select(&fd_read_set, &fd_write_set, &fd_except_set);
						if (i->s())
							i = m_admins.erase(i);
						else
							i++;
					}
				}
				{
					for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
						i->post_select(&fd_read_set, &fd_write_set, &fd_except_set);
				}
			}
		}
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
			i->close();
	}
}
