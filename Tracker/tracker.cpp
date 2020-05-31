#include "stdafx.h"
#include "tracker.h"

#include <bt_strings.h>
#include <windows/nt_service.h>
#include "connection.h"
#include "epoll.h"
#include "transaction.h"

#ifdef XBT_SYSTEMD
#include <systemd/sd-daemon.h>
#endif

using namespace std;

namespace std
{
	template<class T, size_t N>
	struct hash<array<T, N>>
	{
		size_t operator()(const array<T, N>& v) const
		{
			return boost::hash_range(v.begin(), v.end());
		}
	};
}

static volatile bool g_sig_term = false;
static boost::ptr_list<connection_t> g_connections;
static unordered_map<array<char, 20>, torrent_t> g_torrents;
static unordered_map<int, user_t> g_users;
static unordered_map<array<char, 32>, user_t*> g_users_torrent_passes;
static config_t g_config;
static Cdatabase g_database;
static Cepoll g_epoll;
static stats_t g_stats;
static string g_announce_log_buffer;
static string g_conf_file = "xbt_tracker.conf";
static string g_scrape_log_buffer;
static string g_table_prefix;
static string g_torrents_users_updates_buffer;
static string g_users_updates_buffer;
static time_t g_clean_up_time;
static time_t g_read_config_time;
static time_t g_read_db_torrents_time;
static time_t g_read_db_users_time;
static time_t g_time = time(NULL);
static time_t g_write_db_torrents_time;
static time_t g_write_db_users_time;
static unsigned long long g_secret;
static int g_tid_end = 0;
static bool g_read_users_can_leech;
static bool g_read_users_peers_limit;
static bool g_read_users_torrent_pass;
static bool g_read_users_wait_time;
static const char g_service_name[] = "XBT Tracker";

void accept(const Csocket&);
	
template<class... A>
static void async_query(const A&... a)
{
	g_database.query_nothrow(make_query(g_database, a...));
}

template<class... A>
Csql_result query(std::string_view q, const A&... a)
{
	return query(g_database, q, a...);
}

static void sig_handler(int v)
{
	if (v == SIGTERM)
		g_sig_term = true;
}

class tcp_listen_socket_t : public client_t
{
public:
	tcp_listen_socket_t(const Csocket& s)
	{
		m_s = s;
	}

	virtual void process_events(int)
	{
		accept(m_s);
	}
};

class udp_listen_socket_t : public client_t
{
public:
	udp_listen_socket_t(const Csocket& s)
	{
		m_s = s;
	}

	virtual void process_events(int events)
	{
		if (events & EPOLLIN)
			Ctransaction(m_s).recv();
	}
};

static bool is_ipv4(std::array<char, 16> v)
{
	return v[0] == 0
		&& v[1] == 0
		&& v[2] == 0
		&& v[3] == 0
		&& v[4] == 0
		&& v[5] == 0
		&& v[6] == -1
		&& v[7] == -1
		&& v[8] == 0
		&& v[9] == 0
		&& v[10] == 0
		&& v[11] == 0;
}

string to_sql(std::array<char, 16> v)
{
	return is_ipv4(v) ? string(&v[12], 4) : string(&v[0], 16);
}

const config_t& srv_config()
{
	return g_config;
}

const torrent_t* find_torrent(string_view info_hash)
{
	return find_ptr(g_torrents, to_array<char, 20>(info_hash));
}

user_t* find_user_by_uid(int v)
{
	return find_ptr(g_users, v);
}

long long srv_secret()
{
	return g_secret;
}

stats_t& srv_stats()
{
	return g_stats;
}

time_t srv_time()
{
	return g_time;
}

