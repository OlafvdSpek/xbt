#include "stdafx.h"
#include "server.h"

#include <bt_strings.h>
#include "connection.h"
#include "epoll.h"
#include "transaction.h"

namespace boost
{
	template<class T, size_t N>
	struct hash<std::array<T, N>>
	{
		size_t operator()(const std::array<T, N>& v) const
		{
			return boost::hash_range(v.begin(), v.end());
		}
	};
}

static volatile bool g_sig_term = false;
boost::ptr_list<Cconnection> m_connections;
boost::unordered_map<std::array<char, 20>, t_torrent> m_torrents;
boost::unordered_map<int, t_user> m_users;
boost::unordered_map<std::array<char, 32>, t_user*> m_users_torrent_passes;
Cconfig m_config;
Cdatabase m_database;
Cepoll m_epoll;
Cstats m_stats;
std::string m_announce_log_buffer;
std::string m_conf_file;
std::string m_scrape_log_buffer;
std::string m_table_prefix;
std::string m_torrents_users_updates_buffer;
std::string m_users_updates_buffer;
time_t m_clean_up_time;
time_t m_read_config_time;
time_t m_read_db_torrents_time;
time_t m_read_db_users_time;
time_t m_time = time(NULL);
time_t m_write_db_torrents_time;
time_t m_write_db_users_time;
unsigned long long m_secret;
int m_fid_end = 0;
bool m_read_users_can_leech;
bool m_read_users_peers_limit;
bool m_read_users_torrent_pass;
bool m_read_users_wait_time;
bool m_use_sql;

void accept(const Csocket&);
	
static void async_query(const std::string& v)
{
	m_database.query_nothrow(v);
}

static void sig_handler(int v)
{
	if (v == SIGTERM)
		g_sig_term = true;
}

class Ctcp_listen_socket : public Cclient
{
public:
	Ctcp_listen_socket(const Csocket& s)
	{
		m_s = s;
	}

	virtual void process_events(int)
	{
		accept(m_s);
	}
};

class Cudp_listen_socket : public Cclient
{
public:
	Cudp_listen_socket(const Csocket& s)
	{
		m_s = s;
	}

	virtual void process_events(int events)
	{
		if (events & EPOLLIN)
			Ctransaction(m_s).recv();
	}
};

const Cconfig& srv_config()
{
	return m_config;
}

Cdatabase& srv_database()
{
	return m_database;
}

const t_torrent* find_torrent(const std::string& id)
{
	return find_ptr(m_torrents, to_array<char, 20>(id));
}

t_user* find_user_by_uid(int v)
{
	return find_ptr(m_users, v);
}

long long srv_secret()
{
	return m_secret;
}

Cstats& srv_stats()
{
	return m_stats;
}

time_t srv_time()
{
	return m_time;
}

void read_config()
{
	if (m_use_sql)
	{
		try
		{
			Csql_result result = Csql_query(m_database, "select name, value from @config where value is not null").execute();
			Cconfig config;
			while (Csql_row row = result.fetch_row())
			{
				if (config.set(row[0].s(), row[1].s()))
					std::cerr << "unknown config name: " << row[0].s() << std::endl;
			}
			config.load(m_conf_file);
			if (config.m_torrent_pass_private_key.empty())
			{
				config.m_torrent_pass_private_key = generate_random_string(27);
				Csql_query(m_database, "insert into @config (name, value) values ('torrent_pass_private_key', ?)")(config.m_torrent_pass_private_key).execute();
			}
			m_config = config;
			m_database.set_name("completed", m_config.m_column_files_completed);
			m_database.set_name("leechers", m_config.m_column_files_leechers);
			m_database.set_name("seeders", m_config.m_column_files_seeders);
			m_database.set_name("fid", m_config.m_column_files_fid);
			m_database.set_name("uid", m_config.m_column_users_uid);
			m_database.set_name("announce_log", m_config.m_table_announce_log.empty() ? m_table_prefix + "announce_log" : m_config.m_table_announce_log);
			m_database.set_name("files", m_config.m_table_torrents.empty() ? m_table_prefix + "files" : m_config.m_table_torrents);
			m_database.set_name("files_users", m_config.m_table_torrents_users.empty() ? m_table_prefix + "files_users" : m_config.m_table_torrents_users);
			m_database.set_name("scrape_log", m_config.m_table_scrape_log.empty() ? m_table_prefix + "scrape_log" : m_config.m_table_scrape_log);
			m_database.set_name("users", m_config.m_table_users.empty() ? m_table_prefix + "users" : m_config.m_table_users);
		}
		catch (bad_query&)
		{
		}
	}
	else
	{
		Cconfig config;
		if (!config.load(m_conf_file))
			m_config = config;
	}
	if (m_config.m_listen_ipas.empty())
		m_config.m_listen_ipas.insert(htonl(INADDR_ANY));
	if (m_config.m_listen_ports.empty())
		m_config.m_listen_ports.insert(2710);
	m_read_config_time = srv_time();
}

