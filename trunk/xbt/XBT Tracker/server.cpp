#include "stdafx.h"
#include "server.h"

#include <signal.h>
#include "bt_misc.h"
#include "bt_strings.h"
#include "transaction.h"

static volatile bool g_sig_hup = false;
static volatile bool g_sig_term = false;

class Cannounce_output_http_compact: public Cserver::Cannounce_output
{
public:
	void peer(int h, const Cserver::t_peer& peer)
	{
		m_peers.append(reinterpret_cast<const char*>(&h), 4);
		m_peers.append(reinterpret_cast<const char*>(&peer.port), 2);
	}

	Cbvalue v() const
	{
		Cbvalue v;
		if (m_complete)
			v.d(bts_complete, m_complete);
		if (m_incomplete)
			v.d(bts_incomplete, m_incomplete);
		v.d(bts_interval, m_interval);
		v.d(bts_min_interval, m_interval);
		v.d(bts_peers, m_peers);
		return v;
	}
private:
	string m_peers;
};

class Cannounce_output_http: public Cserver::Cannounce_output
{
public:
	void no_peer_id(bool v)
	{
		m_no_peer_id = v;
	}

	void peer(int h, const Cserver::t_peer& p)
	{
		Cbvalue peer;
		if (!m_no_peer_id)
			peer.d(bts_peer_id, p.peer_id);
		peer.d(bts_ipa, Csocket::inet_ntoa(h));
		peer.d(bts_port, ntohs(p.port));
		m_peers.l(peer);
	}

	Cbvalue v() const
	{
		Cbvalue v;
		if (m_complete)
			v.d(bts_complete, m_complete);
		if (m_incomplete)
			v.d(bts_incomplete, m_incomplete);
		v.d(bts_interval, m_interval);
		v.d(bts_min_interval, m_interval);
		v.d(bts_peers, m_peers);
		return v;
	}

	Cannounce_output_http():
		m_peers(Cbvalue::vt_list)
	{
	}
private:
	bool m_no_peer_id;
	Cbvalue m_peers;
};

Cserver::Cserver(Cdatabase& database, const string& table_prefix, bool use_sql):
	m_database(database)
{
	m_fid_end = 0;

	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
	m_table_prefix = table_prefix;
	m_time = ::time(NULL);
	m_use_sql = use_sql;
}

int Cserver::run()
{
	read_config();
	test_sql();
	if (m_epoll.create(1 << 10) == -1)
	{
		cerr << "epoll_create failed" << endl;
		return 1;
	}
	t_tcp_sockets lt;
	t_udp_sockets lu;
	for (Cconfig::t_listen_ipas::const_iterator j = m_config.m_listen_ipas.begin(); j != m_config.m_listen_ipas.end(); j++)
	{
		for (Cconfig::t_listen_ports::const_iterator i = m_config.m_listen_ports.begin(); i != m_config.m_listen_ports.end(); i++)
		{
			Csocket l;
			if (l.open(SOCK_STREAM) == INVALID_SOCKET)
				cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(*j, htons(*i)))
				cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (l.listen())
				cerr << "listen failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else
			{
				lt.push_back(Ctcp_listen_socket(this, l));
				if (m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &lt.back()))
					return 1;
				continue;
			}
			return 1;
		}
		for (Cconfig::t_listen_ports::const_iterator i = m_config.m_listen_ports.begin(); i != m_config.m_listen_ports.end(); i++)
		{
			Csocket l;
			if (l.open(SOCK_DGRAM) == INVALID_SOCKET)
				cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(*j, htons(*i)))
				cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else
			{
				lu.push_back(Cudp_listen_socket(this, l));
				if (m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &lu.back()))
					return 1;
				continue;
			}
			return 1;
		}
	}
#ifndef WIN32
#if 1
	if (m_config.m_daemon && daemon(true, false))
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
	ofstream(m_config.m_pid_file.c_str()) << getpid() << endl;
	struct sigaction act;
	act.sa_handler = sig_handler;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	if (sigaction(SIGHUP, &act, NULL)
		|| sigaction(SIGTERM, &act, NULL))
		cerr << "sigaction failed" << endl;
	act.sa_handler = SIG_IGN;
	if (sigaction(SIGPIPE, &act, NULL))
		cerr << "sigaction failed" << endl;
#endif
	clean_up();
	read_db_deny_from_hosts();
	read_db_files();
	read_db_ipas();
	read_db_users();
	write_db_files();
	write_db_users();
#ifdef EPOLL
	const int c_events = 64;

	epoll_event events[c_events];
