// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

#include <signal.h>
#include "bt_strings.h"
#include "stream_reader.h"

#define for if (0) {} else for

const char* g_pid_fname = "xbt_client_back_end.pid";
static volatile bool g_sig_term = false;
const static int g_state_version = 2;

class Clock
{
#ifdef WIN32
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
#else
public:
	Clock(int)
	{
	}
#endif
};

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cserver::Cserver()
{
	m_admin_port = m_new_admin_port = 6879;
	m_bind_before_connect = false;
	m_completes_dir = "Completes";
	m_end_mode = false;
	m_incompletes_dir = "Incompletes";
	m_local_app_data_dir = ".";
	m_log_peer_connect_failures = false;
	m_log_peer_connection_closures = false;
	m_log_peer_recv_failures = false;
	m_log_peer_send_failures = true;
	m_peer_limit = 0;
	m_peer_port = m_new_peer_port = 6881;
	m_public_ipa = 0;
	m_run = false;
	m_seeding_ratio = 0;
	m_torrent_limit = 0;
	m_torrents_dir = "Torrents";
	m_tracker_port = m_new_tracker_port = 2710;
	m_update_chokes_time = 0;
	m_update_send_quotas_time = time(NULL);
	m_update_states_time = 0;
	m_upload_rate = 0;
	m_upload_slots = 8;

#ifdef WIN32
	InitializeCriticalSection(&m_cs);
#endif
	srand(time(NULL));
#ifndef NDEBUG
	m_logger.open("/temp/bt_logger.txt");
#endif
}

Cserver::~Cserver()
{
#ifdef WIN32
	DeleteCriticalSection(&m_cs);
#endif
}

static string new_peer_id()
{
	string v;
	v = "XBT036--";
	v.resize(20);
	for (size_t i = 8; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
#ifndef NDEBUG
	v[6] = 'd';
#endif
	return v;
}

void Cserver::admin_port(int v)
{
	m_new_admin_port = max(0, v);
}

void Cserver::peer_port(int v)
{
	m_new_peer_port = max(0, v);
}

void Cserver::public_ipa(int v)
{
	m_public_ipa = v == INADDR_NONE ? 0 : v;
}

void Cserver::seeding_ratio(int v)
{
	m_seeding_ratio = v ? max(100, v) : 0;
}

void Cserver::tracker_port(int v)
{
	m_new_tracker_port = max(0, v);
}

void Cserver::upload_rate(int v)
{
	m_upload_rate = max(0, v);
}

void Cserver::upload_slots(int v)
{
	m_upload_slots = max(0, v);
}

int Cserver::run()
{
	m_admin_port = m_new_admin_port;
	m_peer_port = m_new_peer_port;
	m_tracker_port = m_new_tracker_port;
	Csocket l, la, lt;
	if (l.open(SOCK_STREAM) == INVALID_SOCKET
		|| la.open(SOCK_STREAM) == INVALID_SOCKET
		|| lt.open(SOCK_DGRAM) == INVALID_SOCKET)
		return alert(Calert(Calert::emerg, "Server", "socket failed" + Csocket::error2a(WSAGetLastError()))), 1;
	while (admin_port() < 0x10000 && la.bind(htonl(INADDR_LOOPBACK), htons(admin_port())) && WSAGetLastError() == WSAEADDRINUSE)
		m_admin_port++;
	while (peer_port() < 0x10000 && l.bind(htonl(INADDR_ANY), htons(peer_port())) && WSAGetLastError() == WSAEADDRINUSE)
		m_peer_port++;
	while (tracker_port() < 0x10000 && lt.bind(htonl(INADDR_ANY), htons(tracker_port())) && WSAGetLastError() == WSAEADDRINUSE)
		m_tracker_port++;
	mkpath(local_app_data_dir());
	if (l.listen() || la.listen())
		return alert(Calert(Calert::emerg, "Server", "listen failed" + Csocket::error2a(WSAGetLastError()))), 1;
	load_state(Cvirtual_binary(state_fname()));
	m_tracker_accounts.load(Cvirtual_binary(trackers_fname()));
#ifndef WIN32
	if (daemon(true, false))
		alert(Calert(Calert::error, "Server", "daemon failed" + n(errno)));
	ofstream(g_pid_fname) << getpid() << endl;
	struct sigaction act;
	act.sa_handler = sig_handler;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	if (sigaction(SIGTERM, &act, NULL))
		cerr << "sigaction failed" << endl;
#endif
	m_save_state_time = time(NULL);
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
	for (m_run = true; !g_sig_term && m_run; )
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
			}
		}
		if (m_new_tracker_port != m_tracker_port)
		{
			Csocket s;
			if (s.open(SOCK_DGRAM) != INVALID_SOCKET
				&& !s.bind(htonl(INADDR_ANY), htons(m_new_tracker_port)))
			{
				lt = s;
				m_tracker_port = m_new_tracker_port;
			}
		}
		update_send_quotas();
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		bool hash = true;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (hash && i->hash())
				hash = false;
		}
		int n = pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
		if (below_peer_limit())
		{
			FD_SET(l, &fd_read_set);
			n = max(n, static_cast<SOCKET>(l));
		}
		FD_SET(la, &fd_read_set);
		n = max(n, static_cast<SOCKET>(la));
		FD_SET(lt, &fd_read_set);
		n = max(n, static_cast<SOCKET>(lt));
		unlock();
		timeval tv;
		tv.tv_sec = hash ? 1 : 0;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
		{
			alert(Calert(Calert::error, "Server", "select failed: " + Csocket::error2a(WSAGetLastError())));
			break;
		}
		if (0)
		{
#ifdef WIN32
			static ofstream f("/temp/select log.txt");
			f << time(NULL);
			f << "\tR:";
			for (int i = 0; i < fd_read_set.fd_count; i++)
				f << ' ' << fd_read_set.fd_array[i];
			f << "\tW:";
			for (int i = 0; i < fd_write_set.fd_count; i++)
				f << ' ' << fd_write_set.fd_array[i];
			f << "\tE:";
			for (int i = 0; i < fd_except_set.fd_count; i++)
				f << ' ' << fd_except_set.fd_array[i];
			f << endl;
#endif
		}
		lock();
		if (FD_ISSET(l, &fd_read_set))
		{
			sockaddr_in a;
			while (1)
			{
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						alert(Calert(Calert::error, "Server", "accept failed: " + Csocket::error2a(WSAGetLastError())));
					break;
				}
				else
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed: " + Csocket::error2a(WSAGetLastError())));
					m_links.push_back(Cbt_link(this, a, s));
				}
			}
		}
		if (FD_ISSET(la, &fd_read_set))
		{
			sockaddr_in a;
			while (1)
			{
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(la, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						alert(Calert(Calert::error, "Server", "accept failed: " + Csocket::error2a(WSAGetLastError())));
					break;						
				}
				else
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed: " + Csocket::error2a(WSAGetLastError())));
					m_admins.push_back(Cbt_admin_link(this, a, s));
				}
			}
		}
		if (FD_ISSET(lt, &fd_read_set))
			m_udp_tracker.recv(lt);
		post_select(&fd_read_set, &fd_write_set, &fd_except_set);
		if (time(NULL) - m_update_chokes_time > 10)
			update_chokes();
		else if (time(NULL) - m_update_states_time > 15)
			update_states();
		else if (time(NULL) - m_save_state_time > 60)
			save_state(true).save(state_fname());
		unlock();
	}
	save_state(false).save(state_fname());
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->close();
	unlink(g_pid_fname);
	return 0;
}