void read_config()
{
	try
	{
		config_t config;
		for (auto row : query("select name, value from @config where value is not null"))
		{
			if (config.set(row[0], string_view(row[1])))
				cerr << "unknown config name: " << row[0] << endl;
		}
		config.load(g_conf_file);
		if (config.torrent_pass_private_key_.empty())
		{
			config.torrent_pass_private_key_ = generate_random_string(27);
			query("insert into @config (name, value) values ('torrent_pass_private_key', ?)", config.torrent_pass_private_key_);
		}
		g_config = config;
		g_database.set_name("completed", g_config.column_torrents_completed_);
		g_database.set_name("leechers", g_config.column_torrents_leechers_);
		g_database.set_name("seeders", g_config.column_torrents_seeders_);
		g_database.set_name("tid", g_config.column_torrents_tid_);
		g_database.set_name("uid", g_config.column_users_uid_);
		g_database.set_name("announce_log", g_config.table_announce_log_.empty() ? g_table_prefix + "announce_log" : g_config.table_announce_log_);
		g_database.set_name("scrape_log", g_config.table_scrape_log_.empty() ? g_table_prefix + "scrape_log" : g_config.table_scrape_log_);
		g_database.set_name("torrents", g_config.table_torrents_.empty() ? g_table_prefix + "torrents" : g_config.table_torrents_);
		g_database.set_name("torrents_users", g_config.table_torrents_users_.empty() ? g_table_prefix + "peers" : g_config.table_torrents_users_);
		g_database.set_name("users", g_config.table_users_.empty() ? g_table_prefix + "users" : g_config.table_users_);
	}
	catch (bad_query&)
	{
	}
	if (g_config.listen_ipas_.empty())
		g_config.listen_ipas_.insert(htonl(INADDR_ANY));
	if (g_config.listen_ports_.empty())
		g_config.listen_ports_.insert(2710);
	g_read_config_time = srv_time();
}

void read_db_torrents()
{
	g_read_db_torrents_time = srv_time();
	try
	{
		if (!g_config.auto_register_)
		{
			for (auto row : query(g_database, "select info_hash, @tid from @torrents where flags & 1"))
			{
				g_torrents.erase(to_array<char, 20>(row[0]));
				query("delete from @torrents where @tid = ?", row[1]);
			}
		}
		if (g_config.auto_register_ && !g_torrents.empty())
			return;
		for (auto row : query("select info_hash, @completed, @tid, ctime from @torrents where @tid >= ?", g_tid_end))
		{
			g_tid_end = max<int>(g_tid_end, row[2].i() + 1);
			if (row[0].size() != 20 || find_torrent(row[0]))
				continue;
			torrent_t& t = g_torrents[to_array<char, 20>(row[0])];
			if (t.tid)
				continue;
			t.completed = row[1].i();
			t.dirty = false;
			t.tid = row[2].i();
			t.ctime = row[3].i();
		}
	}
	catch (bad_query&)
	{
	}
}

void read_db_users()
{
	g_read_db_users_time = srv_time();
	try
	{
		Csql_query q(g_database, "select @uid");
		if (g_read_users_can_leech)
			q += ", can_leech";
		if (g_read_users_peers_limit)
			q += ", peers_limit";
		if (g_read_users_torrent_pass)
			q += ", torrent_pass";
		q += ", torrent_pass_version";
		if (g_read_users_wait_time)
			q += ", wait_time";
		q += " from @users";
		Csql_result result = q.execute();
		g_users.reserve(result.size());
		for (auto& i : g_users)
			i.second.marked = true;
		g_users_torrent_passes.clear();
		for (auto row : result)
		{
			user_t& user = g_users[row[0].i()];
			user.marked = false;
			int c = 0;
			user.uid = row[c++].i();
			if (g_read_users_can_leech)
				user.can_leech = row[c++].i();
			if (g_read_users_peers_limit)
				user.peers_limit = row[c++].i();
			if (g_read_users_torrent_pass)
			{
				if (row[c].size() == 32)
					g_users_torrent_passes[to_array<char, 32>(row[c])] = &user;
				c++;
			}
			user.torrent_pass_version = row[c++].i();
			if (g_read_users_wait_time)
				user.wait_time = row[c++].i();
		}
		for (auto i = g_users.begin(); i != g_users.end(); )
		{
			if (i->second.marked)
				g_users.erase(i++);
			else
				i++;
		}
	}
	catch (bad_query&)
	{
	}
}

string_view db_name(string_view v)
{
	return g_database.name(v);
}

void write_db_torrents()
{
	g_write_db_torrents_time = srv_time();
	try
	{
		string buffer;
		while (1)
		{
			for (auto& i : g_torrents)
			{
				torrent_t& t = i.second;
				if (!t.dirty)
					continue;
				if (!t.tid)
				{
					query("insert into @torrents (info_hash, mtime, ctime) values (?, unix_timestamp(), unix_timestamp())", i.first);
					t.tid = g_database.insert_id();
				}
				buffer += make_query(g_database, "(?,?,?,?),", t.leechers, t.seeders, t.completed, t.tid);
				t.dirty = false;
				if (buffer.size() > 255 << 10)
					break;
			}
			if (buffer.empty())
				break;
			buffer.pop_back();
			async_query("insert ignore into @torrents (@leechers, @seeders, @completed, @tid) values ?"
				" on duplicate key update"
				"  @leechers = values(@leechers),"
				"  @seeders = values(@seeders),"
				"  @completed = values(@completed),"
				"  mtime = unix_timestamp()", raw(buffer));
			buffer.clear();
		}
	}
	catch (bad_query&)
	{
	}
	if (!g_announce_log_buffer.empty())
	{
		g_announce_log_buffer.pop_back();
		async_query("insert delayed into @announce_log (ipv6, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values ?", raw(g_announce_log_buffer));
		g_announce_log_buffer.erase();
	}
	if (!g_scrape_log_buffer.empty())
	{
		g_scrape_log_buffer.pop_back();
		async_query("insert delayed into @scrape_log (ipv6, uid, mtime) values ", raw(g_scrape_log_buffer));
		g_scrape_log_buffer.erase();
	}
}