#else
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
#endif
	while (!g_sig_term)
	{
#ifdef EPOLL
		int r = m_epoll.wait(events, c_events, 1000);
		if (r == -1)
			cerr << "epoll_wait failed: " << errno << endl;
		else
		{
			int prev_time = m_time;
			m_time = ::time(NULL);
			for (int i = 0; i < r; i++)
			{
				reinterpret_cast<Cclient*>(events[i].data.ptr)->process_events(events[i].events);
			}
			if (m_time == prev_time)
				continue;
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->run())
					i = m_connections.erase(i);
				else
					i++;
			}
			for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); )
			{
				if (i->run())
					i = m_peer_links.erase(i);
				else
					i++;
			}
		}
#else
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		int n = 0;
		{
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); i++)
			{
				int z = i->pre_select(&fd_read_set, &fd_write_set);
				n = max(n, z);
			}
		}
		{
			for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); i++)
			{
				int z = i->pre_select(&fd_write_set, &fd_except_set);
				n = max(n, z);
			}
		}
		for (t_tcp_sockets::iterator i = lt.begin(); i != lt.end(); i++)
		{
			FD_SET(i->s(), &fd_read_set);
			n = max(n, static_cast<SOCKET>(i->s()));
		}
		for (t_udp_sockets::iterator i = lu.begin(); i != lu.end(); i++)
		{
			FD_SET(i->s(), &fd_read_set);
			n = max(n, static_cast<SOCKET>(i->s()));
		}
		timeval tv;
		tv.tv_sec = 1;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		else
		{
			m_time = ::time(NULL);
			for (t_tcp_sockets::iterator i = lt.begin(); i != lt.end(); i++)
			{
				if (FD_ISSET(i->s(), &fd_read_set))
					accept(i->s());
			}
			for (t_udp_sockets::iterator i = lu.begin(); i != lu.end(); i++)
			{
				if (FD_ISSET(i->s(), &fd_read_set))
					Ctransaction(*this, i->s()).recv();
			}
			{
				for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
				{
					if (i->post_select(&fd_read_set, &fd_write_set))
						i = m_connections.erase(i);
					else
						i++;
				}
			}
			{
				for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); )
				{
					if (i->post_select(&fd_write_set, &fd_except_set))
						i = m_peer_links.erase(i);
					else
						i++;
				}
			}
		}
#endif
		if (time() - m_read_config_time > m_config.m_read_config_interval)
			read_config();
		else if (time() - m_clean_up_time > m_config.m_clean_up_interval)
			clean_up();
		else if (time() - m_read_db_deny_from_hosts_time > m_config.m_read_db_interval)
			read_db_deny_from_hosts();
		else if (time() - m_read_db_files_time > m_config.m_read_db_interval)
			read_db_files();
		else if (time() - m_read_db_ipas_time > m_config.m_read_db_interval)
			read_db_ipas();
		else if (time() - m_read_db_users_time > m_config.m_read_db_interval)
			read_db_users();
		else if (m_config.m_write_db_interval && time() - m_write_db_files_time > m_config.m_write_db_interval)
			write_db_files();
		else if (m_config.m_write_db_interval && time() - m_write_db_users_time > m_config.m_write_db_interval)
			write_db_users();
	}
	write_db_files();
	write_db_users();
	unlink(m_config.m_pid_file.c_str());
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
			t_deny_from_hosts::const_iterator i = m_deny_from_hosts.lower_bound(ntohl(a.sin_addr.s_addr));
			if (i != m_deny_from_hosts.begin())
			{		
				i--;
				if (ntohl(a.sin_addr.s_addr) <= i->second.end)
					continue;
			}
			if (s.blocking(false))
				cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#ifdef TCP_CORK
			if (s.setsockopt(IPPROTO_TCP, TCP_CORK, true))
				cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#endif
			m_connections.push_back(Cconnection(this, s, a, m_config.m_log_access));
			m_epoll.ctl(EPOLL_CTL_ADD, s, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_connections.back());
		}
	}
}