int Cserver::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	int n = 0;
	{
		for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); i++)
		{
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	{
		bool hash = true;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (hash && i->hash())
				hash = false;
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	{
		for (t_links::iterator i = m_links.begin(); i != m_links.end(); i++)
		{
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	return n;
}

void Cserver::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); )
	{
		if (!i->post_select(fd_read_set, fd_write_set, fd_except_set) && *i)
			i++;
		else
			i = m_admins.erase(i);
	}
	for (t_links::iterator i = m_links.begin(); i != m_links.end(); )
	{
		if (!i->post_select(fd_read_set, fd_write_set, fd_except_set) && *i)
			i++;
		else
			i = m_links.erase(i);
	}
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->post_select(fd_read_set, fd_write_set, fd_except_set);
}

void Cserver::stop()
{
	m_run = false;
}

int Cserver::pre_file_dump(const string& id, int flags) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
			return i->pre_dump(flags);
	};
	return 0;
}

void Cserver::file_dump(Cstream_writer& w, const string& id, int flags) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
		{
			i->dump(w, flags);
			return;
		}
	}
}

int Cserver::pre_dump(int flags) const
{
	int size = 4;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		size += i->pre_dump(flags);
	return size;
}

void Cserver::dump(Cstream_writer& w, int flags) const
{
	w.write_int(4, m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->dump(w, flags);
}

void Cserver::insert_peer(const char* r, const sockaddr_in& a, const Csocket& s)
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == string(r + hs_info_hash, 20))
			i->insert_peer(r, a, s);
	}
}

void Cserver::lock()
{
#ifdef WIN32
	EnterCriticalSection(&m_cs);
#endif
}

void Cserver::unlock()
{
#ifdef WIN32
	LeaveCriticalSection(&m_cs);
#endif
}

Cvirtual_binary Cserver::get_file_status(const string& id, int flags)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_file_dump(id, flags)));
	file_dump(w, id, flags);
	assert(w.w() == d.data_end());
	return d;
}

