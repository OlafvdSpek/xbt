// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

#include "bt_strings.h"
#include "stream_reader.h"

class Clock
{
public:
	Clock(CRITICAL_SECTION& cs)
	{
		EnterCriticalSection(m_cs = &cs);
	}

	~Clock()
	{
		LeaveCriticalSection(m_cs);
	}
private:
	Clock(const Clock&)
	{
	}

	operator=(const Clock&)
	{
	}	

	CRITICAL_SECTION* m_cs;
};

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cserver::Cserver()
{
	m_admin_port = 6879;
	m_peer_port = 6889;

	InitializeCriticalSection(&m_cs);
}

Cserver::~Cserver()
{
	DeleteCriticalSection(&m_cs);
}

static string new_peer_id()
{
	string v;
	v = "XBT000";
	v.resize(20);
	for (int i = 6; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

void Cserver::run()
{
	Csocket l, la;
	if (l.open(SOCK_STREAM) == INVALID_SOCKET
		|| la.open(SOCK_STREAM) == INVALID_SOCKET)
		cerr << "socket failed" << endl;
	if (l.bind(htonl(INADDR_ANY), htons(peer_port()))
		|| la.bind(htonl(INADDR_LOOPBACK), htons(admin_port())))
		cerr << "bind failed" << endl;
	else if (listen(l, SOMAXCONN)
		|| listen(la, SOMAXCONN))
		cerr << "listen failed" << endl;
	else
	{
		load_state(Cvirtual_binary(state_fname()));
		save_state(true).save(state_fname());
#ifndef WIN32
		if (daemon(true, false))
			cerr << "daemon failed" << endl;
		ofstream("xbt.pid") << getpid() << endl;
#endif
		fd_set fd_read_set;
		fd_set fd_write_set;
		fd_set fd_except_set;
		for (m_run = true; m_run; )
		{
			lock();
			FD_ZERO(&fd_read_set);
			FD_ZERO(&fd_write_set);
			FD_ZERO(&fd_except_set);
			FD_SET(l, &fd_read_set);
			FD_SET(la, &fd_read_set);
			int n = max(l, la);
			{
				for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); i++)
				{
					int z = i->pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
					n = max(n, z);
				}
			}
			{
				for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
				{
					int z = i->pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
					n = max(n, z);
				}
			}
			{
				for (t_links::iterator i = m_links.begin(); i != m_links.end(); i++)
				{
					int z = i->pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
					n = max(n, z);
				}
			}
			unlock();
			TIMEVAL tv;
			tv.tv_sec = 1;
			tv.tv_usec = 0;
			if (select(n, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			{
				cerr << "select failed: " << WSAGetLastError() << endl;
				break;
			}
			lock();
			if (FD_ISSET(l, &fd_read_set))
			{
				sockaddr_in a;
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
					cerr << "accept failed: " << WSAGetLastError() << endl;
				else
				{
					if (s.blocking(false))
						cerr << "ioctlsocket failed" << endl;
					m_links.push_back(Cbt_link(this, a, s));
				}
			}
			if (FD_ISSET(la, &fd_read_set))
			{
				sockaddr_in a;
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(la, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
					cerr << "accept failed: " << WSAGetLastError() << endl;
				else
				{
					if (s.blocking(false))
						cerr << "ioctlsocket failed" << endl;
					m_admins.push_back(Cbt_admin_link(this, a, s));
				}
			}
			{
				for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); )
				{
					i->post_select(&fd_read_set, &fd_write_set, &fd_except_set);
					if (*i)
						i++;
					else
						i = m_admins.erase(i);
				}
			}
			{
				for (t_links::iterator i = m_links.begin(); i != m_links.end(); )
				{
					i->post_select(&fd_read_set, &fd_write_set, &fd_except_set);
					if (*i)
						i++;
					else
						i = m_links.erase(i);
				}
			}
			{
				for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
					i->post_select(&fd_read_set, &fd_write_set, &fd_except_set);
			}
			unlock();
		}
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
			i->close();
	}
	save_state(false).save(state_fname());
}

void Cserver::stop()
{
	m_run = false;
}

ostream& Cserver::dump(ostream& os) const
{
	os << "<link rel=stylesheet href=\"http://xccu.sourceforge.net/xcc.css\"><meta http-equiv=refresh content=5><title>XBT Client</title>";
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i != m_files.begin())
			os << "<hr>";
		os << *i;
	}
	return os;
}

ostream& operator<<(ostream& os, const Cserver& v)
{
	return v.dump(os);
}

int Cserver::pre_dump() const
{
	int size = 4;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		size += i->pre_dump();
	return size;
}

void Cserver::dump(Cstream_writer& w) const
{
	w.write_int32(m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->dump(w);
}

void Cserver::insert_peer(const t_bt_handshake& handshake, const sockaddr_in& a, const Csocket& s)
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == handshake.info_hash())
			i->insert_peer(handshake, a, s);
	}
}

void Cserver::lock()
{
	EnterCriticalSection(&m_cs);
}

void Cserver::unlock()
{
	LeaveCriticalSection(&m_cs);
}

Cvirtual_binary Cserver::get_status()
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_dump()));
	dump(w);
	assert(w.w() == d.data_end());
	return d;
}

int Cserver::open(const Cvirtual_binary& info, const string& name)
{
	Clock l(m_cs);
	Cbt_file f;
	if (f.info(info, true))
		return 1;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == f.m_info_hash)
			return 2;
	}
	if (f.open(name, true))
		return 3;
	f.m_local_port = peer_port();
	f.m_peer_id = new_peer_id();
	m_files.push_front(f);
	save_state(true).save(state_fname());
	return 0;
}

int Cserver::close(const string& id)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->close();
		m_files.erase(i);
		return 0;
	}
	return 1;
}

void Cserver::load_state(const Cvirtual_binary& d)
{
	if (d.size() < 4)
		return;
	Cstream_reader r(d);
	for (int c_files = r.read_int32(); c_files--; )
	{
		Cbt_file f;
		f.load_state(r);
		if (f.open(f.m_name, !f.c_valid_pieces()))
			continue;
		f.m_local_port = peer_port();
		f.m_peer_id = new_peer_id();
		m_files.push_front(f);
	}
	assert(r.r() == d.data_end());
}

Cvirtual_binary Cserver::save_state(bool intermediate)
{
	Cvirtual_binary d;
	int cb_d = 4;
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
			cb_d = i->pre_save_state(intermediate);
	}
	Cstream_writer w(d.write_start(cb_d));
	w.write_int32(m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->save_state(w, intermediate);
	assert(w.w() == d.data_end());
	return d;
}

string Cserver::state_fname() const
{
	return m_dir + "/state.bin";
}