string Cserver::insert_peer(const Ctracker_input& v, bool listen_check, bool udp, t_user* user)
{
	if (m_use_sql && m_config.m_log_announce)
	{
		Csql_query q(m_database, "(?,?,?,?,?,?,?,?,?,?),");
		q.p(ntohl(v.m_ipa));
		q.p(ntohs(v.m_port));
		q.p(v.m_event);
		q.pe(v.m_info_hash);
		q.pe(v.m_peer_id);
		q.p(v.m_downloaded);
		q.p(v.m_left);
		q.p(v.m_uploaded);
		q.p(user ? user->uid : 0);
		q.p(time());
		m_announce_log_buffer += q.read();
	}
	if (!m_config.m_offline_message.empty())
		return m_config.m_offline_message;
	if (!m_config.m_auto_register && m_files.find(v.m_info_hash) == m_files.end())
		return bts_unregistered_torrent;
	t_file& file = m_files[v.m_info_hash];
	if (v.m_left && user && user->fid_end && file.fid > user->fid_end)
		return bts_wait_time;
	t_peers::iterator i = file.peers.find(v.m_ipa);
	if (i != file.peers.end())
	{
		(i->second.left ? file.leechers : file.seeders)--;
		t_user* old_user = i->second.uid ? find_user_by_uid(i->second.uid) : NULL;
		if (old_user)
			(i->second.left ? old_user->incompletes : old_user->completes)--;
	}
	else if (v.m_left && user && user->torrents_limit && user->incompletes >= user->torrents_limit)
		return bts_torrents_limit_reached;
	else if (v.m_left && user && user->peers_limit)
	{
		int c = 0;
		for (t_peers::const_iterator j = file.peers.begin(); j != file.peers.end(); j++)
			c += j->second.left && j->second.uid == user->uid;
		if (c >= user->peers_limit)
			return bts_peers_limit_reached;
	}
	if (m_use_sql && user)
	{
		__int64 downloaded = 0;
		__int64 uploaded = 0;
		if (i != file.peers.end()
			&& i->second.peer_id == v.m_peer_id
			&& v.m_downloaded >= i->second.downloaded
			&& v.m_uploaded >= i->second.uploaded)
		{
			downloaded = v.m_downloaded - i->second.downloaded;
			uploaded = v.m_uploaded - i->second.uploaded;
		}
		Csql_query q(m_database, "(?,1,?,?,?,?,?,?),");
		q.p(v.m_event != Ctracker_input::e_stopped);
		q.p(v.m_event == Ctracker_input::e_completed);
		q.p(downloaded);
		q.p(v.m_left);
		q.p(uploaded);
		q.pe(v.m_info_hash);
		q.p(user->uid);
		m_files_users_updates_buffer += q.read();
		if (downloaded || uploaded)
		{
			Csql_query q(m_database, "(?,?,?),");
			q.p(downloaded);
			q.p(uploaded);
			q.p(user->uid);
			m_users_updates_buffer += q.read();
		}
	}
	if (v.m_event == Ctracker_input::e_stopped)
		file.peers.erase(v.m_ipa);
	else
	{
		t_peer& peer = file.peers[v.m_ipa];
		peer.downloaded = v.m_downloaded;
		peer.left = v.m_left;
		peer.peer_id = v.m_peer_id;
		peer.port = v.m_port;
		peer.uid = user ? user->uid : 0;
		peer.uploaded = v.m_uploaded;
		(peer.left ? file.leechers : file.seeders)++;
		if (user)
			(peer.left ? user->incompletes : user->completes)++;

		if (!m_config.m_listen_check || !listen_check)
			peer.listening = true;
		else if (!peer.listening && time() - peer.mtime > 7200)
		{
			Cpeer_link peer_link(v.m_ipa, v.m_port, this, v.m_info_hash, v.m_ipa);
			if (peer_link.s() != INVALID_SOCKET)
			{
				m_peer_links.push_back(peer_link);
				m_epoll.ctl(EPOLL_CTL_ADD, peer_link.s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_peer_links.back());
			}
		}
		peer.mtime = time();
	}
	if (udp)
	{
		m_stats.announced_udp++;
	}
	else if (v.m_compact)
	{
		m_stats.announced_http_compact++;
	}
	else if (v.m_no_peer_id)
	{
		m_stats.announced_http_no_peer_id++;
	}
	else
	{
		m_stats.announced_http++;
	}
	file.dirty = true;
	return "";
}

void Cserver::update_peer(const string& file_id, int peer_id, bool listening)
{
	t_files::iterator i = m_files.find(file_id);
	if (i == m_files.end())
		return;
	t_peers::iterator j = i->second.peers.find(peer_id);
	if (j == i->second.peers.end())
		return;
	j->second.listening = listening;
}

void Cserver::t_file::select_peers(const Ctracker_input& ti, Cannounce_output& o) const
{
	typedef vector<t_peers::const_iterator> t_candidates;

	o.complete(seeders);
	o.incomplete(leechers);
	t_candidates candidates;
	for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
	{
		if ((ti.m_left || i->second.left) && i->second.listening)
			candidates.push_back(i);
	}
	int c = ti.m_num_want < 0 ? 50 : min(ti.m_num_want, 50);
	if (candidates.size() > c)
	{
		while (c--)
		{
			int i = rand() % candidates.size();
			o.peer(candidates[i]->first, candidates[i]->second);
			candidates[i] = candidates.back();
			candidates.pop_back();
		}
	}
	else
	{
		for (t_candidates::const_iterator i = candidates.begin(); i != candidates.end(); i++)
			o.peer((*i)->first, (*i)->second);
	}
}