Cvirtual_binary Cserver::get_status(int flags)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_dump(flags)));
	dump(w, flags);
	assert(w.w() == d.data_end());
	return d;
}

Cvirtual_binary Cserver::get_trackers()
{
	Clock l(m_cs);
	return m_tracker_accounts.dump();
}

void Cserver::set_trackers(const Cvirtual_binary& d)
{
	Clock l(m_cs);
	m_tracker_accounts.load(d);
	d.save(trackers_fname());
}

int Cserver::announce(const string& id)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->announce();
		return 0;
	}
	return 1;
}

int Cserver::file_priority(const string& id, int priority)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->priority(priority);
		return 0;
	}
	return 1;
}

int Cserver::file_state(const string& id, Cbt_file::t_state state)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->state(state);
		return 0;
	}
	return 1;
}

void Cserver::sub_file_priority(const string& file_id, const string& sub_file_id, int priority)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != file_id)
			continue;
		i->sub_file_priority(sub_file_id, priority);
		return;
	}
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
#ifdef WIN32
	while (!m_run)
		Sleep(100);
#endif
	Clock l(m_cs);
	Cbt_file f;
	f.m_server = this;
	if (f.torrent(info))
		return 1;
	mkpath(torrents_dir());
	info.save(torrents_dir() + '/' + f.m_name + ".torrent");
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == f.m_info_hash)
			return 2;
	}
	f.m_name = name.empty() ? incompletes_dir() + '/' + f.m_name : name;
	f.m_peer_id = new_peer_id();
	if (below_torrent_limit())
		f.open();
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
	string trackers = v.substr(a, b++ - a);
	a = v.find('/', b);
	if (a == string::npos)
		return 3;
	string info_hash = hex_decode(v.substr(b, a++ - b));
	b = v.find('/', a);
	if (b == string::npos)
		return 4;
	string peers = hex_decode(v.substr(b));
#ifdef WIN32
	while (!m_run)
		Sleep(100);
#endif
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != info_hash)
			continue;
		for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
			i->insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
		return 0;
	}
	if (info_hash.empty())
		return 5;
	Cbt_file f;
	f.m_server = this;
	for (a = 0; (b = trackers.find(',', a)) != string::npos; a = b + 1)
	{
		f.m_trackers.push_back(uri_decode(trackers.substr(a, b - a)));
	}
	for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
		f.insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
	f.m_info_hash = info_hash;
	f.m_peer_id = new_peer_id();
	m_files.push_front(f);
	save_state(true).save(state_fname());
	return 0;
}

int Cserver::close(const string& id, bool erase)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->close();
		if (erase)
			i->erase();
		m_files.erase(i);
		save_state(true).save(state_fname());
		return 0;
	}
	return 1;
}

void Cserver::load_state(const Cvirtual_binary& d)
{
	Clock l(m_cs);
	Cstream_reader r(d);
	if (d.size() < 8 || r.read_int(4) != g_state_version)
		return;
	for (int c_files = r.read_int(4); c_files--; )
	{
		Cbt_file f;
		f.m_server = this;
		f.load_state(r);
		f.m_peer_id = new_peer_id();
		m_files.push_front(f);
	}
	assert(r.r() == d.data_end());
}

Cvirtual_binary Cserver::save_state(bool intermediate)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	int cb_d = 8;
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
			cb_d += i->pre_save_state(intermediate);
	}
	Cstream_writer w(d.write_start(cb_d));
	w.write_int(4, g_state_version);
	w.write_int(4, m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->save_state(w, intermediate);
	assert(w.w() == d.data_end());
	m_save_state_time = time(NULL);
	return d;
}

string Cserver::state_fname() const
{
	return local_app_data_dir() + "/state.bin";
}

string Cserver::trackers_fname() const
{
	return local_app_data_dir() + "/trackers.bin";
}

void Cserver::alert(const Calert& v)
{
	m_alerts.push_back(v);
}

void Cserver::update_chokes()
{
	typedef multimap<int, Cbt_peer_link*, less<int> > t_links0;
	typedef vector<Cbt_peer_link*> t_links1;
	t_links0 links0;
	t_links1 links1;
	t_links1 links2;
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); j++)
		{
			if (j->m_state != 3)
				continue;
			if (i->state() != Cbt_file::s_running || !j->m_left)
				j->choked(true);
			else if (!m_upload_slots)
				j->choked(false);
			else if (j->m_down_counter.rate() > 256)
				links0.insert(t_links0::value_type(j->m_down_counter.rate(), &*j));
			else if (j->m_remote_interested)
				(j->m_local_interested ? links1 : links2).push_back(&*j);
		}
	}
	int slots_left = max(4, m_upload_slots);
	for (t_links0::iterator i = links0.begin(); i != links0.end(); i++)
	{
		if (slots_left)
		{
			if (i->second->m_remote_interested)
				slots_left--;
			i->second->choked(false);
		}
		else
			links1.push_back(&*i->second);
	}
	while (slots_left-- && !links1.empty())
	{
		int i = rand() % links1.size();
		links1[i]->choked(false);
		links1[i] = links1.back();
		links1.pop_back();
	}
	while (slots_left-- && !links2.empty())
	{
		int i = rand() % links2.size();
		links2[i]->choked(false);
		links2[i] = links2.back();
		links2.pop_back();
	}
	for (t_links1::const_iterator i = links1.begin(); i != links1.end(); i++)
		(*i)->choked(true);
	for (t_links1::const_iterator i = links2.begin(); i != links2.end(); i++)
		(*i)->choked(true);
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

