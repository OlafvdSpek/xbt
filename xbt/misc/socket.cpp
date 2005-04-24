// socket.cpp: implementation of the Csocket class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "socket.h"

#ifdef WIN32
#pragma comment(lib, "ws2_32.lib")
#else
#include <sys/ioctl.h>
#include <sys/socket.h>
#include <fcntl.h>
#include <netdb.h>
#include <unistd.h>
#endif

#ifndef INADDR_NONE
const int INADDR_NONE = -1;
#endif

#ifndef MSG_NOSIGNAL
const int MSG_NOSIGNAL = 0;
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Csocket_source::Csocket_source(SOCKET s)
{
	m_s = s;
	mc_references = 1;
}

Csocket_source* Csocket_source::attach()
{
	if (this)
		mc_references++;
	return this;
}

void Csocket_source::detach()
{
	if (!this || --mc_references)
		return;
	closesocket(m_s);
	delete this;
}

Csocket::Csocket(SOCKET s)
{
	m_source = s == INVALID_SOCKET ? NULL : new Csocket_source(s);
}

Csocket::Csocket(const Csocket& v)
{
	m_source = v.m_source->attach();
}

Csocket::~Csocket()
{
	m_source->detach();
}

const Csocket& Csocket::operator=(const Csocket& v)
{
	if (this != &v)
	{
		m_source->detach();
		m_source = v.m_source->attach();
	}
	return *this;
}

int Csocket::accept(int& h, int& p)
{
	sockaddr_in a;
	socklen_t cb_a = sizeof(sockaddr_in);
	a.sin_family = AF_INET;
	int r = ::accept(*this, reinterpret_cast<sockaddr*>(&a), &cb_a);
	if (r == INVALID_SOCKET)
		return r;
	h = a.sin_addr.s_addr;
	p = a.sin_port;
	return 0;
}

int Csocket::bind(int h, int p)
{
	sockaddr_in a = {0};
	a.sin_family = AF_INET;
	a.sin_addr.s_addr = h;
	a.sin_port = p;
	return ::bind(*this, reinterpret_cast<sockaddr*>(&a), sizeof(sockaddr_in));
}

int Csocket::blocking(bool v)
{
#ifdef FIONBIO
	unsigned long p = !v;
	return ioctlsocket(*this, FIONBIO, &p);
#else
	return fcntl(*this, F_SETFL, v ? 0 : O_NONBLOCK) == -1;
#endif
}

void Csocket::close()
{
	*this = INVALID_SOCKET;
}

int Csocket::connect(int h, int p)
{
	sockaddr_in a = {0};
	a.sin_family = AF_INET;
	a.sin_addr.s_addr = h;
	a.sin_port = p;
	return ::connect(*this, reinterpret_cast<sockaddr*>(&a), sizeof(sockaddr_in));
}

int Csocket::listen()
{
	return ::listen(*this, SOMAXCONN);
}

const Csocket& Csocket::open(int t, bool _blocking)
{
	*this = socket(AF_INET, t, 0);
	if (!_blocking && blocking(false))
		close();
	return *this;
}

int Csocket::recv(void* d, int cb_d) const
{
	return ::recv(*this, reinterpret_cast<char*>(d), cb_d, MSG_NOSIGNAL);
}

int Csocket::recvfrom(void* d, int cb_d, sockaddr* a, socklen_t* cb_a) const
{
	return ::recvfrom(*this, reinterpret_cast<char*>(d), cb_d, MSG_NOSIGNAL, a, cb_a);
}

int Csocket::send(const void* s, int cb_s) const
{
	return ::send(*this, reinterpret_cast<const char*>(s), cb_s, MSG_NOSIGNAL);
}

int Csocket::sendto(const void* s, int cb_s, const sockaddr* a, socklen_t cb_a) const
{
	return ::sendto(*this, reinterpret_cast<const char*>(s), cb_s, MSG_NOSIGNAL, a, cb_a);
}

