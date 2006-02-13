#include "stdafx.h"
#include "server.h"

#include "connection_handler_http_server.h"

static volatile bool g_sig_term = false;

static string new_peer_key()
{
	string v;
	v.resize(8);
	for (size_t i = 0; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

Cserver::Cserver()
{
	m_pass = new_peer_key();
	m_port = 51885;
}

int Cserver::run()
{
	if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
	{
		cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		return 1;
	}
	if (m_s.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
		m_s.bind(htonl(INADDR_LOOPBACK), htons(port())))
	{
		cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		return 1;
	}
	if (m_s.listen())
	{
		cerr << "listen failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		return 1;
	}
#ifndef WIN32
#if 1
	if (daemon(true, false))
		cerr << "daemon failed" << endl;
#else
	switch (fork())
	{
	case -1:
		cerr << "fork failed" << endl;
		break;
	case 0:
		break;
	default:
		exit(0);
	}
#endif
	struct sigaction act;
	act.sa_handler = sig_handler;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	if (sigaction(SIGTERM, &act, NULL))
		cerr << "sigaction failed" << endl;
	act.sa_handler = SIG_IGN;
	if (sigaction(SIGPIPE, &act, NULL))
		cerr << "sigaction failed" << endl;
#endif
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
	while (!g_sig_term)
	{
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		int n = 0;
		for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); i++)
		{
			int z = i->pre_select(&fd_read_set, &fd_write_set);
			n = max(n, z);
		}
		FD_SET(m_s, &fd_read_set);
		n = max(n, static_cast<SOCKET>(m_s));
		timeval tv;
		tv.tv_sec = 1;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		else
		{
			m_time = ::time(NULL);
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->post_select(&fd_read_set, &fd_write_set))
					m_connections.erase(i++);
				else
					i++;
			}
			if (FD_ISSET(m_s, &fd_read_set))
				accept(m_s);
		}
	}
	return 0;
}

void Cserver::accept(const Csocket& l)
{
	sockaddr_in a;
	while (1)
	{
		socklen_t cb_a = sizeof(sockaddr_in);
		Csocket s = ::accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
		if (s == SOCKET_ERROR)
		{
			if (WSAGetLastError() == WSAECONNABORTED)
				continue;
			if (WSAGetLastError() != WSAEWOULDBLOCK)
				cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			break;
		}
		else
		{
			if (s.blocking(false))
				cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#ifdef TCP_CORK
			if (s.setsockopt(IPPROTO_TCP, TCP_CORK, true))
				cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#endif
			m_connections.push_back(Cconnection(this, s, a, new Cconnection_handler_http_server));
		}
	}
}

void Cserver::sig_handler(int v)
{
#ifndef WIN32
	switch (v)
	{
	case SIGHUP:
		g_sig_hup = true;
		break;
	case SIGTERM:
		g_sig_term = true;
		break;
	}
#endif
}

void Cserver::term()
{
	g_sig_term = true;
}

static size_t write_data(void* buffer, size_t size, size_t nmemb, void* userp)
{
	reinterpret_cast<string*>(userp)->append(reinterpret_cast<char*>(buffer), size * nmemb);
	return size * nmemb;
}

int Cserver::http_get(const string& url, string& d)
{
	CURL* handle = curl_easy_init();
	curl_easy_setopt(handle, CURLOPT_ENCODING, "");
	curl_easy_setopt(handle, CURLOPT_FOLLOWLOCATION, true);
	curl_easy_setopt(handle, CURLOPT_MAXREDIRS, 9);
	curl_easy_setopt(handle, CURLOPT_URL, url.c_str());
	curl_easy_setopt(handle, CURLOPT_WRITEDATA, &d);	
	curl_easy_setopt(handle, CURLOPT_WRITEFUNCTION, write_data);
	CURLcode result = curl_easy_perform(handle);
	return 0;
}
