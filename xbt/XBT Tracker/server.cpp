// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

#include <signal.h>
#include "bt_misc.h"
#include "bt_strings.h"
#include "transaction.h"

const char* g_pid_fname = "xbt_tracker.pid";
static volatile bool g_sig_hup = false;
static volatile bool g_sig_term = false;

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

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
		in_addr a;
		a.s_addr = h;
		peer.d(bts_ipa, static_cast<string>(inet_ntoa(a)));
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

Cserver::Cserver(Cdatabase& database):
	m_database(database)
{
	m_fid_end = 0;

	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
}

int Cserver::run()
{
	read_config();
	t_sockets lt, lu;
	for (t_listen_ipas::const_iterator j = m_listen_ipas.begin(); j != m_listen_ipas.end(); j++)
	{
		for (t_listen_ports::const_iterator i = m_listen_ports.begin(); i != m_listen_ports.end(); i++)
		{
			Csocket l0, l1;
			int v = true;
			if (l0.open(SOCK_STREAM) == INVALID_SOCKET)			
				cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (setsockopt(l0, SOL_SOCKET, SO_REUSEADDR, reinterpret_cast<const char*>(&v), sizeof(int)),
				l0.bind(*j, htons(*i)))			
				cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (l0.listen())
				cerr << "listen failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else
				lt.push_back(l0);
			if (l1.open(SOCK_DGRAM) == INVALID_SOCKET)
				cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else if (setsockopt(l1, SOL_SOCKET, SO_REUSEADDR, reinterpret_cast<const char*>(&v), sizeof(int)),
				l1.bind(*j, htons(*i)))
				cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
			else
				lu.push_back(l1);
		}
	}
	if (lt.empty() || lu.empty())
		return 1;
#ifndef WIN32
#if 1
	if (m_daemon && daemon(true, false))
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
	ofstream(g_pid_fname) << getpid() << endl;
	struct sigaction act;
	act.sa_handler = sig_handler;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	if (sigaction(SIGHUP, &act, NULL)
		|| sigaction(SIGTERM, &act, NULL))
		cerr << "sigaction failed" << endl;
#endif
	clean_up();
	read_db_files();
	read_db_ipas();
	read_db_users();
	write_db_files();
	write_db_users();
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
	while (!g_sig_term)
	{
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
		for (t_sockets::iterator i = lt.begin(); i != lt.end(); i++)
		{
			FD_SET(*i, &fd_read_set);
			n = max(n, static_cast<SOCKET>(*i));
		}
		for (t_sockets::iterator i = lu.begin(); i != lu.end(); i++)
		{
			FD_SET(*i, &fd_read_set);
			n = max(n, static_cast<SOCKET>(*i));
		}
		timeval tv;
		tv.tv_sec = 1;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		else 
		{
			for (t_sockets::iterator i = lt.begin(); i != lt.end(); i++)
			{
				if (!FD_ISSET(*i, &fd_read_set))
					continue;
				sockaddr_in a;
				while (1)
				{
					socklen_t cb_a = sizeof(sockaddr_in);
					Csocket s = accept(*i, reinterpret_cast<sockaddr*>(&a), &cb_a);
					if (s == SOCKET_ERROR)
					{
						if (WSAGetLastError() != WSAEWOULDBLOCK)
							cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << endl;
						break;
					}
					else
					{
						if (s.blocking(false))
							cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
						m_connections.push_front(Cconnection(this, s, a, m_log_access));
					}
				}
			}
			for (t_sockets::iterator i = lu.begin(); i != lu.end(); i++)
			{
				if (FD_ISSET(*i, &fd_read_set))
					Ctransaction(*this, *i).recv();
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
		if (time(NULL) - m_read_config_time > m_read_config_interval)
			read_config();
		else if (time(NULL) - m_clean_up_time > m_clean_up_interval)
			clean_up();
		else if (time(NULL) - m_read_db_files_time > m_read_db_interval)
			read_db_files();
		else if (time(NULL) - m_read_db_ipas_time > m_read_db_interval)
			read_db_ipas();
		else if (time(NULL) - m_read_db_users_time > m_read_db_interval)
			read_db_users();
		else if (time(NULL) - m_write_db_files_time > m_write_db_interval)
			write_db_files();
		else if (time(NULL) - m_write_db_users_time > m_write_db_interval)
			write_db_users();
	}
	write_db_files();
	write_db_users();
	unlink(g_pid_fname);
	return 0;
}

void Cserver::insert_peer(const Ctracker_input& v, bool listen_check, bool udp, int uid)
{
	if (m_log_announce)
	{
		Csql_query q(m_database, "(%d,%d,%d,%s,%s,%d,%d,%d,%d,%d),");
		q.p(ntohl(v.m_ipa));
		q.p(ntohs(v.m_port));
		q.p(v.m_event);
		q.pe(v.m_info_hash);
		q.pe(v.m_peer_id);
		q.p(v.m_downloaded);
		q.p(v.m_left);
		q.p(v.m_uploaded);
		q.p(uid);
		q.p(time(NULL));
		m_announce_log_buffer += q.read();
	}
	if (!m_auto_register && m_files.find(v.m_info_hash) == m_files.end())
		return;
	t_file& file = m_files[v.m_info_hash];
	t_peers::iterator i = file.peers.find(v.m_ipa);
	if (i != file.peers.end())
		(i->second.left ? file.leechers : file.seeders)--;
	if (v.m_event == Ctracker_input::e_stopped)
	{
		file.peers.erase(v.m_ipa);
		if (uid && (v.m_downloaded || v.m_uploaded))
		{
			Csql_query q(m_database, "(%d,%d,%d),");
			q.p(v.m_downloaded);
			q.p(v.m_uploaded);
			q.p(uid);
			m_users_updates_buffer += q.read();
		}
	}
	else
	{
		t_peer& peer = file.peers[v.m_ipa];
		// peer.downloaded = v.m_downloaded;
		peer.left = v.m_left;
		peer.peer_id = v.m_peer_id;
		peer.port = v.m_port;
		// peer.uploaded = v.m_uploaded;
		(peer.left ? file.leechers : file.seeders)++;

		if (!m_listen_check || !listen_check)
			peer.listening = true;
		else if (!peer.listening && time(NULL) - peer.mtime > 900)
		{
			Cpeer_link peer_link(v.m_ipa, v.m_port, this, v.m_info_hash, v.m_ipa);
			if (peer_link)
				m_peer_links.push_front(peer_link);
		}
		peer.mtime = time(NULL);
	}
	switch (v.m_event)
	{
	case Ctracker_input::e_completed:
		file.completed++;
		break;
	case Ctracker_input::e_started:
		file.started++;
		break;
	case Ctracker_input::e_stopped:
		file.stopped++;
		break;
	}
	if (udp)
		file.announced_udp++;
	else if (v.m_compact)
		file.announced_http_compact++;
	else if (v.m_no_peer_id)
		file.announced_http_no_peer_id++;
	else
		file.announced_http++;
	file.dirty = true;
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

Cbvalue Cserver::select_peers(const Ctracker_input& ti)
{
	t_files::const_iterator i = m_files.find(ti.m_info_hash);
	if (i == m_files.end()) 
		return Cbvalue().d(bts_failure_reason, bts_unregistered_torrent);
	if (ti.m_compact)
	{
		Cannounce_output_http_compact o;
		o.interval(m_announce_interval);
		i->second.select_peers(ti, o);
		return o.v();
	}
	Cannounce_output_http o;
	o.interval(m_announce_interval);
	o.no_peer_id(ti.m_no_peer_id);
	i->second.select_peers(ti, o);
	return o.v();
}

void Cserver::t_file::clean_up(int t)
{
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (i->second.mtime < t)
		{
			(i->second.left ? leechers : seeders)--;
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
		i->second.clean_up(time(NULL) - static_cast<int>(1.5 * m_announce_interval));
	m_clean_up_time = time(NULL);
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
	if (m_log_scrape)
	{
		Csql_query q(m_database, "(%d,%s,%d),");
		q.p(ntohl(ti.m_ipa));
		if (ti.m_info_hash.empty())
			q.p("NULL");
		else
			q.pe(ti.m_info_hash);
		q.p(time(NULL));
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
			i->second.scraped_http++;
			i->second.dirty = true;
			files.d(i->first, i->second.scrape());
		}
	}
	v.d(bts_files, files);
	return v;
}

void Cserver::read_db_files()
{
	m_read_db_files_time = time(NULL);
	try
	{
		Csql_query q(m_database);
		if (!m_auto_register)
		{
			q = "select info_hash, fid from xbt_files where flags & 1";
			Csql_result result = q.execute();
			for (Csql_row row; row = result.fetch_row(); )
			{
				if (row.size(0) != 20)
					continue;
				m_files.erase(string(row.f(0), 20));
				q = "delete from xbt_files where fid = %s";
				q.p(row.f_int(1));
				q.execute();
			}

		}
		if (m_files.empty())
			m_database.query("update xbt_files set leechers = 0, seeders = 0");
		else if (m_auto_register)
			return;			
		q = "select info_hash, completed, fid, started, stopped, announced_http, announced_http_compact, announced_http_no_peer_id, announced_udp, scraped_http, scraped_udp"
			" from xbt_files where fid >= %s";
		q.p(m_fid_end);
		Csql_result result = q.execute();
		for (Csql_row row; row = result.fetch_row(); )
		{
			m_fid_end = max(m_fid_end, static_cast<int>(row.f_int(2, 0)) + 1);
			if (row.size(0) != 20 || m_files.find(string(row.f(0), 20)) != m_files.end())
				continue;
			t_file& file = m_files[string(row.f(0), 20)];
			file.completed = row.f_int(1, 0);
			file.dirty = false;
			file.fid = row.f_int(2, 0);
			file.started = row.f_int(3, 0);
			file.stopped = row.f_int(4, 0);
			file.announced_http = row.f_int(5, 0);
			file.announced_http_compact = row.f_int(6, 0);
			file.announced_http_no_peer_id = row.f_int(7, 0);
			file.announced_udp = row.f_int(8, 0);
			file.scraped_http = row.f_int(9, 0);
			file.scraped_udp = row.f_int(10, 0);
		}
	}
	catch (Cxcc_error error)
	{
	}
}

void Cserver::read_db_ipas()
{
	try
	{
		Csql_query q(m_database, "select ipa, uid from xbt_ipas");
		Csql_result result = q.execute();
		m_ipas.clear();
		for (Csql_row row; row = result.fetch_row(); )
			m_ipas[row.f_int(0)] = row.f_int(1);
	}
	catch (Cxcc_error error)
	{
	}
	m_read_db_ipas_time = time(NULL);
}

void Cserver::read_db_users()
{
	try
	{
		Csql_query q(m_database, "select uid, name, pass from xbt_users");
		Csql_result result = q.execute();
		m_users.clear();
		for (Csql_row row; row = result.fetch_row(); )
		{
			if (row.size(2) != 20)
				continue;
			t_user& user = m_users[row.f(1)];
			user.uid = row.f_int(0);
			user.pass.assign(row.f(2), 20);
		}
	}
	catch (Cxcc_error error)
	{
	}
	m_read_db_users_time = time(NULL);
}

void Cserver::write_db_files()
{
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
				q = "insert into xbt_files (info_hash, ctime) values (%s, NULL)";
				q.pe(i->first);
				q.execute();
				file.fid = m_database.insert_id();
			}
			if (m_update_files_method)
				q = "(%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d),";
			else
			{
				q = "update xbt_files"
					" set leechers = %s, seeders = %s, completed = %s, started = %s, stopped = %s, announced_http = %s, announced_http_compact = %s, announced_http_no_peer_id = %s, announced_udp = %s, scraped_http = %s, scraped_udp = %s"
					" where fid = %s";
			}
			q.p(file.leechers);
			q.p(file.seeders);
			q.p(file.completed);
			q.p(file.started);
			q.p(file.stopped);
			q.p(file.announced_http);
			q.p(file.announced_http_compact);
			q.p(file.announced_http_no_peer_id);
			q.p(file.announced_udp);
			q.p(file.scraped_http);
			q.p(file.scraped_udp);
			q.p(file.fid);
			if (m_update_files_method)
				buffer += q.read();
			else
				q.execute();
			file.dirty = false;
		}
		if (!buffer.empty())
		{
			buffer.erase(buffer.size() - 1);
			m_database.query("insert into xbt_files_updates (leechers, seeders, completed, started, stopped, announced_http, announced_http_compact, announced_http_no_peer_id, announced_udp, scraped_http, scraped_udp, fid) values " + buffer);
			m_database.query("update xbt_files f, xbt_files_updates fu"
				" set f.leechers = fu.leechers, f.seeders = fu.seeders, f.completed = fu.completed, f.started = fu.started, f.stopped = fu.stopped,"
				"  f.announced_http = fu.announced_http, f.announced_http_compact = fu.announced_http_compact, f.announced_http_no_peer_id = fu.announced_http_no_peer_id,"
				"  f.announced_udp = fu.announced_udp, f.scraped_http = fu.scraped_http, f.scraped_udp = fu.scraped_udp"
				" where f.fid = fu.fid");
			m_database.query("delete from xbt_files_updates");
		}
	}
	catch (Cxcc_error error)
	{
	}
	if (!m_announce_log_buffer.empty())
	{
		try
		{
			m_announce_log_buffer.erase(m_announce_log_buffer.size() - 1);
			m_database.query("insert delayed into xbt_announce_log (ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values " + m_announce_log_buffer);
		}
		catch (Cxcc_error error)
		{
		}
		m_announce_log_buffer.erase();
	}
	if (!m_scrape_log_buffer.empty())
	{
		try
		{
			m_scrape_log_buffer.erase(m_scrape_log_buffer.size() - 1);
			m_database.query("insert delayed into xbt_scrape_log (ipa, info_hash, mtime) values " + m_scrape_log_buffer);
		}
		catch (Cxcc_error error)
		{
		}
		m_scrape_log_buffer.erase();
	}
	m_write_db_files_time = time(NULL);
}