void write_db_users()
{
	g_write_db_users_time = srv_time();
	if (!g_torrents_users_updates_buffer.empty())
	{
		g_torrents_users_updates_buffer.pop_back();
		async_query("insert ignore into @torrents_users (active, completed, downloaded, `left`, uploaded, mtime, tid, uid) values ?"
			" on duplicate key update"
			"  active = values(active),"
			"  completed = completed + values(completed),"
			"  downloaded = downloaded + values(downloaded),"
			"  `left` = values(`left`),"
			"  uploaded = uploaded + values(uploaded),"
			"  mtime = values(mtime)", raw(g_torrents_users_updates_buffer));
		g_torrents_users_updates_buffer.erase();
	}
	async_query("update @torrents_users set active = 0 where mtime < unix_timestamp() - 60 * 60");
	if (!g_users_updates_buffer.empty())
	{
		g_users_updates_buffer.pop_back();
		async_query("insert ignore into @users (downloaded, uploaded, @uid) values ?"
			" on duplicate key update"
			"  downloaded = downloaded + values(downloaded),"
			"  uploaded = uploaded + values(uploaded)", raw(g_users_updates_buffer));
		g_users_updates_buffer.erase();
	}
}

int test_sql()
{
	try
	{
		mysql_get_server_version(g_database);
		if (g_config.log_announce_)
			query("select id, ipv6, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime from @announce_log where 0");
		query("select name, value from @config where 0");
		query("select @tid, info_hash, @leechers, @seeders, flags, mtime, ctime from @torrents where 0");
		query("select tid, uid, active, completed, downloaded, `left`, uploaded from @torrents_users where 0");
		if (g_config.log_scrape_)
			query("select id, ipv6, uid, mtime from @scrape_log where 0");
		query("select @uid, torrent_pass_version, downloaded, uploaded from @users where 0");
		// query("update @torrents_users set active = 0");
		g_read_users_can_leech = query("show columns from @users like 'can_leech'").size();
		g_read_users_peers_limit = query("show columns from @users like 'peers_limit'").size();
		g_read_users_torrent_pass = query("show columns from @users like 'torrent_pass'").size();
		g_read_users_wait_time = query("show columns from @users like 'wait_time'").size();
		return 0;
	}
	catch (bad_query&)
	{
	}
	return 1;
}

void clean_up(torrent_t& t, time_t time)
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
	for (auto& i : g_torrents)
		clean_up(i.second, srv_time() - static_cast<int>(1.5 * g_config.announce_interval_));
	g_clean_up_time = srv_time();
}