Cbvalue Cserver::select_peers(const Ctracker_input& ti, const t_user* user)
{
	t_files::const_iterator i = m_files.find(ti.m_info_hash);
	if (i == m_files.end())
		return Cbvalue();
	if (ti.m_compact)
	{
		Cannounce_output_http_compact o;
		o.interval(m_config.m_announce_interval);
		i->second.select_peers(ti, o);
		return o.v();
	}
	Cannounce_output_http o;
	o.interval(m_config.m_announce_interval);
	o.no_peer_id(ti.m_no_peer_id);
	i->second.select_peers(ti, o);
	return o.v();
}

void Cserver::t_file::clean_up(time_t t, Cserver& server)
{
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (i->second.mtime < t)
		{
			(i->second.left ? leechers : seeders)--;
			t_user* user = i->second.uid ? server.find_user_by_uid(i->second.uid) : NULL;
			if (user)
				(i->second.left ? user->incompletes : user->completes)--;
			peers.erase(i++);
			dirty = true;
		}
		else
			i++;
	}
}

void Cserver::clean_up()
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->second.clean_up(time() - static_cast<int>(1.5 * m_config.m_announce_interval), *this);
	m_clean_up_time = time();
}

Cbvalue Cserver::t_file::scrape() const
{
	Cbvalue v;
	v.d(bts_complete, seeders);
	v.d(bts_downloaded, completed);
	v.d(bts_incomplete, leechers);
	return v;
}

Cbvalue Cserver::scrape(const Ctracker_input& ti)
{
	if (m_use_sql && m_config.m_log_scrape)
	{
		Csql_query q(m_database, "(?,?,?),");
		q.p(ntohl(ti.m_ipa));
		if (ti.m_info_hash.empty())
			q.p("NULL");
		else
			q.pe(ti.m_info_hash);
		q.p(time());
		m_scrape_log_buffer += q.read();
	}
	Cbvalue v;
	Cbvalue files(Cbvalue::vt_dictionary);
	if (ti.m_info_hash.empty())
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (i->second.leechers || i->second.seeders)
				files.d(i->first, i->second.scrape());
		}
	}
	else
	{
		t_files::iterator i = m_files.find(ti.m_info_hash);
		if (i != m_files.end())
		{
			m_stats.scraped_http++;
			i->second.dirty = true;
			files.d(i->first, i->second.scrape());
		}
	}
	v.d(bts_files, files);
	if (m_config.m_scrape_interval)
		v.d(bts_flags, Cbvalue().d(bts_min_request_interval, m_config.m_scrape_interval));
	return v;
}

void Cserver::read_db_deny_from_hosts()
{
	if (!m_use_sql)
		return;
	try
	{
		Csql_query q(m_database, "select begin, end from ?");
		q.p(table_name(table_deny_from_hosts));
		Csql_result result = q.execute();
		for (t_deny_from_hosts::iterator i = m_deny_from_hosts.begin(); i != m_deny_from_hosts.end(); i++)
			i->second.marked = true;
		for (Csql_row row; row = result.fetch_row(); )
		{
			t_deny_from_host& deny_from_host = m_deny_from_hosts[row.f_int(0)];
			deny_from_host.marked = false;
			deny_from_host.end = row.f_int(1);
		}
		for (t_deny_from_hosts::iterator i = m_deny_from_hosts.begin(); i != m_deny_from_hosts.end(); )
		{
			if (i->second.marked)
				m_deny_from_hosts.erase(i++);
			else
				i++;
		}
	}
	catch (Cxcc_error)
	{
	}
	m_read_db_deny_from_hosts_time = time();
}

void Cserver::read_db_files()
{
	m_read_db_files_time = time();
	if (m_use_sql)
		read_db_files_sql();
	else if (!m_config.m_auto_register)
	{
		set<string> new_files;
		ifstream is("xbt_files.txt");
		string s;
		while (getline(is, s))
		{
			s = hex_decode(s);
			if (s.size() != 20)
				continue;
			m_files[s];
			new_files.insert(s);
		}
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); )
		{
			if (new_files.find(i->first) == new_files.end())
				m_files.erase(i++);
			else
				i++;
		}
	}
}

