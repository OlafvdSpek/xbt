// connection.cpp: implementation of the Cconnection class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "connection.h"

#include "bt_misc.h"
#include "server.h"
#include "xcc_z.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cconnection::Cconnection()
{
}

Cconnection::Cconnection(Cserver* server, const Csocket& s, const sockaddr_in& a)
{
	m_server = server;
	m_s = s;
	m_a = a;
	m_ctime = time(NULL);
	
	m_state = 0;
	m_read_b.resize(4 << 10);
	m_w = 0;
}

int Cconnection::pre_select(fd_set* fd_read_set, fd_set* fd_write_set)
{
	FD_SET(m_s, fd_read_set);
	if (!m_write_b.empty())
		FD_SET(m_s, fd_write_set);
	return m_s;
}

int Cconnection::post_select(fd_set* fd_read_set, fd_set* fd_write_set)
{
	return FD_ISSET(m_s, fd_read_set) && recv()
		|| FD_ISSET(m_s, fd_write_set) && send()
		|| time(NULL) - m_ctime > 15
		|| m_state == 5 && m_write_b.empty();
}

int Cconnection::recv()
{
	for (int r; r = m_s.recv(&m_read_b.front() + m_w, m_read_b.size() - m_w); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				return 1;
			case WSAEWOULDBLOCK:
				return 0;
			}
			cerr << "recv failed: " << Csocket::error2a(e) << endl;
			return 1;
		}
		if (m_state == 5)
			return 0;
		char* a = &m_read_b.front() + m_w;
		m_w += r;
		int state;
		do
		{
			state = m_state;
			while (a < &m_read_b.front() + m_w && *a != '\n' && *a != '\r')
			{
				a++;
				if (m_state)
					m_state = 1;

			}
			if (a < &m_read_b.front() + m_w)
			{
				switch (m_state)
				{
				case 0:
					read(string(&m_read_b.front(), a));
					m_state = 1;
				case 1:
				case 3:
					m_state += *a == '\n' ? 2 : 1;
					break;
				case 2:
				case 4:
					m_state++;
					break;
				}
				a++;
			}
		}
		while (state != m_state);
		if (m_state == 5)
			return 0;
	}
	m_state = 5;
	return 0;
}

int Cconnection::send()
{
	for (int r; r = m_s.send(&m_write_b.front() + m_r, m_write_b.size() - m_r); )
	{
		if (r == SOCKET_ERROR)
		{
			int e = WSAGetLastError();
			switch (e)
			{
			case WSAECONNABORTED:
			case WSAECONNRESET:
				return 1;
			case WSAEWOULDBLOCK:
				return 0;
			}
			cerr << "send failed: " << Csocket::error2a(e) << endl;
			return 0;
		}
		m_r += r;
		if (m_r == m_write_b.size())
		{
			m_write_b.clear();
			break;
		}
	}
	return 0;
}

void Cconnection::read(const string& v)
{
	cout << v << endl;
	{
		static ofstream f("xbt_tracker_raw.log");
		f << time(NULL) << '\t' << inet_ntoa(m_a.sin_addr) << '\t' << ntohs(m_a.sin_port) << '\t' << v << endl;
	}
	Ctracker_input ti;
	int a = v.find('?');
	if (a++ != string::npos)
	{
		int b = v.find(' ', a);
		if (b == string::npos)
			return;
		while (a < b)
		{
			int c = v.find('=', a);
			if (c++ == string::npos)
				break;
			int d = v.find_first_of(" &", c);
			assert(d != string::npos);
			ti.set(v.substr(a, c - a - 1), uri_decode(v.substr(c, d - c)));
			a = d + 1;
		}
	}
	if (!ti.m_ipa || !is_private_ipa(m_a.sin_addr.s_addr))
		ti.m_ipa = m_a.sin_addr.s_addr;
	string h = "HTTP/1.0 200 OK\r\n"
		"Content-Type: text/html; charset=us-ascii\r\n";
	Cvirtual_binary s;
	bool gzip = true;
	switch (5 < v.length() ? v[5] : 0) 
	{
	case 'a':
		gzip = m_server->gzip_announce() && !ti.m_compact;
		if (ti.valid())
		{
			m_server->insert_peer(ti, ti.m_ipa == m_a.sin_addr.s_addr, false, 0);
			s = m_server->select_peers(ti).read();
		}
		break;
	case 'd':
		gzip = m_server->gzip_debug();
		{
			string v = m_server->debug(ti);
			s = Cvirtual_binary(v.c_str(), v.length());
		}
		break;
	case 's':
		gzip = m_server->gzip_scrape() && ti.m_info_hash.empty();
		s = m_server->scrape(ti).read();
		break;
	default:
		if (m_server->redirect_url().empty())
			h = "HTTP/1.0 404 Not Found\r\n";
		else
		{
			h = "HTTP/1.0 302 Found\r\n"
				"Location: " + m_server->redirect_url() + "\r\n";
		}
	}
	if (gzip && s)
	{
		static ofstream f("xbt_tracker_gzip.log");
		f << time(NULL) << '\t' << v[5] << '\t' << s.size() << '\t';
		Cvirtual_binary s2 = xcc_z::gzip(s);
		f << s2.size() << '\t' << ti.m_compact << '\t' << (!ti.m_compact && ti.m_no_peer_id) << endl;
		if (s2.size() + 24 < s.size())
		{
			h += "Content-Encoding: gzip\r\n";
			s = s2;
		}
	}
	h += "\r\n";
	Cvirtual_binary d;
	memcpy(d.write_start(h.size() + s.size()), h.c_str(), h.size());
	s.read(d.data_edit() + h.size());
	int r = m_s.send(d, d.size());
	if (r == SOCKET_ERROR)
		cerr << "send failed: " << Csocket::error2a(WSAGetLastError()) << endl;
	else if (r != d.size())
	{
		m_write_b.resize(d.size() - r);
		memcpy(&m_write_b.front(), d + r, d.size() - r);
		m_r = 0;
	}
}