void read_db_torrents_sql()
{
	try
	{
		if (!m_config.m_auto_register)
		{
			Csql_result result = Csql_query(m_database, "select info_hash, @fid from @files where flags & 1").execute();
			while (Csql_row row = result.fetch_row())
			{
				m_torrents.erase(to_array<char, 20>(row[0]));
				Csql_query(m_database, "delete from @files where @fid = ?")(row[1]).execute();
			}
		}
		if (m_config.m_auto_register && !m_torrents.empty())
			return;
		Csql_result result = Csql_query(m_database, "select info_hash, @completed, @fid, ctime from @files where @fid >= ?")(m_fid_end).execute();
		// m_torrents.reserve(m_torrents.size() + result.size());
		while (Csql_row row = result.fetch_row())
		{
			m_fid_end = std::max<int>(m_fid_end, row[2].i() + 1);
			if (row[0].size() != 20 || find_torrent(row[0].s()))
				continue;
			t_torrent& file = m_torrents[to_array<char, 20>(row[0])];
			if (file.fid)
				continue;
			file.completed = row[1].i();
			file.dirty = false;
			file.fid = row[2].i();
			file.ctime = row[3].i();
		}
	}
	catch (bad_query&)
	{
	}
}

void read_db_torrents()
{
	m_read_db_torrents_time = srv_time();
	if (m_use_sql)
		read_db_torrents_sql();
	else if (!m_config.m_auto_register)
	{
		std::set<t_torrent*> new_torrents;
		std::ifstream is("xbt_torrents.txt");
		for (std::string s; getline(is, s); )
		{
			s = hex_decode(s);
			if (s.size() == 20)
				new_torrents.insert(&m_torrents[to_array<char, 20>(s)]);
		}
		for (auto i = m_torrents.begin(); i != m_torrents.end(); )
		{
			if (new_torrents.count(&i->second))
				i++;
			else
				m_torrents.erase(i++);
		}
	}
}

void read_db_users()
{
	m_read_db_users_time = srv_time();
	if (!m_use_sql)
		return;
	try
	{
		Csql_query q(m_database, "select @uid");
		if (m_read_users_can_leech)
			q += ", can_leech";
		if (m_read_users_peers_limit)
			q += ", peers_limit";
		if (m_read_users_torrent_pass)
			q += ", torrent_pass";
		q += ", torrent_pass_version";
		if (m_read_users_wait_time)
			q += ", wait_time";
		q += " from @users";
		Csql_result result = q.execute();
		// m_users.reserve(result.size());
		for (auto& i : m_users)
			i.second.marked = true;
		m_users_torrent_passes.clear();
		while (Csql_row row = result.fetch_row())
		{
			t_user& user = m_users[row[0].i()];
			user.marked = false;
			int c = 0;
			user.uid = row[c++].i();
			if (m_read_users_can_leech)
				user.can_leech = row[c++].i();
			if (m_read_users_peers_limit)
				user.peers_limit = row[c++].i();
			if (m_read_users_torrent_pass)
			{
				if (row[c].size() == 32)
					m_users_torrent_passes[to_array<char, 32>(row[c])] = &user;
				c++;
			}
			user.torrent_pass_version = row[c++].i();
			if (m_read_users_wait_time)
				user.wait_time = row[c++].i();
		}
		for (auto i = m_users.begin(); i != m_users.end(); )
		{
			if (i->second.marked)
				m_users.erase(i++);
			else
				i++;
		}
	}
	catch (bad_query&)
	{
	}
}

const std::string& db_name(const std::string& v)
{
	return m_database.name(v);
}

