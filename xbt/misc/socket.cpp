// socket.cpp: implementation of the Csocket class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "socket.h"

#ifdef WIN32
#pragma comment(lib, "ws2_32.lib")
#else
#include <netdb.h>
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
	if (this && !--mc_references)
	{
		closesocket(m_s);
		delete this;
	}
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

int Csocket::bind(int h, int p)
{
	sockaddr_in a;
	a.sin_family = AF_INET;
	a.sin_addr.s_addr = h;
	a.sin_port = p;
	return ::bind(*this, reinterpret_cast<sockaddr*>(&a), sizeof(sockaddr_in));
}

int Csocket::blocking(bool v)
{
	unsigned long p = !v;
	return ioctlsocket(*this, FIONBIO, &p);
}

void Csocket::close()
{
	*this = INVALID_SOCKET;
}

int Csocket::connect(int h, int p)
{
	sockaddr_in a;
	a.sin_family = AF_INET;
	a.sin_addr.s_addr = h;
	a.sin_port = p;
	return ::connect(*this, reinterpret_cast<sockaddr*>(&a), sizeof(sockaddr_in));
}

const Csocket& Csocket::open(int t, bool _blocking)
{
	*this = socket(AF_INET, t, 0);
	if (!_blocking && blocking(false))
		close();
	return *this;
}

int Csocket::recv(void* d, int cb_d)
{
	return ::recv(*this, reinterpret_cast<char*>(d), cb_d, MSG_NOSIGNAL);
}

int Csocket::recvfrom(void* d, int cb_d, sockaddr* a, socklen_t* cb_a)
{
	return ::recvfrom(*this, reinterpret_cast<char*>(d), cb_d, MSG_NOSIGNAL, a, cb_a);
}

int Csocket::send(const void* s, int cb_s)
{
	return ::send(*this, reinterpret_cast<const char*>(s), cb_s, MSG_NOSIGNAL);
}

int Csocket::sendto(const void* s, int cb_s, const sockaddr* a, socklen_t cb_a)
{
	return ::sendto(*this, reinterpret_cast<const char*>(s), cb_s, MSG_NOSIGNAL, a, cb_a);
}

int Csocket::get_host(const string& name)
{
	hostent* e = gethostbyname(name.c_str());
	return e && e->h_addrtype == AF_INET && e->h_length == sizeof(in_addr) && e->h_addr_list ? *reinterpret_cast<int*>(*e->h_addr_list) : INADDR_NONE;
}