void Cserver::read_db_files_sql()
{
	try
	{
		Csql_query q(m_database);
		if (!m_config.m_auto_register)
		{
			q = "select info_hash, ? from ? where flags & 1";
			q.p(column_name(column_files_fid));
			q.p(table_name(table_files));
			Csql_result result = q.execute();
			for (Csql_row row; row = result.fetch_row(); )
			{
				if (row.size(0) != 20)
					continue;
				m_files.erase(row.f(0));
				q = "delete from ? where ? = ?";
				q.p(table_name(table_files));
				q.p(column_name(column_files_fid));
				q.p(row.f_int(1));
				q.execute();
			}

		}
		if (m_files.empty())
			m_database.query("update " + table_name(table_files) + " set " + column_name(column_files_leechers) + " = 0, " + column_name(column_files_seeders) + " = 0");
		else if (m_config.m_auto_register)
			return;
		q = "select info_hash, " + column_name(column_files_completed) + ", ?"
			" from ? where ? >= ?";
		q.p(column_name(column_files_fid));
		q.p(table_name(table_files));
		q.p(column_name(column_files_fid));
		q.p(m_fid_end);
		Csql_result result = q.execute();
		for (Csql_row row; row = result.fetch_row(); )
		{
			m_fid_end = max(m_fid_end, static_cast<int>(row.f_int(2, 0)) + 1);
			if (row.size(0) != 20 || m_files.find(row.f(0)) != m_files.end())
				continue;
			t_file& file = m_files[row.f(0)];
			if (file.fid)
				continue;
			file.completed = row.f_int(1, 0);
			file.dirty = false;
			file.fid = row.f_int(2, 0);
		}
	}
	catch (Cxcc_error)
	{
	}
}

void Cserver::read_db_ipas()
{
	if (!m_use_sql)
		return;
	try
	{
		Csql_query q(m_database, "select ipa, uid from ?");
		q.p(table_name(table_ipas));
		Csql_result result = q.execute();
		m_ipas.clear();
		for (Csql_row row; row = result.fetch_row(); )
			m_ipas[row.f_int(0)] = row.f_int(1);
	}
	catch (Cxcc_error)
	{
	}
	m_read_db_ipas_time = time();
}

void Cserver::read_db_users()
{
	if (!m_use_sql)
		return;
	try
	{
		Csql_query q(m_database, "select ?, name, pass, torrent_pass, fid_end, torrents_limit, peers_limit, torrent_pass_secret from ?");
		q.p(column_name(column_users_uid));
		q.p(table_name(table_users));
		Csql_result result = q.execute();
		for (t_users::iterator i = m_users.begin(); i != m_users.end(); i++)
			i->second.marked = true;
		m_users_names.clear();
		m_users_torrent_passes.clear();
		for (Csql_row row; row = result.fetch_row(); )
		{
			t_user& user = m_users[row.f_int(0)];
			user.marked = false;
			user.uid = row.f_int(0);
			user.fid_end = row.f_int(4);
			user.pass.assign(row.f(2));
			user.torrents_limit = row.f_int(5);
			user.peers_limit = row.f_int(6);
			user.torrent_pass_secret = row.f_int(7);
			if (row.size(1))
				m_users_names[row.f(1)] = &user;
			if (row.size(3))
				m_users_torrent_passes[row.f(3)] = &user;
		}
		for (t_users::iterator i = m_users.begin(); i != m_users.end(); )
		{
			if (i->second.marked)
				m_users.erase(i++);
			else
				i++;
		}
	}
	catch (Cxcc_error)
	{
	}
	m_read_db_users_time = time();
}