void write_db_torrents()
{
	m_write_db_torrents_time = srv_time();
	if (!m_use_sql)
		return;
	try
	{
		std::string buffer;
		while (1)
		{
			for (auto& i : m_torrents)
			{
				t_torrent& file = i.second;
				if (!file.dirty)
					continue;
				if (!file.fid)
				{
					Csql_query(m_database, "insert into @files (info_hash, mtime, ctime) values (?, unix_timestamp(), unix_timestamp())")(i.first).execute();
					file.fid = m_database.insert_id();
				}
				buffer += Csql_query(m_database, "(?,?,?,?),")(file.leechers)(file.seeders)(file.completed)(file.fid).read();
				file.dirty = false;
				if (buffer.size() > 255 << 10)
					break;
			}
			if (buffer.empty())
				break;
			buffer.pop_back();
			async_query("insert into " + db_name("files") + " (" + db_name("leechers") + ", " + db_name("seeders") + ", " + db_name("completed") + ", " + db_name("fid") + ") values "
				+ buffer
				+ " on duplicate key update"
				+ "  " + db_name("leechers") + " = values(" + db_name("leechers") + "),"
				+ "  " + db_name("seeders") + " = values(" + db_name("seeders") + "),"
				+ "  " + db_name("completed") + " = values(" + db_name("completed") + "),"
				+ "  mtime = unix_timestamp()");
			buffer.clear();
		}
	}
	catch (bad_query&)
	{
	}
	if (!m_announce_log_buffer.empty())
	{
		m_announce_log_buffer.pop_back();
		async_query("insert delayed into " + db_name("announce_log") + " (ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values " + m_announce_log_buffer);
		m_announce_log_buffer.erase();
	}
	if (!m_scrape_log_buffer.empty())
	{
		m_scrape_log_buffer.pop_back();
		async_query("insert delayed into " + db_name("scrape_log") + " (ipa, uid, mtime) values " + m_scrape_log_buffer);
		m_scrape_log_buffer.erase();
	}
}

void write_db_users()
{
	m_write_db_users_time = srv_time();
	if (!m_use_sql)
		return;
	if (!m_torrents_users_updates_buffer.empty())
	{
		m_torrents_users_updates_buffer.pop_back();
		async_query("insert into " + db_name("files_users") + " (active, announced, completed, downloaded, `left`, uploaded, mtime, fid, uid) values "
			+ m_torrents_users_updates_buffer
			+ " on duplicate key update"
			+ "  active = values(active),"
			+ "  announced = announced + values(announced),"
			+ "  completed = completed + values(completed),"
			+ "  downloaded = downloaded + values(downloaded),"
			+ "  `left` = values(`left`),"
			+ "  uploaded = uploaded + values(uploaded),"
			+ "  mtime = values(mtime)");
		m_torrents_users_updates_buffer.erase();
	}
	async_query("update " + db_name("files_users") + " set active = 0 where mtime < unix_timestamp() - 60 * 60");
	if (!m_users_updates_buffer.empty())
	{
		m_users_updates_buffer.pop_back();
		async_query("insert into " + db_name("users") + " (downloaded, uploaded, " + db_name("uid") + ") values "
			+ m_users_updates_buffer
			+ " on duplicate key update"
			+ "  downloaded = downloaded + values(downloaded),"
			+ "  uploaded = uploaded + values(uploaded)");
		m_users_updates_buffer.erase();
	}
}

int test_sql()
{
	if (!m_use_sql)
		return 0;
	try
	{
		mysql_get_server_version(m_database);
		if (m_config.m_log_announce)
			Csql_query(m_database, "select id, ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime from @announce_log where 0").execute();
		Csql_query(m_database, "select name, value from @config where 0").execute();
		Csql_query(m_database, "select @fid, info_hash, @leechers, @seeders, flags, mtime, ctime from @files where 0").execute();
		Csql_query(m_database, "select fid, uid, active, announced, completed, downloaded, `left`, uploaded from @files_users where 0").execute();
		if (m_config.m_log_scrape)
			Csql_query(m_database, "select id, ipa, uid, mtime from @scrape_log where 0").execute();
		Csql_query(m_database, "select @uid, torrent_pass_version, downloaded, uploaded from @users where 0").execute();
		Csql_query(m_database, "update @files set @leechers = 0, @seeders = 0").execute();
		// Csql_query(m_database, "update @files_users set active = 0").execute();
		m_read_users_can_leech = Csql_query(m_database, "show columns from @users like 'can_leech'").execute();
		m_read_users_peers_limit = Csql_query(m_database, "show columns from @users like 'peers_limit'").execute();
		m_read_users_torrent_pass = Csql_query(m_database, "show columns from @users like 'torrent_pass'").execute();
		m_read_users_wait_time = Csql_query(m_database, "show columns from @users like 'wait_time'").execute();
		return 0;
	}
	catch (bad_query&)
	{
	}
	return 1;
}

