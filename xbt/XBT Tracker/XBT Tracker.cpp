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
	if (error = static_config.read("xbt_tracker.conf"))
		cerr << error.message() << endl;
	else if (error = database.open(static_config.mysql_host, static_config.mysql_user, static_config.mysql_password, static_config.mysql_db, true))
		cerr << error.message() << endl;
	Cserver(database).run();
#ifdef WIN32
	WSACleanup();
#endif
	return 0;
}
