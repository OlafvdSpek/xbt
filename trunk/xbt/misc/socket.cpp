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
	mc_references++;
	return this;
}

void Csocket_source::detach()
{
	if (!--mc_references)
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
	m_source = v.m_source ? v.m_source->attach() : NULL;
}

Csocket::~Csocket()
{
	if (m_source)
		m_source->detach();
}

const Csocket& Csocket::operator=(const Csocket& v)
{
	if (this != &v)
	{
		if (m_source)
			m_source->detach();
		m_source = v.m_source ? v.m_source->attach() : NULL;
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

const Csocket& Csocket::open(int t)
{
	return *this = socket(AF_INET, t, 0);
}

int Csocket::recv(void* d, int cb_d)
{
	return ::recv(*this, reinterpret_cast<char*>(d), cb_d, MSG_NOSIGNAL);
}

int Csocket::send(const void* s, int cb_s)
{
	return ::send(*this, reinterpret_cast<const char*>(s), cb_s, MSG_NOSIGNAL);
}

int Csocket::get_host(const string& name)
{
	hostent* e = gethostbyname(name.c_str());
	return e && e->h_addrtype == AF_INET && e->h_length == sizeof(in_addr) && e->h_addr_list ? *reinterpret_cast<int*>(*e->h_addr_list) : INADDR_NONE;
}
