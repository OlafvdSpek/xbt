// bt_tracker_url.cpp: implementation of the Cbt_tracker_url class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_tracker_url.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_tracker_url::Cbt_tracker_url()
{
}

Cbt_tracker_url::Cbt_tracker_url(const string& v)
{
	write(v);
}

void Cbt_tracker_url::clear()
{
	m_protocol = tp_unknown;
	m_host.erase();
	m_port = 0;
	m_path.erase();
}

bool Cbt_tracker_url::valid() const
{
	switch (m_protocol)
	{
	case tp_http:
		if (m_path.empty() || m_path[0] != '/')
			return false;
	case tp_udp:
		return !m_host.empty()
			&& m_port >= 0 && m_port < 0x10000;
	}
	return false;
}

void Cbt_tracker_url::write(const string& v)
{
	clear();
	int a = v.find("://");
	if (a == string::npos)
		return;
	int protocol;
	if (v.substr(0, a) == "http")
		protocol = tp_http;
	else if (v.substr(0, a) == "udp")
		protocol = tp_udp;
	else 
		return;
	a += 3;
	int b = v.find_first_of("/:", a);
	if (b == string::npos)
		return;
	string host = v.substr(a, b - a);
	int port;
	if (v[b] == '/')
		port = m_protocol == tp_http ? 80 : 2710;
	else
	{
		b++;
		a = v.find('/', b);
		if (a == string::npos)
			return;
		port = atoi(v.substr(b, a - b).c_str());
		if (port == 2710)
			protocol = tp_udp;
		b = a;
	}
	m_protocol = protocol;
	m_host = host;
	m_port = port;
	m_path = v.substr(b);
}