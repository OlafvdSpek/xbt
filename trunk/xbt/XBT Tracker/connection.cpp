// connection.cpp: implementation of the Cconnection class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "connection.h"

#include "server.h"
#include "xcc_z.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cconnection::Cconnection()
{
}

Cconnection::Cconnection(Cserver* server, SOCKET s, const sockaddr_in& a)
{
	m_server = server;
	m_s = s;
	m_a = a;
	m_ctime = time(NULL);
	
	m_read_b.resize(4 << 10);
	m_w = 0;
}

int Cconnection::pre_select(fd_set* fd_read_set)
{
	FD_SET(m_s, fd_read_set);
	return m_s;
}

void Cconnection::post_select(fd_set* fd_read_set)
{
	if (FD_ISSET(m_s, fd_read_set))
		recv();
	if (m_ctime + 15 < time(NULL))
		close();
}

void Cconnection::recv()
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
				close();
			case WSAEWOULDBLOCK:
				break;
			default:
				cerr << "recv failed: " << e << endl;
				close();
			}
			return;
		}
		char* a = &m_read_b.front() + m_w;
		m_w += r;
		while (a < &m_read_b.front() + m_w && *a != '\n' && *a != '\r')
			a++;
		if (a < &m_read_b.front() + m_w)
		{
			read(string(&m_read_b.front(), a));
			break;
		}
	}
	close();
}

void Cconnection::close()
{
	m_s.close();
}

static string hex_encode(const string& v)
{
	string w;
	w.reserve(v.length() << 1);
	for (int i = 0; i < v.length(); i++)
	{
		w += "0123456789abcdef"[v[i] >> 4 & 0xf];
		w += "0123456789abcdef"[v[i] & 0xf];
	}
	return w;
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
	bool announce = !strnicmp(v.c_str(), "get /a", 6);
	bool scrape = !strnicmp(v.c_str(), "get /s", 6);
	if (announce ? !ti.valid() : !scrape)
		return;
	ti.m_ipa = inet_ntoa(m_a.sin_addr);
	{
		static ofstream f("xbt_tracker.log");
		f << time(NULL) << '\t' << inet_ntoa(m_a.sin_addr) << '\t' << ntohs(m_a.sin_port) 
			 << '\t'<< ti.m_event << '\t' << ti.m_downloaded << '\t' << ti.m_uploaded << '\t' << ti.m_left 
			 << '\t'<< hex_encode(ti.m_info_hash) << '\t' << hex_encode(ti.m_peer_id) << endl;
	}
	if (announce)
		m_server->insert_peer(ti);
	Cvirtual_binary s = xcc_z::gzip((announce ? m_server->select_peers(ti) : m_server->scrape(ti)).read());
	const char* h = "HTTP/1.0 200 OK\r\nContent-Type: text/plain\r\nContent-Encoding: gzip\r\n\r\n";
	Cvirtual_binary d;
	s.read(strcpy(reinterpret_cast<char*>(d.write_start(strlen(h) + s.size())), h) + strlen(h));
	if (m_s.send(d, d.size()) != d.size())
		cerr << "send failed" << endl;
}
