#include "stdafx.h"
#include "bt_tracker_url.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

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
	int port;
	if (!stricmp(v.substr(0, a).c_str(), "http"))
	{
		protocol = tp_http;
		port = 80;
	}
	else if (!stricmp(v.substr(0, a).c_str(), "udp"))
	{
		protocol = tp_udp;
		port = 2710;
	}
	else
		return;
	a += 3;
	int b = v.find_first_of("/:", a);
	string host;
	if (b == string::npos)
		host = v.substr(a);
	else
	{
		host = v.substr(a, b - a);
		if (v[b] == '/')
			m_path = v.substr(b);
		else
		{
			b++;
			a = v.find('/', b);
			if (a == string::npos)
				port = atoi(v.substr(b).c_str());
			else
			{
				port = atoi(v.substr(b, a - b).c_str());
				m_path = v.substr(a);
			}
		}
	}
	m_protocol = protocol;
	m_host = host;
	m_port = port;
}