int srv_run()
{
	for (int i = 0; i < 8; i++)
		g_secret = g_secret << 8 ^ rand();
	g_database.set_name("config", g_table_prefix + "config");

	read_config();
	if (test_sql())
		return 1;
	if (g_epoll.create() == -1)
	{
		cerr << "epoll_create failed\n";
		return 1;
	}
	list<tcp_listen_socket_t> lt;
	list<udp_listen_socket_t> lu;
#ifdef XBT_SYSTEMD
	{
		int count = sd_listen_fds(true);
		for (int i = 0; i < count; i++)
		{
			Csocket s(SD_LISTEN_FDS_START + i);
			if (s.blocking(false))
			{
				cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << '\n';
				return 1;
			}
			lt.push_back(tcp_listen_socket_t(s));
			if (g_epoll.ctl(EPOLL_CTL_ADD, s, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP, &lt.back()))
				return 1;
		}
	}
#endif
	if (lt.empty())
	{
		for (auto& j : g_config.listen_ipas_)
		{
			for (auto& i : g_config.listen_ports_)
			{
				Csocket l;
				if (l.open(SOCK_STREAM) == INVALID_SOCKET)
					cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
					l.bind(j, htons(i)))
					cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				else if (l.listen())
					cerr << "listen failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				else
				{
#ifdef SO_ACCEPTFILTER
					accept_filter_arg afa;
					bzero(&afa, sizeof(afa));
					strcpy(afa.af_name, "httpready");
					if (l.setsockopt(SOL_SOCKET, SO_ACCEPTFILTER, &afa, sizeof(afa)))
						cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#elif 0 // TCP_DEFER_ACCEPT
					if (l.setsockopt(IPPROTO_TCP, TCP_DEFER_ACCEPT, 90))
						cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#endif
					lt.push_back(tcp_listen_socket_t(l));
					if (!g_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP, &lt.back()))
						continue;
				}
				return 1;
			}
			for (auto& i : g_config.listen_ports_)
			{
				Csocket l;
				if (l.open(SOCK_DGRAM) == INVALID_SOCKET)
					cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
					l.bind(j, htons(i)))
					cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				else
				{
					lu.push_back(udp_listen_socket_t(l));
					if (!g_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLPRI | EPOLLERR | EPOLLHUP, &lu.back()))
						continue;
				}
				return 1;
			}
		}
	}
	query("update @torrents set @leechers = 0, @seeders = 0");
	clean_up();
	read_db_torrents();
	read_db_users();
	write_db_torrents();
	write_db_users();
#ifndef NDEBUG
	// test_announce();
#endif
#ifndef WIN32
	if (g_config.daemon_)
	{
		if (daemon(true, false))
			cerr << "daemon failed\n";
		ofstream(g_config.pid_file_.c_str()) << getpid() << endl;
		struct sigaction act;
		act.sa_handler = sig_handler;
		sigemptyset(&act.sa_mask);
		act.sa_flags = 0;
		if (sigaction(SIGTERM, &act, NULL))
			cerr << "sigaction failed\n";
		act.sa_handler = SIG_IGN;
		if (sigaction(SIGPIPE, &act, NULL))
			cerr << "sigaction failed\n";
	}
#endif
#ifdef EPOLL
	array<epoll_event, 64> events;
#else
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
#endif
	while (!g_sig_term)
	{
#ifdef EPOLL
		int r = g_epoll.wait(events.data(), events.size(), 5000);
		if (r == -1)
			cerr << "epoll_wait failed: " << errno << endl;
		else
		{
			time_t prev_time = g_time;
			g_time = ::time(NULL);
			for (int i = 0; i < r; i++)
				reinterpret_cast<client_t*>(events[i].data.ptr)->process_events(events[i].events);
			if (g_time == prev_time)
				continue;
			for (auto i = g_connections.begin(); i != g_connections.end(); )
			{
				if (i->run())
					i = g_connections.erase(i);
				else
					i++;
			}
		}
#else
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		int n = 0;
		for (auto& i : g_connections)
		{
			int z = i.pre_select(&fd_read_set, &fd_write_set);
			n = max(n, z);
		}
		for (auto& i : lt)
		{
			FD_SET(i.s(), &fd_read_set);
			n = max<int>(n, i.s());
		}
		for (auto& i : lu)
		{
			FD_SET(i.s(), &fd_read_set);
			n = max<int>(n, i.s());
		}
		timeval tv;
		tv.tv_sec = 5;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << endl;
		else
		{
			g_time = ::time(NULL);
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
			for (auto i = g_connections.begin(); i != g_connections.end(); )
			{
				if (i->post_select(&fd_read_set, &fd_write_set))
					i = g_connections.erase(i);
				else
					i++;
			}
		}
#endif
		if (srv_time() - g_read_config_time > g_config.read_config_interval_)
			read_config();
		else if (srv_time() - g_clean_up_time > g_config.clean_up_interval_)
			clean_up();
		else if (srv_time() - g_read_db_torrents_time > g_config.read_db_interval_)
			read_db_torrents();
		else if (srv_time() - g_read_db_users_time > g_config.read_db_interval_)
			read_db_users();
		else if (g_config.write_db_interval_ && srv_time() - g_write_db_torrents_time > g_config.write_db_interval_)
			write_db_torrents();
		else if (g_config.write_db_interval_ && srv_time() - g_write_db_users_time > g_config.write_db_interval_)
			write_db_users();
	}
	write_db_torrents();
	write_db_users();
	unlink(g_config.pid_file_.c_str());
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
				g_stats.accept_errors++;
				cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << endl;
				xbt_syslog("accept failed: " + Csocket::error2a(WSAGetLastError()));
			}
			break;
		}
		g_stats.accepted_tcp++;
