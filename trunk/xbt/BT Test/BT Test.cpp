// BT Test.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "bt_file.h"
#include "bt_tracker_link.h"

// #include <winsock2.h>

string new_peer_id()
{
	string v;
	v = "XBT00";
	v.resize(20);
	for (int i = 5; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

int main(int argc, char* argv[])
{ 
#ifdef WIN32
	WSADATA wsadata;
	if (WSAStartup(MAKEWORD(2, 0), &wsadata))
		return cerr << "Unable to start WSA" << endl, 0;
#endif
	srand(time(NULL));
	Cbt_file f;
	if (f.info(Cvirtual_binary("f:/archives/bt/old/when.vivid.girls.do.orgies.xxx-spice[1].torrent")))
		return 1;
	if (f.open("f:/temp/xbt/" + f.m_name)) // "d:/temp/kz/special forces 2003.avi"))
		return 1;
	f.m_local_port = 6889;
	f.m_peer_id = new_peer_id();
	Csocket l;
	unsigned long p = 1;
	if ((l.open(SOCK_STREAM)) == INVALID_SOCKET)
		cerr << "socket failed" << endl;
	else if (ioctlsocket(l, FIONBIO, &p))
		cerr << "ioctlsocket failed" << endl;
	if (l.bind(INADDR_ANY, htons(f.m_local_port)))
		cerr << "bind failed" << endl;
	else if (listen(l, SOMAXCONN))
		cerr << "listen failed" << endl;
	else
	{
#ifndef WIN32
		if (daemon(true, false))
			cerr << "daemon failed" << endl;
		ofstream("xbt.pid") << getpid() << endl;
#endif
		Cbt_tracker_link tl;
		fd_set fd_read_set;
		fd_set fd_write_set;
		fd_set fd_except_set;
		while (1)
		{
			FD_ZERO(&fd_read_set);
			FD_ZERO(&fd_write_set);
			FD_ZERO(&fd_except_set);
			FD_SET(l, &fd_read_set);
			int n = f.pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
			n = max(l, n);
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
				if (FD_ISSET(l, &fd_read_set))
				{
					sockaddr_in a;
					socklen_t cb_a = sizeof(sockaddr_in);
					Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
					if (s == SOCKET_ERROR)
						cerr << "accept failed: " << WSAGetLastError() << endl;
					else
					{
						unsigned long p = true;
						if (ioctlsocket(s, FIONBIO, &p))
							cerr << "ioctlsocket failed" << endl;
						f.insert_peer(a, s);
					}
				}
				f.post_select(&fd_read_set, &fd_write_set, &fd_except_set);
			}
		}
	}
	f.close();
	return 0;
}