void clean_up(t_torrent& t, time_t time)
{
	for (auto i = t.peers.begin(); i != t.peers.end(); )
	{
		if (i->second.mtime < time)
		{
			(i->second.left ? t.leechers : t.seeders)--;
			t.peers.erase(i++);
			t.dirty = true;
		}
		else
			i++;
	}
}

void clean_up()
{
	for (auto& i : m_torrents)
		clean_up(i.second, srv_time() - static_cast<int>(1.5 * m_config.m_announce_interval));
	m_clean_up_time = srv_time();
}

int srv_run(const std::string& table_prefix, bool use_sql, const std::string& conf_file)
{
	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
	m_conf_file = conf_file;
	m_database.set_name("config", table_prefix + "config");
	m_table_prefix = table_prefix;
	m_use_sql = use_sql;

	read_config();
	if (test_sql())
		return 1;
	if (m_epoll.create() == -1)
	{
		std::cerr << "epoll_create failed" << std::endl;
		return 1;
	}
	std::list<Ctcp_listen_socket> lt;
	std::list<Cudp_listen_socket> lu;
	for (auto& j : m_config.m_listen_ipas)
	{
		for (auto& i : m_config.m_listen_ports)
		{
			Csocket l;
			if (l.open(SOCK_STREAM) == INVALID_SOCKET)
				std::cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(j, htons(i)))
				std::cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.listen())
				std::cerr << "listen failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else
			{
#ifdef SO_ACCEPTFILTER
				accept_filter_arg afa;
				bzero(&afa, sizeof(afa));
				strcpy(afa.af_name, "httpready");
				if (l.setsockopt(SOL_SOCKET, SO_ACCEPTFILTER, &afa, sizeof(afa)))
					std::cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
#elif 0 // TCP_DEFER_ACCEPT
				if (l.setsockopt(IPPROTO_TCP, TCP_DEFER_ACCEPT, 90))
					std::cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
#endif
				lt.push_back(Ctcp_listen_socket(l));
				if (!m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP, &lt.back()))
					continue;
			}
			return 1;
		}
		for (auto& i : m_config.m_listen_ports)
		{
			Csocket l;
			if (l.open(SOCK_DGRAM) == INVALID_SOCKET)
				std::cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(j, htons(i)))
				std::cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else
			{
				lu.push_back(Cudp_listen_socket(l));
				if (!m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLPRI | EPOLLERR | EPOLLHUP, &lu.back()))
					continue;
			}
			return 1;
		}
	}
	clean_up();
	read_db_torrents();
	read_db_users();
	write_db_torrents();
	write_db_users();
#ifndef NDEBUG
	// test_announce();
#endif
#ifndef WIN32
	if (m_config.m_daemon)
	{
		if (daemon(true, false))
			std::cerr << "daemon failed" << std::endl;
		std::ofstream(m_config.m_pid_file.c_str()) << getpid() << std::endl;
		struct sigaction act;
		act.sa_handler = sig_handler;
		sigemptyset(&act.sa_mask);
		act.sa_flags = 0;
		if (sigaction(SIGTERM, &act, NULL))
			std::cerr << "sigaction failed" << std::endl;
		act.sa_handler = SIG_IGN;
		if (sigaction(SIGPIPE, &act, NULL))
			std::cerr << "sigaction failed" << std::endl;
	}
#endif
#ifdef EPOLL
	std::array<epoll_event, 64> events;
#else
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
#endif
	while (!g_sig_term)
	{
#ifdef EPOLL
		int r = m_epoll.wait(events.data(), events.size(), 5000);
		if (r == -1)
			std::cerr << "epoll_wait failed: " << errno << std::endl;
		else
		{
			time_t prev_time = m_time;
			m_time = ::time(NULL);
			for (int i = 0; i < r; i++)
				reinterpret_cast<Cclient*>(events[i].data.ptr)->process_events(events[i].events);
			if (m_time == prev_time)
				continue;
			for (auto i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->run())
					i = m_connections.erase(i);
				else
					i++;
			}
		}