void Cserver::write_db_files()
{
	if (!m_use_sql)
		return;
	try
	{
		string buffer;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			t_file& file = i->second;
			if (!file.dirty)
				continue;
			Csql_query q(m_database);
			if (!file.fid)
			{
				q = "insert into ? (info_hash, ctime) values (?, NULL)";
				q.p(table_name(table_files));
				q.pe(i->first);
				q.execute();
				file.fid = m_database.insert_id();
			}
			q = "(?,?,?,?),";
			q.p(file.leechers);
			q.p(file.seeders);
			q.p(file.completed);
			q.p(file.fid);
			buffer += q.read();
			file.dirty = false;
		}
		if (!buffer.empty())
		{
			buffer.erase(buffer.size() - 1);
			m_database.query("insert into " + table_name(table_files) + " (" + column_name(column_files_leechers) + ", " + column_name(column_files_seeders) + ", " + column_name(column_files_completed) + ", started, stopped, announced_http, announced_http_compact, announced_http_no_peer_id, announced_udp, scraped_http, scraped_udp, " + column_name(column_files_fid) + ") values " 
				+ buffer
				+ " on duplicate key update"
				+ "  " + column_name(column_files_leechers) + " = values(" + column_name(column_files_leechers) + "),"
				+ "  " + column_name(column_files_seeders) + " = values(" + column_name(column_files_seeders) + "),"
				+ "  " + column_name(column_files_completed) + " = values(" + column_name(column_files_completed) + ")");
		}
	}
	catch (Cxcc_error)
	{
	}
	if (!m_announce_log_buffer.empty())
	{
		try
		{
			m_announce_log_buffer.erase(m_announce_log_buffer.size() - 1);
			m_database.query("insert delayed into " + table_name(table_announce_log) + " (ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values " + m_announce_log_buffer);
		}
		catch (Cxcc_error)
		{
		}
		m_announce_log_buffer.erase();
	}
	if (!m_scrape_log_buffer.empty())
	{
		try
		{
			m_scrape_log_buffer.erase(m_scrape_log_buffer.size() - 1);
			m_database.query("insert delayed into " + table_name(table_scrape_log) + " (ipa, info_hash, mtime) values " + m_scrape_log_buffer);
		}
		catch (Cxcc_error)
		{
		}
		m_scrape_log_buffer.erase();
	}
	m_write_db_files_time = time();
}

void Cserver::write_db_users()
{
	if (!m_use_sql)
		return;
	if (!m_files_users_updates_buffer.empty())
	{
		m_files_users_updates_buffer.erase(m_files_users_updates_buffer.size() - 1);
		try
		{
			m_database.query("insert into " + table_name(table_files_users) + " (active, announced, completed, downloaded, `left`, uploaded, info_hash, uid) values "
				+ m_files_users_updates_buffer
				+ " on duplicate key update"
				+ "  active = values(active),"
				+ "  announced = announced + values(announced),"
				+ "  completed = completed + values(completed),"
				+ "  downloaded = downloaded + values(downloaded),"
				+ "  `left` = values(`left`),"
				+ "  uploaded = uploaded + values(uploaded)");
		}
		catch (Cxcc_error)
		{
		}
		m_files_users_updates_buffer.erase();
	}
	if (!m_users_updates_buffer.empty())
	{
		m_users_updates_buffer.erase(m_users_updates_buffer.size() - 1);
		try
		{
			m_database.query("insert into " + table_name(table_users) + " (downloaded, uploaded, " + column_name(column_users_uid) + ") values "
				+ m_users_updates_buffer
				+ " on duplicate key update"
				+ "  downloaded = downloaded + values(downloaded),"
				+ "  uploaded = uploaded + values(uploaded)");
		}
		catch (Cxcc_error)
		{
		}
		m_users_updates_buffer.erase();
	}
	m_write_db_users_time = time();
}

void Cserver::read_config()
{
	if (m_use_sql)
	{
		try
		{
			Csql_result result = m_database.query("select name, value from " + table_name(table_config) + " where value is not null");
			Cconfig config;
			for (Csql_row row; row = result.fetch_row(); )
				config.set(row.f(0), row.f(1));
			m_config = config;
		}
		catch (Cxcc_error)
		{
		}
	}
	else 
	{
		ifstream is("xbt_tracker.conf");
		if (is)
		{
			Cconfig config;
			string s;
			while (getline(is, s))
			{
				int i = s.find('=');
				if (i == string::npos)
					continue;
				config.set(s.substr(0, i), s.substr(i + 1));
			}
			m_config = config;
		}
	}
	if (m_config.m_listen_ipas.empty())
		m_config.m_listen_ipas.insert(htonl(INADDR_ANY));
	if (m_config.m_listen_ports.empty())
		m_config.m_listen_ports.insert(2710);
	m_read_config_time = time();
}

string Cserver::t_file::debug() const
{
	string page;
	for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
	{
		page += "<tr><td>" + Csocket::inet_ntoa(i->first)
			+ "<td align=right>" + n(ntohs(i->second.port))
			+ "<td>" + (i->second.listening ? '*' : ' ')
			+ "<td align=right>" + n(i->second.left)
			+ "<td align=right>" + n(::time(NULL) - i->second.mtime)
			+ "<td>" + hex_encode(i->second.peer_id);
	}
	return page;
}

