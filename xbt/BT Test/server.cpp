// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

#include "bt_strings.h"
#include "stream_reader.h"

#define for if (0) {} else for

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
	m_new_admin_port = 6879;
	m_new_peer_port = 6889;
	m_run = false;
	m_update_chokes_time = 0;
	m_update_send_quotas_time = time(NULL);
	m_upload_rate = 0;

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

void Cserver::admin_port(int v)
{
	m_new_admin_port = v;
}

void Cserver::peer_port(int v)
{
	m_new_peer_port = v;
}

void Cserver::public_ipa(int v)
{
	m_public_ipa = v == INADDR_NONE ? 0 : v;
}

void Cserver::upload_rate(int v)
{
	m_upload_rate = v;
}

int Cserver::run()
{
	m_admin_port = m_new_admin_port;
	m_peer_port = m_new_peer_port;
	Csocket l, la;
	if (l.open(SOCK_STREAM) == INVALID_SOCKET
		|| la.open(SOCK_STREAM) == INVALID_SOCKET)
		return alert(Calert(Calert::emerg, "Server", "socket failed" + n(WSAGetLastError()))), 1;
	while (admin_port() < 0x10000 && la.bind(htonl(INADDR_LOOPBACK), htons(admin_port())) && WSAGetLastError() == WSAEADDRINUSE)
		m_admin_port++;
	while (peer_port() < 0x10000 && l.bind(htonl(INADDR_ANY), htons(peer_port())) && WSAGetLastError() == WSAEADDRINUSE)
		m_peer_port++;
	if (l.listen()
		|| la.listen())
		return alert(Calert(Calert::emerg, "Server", "listen failed" + n(WSAGetLastError()))), 1;
	else
	{
		load_state(Cvirtual_binary(state_fname()));
		save_state(true).save(state_fname());
#ifndef WIN32
		if (daemon(true, false))
			alert(Calert(Calert::error, "Server", "daemon failed" + n(errno)));
		ofstream("xbt.pid") << getpid() << endl;
#endif
		fd_set fd_read_set;
		fd_set fd_write_set;
		fd_set fd_except_set;
		for (m_run = true; m_run; )
		{
			lock();
			if (m_new_admin_port != m_admin_port)
			{
				Csocket s;
				if (s.open(SOCK_STREAM) != INVALID_SOCKET
					&& !s.bind(htonl(INADDR_LOOPBACK), htons(m_new_admin_port))
					&& !s.listen())
				{
					la = s;
					m_admin_port = m_new_admin_port;
				}
			}
			if (m_new_peer_port != m_peer_port)
			{
				Csocket s;
				if (s.open(SOCK_STREAM) != INVALID_SOCKET
					&& !s.bind(htonl(INADDR_ANY), htons(m_new_peer_port))
					&& !s.listen())
				{
					l = s;
					m_peer_port = m_new_peer_port;
					for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
						i->m_local_port = peer_port();
				}
			}
			update_send_quotas();
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
				alert(Calert(Calert::error, "Server", "select failed" + ::n(WSAGetLastError())));
				break;
			}
			lock();
			if (FD_ISSET(l, &fd_read_set))
			{
				sockaddr_in a;
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
					alert(Calert(Calert::error, "Server", "accept failed" + ::n(WSAGetLastError())));
				else
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed" + ::n(WSAGetLastError())));
					m_links.push_back(Cbt_link(this, a, s));
				}
			}
			if (FD_ISSET(la, &fd_read_set))
			{
				sockaddr_in a;
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(la, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
					alert(Calert(Calert::error, "Server", "accept failed" + ::n(WSAGetLastError())));
				else
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed" + ::n(WSAGetLastError())));
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
			if (time(NULL) - m_update_chokes_time > 10)
				update_chokes();
			unlock();
		}
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
			i->close();
	}
	save_state(false).save(state_fname());
	return 0;
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

int Cserver::pre_file_dump(const string& id) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
			return i->pre_dump(true);
	};
	return 0;
}