#else
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		int n = 0;
		for (auto& i : m_connections)
		{
			int z = i.pre_select(&fd_read_set, &fd_write_set);
			n = std::max(n, z);
		}
		for (auto& i : lt)
		{
			FD_SET(i.s(), &fd_read_set);
			n = std::max<int>(n, i.s());
		}
		for (auto& i : lu)
		{
			FD_SET(i.s(), &fd_read_set);
			n = std::max<int>(n, i.s());
		}
		timeval tv;
		tv.tv_sec = 5;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			std::cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
		else
		{
			m_time = ::time(NULL);
			for (auto& i : lt)
			{
				if (FD_ISSET(i.s(), &fd_read_set))
					accept(i.s());
			}
			for (auto& i : lu)
			{
				if (FD_ISSET(i.s(), &fd_read_set))
					Ctransaction(i.s()).recv();
			}
			for (auto i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->post_select(&fd_read_set, &fd_write_set))
					i = m_connections.erase(i);
				else
					i++;
			}
		}
#endif
		if (srv_time() - m_read_config_time > m_config.m_read_config_interval)
			read_config();
		else if (srv_time() - m_clean_up_time > m_config.m_clean_up_interval)
			clean_up();
		else if (srv_time() - m_read_db_torrents_time > m_config.m_read_db_interval)
			read_db_torrents();
		else if (srv_time() - m_read_db_users_time > m_config.m_read_db_interval)
			read_db_users();
		else if (m_config.m_write_db_interval && srv_time() - m_write_db_torrents_time > m_config.m_write_db_interval)
			write_db_torrents();
		else if (m_config.m_write_db_interval && srv_time() - m_write_db_users_time > m_config.m_write_db_interval)
			write_db_users();
	}
	write_db_torrents();
	write_db_users();
	unlink(m_config.m_pid_file.c_str());
	return 0;
}

void accept(const Csocket& l)
{
	sockaddr_in a;
	for (int i = 0; i < 10000; i++)
	{
		socklen_t cb_a = sizeof(sockaddr_in);
#ifdef SOCK_NONBLOCK
		Csocket s = accept4(l, reinterpret_cast<sockaddr*>(&a), &cb_a, SOCK_NONBLOCK);
#else
		Csocket s = ::accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
#endif
		if (s == SOCKET_ERROR)
		{
			if (WSAGetLastError() == WSAECONNABORTED)
				continue;
			if (WSAGetLastError() != WSAEWOULDBLOCK)
			{
				m_stats.accept_errors++;
				std::cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
				xbt_syslog("accept failed: " + Csocket::error2a(WSAGetLastError()));
			}
			break;
		}
		m_stats.accepted_tcp++;
#ifndef SOCK_NONBLOCK
		if (s.blocking(false))
			std::cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
#endif
		std::unique_ptr<Cconnection> connection(new Cconnection(s, a));
		connection->process_events(EPOLLIN);
		if (connection->s() != INVALID_SOCKET)
		{
			m_stats.slow_tcp++;
			m_connections.push_back(connection.release());
			m_epoll.ctl(EPOLL_CTL_ADD, m_connections.back().s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_connections.back());
		}
	}
}

