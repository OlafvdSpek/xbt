#include "stdafx.h"
#include "server.h"
#include "static_config.h"

// #include <winsock2.h>

int main(int argc, char* argv[])
{
#ifdef WIN32
	WSADATA wsadata;
	if (WSAStartup(MAKEWORD(2, 0), &wsadata))
		return cerr << "Unable to start WSA" << endl, 0;
#endif
	srand(time(NULL));
	Cdatabase database;
	Cxcc_error error;
	Cstatic_config static_config;
	Csocket l0, l1;
	if (error = static_config.read("xbt_tracker.conf"))
		cerr << error.message() << endl;
	if (error = database.open(static_config.mysql_host, static_config.mysql_user, static_config.mysql_password, static_config.mysql_db, true))
		cerr << error.message() << endl;
	if (l0.open(SOCK_STREAM) == INVALID_SOCKET
		|| l1.open(SOCK_DGRAM) == INVALID_SOCKET)
		cerr << "socket failed: " << WSAGetLastError() << endl;
	else if (l0.bind(htonl(INADDR_ANY), htons(2710))
		|| l1.bind(htonl(INADDR_ANY), htons(2710)))
		cerr << "bind failed: " << WSAGetLastError() << endl;
	else if (listen(l0, SOMAXCONN))
		cerr << "listen failed: " << WSAGetLastError() << endl;
	else
	{
#ifndef WIN32
		if (daemon(true, false))
			cerr << "daemon failed" << endl;
		ofstream("xbt_tracker.pid") << getpid() << endl;
#endif
		Cserver server(database);
		server.read_config();
		server.run(l0, l1);
	}
#ifdef WIN32
	WSACleanup();
#endif
	return 0;
}