void Cserver::file_dump(Cstream_writer& w, const string& id) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
		{
			i->dump(w, true);
			return;
		}
	}
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

Cvirtual_binary Cserver::get_file_status(const string& id)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_file_dump(id)));
	file_dump(w, id);
	assert(w.w() == d.data_end());
	return d;
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

int Cserver::start_file(const string& id)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->m_run = true;
		return 0;
	}
	return 1;
}

int Cserver::stop_file(const string& id)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->m_run = false;
		return 0;
	}
	return 1;
}

string Cserver::get_url(const string& id)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
			return i->get_url();
	}
	return "";
}

int Cserver::open(const Cvirtual_binary& info, const string& name)
{
	while (!m_run)
		Sleep(100);
	Clock l(m_cs);
	Cbt_file f;
	if (f.torrent(info))
		return 1;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == f.m_info_hash)
			return 2;
	}
	if (f.open(name, true))
		return 3;
	f.m_local_ipa = public_ipa();
	f.m_local_port = peer_port();
	f.m_peer_id = new_peer_id();
	m_files.push_front(f);
	save_state(true).save(state_fname());
	return 0;
}

int Cserver::open_url(const string& v)
{
	int a = v.find("://");
	if (a == string::npos || v.substr(0, a) != "xbtp")
		return 1;
	a += 3;
	int b = v.find('/', a);
	if (b == string::npos)
		return 2;
	string tracker = v.substr(a, b++ - a);
	a = v.find('/', b);
	if (a == string::npos)
		return 3;
	string info_hash = hex_decode(v.substr(b, a++ - b));
	b = v.find('/', a);
	if (b == string::npos)
		return 4;
	string info_hashes_hash = hex_decode(v.substr(a, b++ - a));
	string peers = hex_decode(v.substr(b));
	while (!m_run)
		Sleep(100);
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != info_hash)
			continue;
		for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
			i->insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
		return 0;
	}
	return 5;
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
		save_state(true).save(state_fname());
		return 0;
	}
	return 1;
}

void Cserver::load_state(const Cvirtual_binary& d)
{
	Clock l(m_cs);
	if (d.size() < 4)
		return;
	Cstream_reader r(d);
	for (int c_files = r.read_int32(); c_files--; )
	{
		Cbt_file f;
		f.load_state(r);
		if (f.open(f.m_name, !f.c_valid_pieces()))
			continue;
		f.m_local_ipa = public_ipa();
		f.m_local_port = peer_port();
		f.m_peer_id = new_peer_id();
		m_files.push_front(f);
	}
	assert(r.r() == d.data_end());
}

Cvirtual_binary Cserver::save_state(bool intermediate)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	int cb_d = 4;
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
			cb_d += i->pre_save_state(intermediate);
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

void Cserver::alert(const Calert& v)
{
	m_alerts.push_back(v);
}

void Cserver::update_chokes()
{
	m_update_chokes_time = time(NULL);
}

void Cserver::update_send_quotas()
{
	if (m_upload_rate)
	{
		int t = time(NULL);
		if (m_update_send_quotas_time == t)
		{
			if (!m_send_quota)
				return;
		}
		else
			m_send_quota = min(t - m_update_send_quotas_time, 3) * m_upload_rate;
		m_update_send_quotas_time = t;

		typedef multimap<int, Cbt_peer_link*> t_links;
		t_links links;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); j++)
			{
				if (j->cb_write_buffer())
					links.insert(t_links::value_type(j->cb_write_buffer(), &*j));
			}
		}
		for (t_links::iterator i = links.begin(); i != links.end(); i++)
		{
			int q = min(i->first, m_send_quota / links.size());
			i->second->send_quota(q);
			m_send_quota -= q;
		}
	}
	else
	{
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); j++)
				j->send_quota(INT_MAX);
		}
	}
}