void Cserver::write_db_users()
{
	if (!m_users_updates_buffer.empty())
	{
		m_users_updates_buffer.erase(m_users_updates_buffer.size() - 1);
		try
		{
			m_database.query("insert into xbt_users_updates (downloaded, uploaded, uid) values " + m_users_updates_buffer);
			m_database.query("insert ignore into xbt_users (uid) select uid from xbt_users_updates");
			m_database.query("update xbt_users u, xbt_users_updates uu"
				" set u.downloaded = u.downloaded + uu.downloaded, u.uploaded = u.uploaded + uu.uploaded"
				" where u.uid = uu.uid");
			m_database.query("delete from xbt_users_updates");
		}
		catch (Cxcc_error error)
		{
		}
		m_users_updates_buffer.erase();
	}
	m_write_db_users_time = time(NULL);
}

void Cserver::read_config()
{
	m_announce_interval = 1800;
	m_anonymous_connect = true;
	m_anonymous_announce = true;
	m_anonymous_scrape = true;
	m_auto_register = true;
	m_clean_up_interval = 60;
	m_daemon = true;
	m_gzip_announce = true;
	m_gzip_debug = true;
	m_gzip_scrape = true;
	m_listen_check = true;
	m_listen_ipas.clear();
	m_listen_ports.clear();
	m_log_access = false;
	m_log_announce = false;
	m_log_scrape = false;
	m_read_config_interval = 300;
	m_read_db_interval = 60;
	m_redirect_url.erase();
	m_update_files_method = 0;
	m_write_db_interval = 60;
	try
	{
		Csql_result result = m_database.query("select name, value from xbt_config where value is not null");
		for (Csql_row row; row = result.fetch_row(); )
		{
			if (!strcmp(row.f(0), "announce_interval"))
				m_announce_interval = row.f_int(1);
			else if (!strcmp(row.f(0), "auto_register"))
				m_auto_register = row.f_int(1);
			else if (!strcmp(row.f(0), "anonymous_connect"))
				m_anonymous_connect = row.f_int(1);
			else if (!strcmp(row.f(0), "anonymous_announce"))
				m_anonymous_announce = row.f_int(1);
			else if (!strcmp(row.f(0), "anonymous_scrape"))
				m_anonymous_scrape = row.f_int(1);
			else if (!strcmp(row.f(0), "daemon"))
				m_daemon = row.f_int(1);
			else if (!strcmp(row.f(0), "gzip_announce"))
				m_gzip_announce = row.f_int(1);
			else if (!strcmp(row.f(0), "gzip_debug"))
				m_gzip_debug = row.f_int(1);
			else if (!strcmp(row.f(0), "gzip_scrape"))
				m_gzip_scrape = row.f_int(1);
			else if (!strcmp(row.f(0), "listen_check"))
				m_listen_check = row.f_int(1);
			else if (!strcmp(row.f(0), "listen_ipa"))
				m_listen_ipas.insert(inet_addr(row.f(1)));
			else if (!strcmp(row.f(0), "listen_port"))
				m_listen_ports.insert(row.f_int(1));
			else if (!strcmp(row.f(0), "log_access"))
				m_log_access = row.f_int(1);
			else if (!strcmp(row.f(0), "log_announce"))
				m_log_announce = row.f_int(1);
			else if (!strcmp(row.f(0), "log_scrape"))
				m_log_scrape = row.f_int(1);
			else if (!strcmp(row.f(0), "read_config_interval"))
				m_read_config_interval = row.f_int(1);
			else if (!strcmp(row.f(0), "read_db_interval"))
				m_read_db_interval = row.f_int(1);
			else if (!strcmp(row.f(0), "redirect_url"))
				m_redirect_url = row.f(1);
			else if (!strcmp(row.f(0), "update_files_method"))
				m_update_files_method = row.f_int(1);
			else if (!strcmp(row.f(0), "write_db_interval"))
				m_write_db_interval = row.f_int(1);
		}
	}
	catch (Cxcc_error error)
	{
	}
	if (m_listen_ipas.empty())
		m_listen_ipas.insert(htonl(INADDR_ANY));

	if (m_listen_ports.empty())
		m_listen_ports.insert(2710);
	m_read_config_time = time(NULL);
}