#ifndef SOCK_NONBLOCK
		if (s.blocking(false))
			cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << endl;
#endif
		unique_ptr<connection_t> connection(new connection_t(s, a));
		connection->process_events(EPOLLIN);
		if (connection->s() != INVALID_SOCKET)
		{
			g_stats.slow_tcp++;
			g_connections.push_back(connection.release());
			g_epoll.ctl(EPOLL_CTL_ADD, g_connections.back().s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &g_connections.back());
		}
	}
}

string srv_insert_peer(const tracker_input_t& in, bool udp, user_t* user)
{
	if (g_config.log_announce_)
	{
		g_announce_log_buffer += make_query(g_database, "(?,?,?,?,?,?,?,?,?,?),",
			to_sql(in.ipv6_),
			ntohs(in.port_),
			int(in.event_),
			in.info_hash_,
			in.peer_id_,
			in.downloaded_,
			in.left_,
			in.uploaded_,
			user ? user->uid : 0,
			srv_time());
	}
	if (!g_config.offline_message_.empty())
		return g_config.offline_message_;
	if (0)
		return bts_banned_client;
	if (!g_config.anonymous_announce_ && !user)
		return bts_unregistered_torrent_pass;
	if (!g_config.auto_register_ && !find_torrent(in.info_hash_))
		return bts_unregistered_torrent;
	if (in.left_ && user && !user->can_leech)
		return bts_can_not_leech;
	torrent_t& t = g_torrents[to_array<char, 20>(in.info_hash_)];
	if (!t.ctime)
		t.ctime = srv_time();
	if (in.left_ && user && user->wait_time && t.ctime + user->wait_time > srv_time())
		return bts_wait_time;
	peer_t* p = find_ptr(t.peers, in.peer_id_);
	if (p)
		(p->left ? t.leechers : t.seeders)--;
	else if (in.left_ && user && user->peers_limit)
	{
		int c = 0;
		for (auto& j : t.peers)
			c += j.second.left && j.second.uid == user->uid;
		if (c >= user->peers_limit)
			return bts_peers_limit_reached;
	}
	if (user && t.tid)
	{
		long long downloaded = 0;
		long long uploaded = 0;
		if (p
			&& p->uid == user->uid
			&& in.downloaded_ >= p->downloaded
			&& in.uploaded_ >= p->uploaded)
		{
			downloaded = in.downloaded_ - p->downloaded;
			uploaded = in.uploaded_ - p->uploaded;
		}
		g_torrents_users_updates_buffer += make_query(g_database, "(?,?,?,?,?,?,?,?),",
			in.event_ != tracker_input_t::e_stopped,
			in.event_ == tracker_input_t::e_completed,
			downloaded,
			in.left_,
			uploaded,
			srv_time(),
			t.tid,
			user->uid);
		if (downloaded || uploaded)
			g_users_updates_buffer += make_query(g_database, "(?,?,?),", downloaded, uploaded, user->uid);
		if (g_torrents_users_updates_buffer.size() > 255 << 10)
			write_db_users();
	}
	if (in.event_ == tracker_input_t::e_stopped)
		t.peers.erase(in.peer_id_);
	else
	{
		peer_t& peer = p ? *p : t.peers[in.peer_id_];
		peer.downloaded = in.downloaded_;
		peer.left = in.left_;
		peer.port = in.port_;
		peer.uid = user ? user->uid : 0;
		peer.uploaded = in.uploaded_;
		(peer.left ? t.leechers : t.seeders)++;
		peer.mtime = srv_time();
		if (is_ipv4(in.ipv6_))
			memcpy(peer.ipv4.data(), &in.ipv6_[12], 4);
		else
			peer.ipv6 = in.ipv6_;
	}
	if (in.event_ == tracker_input_t::e_completed)
		t.completed++;
	(udp ? g_stats.announced_udp : g_stats.announced_http)++;
	t.dirty = true;
	return string();
}