string Cserver::debug(const Ctracker_input& ti) const
{
	string page;
	page += "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	int leechers = 0;
	int seeders = 0;
	int torrents = 0;
	page += "<table>";
	if (ti.m_info_hash.empty())
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (!i->second.leechers && !i->second.seeders)
				continue;
			leechers += i->second.leechers;
			seeders += i->second.seeders;
			torrents++;
			page += "<tr><td align=right>" + n(i->second.fid)
				+ "<td><a href=\"?info_hash=" + uri_encode(i->first) + "\">" + hex_encode(i->first) + "</a>"
				+ "<td>" + (i->second.dirty ? '*' : ' ')
				+ "<td align=right>" + n(i->second.leechers)
				+ "<td align=right>" + n(i->second.seeders);
		}
	}
	else
	{
		t_files::const_iterator i = m_files.find(ti.m_info_hash);
		if (i != m_files.end())
			page += i->second.debug();
	}
	page += "</table>";
	return page;
}

string Cserver::statistics() const
{
	string page;
	page += "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	int leechers = 0;
	int seeders = 0;
	int torrents = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (!i->second.leechers && !i->second.seeders)
			continue;
		leechers += i->second.leechers;
		seeders += i->second.seeders;
		torrents++;
	}
	time_t t = time();
	page += "<table><tr><td>leechers<td align=right>" + n(leechers)
		+ "<tr><td>seeders<td align=right>" + n(seeders)
		+ "<tr><td>peers<td align=right>" + n(leechers + seeders)
		+ "<tr><td>torrents<td align=right>" + n(torrents)
		+ "<tr><td>"
		+ "<tr><td>announced<td align=right>" + n(m_stats.announced());
	if (m_stats.announced())
	{
		page += "<tr><td>announced http <td align=right>" + n(m_stats.announced_http) + "<td align=right>" + n(static_cast<__int64>(m_stats.announced_http) * 100 / m_stats.announced()) + " %"
			+ "<tr><td>announced http compact<td align=right>" + n(m_stats.announced_http_compact) + "<td align=right>" + n(static_cast<__int64>(m_stats.announced_http_compact) * 100 / m_stats.announced()) + " %"
			+ "<tr><td>announced http no peer id<td align=right>" + n(m_stats.announced_http_no_peer_id) + "<td align=right>" + n(static_cast<__int64>(m_stats.announced_http_no_peer_id) * 100 / m_stats.announced()) + " %"
			+ "<tr><td>announced udp<td align=right>" + n(m_stats.announced_udp) + "<td align=right>" + n(static_cast<__int64>(m_stats.announced_udp) * 100 / m_stats.announced()) + " %";
	}
	page += "<tr><td>scraped<td align=right>" + n(m_stats.scraped());
	if (m_stats.scraped())
	{
		page += "<tr><td>scraped http<td align=right>" + n(m_stats.scraped_http) + "<td align=right>" + n(static_cast<__int64>(m_stats.scraped_http) * 100 / m_stats.scraped()) + " %"
			+ "<tr><td>scraped udp<td align=right>" + n(m_stats.scraped_udp) + "<td align=right>" + n(static_cast<__int64>(m_stats.scraped_udp) * 100 / m_stats.scraped()) + " %";
	}
	page += string("<tr><td>")
		+ "<tr><td>up time<td align=right>" + duration2a(time() - m_stats.start_time)
		+ "<tr><td>"
		+ "<tr><td>anonymous connect<td align=right>" + n(m_config.m_anonymous_connect)
		+ "<tr><td>anonymous announce<td align=right>" + n(m_config.m_anonymous_announce)
		+ "<tr><td>anonymous scrape<td align=right>" + n(m_config.m_anonymous_scrape)
		+ "<tr><td>auto register<td align=right>" + n(m_config.m_auto_register)
		+ "<tr><td>listen check<td align=right>" + n(m_config.m_listen_check)
		+ "<tr><td>read config time<td align=right>" + n(t - m_read_config_time) + " / " + n(m_config.m_read_config_interval)
		+ "<tr><td>clean up time<td align=right>" + n(t - m_clean_up_time) + " / " + n(m_config.m_clean_up_interval)
		+ "<tr><td>read db files time<td align=right>" + n(t - m_read_db_files_time) + " / " + n(m_config.m_read_db_interval);
	if (m_use_sql)
	{
		page += "<tr><td>read db ipas time<td align=right>" + n(t - m_read_db_ipas_time) + " / " + n(m_config.m_read_db_interval)
			+ "<tr><td>read db users time<td align=right>" + n(t - m_read_db_users_time) + " / " + n(m_config.m_read_db_interval)
			+ "<tr><td>write db files time<td align=right>" + n(t - m_write_db_files_time) + " / " + n(m_config.m_write_db_interval)
			+ "<tr><td>write db users time<td align=right>" + n(t - m_write_db_users_time) + " / " + n(m_config.m_write_db_interval);
	}
	page += "</table>";
	return page;
}

