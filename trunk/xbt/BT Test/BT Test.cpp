#include "stdafx.h"
#include "server.h"

// #include <winsock2.h>

int main(int argc, char* argv[])
{ 
#ifdef WIN32
	WSADATA wsadata;
	if (WSAStartup(MAKEWORD(2, 0), &wsadata))
		return cerr << "Unable to start WSA" << endl, 0;
#endif
	srand(time(NULL));
	Cserver server;
	server.run();
#ifdef WIN32
	WSACleanup();
#endif
	return 0;
}