void torrent_t::select_peers(mutable_str_ref& d, const tracker_input_t& ti) const
{
	if (ti.event_ == tracker_input_t::e_stopped)
		return;
	vector<array<char, 6>> candidates;
	candidates.reserve(peers.size());
	for (auto& i : peers)
	{
		if (!ti.left_ && !i.second.left)
			continue;
		array<char, 6> v;
		memcpy(&v[0], &i.second.ipv4, 4);
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
	while (c--)
	{
		int i = rand() % candidates.size();
		memcpy(d.data(), candidates[i]);
		d.advance_begin(6);
		candidates[i] = candidates.back();
		candidates.pop_back();
	}
}

string srv_select_peers(const tracker_input_t& ti)
{
	const torrent_t* t = find_torrent(ti.info_hash_);
	if (!t)
		return string();
	array<char, 300> peers0;
	mutable_str_ref peers = peers0;
	t->select_peers(peers, ti);
	peers.assign(peers0.data(), peers.data());
	return (boost::format("d8:completei%de10:incompletei%de8:intervali%de12:min intervali%de5:peers%d:%se")
		% t->seeders % t->leechers % g_config.announce_interval_ % g_config.announce_interval_ % peers.size() % peers).str();
}

string srv_scrape(const tracker_input_t& ti, user_t* user)
{
	if (g_config.log_scrape_)
		g_scrape_log_buffer += make_query(g_database, "(?,?,?),", to_sql(ti.ipv6_), user ? user->uid : 0, srv_time());
	if (!g_config.anonymous_scrape_ && !user)
		return "d14:failure reason25:unregistered torrent passe";
	string d;
	d += "d5:filesd";
	if (ti.info_hashes_.empty())
	{
		g_stats.scraped_full++;
		d.reserve(90 * g_torrents.size());
		for (auto& i : g_torrents)
		{
			if (i.second.leechers || i.second.seeders)
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % boost::make_iterator_range(i.first) % i.second.seeders % i.second.completed % i.second.leechers).str();
		}
	}
	else
	{
		g_stats.scraped_http++;
		if (ti.info_hashes_.size() > 1)
			g_stats.scraped_multi++;
		for (auto& j : ti.info_hashes_)
		{
			if (const torrent_t* i = find_torrent(j))
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % j % i->seeders % i->completed % i->leechers).str();
		}
	}
	d += "e";
	if (g_config.scrape_interval_)
		d += (boost::format("5:flagsd20:min_request_intervali%dee") % g_config.scrape_interval_).str();
	d += "e";
	return d;
}

void debug(const torrent_t& t, string& os)
{
	os << "<tr><th>IPv4<th>IPv6<th>Port<th>UID<th>Seeder<th>Modified<th>Peer ID";
	for (auto& i : t.peers)
	{
		os << "<tr>"
			<< "<td>" << Csocket::inet_ntoa(i.second.ipv4)
			<< "<td>" << Csocket::inet_ntoa(i.second.ipv6)
			<< "<td class=ar>" << ntohs(i.second.port)
			<< "<td class=ar>" << i.second.uid
			<< "<td class=ar>" << !i.second.left
			<< "<td class=ar>" << duration2a(srv_time() - i.second.mtime) << " ago"
			<< "<td>" << hex_encode(i.first);
	}
}

string srv_debug(const tracker_input_t& ti)
{
	string os;
	os << "<!DOCTYPE HTML><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	os << "<table>";
	if (ti.info_hash_.empty())
	{
		for (auto& i : g_torrents)
		{
			if (!i.second.leechers && !i.second.seeders)
				continue;
			os << "<tr><td class=ar>" << i.second.tid
				<< "<td><a href=\"?info_hash=" << uri_encode(i.first) << "\">" << hex_encode(i.first) << "</a>"
				<< "<td>" << (i.second.dirty ? '*' : ' ')
				<< "<td class=ar>" << i.second.leechers
				<< "<td class=ar>" << i.second.seeders;
		}
	}
	else if (const torrent_t* i = find_torrent(ti.info_hash_))
		debug(*i, os);
	os << "</table>";
	return os;
}