string Cserver::t_file::debug() const
{
	string page;
	for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
	{
		in_addr a;
		a.s_addr = i->first;
		page += "<tr><td>" + static_cast<string>(inet_ntoa(a))
			+ "<td align=right>" + n(ntohs(i->second.port))
			+ "<td>" + (i->second.listening ? '*' : ' ')
			+ "<td align=right>" + n(i->second.left)
			+ "<td align=right>" + n(time(NULL) - i->second.mtime)
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
				+ "<td align=right>" + n(i->second.seeders) 
				+ "<td align=right>" + n(i->second.announced_http) 
				+ "<td align=right>" + n(i->second.announced_http_compact) 
				+ "<td align=right>" + n(i->second.announced_http_no_peer_id) 
				+ "<td align=right>" + n(i->second.announced_udp) 
				+ "<td align=right>" + n(i->second.scraped_http) 
				+ "<td align=right>" + n(i->second.scraped_udp) 
				+ "<td align=right>" + n(i->second.completed) 
				+ "<td align=right>" + n(i->second.started) 
				+ "<td align=right>" + n(i->second.stopped);
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
	int t = time(NULL);
	page += "<table><tr><td>leechers<td align=right>" + n(leechers)
		+ "<tr><td>seeders<td align=right>" + n(seeders)
		+ "<tr><td>peers<td align=right>" + n(leechers + seeders)
		+ "<tr><td>torrents<td align=right>" + n(torrents)
		+ "<tr><td>"
		+ "<tr><td>auto register<td align=right>" + n(m_auto_register) 
		+ "<tr><td>listen check<td align=right>" + n(m_listen_check) 
		+ "<tr><td>read config time<td align=right>" + n(t - m_read_config_time) + " / " + n(m_read_config_interval)
		+ "<tr><td>clean up time<td align=right>" + n(t - m_clean_up_time) + " / " + n(m_clean_up_interval)
		+ "<tr><td>read db files time<td align=right>" + n(t - m_read_db_files_time) + " / " + n(m_read_db_interval)
		+ "<tr><td>read db ipas time<td align=right>" + n(t - m_read_db_ipas_time) + " / " + n(m_read_db_interval)
		+ "<tr><td>read db users time<td align=right>" + n(t - m_read_db_users_time) + " / " + n(m_read_db_interval)
		+ "<tr><td>write db files time<td align=right>" + n(t - m_write_db_files_time) + " / " + n(m_write_db_interval)
		+ "<tr><td>write db users time<td align=right>" + n(t - m_write_db_users_time) + " / " + n(m_write_db_interval)
		+ "</table>";
	return page;
}

const Cserver::t_user* Cserver::find_user(const string& v) const
{
	t_users::const_iterator i = m_users.find(v);
	return i == m_users.end() ? NULL : &i->second;
}

int Cserver::get_user_id(int v) const
{
	t_ipas::const_iterator i = m_ipas.find(v);
	return i == m_ipas.end() ? 0 : i->second;
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
