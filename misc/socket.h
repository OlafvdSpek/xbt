#pragma once

#include <boost/utility.hpp>
#include <memory>
#include <string>
#include <xbt/data_ref.h>

#ifdef WIN32
#include <winsock2.h>
#include <ws2tcpip.h>

typedef int socklen_t;
#else
#include <arpa/inet.h>
#include <cerrno>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <sys/socket.h>
#include <sys/uio.h>

#define closesocket close
#define ioctlsocket ioctl
#define WSAGetLastError() errno

#define WSAEACCES EACCES
#define WSAEADDRINUSE EADDRINUSE
#define WSAEADDRNOTAVAIL EADDRNOTAVAIL
#define WSAEAFNOSUPPORT EAFNOSUPPORT
#define WSAEALREADY EALREADY
#define WSAEBADF EBADF
#define WSAECONNABORTED ECONNABORTED
#define WSAECONNREFUSED ECONNREFUSED
#define WSAECONNRESET ECONNRESET
#define WSAEDESTADDRREQ EDESTADDRREQ
#define WSAEDQUOT EDQUOT
#define WSAEFAULT EFAULT
#define WSAEHOSTDOWN EHOSTDOWN
#define WSAEHOSTUNREACH EHOSTUNREACH
#define WSAEINPROGRESS EINPROGRESS
#define WSAEINTR EINTR
#define WSAEINVAL EINVAL
#define WSAEISCONN EISCONN
#define WSAELOOP ELOOP
#define WSAEMFILE EMFILE
#define WSAEMSGSIZE EMSGSIZE
#define WSAENAMETOOLONG ENAMETOOLONG
#define WSAENETDOWN ENETDOWN
#define WSAENETRESET ENETRESET
#define WSAENETUNREACH ENETUNREACH
#define WSAENOBUFS ENOBUFS
#define WSAENOPROTOOPT ENOPROTOOPT
#define WSAENOTCONN ENOTCONN
#define WSAENOTEMPTY ENOTEMPTY
#define WSAENOTSOCK ENOTSOCK
#define WSAEOPNOTSUPP EOPNOTSUPP
#define WSAEPFNOSUPPORT EPFNOSUPPORT
#define WSAEPROTONOSUPPORT EPROTONOSUPPORT
#define WSAEPROTOTYPE EPROTOTYPE
#define WSAEREMOTE EREMOTE
#define WSAESHUTDOWN ESHUTDOWN
#define WSAESOCKTNOSUPPORT ESOCKTNOSUPPORT
#define WSAESTALE ESTALE
#define WSAETIMEDOUT ETIMEDOUT
#define WSAETOOMANYREFS ETOOMANYREFS
#define WSAEUSERS EUSERS
#define WSAEWOULDBLOCK EWOULDBLOCK

typedef int SOCKET;

const int INVALID_SOCKET = -1;
const int SOCKET_ERROR = -1;
#endif

class Csocket_source : boost::noncopyable
{
public:
	Csocket_source(SOCKET s)
	{
		m_s = s;
	}

	~Csocket_source()
	{
		closesocket(m_s);
	}

	operator SOCKET() const
	{
		return m_s;
	}
private:
	SOCKET m_s;
};

class Csocket
{
public:
	static std::string error2a(int v);
	static int get_host(const std::string& name);
	static std::string inet_ntoa(int h);
	static std::string inet_ntoa(std::array<unsigned char, 4>);
	static std::string inet_ntoa(std::array<unsigned char, 16>);
	static std::string inet_ntoa(in6_addr);
	static int start_up();
	int accept(int& h, int& p);
	int bind(int h, int p);
	int bind6(int p);
	int blocking(bool v);
	void close();
	int connect(int h, int p);
	int getsockopt(int level, int name, void* v, socklen_t& cb_v);
	int getsockopt(int level, int name, int& v);
	int listen();
	const Csocket& open(int t, bool blocking = false);
	const Csocket& open6(int t, bool blocking = false);
	int recv(mutable_str_ref) const;
	int recvfrom(mutable_str_ref, sockaddr* a, socklen_t* cb_a) const;
	int send(str_ref) const;
	int sendto(str_ref, const sockaddr* a, socklen_t cb_a) const;
	int setsockopt(int level, int name, const void* v, int cb_v);
	int setsockopt(int level, int name, int v);
	Csocket(SOCKET = INVALID_SOCKET);

	operator SOCKET() const
	{
		return m_source ? static_cast<SOCKET>(*m_source) : INVALID_SOCKET;
	}
private:
	std::shared_ptr<Csocket_source> m_source;
};
