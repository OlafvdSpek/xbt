#include "stdafx.h"
#include "windows/nt_service.h"
#include "server.h"
#include "static_config.h"

const char* g_service_name = "XBT Tracker";

int main(int argc, char* argv[])
{
#ifdef WIN32
	if (argc >= 2)
	{
		if (!strcmp(argv[1], "--install"))
		{
			if (nt_service_install(g_service_name))
			{
				cerr << "Failed to install service " << g_service_name << "." << endl;
				return 1;
			}
			cout << "Service " << g_service_name << " has been installed." << endl;
			return 0;
		}
		else if (!strcmp(argv[1], "--uninstall"))
		{
			if (nt_service_uninstall(g_service_name))
			{
				cerr << "Failed to uninstall service " << g_service_name << "." << endl;
				return 1;
			}
			cout << "Service " << g_service_name << " has been uninstalled." << endl;
			return 0;
		}
	}
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
	if (error = database.open(static_config.mysql_host, static_config.mysql_user, static_config.mysql_password, static_config.mysql_db, true))
		cerr << error.message() << endl;
	Cserver(database).run();
#ifdef WIN32
	WSACleanup();
#endif
	return 0;
}
