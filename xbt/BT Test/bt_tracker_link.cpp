// bt_tracker_link.cpp: implementation of the Cbt_tracker_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_tracker_link.h"

#include "bt_file.h"
#include "bt_misc.h"
#include "bt_strings.h"

enum
{
	tp_http,
	tp_tcp,
	tp_udp,
	tp_unknown
};

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_tracker_link::Cbt_tracker_link()
{
	m_state = 0;
}

Cbt_tracker_link::~Cbt_tracker_link()
{
}

static int split_url(const string& url, int& protocol, string& address, int& port, string& path)
{
	int a = url.find("://");
	if (a == string::npos)
		return 1;
	int b = url.find(':', a + 3);
	int c = url.find('/', a + 3);
	if (c == string::npos)
		return 1;
	if (url.substr(0, a) == "http")
		protocol = tp_http;
	else if (url.substr(0, a) == "tcp")
		protocol = tp_tcp;
	else if (url.substr(0, a) == "udp")
		protocol = tp_udp;
	else 
		protocol = tp_unknown;
	if (b == string::npos || b > c)
	{
		address = url.substr(a + 3, c - a - 3);
		port = 80;
	}
	else
	{
		address = url.substr(a + 3, b - a - 3);
		port = atoi(url.substr(b + 1, c - b - 1).c_str());
	}
	path = url.substr(c);
	return 0;
}

int Cbt_tracker_link::write(Cbt_file* f)
{
	switch (m_state)
	{
	case -1:
		m_state = 0;
		return -1;
	case 0:
		break;
	case 3:
		return 1;
	default:
		return 0;
	}
	m_f = f;
	if (split_url(f->m_trackers.front(), m_protocol, m_host, m_port, m_path))
		return 1;
	switch (m_protocol)
	{
	case tp_http:
		if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
			return 1;
		break;
	case tp_tcp:
		if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
			return 1;
		break;
	case tp_udp:
		if (m_s.open(SOCK_DGRAM) == INVALID_SOCKET)
			return 1;
		break;
	default:
		return 1;
	}
	if (m_s.connect(Csocket::get_host(m_host), htons(m_port)) && WSAGetLastError() != WSAEWOULDBLOCK)
		return 1;
	m_w = m_d.write_start(16 << 10);
	m_state = 1;
	return 0;
}

int Cbt_tracker_link::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 1:
		FD_SET(m_s, fd_write_set);
		FD_SET(m_s, fd_except_set);
	case 2:
		FD_SET(m_s, fd_read_set);
		return m_s;
	}
	return 0;
}

void Cbt_tracker_link::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 1:
		if (FD_ISSET(m_s, fd_write_set))
		{
			strstream os;
			os << "GET " << m_path 
				<< "?info_hash=" << uri_encode(m_f->m_info_hash) 
				<< "&peer_id=" << uri_encode(m_f->m_peer_id) 
				// << "&ip=" << uri_encode("62.163.33.227")
				<< "&port=" << m_f->m_local_port
				<< "&uploaded=" << m_f->m_uploaded 
				<< "&downloaded=" << m_f->m_downloaded 
				<< "&left=" << m_f->m_left
				// << "&event=" << uri_encode("started") 
				<< " HTTP/1.0" << endl
				// << "accept-encoding: gzip" << endl
				<< "host: " << m_host << ':' << m_port << endl
				<< endl;
			if (m_s.send(os.str(), os.pcount()) != os.pcount())
				close();
			else
				m_state = 2;
		}
		else if (FD_ISSET(m_s, fd_except_set))
			close();
		break;
	case 2:
		if (FD_ISSET(m_s, fd_read_set))
		{
			for (int r; r = m_s.recv(m_w, m_d.data_end() - m_w); )
			{
				if (r == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						close();
					return;
				}
				m_w += r;
			}
			if (m_w - m_d == m_d.size())
			{
				close();
				return;
			}
			m_s.close();
			m_d.size(m_w - m_d);
			m_d.save("d:/temp/bt/tracker out.txt");
			m_state = read(m_d) ? -1 : 3;
		}
		break;
	}
}

int Cbt_tracker_link::read(const Cvirtual_binary& d)
{
	for (const byte* r = d; r < d.data_end(); r++)
	{
		if (*r == ' ')
		{
			int http_result = atoi(string(reinterpret_cast<const char*>(r), d.data_end() - r).c_str());
			if (http_result != 200)
				return 1;
			for (const byte* r = d; r + 4 <= d.data_end(); r++)
			{
				if (!memcmp(r, "\r\n\r\n", 4))
				{
					r += 4;
					Cbvalue v;
					if (v.write(r, d.data_end() - r))
						return 1;
					if (v.d(bts_failure_reason).s().empty())
					{
						const Cbvalue::t_list& peers = v.d(bts_peers).l();
						for (Cbvalue::t_list::const_iterator i = peers.begin(); i != peers.end(); i++)
						{
							int ipa = htonl(inet_addr(i->d(bts_ipa).s().c_str()));
							if (!ipa)
							{
								cout << i->d(bts_ipa).s() << endl;
								ipa = Csocket::get_host(i->d(bts_ipa).s());
							}
							sockaddr_in a;
							a.sin_family = AF_INET;
							a.sin_port = htons(i->d(bts_port).i());
							a.sin_addr.s_addr = htonl(ipa);
							m_f->insert_peer(a);
						}
						return 0;
					}
					else
						cout << v.d(bts_failure_reason).s();
				}
			}
		}
	}
	return 1;
}

void Cbt_tracker_link::close()
{
	m_s.close();
	m_state = -1;
}