std::string srv_insert_peer(const Ctracker_input& v, bool udp, t_user* user)
{
	if (m_use_sql && m_config.m_log_announce)
	{
		m_announce_log_buffer += Csql_query(m_database, "(?,?,?,?,?,?,?,?,?,?),")
			(ntohl(v.m_ipa))
			(ntohs(v.m_port))
			(v.m_event)
			(v.m_info_hash)
			(v.m_peer_id)
			(v.m_downloaded)
			(v.m_left)
			(v.m_uploaded)
			(user ? user->uid : 0)
			(srv_time())
			.read();
	}
	if (!m_config.m_offline_message.empty())
		return m_config.m_offline_message;
	if (0)
		return bts_banned_client;
	if (!m_config.m_anonymous_announce && !user)
		return bts_unregistered_torrent_pass;
	if (!m_config.m_auto_register && !find_torrent(v.m_info_hash))
		return bts_unregistered_torrent;
	if (v.m_left && user && !user->can_leech)
		return bts_can_not_leech;
	t_torrent& file = m_torrents[to_array<char, 20>(v.m_info_hash)];
	if (!file.ctime)
		file.ctime = srv_time();
	if (v.m_left && user && user->wait_time && file.ctime + user->wait_time > srv_time())
		return bts_wait_time;
	peer_key_c peer_key(v.m_ipa, user ? user->uid : 0);
	t_peer* i = find_ptr(file.peers, peer_key);
	if (i)
		(i->left ? file.leechers : file.seeders)--;
	else if (v.m_left && user && user->peers_limit)
	{
		int c = 0;
		for (auto& j : file.peers)
			c += j.second.left && j.second.uid == user->uid;
		if (c >= user->peers_limit)
			return bts_peers_limit_reached;
	}
	if (m_use_sql && user && file.fid)
	{
		long long downloaded = 0;
		long long uploaded = 0;
		if (i
			&& i->uid == user->uid
			&& boost::equals(i->peer_id, v.m_peer_id)
			&& v.m_downloaded >= i->downloaded
			&& v.m_uploaded >= i->uploaded)
		{
			downloaded = v.m_downloaded - i->downloaded;
			uploaded = v.m_uploaded - i->uploaded;
		}
		m_torrents_users_updates_buffer += Csql_query(m_database, "(?,1,?,?,?,?,?,?,?),")
			(v.m_event != Ctracker_input::e_stopped)
			(v.m_event == Ctracker_input::e_completed)
			(downloaded)
			(v.m_left)
			(uploaded)
			(srv_time())
			(file.fid)
			(user->uid)
			.read();
		if (downloaded || uploaded)
			m_users_updates_buffer += Csql_query(m_database, "(?,?,?),")(downloaded)(uploaded)(user->uid).read();
		if (m_torrents_users_updates_buffer.size() > 255 << 10)
			write_db_users();
	}
	if (v.m_event == Ctracker_input::e_stopped)
		file.peers.erase(peer_key);
	else
	{
		t_peer& peer = i ? *i : file.peers[peer_key];
		peer.downloaded = v.m_downloaded;
		peer.left = v.m_left;
		peer.peer_id = v.m_peer_id;
		peer.port = v.m_port;
		peer.uid = user ? user->uid : 0;
		peer.uploaded = v.m_uploaded;
		(peer.left ? file.leechers : file.seeders)++;
		peer.mtime = srv_time();
	}
	if (v.m_event == Ctracker_input::e_completed)
		file.completed++;
	(udp ? m_stats.announced_udp : m_stats.announced_http)++;
	file.dirty = true;
	return std::string();
}

void t_torrent::select_peers(mutable_str_ref& d, const Ctracker_input& ti) const
{
	if (ti.m_event == Ctracker_input::e_stopped)
		return;
	std::vector<std::array<char, 6>> candidates;
	candidates.reserve(peers.size());
	for (auto& i : peers)
	{
		if (!ti.m_left && !i.second.left)
			continue;
		std::array<char, 6> v;
		memcpy(&v[0], &i.first.host_, 4);
		memcpy(&v[4], &i.second.port, 2);
		candidates.push_back(v);
	}
	size_t c = d.size() / 6;
	if (candidates.size() <= c)
	{
		memcpy(d.data(), candidates);
		d.advance_begin(6 * candidates.size());
		return;
	}
	const char* d0 = d.begin();
	while (c--)
	{
		int i = rand() % candidates.size();
		memcpy(d.data(), candidates[i]);
		d.advance_begin(6);
		candidates[i] = candidates.back();
		candidates.pop_back();
	}
}

std::string srv_select_peers(const Ctracker_input& ti)
{
	const t_torrent* f = find_torrent(ti.m_info_hash);
	if (!f)
		return std::string();
	std::array<char, 300> peers0;
	mutable_str_ref peers = peers0;
	f->select_peers(peers, ti);
	peers.assign(peers0.data(), peers.data());
	return (boost::format("d8:completei%de10:incompletei%de8:intervali%de12:min intervali%de5:peers%d:%se")
		% f->seeders % f->leechers % m_config.m_announce_interval % m_config.m_announce_interval % peers.size() % peers).str();
}