string srv_statistics()
{
	string os;
	os << "<!DOCTYPE HTML><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	os << "<style>.ar { text-align: right }</style>";
	long long leechers = 0;
	long long seeders = 0;
	int torrents = 0;
	for (auto& i : g_torrents)
	{
		leechers += i.second.leechers;
		seeders += i.second.seeders;
		torrents += i.second.leechers || i.second.seeders;
	}
	int peers = leechers + seeders;
	time_t t = srv_time();
	time_t up_time = max<time_t>(1, t - g_stats.start_time);
	os << "<table>"
		<< "<tr><td>peers<td class=ar>" << peers;
	if (peers)
	{
		os << "<tr><td>seeders<td class=ar>" << seeders << "<td class=ar>" << seeders * 100 / peers << " %"
			<< "<tr><td>leechers<td class=ar>" << leechers << "<td class=ar>" << leechers * 100 / peers << " %";
	}
	os << "<tr><td>torrents<td class=ar>" << torrents
		<< "<tr><td>"
		<< "<tr><td>accepted tcp<td class=ar>" << g_stats.accepted_tcp << "<td class=ar>" << g_stats.accepted_tcp / up_time << " /s"
		<< "<tr><td>slow tcp<td class=ar>" << g_stats.slow_tcp << "<td class=ar>" << g_stats.slow_tcp / up_time << " /s"
		<< "<tr><td>rejected tcp<td class=ar>" << g_stats.rejected_tcp
		<< "<tr><td>accept errors<td class=ar>" << g_stats.accept_errors
		<< "<tr><td>received udp<td class=ar>" << g_stats.received_udp << "<td class=ar>" << g_stats.received_udp / up_time << " /s"
		<< "<tr><td>sent udp<td class=ar>" << g_stats.sent_udp << "<td class=ar>" << g_stats.sent_udp / up_time << " /s";
	if (g_stats.announced())
	{
		os << "<tr><td>announced<td class=ar>" << g_stats.announced() << "<td class=ar>" << g_stats.announced() * 100 / g_stats.accepted_tcp << " %"
			<< "<tr><td>announced http <td class=ar>" << g_stats.announced_http << "<td class=ar>" << g_stats.announced_http * 100 / g_stats.announced() << " %"
			<< "<tr><td>announced udp<td class=ar>" << g_stats.announced_udp << "<td class=ar>" << g_stats.announced_udp * 100 / g_stats.announced() << " %";
	}
	os << "<tr><td>scraped full<td class=ar>" << g_stats.scraped_full;
	os << "<tr><td>scraped multi<td class=ar>" << g_stats.scraped_multi;
	if (g_stats.scraped())
	{
		os << "<tr><td>scraped<td class=ar>" << g_stats.scraped() << "<td class=ar>" << g_stats.scraped() * 100 / g_stats.accepted_tcp << " %"
			<< "<tr><td>scraped http<td class=ar>" << g_stats.scraped_http << "<td class=ar>" << g_stats.scraped_http * 100 / g_stats.scraped() << " %"
			<< "<tr><td>scraped udp<td class=ar>" << g_stats.scraped_udp << "<td class=ar>" << g_stats.scraped_udp * 100 / g_stats.scraped() << " %";
	}
	os << "<tr><td>"
		<< "<tr><td>up time<td class=ar>" << duration2a(up_time)
		<< "<tr><td>"
		<< "<tr><td>anonymous announce<td class=ar>" << g_config.anonymous_announce_
		<< "<tr><td>anonymous scrape<td class=ar>" << g_config.anonymous_scrape_
		<< "<tr><td>auto register<td class=ar>" << g_config.auto_register_
		<< "<tr><td>full scrape<td class=ar>" << g_config.full_scrape_
		<< "<tr><td>read config time<td class=ar>" << t - g_read_config_time << " / " << g_config.read_config_interval_
		<< "<tr><td>clean up time<td class=ar>" << t - g_clean_up_time << " / " << g_config.clean_up_interval_
		<< "<tr><td>read db files time<td class=ar>" << t - g_read_db_torrents_time << " / " << g_config.read_db_interval_
		<< "<tr><td>read db users time<td class=ar>" << t - g_read_db_users_time << " / " << g_config.read_db_interval_
		<< "<tr><td>write db files time<td class=ar>" << t - g_write_db_torrents_time << " / " << g_config.write_db_interval_
		<< "<tr><td>write db users time<td class=ar>" << t - g_write_db_users_time << " / " << g_config.write_db_interval_;
	os << "</table>";
	return os;
}

user_t* find_user_by_torrent_pass(std::string_view v, std::string_view info_hash)
{
	if (v.size() != 32)
		return NULL;
	if (user_t* user = find_user_by_uid(read_int(4, hex_decode(v.substr(0, 8)))))
	{
		if (Csha1((boost::format("%s %d %d %s") % g_config.torrent_pass_private_key_ % user->torrent_pass_version % user->uid % info_hash).str()).read().substr(0, 12) == hex_decode(v.substr(8, 24)))
			return user;
	}
	return find_ptr2(g_users_torrent_passes, to_array<char, 32>(v));
}

void srv_term()
{
	g_sig_term = true;
}

void test_announce()
{
	user_t* u = find_ptr(g_users, 1);
	tracker_input_t i;
	i.info_hash_ = "IHIHIHIHIHIHIHIHIHIH";
	memcpy(i.peer_id_.data(), str_ref("PIPIPIPIPIPIPIPIPIPI"));
	i.ipv6_ = {};
	i.port_ = 54321;
	cout << srv_insert_peer(i, false, u) << endl;
	write_db_torrents();
	write_db_users();
	g_time++;
	i.uploaded_ = 1 << 30;
	i.downloaded_ = 1 << 20;
	cout << srv_insert_peer(i, false, u) << endl;
	write_db_torrents();
	write_db_users();
	g_time += 3600;
	clean_up();
	write_db_torrents();
	write_db_users();
}