int Csocket::getsockopt(int level, int name, void* v, socklen_t& cb_v)
{
	return ::getsockopt(*this, level, name, reinterpret_cast<char*>(v), &cb_v);
}

int Csocket::getsockopt(int level, int name, int& v)
{
	socklen_t cb_v = sizeof(int);
	return getsockopt(level, name, &v, cb_v);
}

int Csocket::setsockopt(int level, int name, const void* v, int cb_v)
{
	return ::setsockopt(*this, level, name, reinterpret_cast<const char*>(v), cb_v);
}

int Csocket::setsockopt(int level, int name, int v)
{
	return setsockopt(level, name, &v, sizeof(int));
}

int Csocket::get_host(const string& name)
{
	hostent* e = gethostbyname(name.c_str());
	return e && e->h_addrtype == AF_INET && e->h_length == sizeof(in_addr) && e->h_addr_list ? *reinterpret_cast<int*>(*e->h_addr_list) : INADDR_NONE;
}

string Csocket::error2a(int v)
{
	switch (v)
	{
	case WSAEACCES: return "EACCES";
	case WSAEADDRINUSE: return "EADDRINUSE";
	case WSAEADDRNOTAVAIL: return "EADDRNOTAVAIL";
	case WSAEAFNOSUPPORT: return "EAFNOSUPPORT";
	case WSAEALREADY: return "EALREADY";
	case WSAEBADF: return "EBADF";
	case WSAECONNABORTED: return "ECONNABORTED";
	case WSAECONNREFUSED: return "ECONNREFUSED";
	case WSAECONNRESET: return "ECONNRESET";
	case WSAEDESTADDRREQ: return "EDESTADDRREQ";
	case WSAEDQUOT: return "EDQUOT";
	case WSAEFAULT: return "EFAULT";
	case WSAEHOSTDOWN: return "EHOSTDOWN";
	case WSAEHOSTUNREACH: return "EHOSTUNREACH";
	case WSAEINPROGRESS: return "EINPROGRESS";
	case WSAEINTR: return "EINTR";
	case WSAEINVAL: return "EINVAL";
	case WSAEISCONN: return "EISCONN";
	case WSAELOOP: return "ELOOP";
	case WSAEMFILE: return "EMFILE";
	case WSAEMSGSIZE: return "EMSGSIZE";
	case WSAENAMETOOLONG: return "ENAMETOOLONG";
	case WSAENETDOWN: return "ENETDOWN";
	case WSAENETRESET: return "ENETRESET";
	case WSAENETUNREACH: return "ENETUNREACH";
	case WSAENOBUFS: return "ENOBUFS";
	case WSAENOPROTOOPT: return "ENOPROTOOPT";
	case WSAENOTCONN: return "ENOTCONN";
	case WSAENOTEMPTY: return "ENOTEMPTY";
	case WSAENOTSOCK: return "ENOTSOCK";
	case WSAEOPNOTSUPP: return "EOPNOTSUPP";
	case WSAEPFNOSUPPORT: return "EPFNOSUPPORT";
	case WSAEPROTONOSUPPORT: return "EPROTONOSUPPORT";
	case WSAEPROTOTYPE: return "EPROTOTYPE";
	case WSAEREMOTE: return "EREMOTE";
	case WSAESHUTDOWN: return "ESHUTDOWN";
	case WSAESOCKTNOSUPPORT: return "ESOCKTNOSUPPORT";
	case WSAESTALE: return "ESTALE";
	case WSAETIMEDOUT: return "ETIMEDOUT";
	case WSAETOOMANYREFS: return "ETOOMANYREFS";
	case WSAEUSERS: return "EUSERS";
	case WSAEWOULDBLOCK: return "EWOULDBLOCK";
	}
	char b[12];
	sprintf(b, "%d", v);
	return b;
}

string Csocket::inet_ntoa(int v)
{
	in_addr a;
	a.s_addr = v;
	return ::inet_ntoa(a);
}