std::string srv_scrape(const Ctracker_input& ti, t_user* user)
{
	if (m_use_sql && m_config.m_log_scrape)
		m_scrape_log_buffer += Csql_query(m_database, "(?,?,?),")(ntohl(ti.m_ipa))(user ? user->uid : 0)(srv_time()).read();
	if (!m_config.m_anonymous_scrape && !user)
		return "d14:failure reason25:unregistered torrent passe";
	std::string d;
	d += "d5:filesd";
	if (ti.m_info_hashes.empty())
	{
		m_stats.scraped_full++;
		d.reserve(90 * m_torrents.size());
		for (auto& i : m_torrents)
		{
			if (i.second.leechers || i.second.seeders)
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % boost::make_iterator_range(i.first) % i.second.seeders % i.second.completed % i.second.leechers).str();
		}
	}
	else
	{
		m_stats.scraped_http++;
		if (ti.m_info_hashes.size() > 1)
			m_stats.scraped_multi++;
		for (auto& j : ti.m_info_hashes)
		{
			if (const t_torrent* i = find_torrent(j))
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % j % i->seeders % i->completed % i->leechers).str();
		}
	}
	d += "e";
	if (m_config.m_scrape_interval)
		d += (boost::format("5:flagsd20:min_request_intervali%dee") % m_config.m_scrape_interval).str();
	d += "e";
	return d;
}

void debug(const t_torrent& t, std::ostream& os)
{
	for (auto& i : t.peers)
	{
		os << "<tr><td>" + Csocket::inet_ntoa(i.first.host_)
			<< "<td class=ar>" << ntohs(i.second.port)
			<< "<td class=ar>" << i.second.uid
			<< "<td class=ar>" << i.second.left
			<< "<td class=ar>" << srv_time() - i.second.mtime
			<< "<td>" << hex_encode(i.second.peer_id);
	}
}

std::string srv_debug(const Ctracker_input& ti)
{
	std::ostringstream os;
	os << "<!DOCTYPE HTML><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	os << "<table>";
	if (ti.m_info_hash.empty())
	{
		for (auto& i : m_torrents)
		{
			if (!i.second.leechers && !i.second.seeders)
				continue;
			os << "<tr><td class=ar>" << i.second.fid
				<< "<td><a href=\"?info_hash=" << uri_encode(i.first) << "\">" << hex_encode(i.first) << "</a>"
				<< "<td>" << (i.second.dirty ? '*' : ' ')
				<< "<td class=ar>" << i.second.leechers
				<< "<td class=ar>" << i.second.seeders;
		}
	}
	else if (const t_torrent* i = find_torrent(ti.m_info_hash))
		debug(*i, os);
	os << "</table>";
	return os.str();
}