int main1()
{
	srand(static_cast<int>(time(NULL)));
	config_t config;
	if (config.load(g_conf_file))
#ifdef WIN32
	{
		char b[MAX_PATH];
		*b = 0;
		GetModuleFileName(NULL, b, MAX_PATH);
		if (*b)
			strrchr(b, '\\')[1] = 0;
		strcat(b, "xbt_tracker.conf");
		if (config.load(b))
			cerr << "Unable to read " << g_conf_file << endl;
		else
			g_conf_file = b;
	}
#else
		cerr << "Unable to read " << g_conf_file << endl;
#endif
	try
	{
		g_database.open(config.mysql_host_, config.mysql_user_, config.mysql_password_, config.mysql_database_);
	}
	catch (bad_query& e)
	{
		cerr << e.what() << endl;
		return 1;
	}
	if (!config.query_log_.empty())
	{
		static ofstream os(config.query_log_.c_str());
		g_database.set_query_log(&os);
	}
	g_table_prefix = config.mysql_table_prefix_;
	return srv_run();
}

#ifdef WIN32
static SERVICE_STATUS g_service_status;
static SERVICE_STATUS_HANDLE gh_service_status;

void WINAPI nt_service_handler(DWORD op)
{
	switch (op)
	{
	case SERVICE_CONTROL_STOP:
		g_service_status.dwCurrentState = SERVICE_STOP_PENDING;
		SetServiceStatus(gh_service_status, &g_service_status);
		srv_term();
		break;
	}
	SetServiceStatus(gh_service_status, &g_service_status);
}

void WINAPI nt_service_main(DWORD argc, LPTSTR* argv)
{
	g_service_status.dwCheckPoint = 0;
	g_service_status.dwControlsAccepted = SERVICE_ACCEPT_STOP;
	g_service_status.dwCurrentState = SERVICE_START_PENDING;
	g_service_status.dwServiceSpecificExitCode = 0;
	g_service_status.dwServiceType = SERVICE_WIN32_OWN_PROCESS;
	g_service_status.dwWaitHint = 0;
	g_service_status.dwWin32ExitCode = NO_ERROR;
	if (!(gh_service_status = RegisterServiceCtrlHandler(g_service_name, nt_service_handler)))
		return;
	SetServiceStatus(gh_service_status, &g_service_status);
	g_service_status.dwCurrentState = SERVICE_RUNNING;
	SetServiceStatus(gh_service_status, &g_service_status);
	main1();
	g_service_status.dwCurrentState = SERVICE_STOPPED;
	SetServiceStatus(gh_service_status, &g_service_status);
}
#endif

int main(int argc, char* argv[])
{
#ifdef WIN32
	if (argc >= 2)
	{
		if (!strcmp(argv[1], "--install"))
		{
			if (nt_service_install(g_service_name))
			{
				cerr << "Failed to install service " << g_service_name << ".\n";
				return 1;
			}
			cout << "Service " << g_service_name << " has been installed.\n";
			return 0;
		}
		else if (!strcmp(argv[1], "--uninstall"))
		{
			if (nt_service_uninstall(g_service_name))
			{
				cerr << "Failed to uninstall service " << g_service_name << ".\n";
				return 1;
			}
			cout << "Service " << g_service_name << " has been uninstalled.\n";
			return 0;
		}
		else if (!strcmp(argv[1], "--conf_file") && argc >= 3)
			g_conf_file = argv[2];
		else
			return 1;
	}
#ifdef NDEBUG
	SERVICE_TABLE_ENTRY st[] =
	{
		{ "", nt_service_main },
		{ NULL, NULL }
	};
	if (StartServiceCtrlDispatcher(st))
		return 0;
	if (GetLastError() != ERROR_CALL_NOT_IMPLEMENTED
		&& GetLastError() != ERROR_FAILED_SERVICE_CONTROLLER_CONNECT)
		return 1;
#endif
#else
	if (argc >= 2)
	{
		if (!strcmp(argv[1], "--conf_file") && argc >= 3)
			g_conf_file = argv[2];
		else
		{
			cerr << "  --conf_file arg (=xbt_tracker.conf)\n";
			return 1;
		}
	}
#endif
	return main1();
}