Cserver::t_user* Cserver::find_user_by_name(const string& v)
{
	t_users_names::const_iterator i = m_users_names.find(v);
	return i == m_users_names.end() ? NULL : i->second;
}

Cserver::t_user* Cserver::find_user_by_ipa(int v)
{
	t_ipas::const_iterator i = m_ipas.find(v);
	return i == m_ipas.end() ? NULL : find_user_by_uid(i->second);
}

Cserver::t_user* Cserver::find_user_by_torrent_pass(const string& v)
{
	t_users_torrent_passes::const_iterator i = m_users_torrent_passes.find(v);
	return i == m_users_torrent_passes.end() ? NULL : i->second;
}

Cserver::t_user* Cserver::find_user_by_uid(int v)
{
	t_users::iterator i = m_users.find(v);
	return i == m_users.end() ? NULL : &i->second;
}

void Cserver::sig_handler(int v)
{
	switch (v)
	{
#ifndef WIN32
	case SIGHUP:
		g_sig_hup = true;
		break;
#endif
	case SIGTERM:
		g_sig_term = true;
		break;
	}
}

void Cserver::term()
{
	g_sig_term = true;
}

string Cserver::column_name(int v) const
{
	switch (v)
	{
	case column_files_completed:
		return m_config.m_column_files_completed.empty() ? "completed" : m_config.m_column_files_completed;
	case column_files_leechers:
		return m_config.m_column_files_leechers.empty() ? "leechers" : m_config.m_column_files_leechers;
	case column_files_seeders:
		return m_config.m_column_files_seeders.empty() ? "seeders" : m_config.m_column_files_seeders;
	case column_files_fid:
		return m_config.m_column_files_fid.empty() ? "fid" : m_config.m_column_files_fid;
	case column_users_uid:
		return m_config.m_column_users_uid.empty() ? "uid" : m_config.m_column_users_uid;
	}
	assert(false);
	return "";
}

string Cserver::table_name(int v) const
{
	switch (v)
	{
	case table_announce_log:
		return m_config.m_table_announce_log.empty() ? m_table_prefix + "announce_log" : m_config.m_table_announce_log;
	case table_config:
		return m_table_prefix + "config";
	case table_deny_from_hosts:
		return m_config.m_table_deny_from_hosts.empty() ? m_table_prefix + "deny_from_hosts" : m_config.m_table_deny_from_hosts;
	case table_files:
		return m_config.m_table_files.empty() ? m_table_prefix + "files" : m_config.m_table_files;
	case table_files_updates:
		return m_config.m_table_files_updates.empty() ? m_table_prefix + "files_updates" : m_config.m_table_files_updates;
	case table_files_users:
		return m_config.m_table_files_users.empty() ? m_table_prefix + "files_users" : m_config.m_table_files_users;
	case table_ipas:
		return m_config.m_table_ipas.empty() ? m_table_prefix + "ipas" : m_config.m_table_ipas;
	case table_scrape_log:
		return m_config.m_table_scrape_log.empty() ? m_table_prefix + "scrape_log" : m_config.m_table_scrape_log;
	case table_users:
		return m_config.m_table_users.empty() ? m_table_prefix + "users" : m_config.m_table_users;
	}
	assert(false);
	return "";
}

int Cserver::test_sql()
{
	if (!m_use_sql)
		return 0;
	try
	{
		m_database.query("select id, ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime from " + table_name(table_announce_log) + " where 0 = 1");
		m_database.query("select name, value from " + table_name(table_config) + " where 0 = 1");
		m_database.query("select begin, end from " + table_name(table_deny_from_hosts) + " where 0 = 1");
		m_database.query("select " + column_name(column_files_fid) + ", info_hash, " + column_name(column_files_leechers) + ", " + column_name(column_files_seeders) + ", flags, mtime, ctime from " + table_name(table_files) + " where 0 = 1");
		m_database.query("select fid, uid, active, announced, completed, downloaded, `left`, uploaded from " + table_name(table_files_users) + " where 0 = 1");
		m_database.query("select ipa, uid, mtime from " + table_name(table_ipas) + " where 0 = 1");
		m_database.query("select id, ipa, info_hash, uid, mtime from " + table_name(table_scrape_log) + " where 0 = 1");
		m_database.query("select " + column_name(column_users_uid) + ", name, pass, fid_end, peers_limit, torrents_limit, torrent_pass, downloaded, uploaded, torrent_pass_secret from " + table_name(table_users) + " where 0 = 1");
		return 0;
	}
	catch (Cxcc_error)
	{
	}
	return 1;
}