std::string srv_statistics()
{
	std::ostringstream os;
	os << "<!DOCTYPE HTML><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	os << "<style>.ar { text-align: right }</style>";
	long long leechers = 0;
	long long seeders = 0;
	int torrents = 0;
	for (auto& i : m_torrents)
	{
		leechers += i.second.leechers;
		seeders += i.second.seeders;
		torrents += i.second.leechers || i.second.seeders;
	}
	int peers = leechers + seeders;
	time_t t = srv_time();
	time_t up_time = std::max<time_t>(1, t - m_stats.start_time);
	os << "<table>"
		<< "<tr><td>peers<td class=ar>" << peers;
	if (peers)
	{
		os << "<tr><td>seeders<td class=ar>" << seeders << "<td class=ar>" << seeders * 100 / peers << " %"
			<< "<tr><td>leechers<td class=ar>" << leechers << "<td class=ar>" << leechers * 100 / peers << " %";
	}
	os << "<tr><td>torrents<td class=ar>" << torrents
		<< "<tr><td>"
		<< "<tr><td>accepted tcp<td class=ar>" << m_stats.accepted_tcp << "<td class=ar>" << m_stats.accepted_tcp / up_time << " /s"
		<< "<tr><td>slow tcp<td class=ar>" << m_stats.slow_tcp << "<td class=ar>" << m_stats.slow_tcp / up_time << " /s"
		<< "<tr><td>rejected tcp<td class=ar>" << m_stats.rejected_tcp
		<< "<tr><td>accept errors<td class=ar>" << m_stats.accept_errors
		<< "<tr><td>received udp<td class=ar>" << m_stats.received_udp << "<td class=ar>" << m_stats.received_udp / up_time << " /s"
		<< "<tr><td>sent udp<td class=ar>" << m_stats.sent_udp << "<td class=ar>" << m_stats.sent_udp / up_time << " /s";
	if (m_stats.announced())
	{
		os << "<tr><td>announced<td class=ar>" << m_stats.announced() << "<td class=ar>" << m_stats.announced() * 100 / m_stats.accepted_tcp << " %"
			<< "<tr><td>announced http <td class=ar>" << m_stats.announced_http << "<td class=ar>" << m_stats.announced_http * 100 / m_stats.announced() << " %"
			<< "<tr><td>announced udp<td class=ar>" << m_stats.announced_udp << "<td class=ar>" << m_stats.announced_udp * 100 / m_stats.announced() << " %";
	}
	os << "<tr><td>scraped full<td class=ar>" << m_stats.scraped_full;
	os << "<tr><td>scraped multi<td class=ar>" << m_stats.scraped_multi;
	if (m_stats.scraped())
	{
		os << "<tr><td>scraped<td class=ar>" << m_stats.scraped() << "<td class=ar>" << m_stats.scraped() * 100 / m_stats.accepted_tcp << " %"
			<< "<tr><td>scraped http<td class=ar>" << m_stats.scraped_http << "<td class=ar>" << m_stats.scraped_http * 100 / m_stats.scraped() << " %"
			<< "<tr><td>scraped udp<td class=ar>" << m_stats.scraped_udp << "<td class=ar>" << m_stats.scraped_udp * 100 / m_stats.scraped() << " %";
	}
	os << "<tr><td>"
		<< "<tr><td>up time<td class=ar>" << duration2a(up_time)
		<< "<tr><td>"
		<< "<tr><td>anonymous announce<td class=ar>" << m_config.m_anonymous_announce
		<< "<tr><td>anonymous scrape<td class=ar>" << m_config.m_anonymous_scrape
		<< "<tr><td>auto register<td class=ar>" << m_config.m_auto_register
		<< "<tr><td>full scrape<td class=ar>" << m_config.m_full_scrape
		<< "<tr><td>read config time<td class=ar>" << t - m_read_config_time << " / " << m_config.m_read_config_interval
		<< "<tr><td>clean up time<td class=ar>" << t - m_clean_up_time << " / " << m_config.m_clean_up_interval
		<< "<tr><td>read db files time<td class=ar>" << t - m_read_db_torrents_time << " / " << m_config.m_read_db_interval;
	if (m_use_sql)
	{
		os << "<tr><td>read db users time<td class=ar>" << t - m_read_db_users_time << " / " << m_config.m_read_db_interval
			<< "<tr><td>write db files time<td class=ar>" << t - m_write_db_torrents_time << " / " << m_config.m_write_db_interval
			<< "<tr><td>write db users time<td class=ar>" << t - m_write_db_users_time << " / " << m_config.m_write_db_interval;
	}
	os << "</table>";
	return os.str();
}

t_user* find_user_by_torrent_pass(str_ref v, str_ref info_hash)
{
	if (v.size() != 32)
		return NULL;
	if (t_user* user = find_user_by_uid(read_int(4, hex_decode(v.substr(0, 8)))))
	{
		if (Csha1((boost::format("%s %d %d %s") % m_config.m_torrent_pass_private_key % user->torrent_pass_version % user->uid % info_hash).str()).read().substr(0, 12) == hex_decode(v.substr(8, 24)))
			return user;
	}
	return find_ptr2(m_users_torrent_passes, to_array<char, 32>(v));
}

void srv_term()
{
	g_sig_term = true;
}

void test_announce()
{
	t_user* u = find_ptr(m_users, 1);
	Ctracker_input i;
	i.m_info_hash = "IHIHIHIHIHIHIHIHIHIH";
	memcpy(i.m_peer_id.data(), str_ref("PIPIPIPIPIPIPIPIPIPI"));
	i.m_ipa = htonl(0x7f000063);
	i.m_port = 54321;
	std::cout << srv_insert_peer(i, false, u) << std::endl;
	write_db_torrents();
	write_db_users();
	m_time++;
	i.m_uploaded = 1 << 30;
	i.m_downloaded = 1 << 20;
	std::cout << srv_insert_peer(i, false, u) << std::endl;
	write_db_torrents();
	write_db_users();
	m_time += 3600;
	clean_up();
	write_db_torrents();
	write_db_users();
}
