// socket.h: interface for the Csocket class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_SOCKET_H__7FCC2721_54CD_4609_8737_92478B4090EA__INCLUDED_)
#define AFX_SOCKET_H__7FCC2721_54CD_4609_8737_92478B4090EA__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>

using namespace std;

#ifdef WIN32
#include <windows.h>

typedef int socklen_t;
#else
#include <arpa/inet.h>
#include <netinet/in.h>

#define closesocket close
#define ioctlsocket ioctl
#define WSAGetLastError() errno

#define WSAEWOULDBLOCK EWOULDBLOCK
#define WSAEINPROGRESS EINPROGRESS
#define WSAEALREADY EALREADY
#define WSAENOTSOCK ENOTSOCK
#define WSAEDESTADDRREQ EDESTADDRREQ
#define WSAEMSGSIZE EMSGSIZE
#define WSAEPROTOTYPE EPROTOTYPE
#define WSAENOPROTOOPT ENOPROTOOPT
#define WSAEPROTONOSUPPORT EPROTONOSUPPORT
#define WSAESOCKTNOSUPPORT ESOCKTNOSUPPORT
#define WSAEOPNOTSUPP EOPNOTSUPP
#define WSAEPFNOSUPPORT EPFNOSUPPORT
#define WSAEAFNOSUPPORT EAFNOSUPPORT
#define WSAEADDRINUSE EADDRINUSE
#define WSAEADDRNOTAVAIL EADDRNOTAVAIL
#define WSAENETDOWN ENETDOWN
#define WSAENETUNREACH ENETUNREACH
#define WSAENETRESET ENETRESET
#define WSAECONNABORTED ECONNABORTED
#define WSAECONNRESET ECONNRESET
#define WSAENOBUFS ENOBUFS
#define WSAEISCONN EISCONN
#define WSAENOTCONN ENOTCONN
#define WSAESHUTDOWN ESHUTDOWN
#define WSAETOOMANYREFS ETOOMANYREFS
#define WSAETIMEDOUT ETIMEDOUT
#define WSAECONNREFUSED ECONNREFUSED
#define WSAELOOP ELOOP
#define WSAENAMETOOLONG ENAMETOOLONG
#define WSAEHOSTDOWN EHOSTDOWN
#define WSAEHOSTUNREACH EHOSTUNREACH
#define WSAENOTEMPTY ENOTEMPTY
#define WSAEUSERS EUSERS
#define WSAEDQUOT EDQUOT
#define WSAESTALE ESTALE
#define WSAEREMOTE EREMOTE

typedef int SOCKET;

const int INVALID_SOCKET = -1;
const int SOCKET_ERROR = -1;
#endif

class Csocket_source
{
public:
	Csocket_source* attach();
	void detach();
	Csocket_source(SOCKET s);

	operator SOCKET() const
	{
		return m_s;
	}
private:	
	SOCKET m_s;
	int mc_references;
};

class Csocket  
{
public:
	static string error2a(int v);
	static int get_host(const string& name);
	int bind(int h, int p);
	int blocking(bool v);
	void close();
	int connect(int h, int p);
	int listen();
	const Csocket& open(int t, bool blocking = false);
	int recv(void*, int);
	int recvfrom(void* d, int cb_d, sockaddr* a, socklen_t* cb_a);
	int send(const void*, int);
	int sendto(const void*, int, const sockaddr* a, socklen_t cb_a);	
	Csocket(SOCKET = INVALID_SOCKET);
	Csocket(const Csocket&);
	const Csocket& operator=(const Csocket&);
	~Csocket();

	operator SOCKET() const
	{
		return m_source ? static_cast<SOCKET>(*m_source) : INVALID_SOCKET;
	}
private:
	Csocket_source* m_source;
};

#endif // !defined(AFX_SOCKET_H__7FCC2721_54CD_4609_8737_92478B4090EA__INCLUDED_)