void Cserver::update_states()
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end() && below_torrent_limit(); i++)
	{
		if (i->state() == Cbt_file::s_queued)
			i->open();
	}
}

string Cserver::completes_dir() const
{
	return m_completes_dir;
}

void Cserver::completes_dir(const string& v)
{
	m_completes_dir = v;
}

string Cserver::incompletes_dir() const
{
	return m_incompletes_dir;
}

void Cserver::incompletes_dir(const string& v)
{
	m_incompletes_dir = v;
}

string Cserver::local_app_data_dir() const
{
	return m_local_app_data_dir;
}

void Cserver::local_app_data_dir(const string& v)
{
	m_local_app_data_dir = v;
}

string Cserver::torrents_dir() const
{
	return m_torrents_dir;
}

void Cserver::torrents_dir(const string& v)
{
	m_torrents_dir = v;
}

bool Cserver::below_peer_limit() const
{
	if (!m_peer_limit)
		return true;
	int c = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		c += i->m_peers.size();
	return c < m_peer_limit;
}

bool Cserver::below_torrent_limit() const
{
	if (!m_torrent_limit)
		return true;
	int c = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		c += i->is_open();
	return c < m_torrent_limit;
}

void Cserver::sig_handler(int v)
{
	switch (v)
	{
	case SIGTERM:
		g_sig_term = true;
		break;
	}
}

Cbvalue Cserver::admin_request(const Cbvalue& s)
{
	Cbvalue d;
	string action = s.d(bts_action).s();
	if (action == bts_close_torrent)
		close(s.d(bts_hash).s());
	else if (action == bts_get_options)
	{
		d.d(bts_admin_port, admin_port());
		d.d(bts_peer_port, peer_port());
		d.d(bts_tracker_port, tracker_port());
		d.d(bts_upload_rate, upload_rate());
		d.d(bts_upload_slots, upload_slots());
		d.d(bts_seeding_ratio, seeding_ratio());
		d.d(bts_peer_limit, peer_limit());
		d.d(bts_torrent_limit, torrent_limit());
	}
	else if (action == bts_get_status)
	{
		Cbvalue files;
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			Cbvalue file;
			file.d(bts_complete, i->c_seeders());
			file.d(bts_incomplete, i->c_leechers());
			file.d(bts_left, i->m_left);
			file.d(bts_priority, i->priority());
			file.d(bts_size, i->mcb_f);
			file.d(bts_state, i->state());
			file.d(bts_total_downloaded, i->m_total_downloaded);
			file.d(bts_total_uploaded, i->m_total_uploaded);
			file.d(bts_down_rate, i->m_down_counter.rate());
			file.d(bts_up_rate, i->m_up_counter.rate());
			file.d(bts_name, i->m_name);
			files.d(i->m_info_hash, file);
		}
		d.d(bts_files, files);
	}
	else if (action == bts_open_torrent) 
		open(Cvirtual_binary(s.d(bts_torrent).s().c_str(), s.d(bts_torrent).s().size()), "");
	else if (action == bts_set_options)
	{
		if (s.d_has(bts_peer_port))
			peer_port(s.d(bts_peer_port).i());
		if (s.d_has(bts_tracker_port))
			tracker_port(s.d(bts_tracker_port).i());
		if (s.d_has(bts_upload_rate))
			upload_rate(s.d(bts_upload_rate).i());
		if (s.d_has(bts_upload_slots))
			upload_slots(s.d(bts_upload_slots).i());
		if (s.d_has(bts_seeding_ratio))
			seeding_ratio(s.d(bts_seeding_ratio).i());
		if (s.d_has(bts_peer_limit))
			peer_limit(s.d(bts_peer_limit).i());
		if (s.d_has(bts_torrent_limit))
			torrent_limit(s.d(bts_torrent_limit).i());
	}
	else if (action == bts_set_priority)
		file_priority(s.d(bts_hash).s(), s.d(bts_priority).i());
	else if (action == bts_set_state)
		file_state(s.d(bts_hash).s(), static_cast<Cbt_file::t_state>(s.d(bts_state).i()));
	return d;
}
