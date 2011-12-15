#include "stdafx.h"
#include "server.h"

#include <bt_strings.h>
#include <bvalue.h>
#include "transaction.h"

static volatile bool g_sig_term = false;

Cserver::Cserver(Cdatabase& database, const std::string& table_prefix, bool use_sql, const std::string& conf_file):
	m_database(database)
{
	m_fid_end = 0;

	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
	m_conf_file = conf_file;
	m_database.set_name("config", table_prefix + "config");
	m_table_prefix = table_prefix;
	m_time = ::time(NULL);
	m_use_sql = use_sql;
}

int Cserver::run()
{
	read_config();
	if (test_sql())
		return 1;
	if (m_epoll.create(1 << 10) == -1)
	{
		std::cerr << "epoll_create failed" << std::endl;
		return 1;
	}
	t_tcp_sockets lt;
	t_udp_sockets lu;
	BOOST_FOREACH(Cconfig::t_listen_ipas::const_reference j, m_config.m_listen_ipas)
	{
		BOOST_FOREACH(Cconfig::t_listen_ports::const_reference i, m_config.m_listen_ports)
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
#elif TCP_DEFER_ACCEPT
				if (l.setsockopt(IPPROTO_TCP, TCP_DEFER_ACCEPT, 90))
					std::cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
#endif
				lt.push_back(Ctcp_listen_socket(this, l));
				if (!m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP, &lt.back()))
					continue;
			}
			return 1;
		}
		BOOST_FOREACH(Cconfig::t_listen_ports::const_reference i, m_config.m_listen_ports)
		{
			Csocket l;
			if (l.open(SOCK_DGRAM) == INVALID_SOCKET)
				std::cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(j, htons(i)))
				std::cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else
			{
				lu.push_back(Cudp_listen_socket(this, l));
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
	boost::array<epoll_event, 64> events;
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
			int prev_time = m_time;
			m_time = ::time(NULL);
			for (int i = 0; i < r; i++)
				reinterpret_cast<Cclient*>(events[i].data.ptr)->process_events(events[i].events);
			if (m_time == prev_time)
				continue;
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
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
		BOOST_FOREACH(t_connections::reference i, m_connections)
		{
			int z = i.pre_select(&fd_read_set, &fd_write_set);
			n = std::max(n, z);
		}
		BOOST_FOREACH(t_tcp_sockets::reference i, lt)
		{
			FD_SET(i.s(), &fd_read_set);
			n = std::max<int>(n, i.s());
		}
		BOOST_FOREACH(t_udp_sockets::reference i, lu)
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
			BOOST_FOREACH(t_tcp_sockets::reference i, lt)
			{
				if (FD_ISSET(i.s(), &fd_read_set))
					accept(i.s());
			}
			BOOST_FOREACH(t_udp_sockets::reference i, lu)
			{
				if (FD_ISSET(i.s(), &fd_read_set))
					Ctransaction(*this, i.s()).recv();
			}
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->post_select(&fd_read_set, &fd_write_set))
					i = m_connections.erase(i);
				else
					i++;
			}
		}
#endif
		if (time() - m_read_config_time > m_config.m_read_config_interval)
			read_config();
		else if (time() - m_clean_up_time > m_config.m_clean_up_interval)
			clean_up();
		else if (time() - m_read_db_torrents_time > m_config.m_read_db_interval)
			read_db_torrents();
		else if (time() - m_read_db_users_time > m_config.m_read_db_interval)
			read_db_users();
		else if (m_config.m_write_db_interval && time() - m_write_db_torrents_time > m_config.m_write_db_interval)
			write_db_torrents();
		else if (m_config.m_write_db_interval && time() - m_write_db_users_time > m_config.m_write_db_interval)
			write_db_users();
	}
	write_db_torrents();
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
      {
        m_stats.accept_errors++;
				std::cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
      }
			break;
		}
		m_stats.accepted_tcp++;
		if (s.blocking(false))
			std::cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
		std::auto_ptr<Cconnection> connection(new Cconnection(this, s, a));
		connection->process_events(EPOLLIN);
		if (connection->s() != INVALID_SOCKET)
		{
  		m_stats.slow_tcp++;
			m_connections.push_back(connection.release());
			m_epoll.ctl(EPOLL_CTL_ADD, m_connections.back().s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_connections.back());
		}
	}
}

std::string Cserver::insert_peer(const Ctracker_input& v, bool udp, t_user* user)
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
			(time())
			.read();
	}
	if (!m_config.m_offline_message.empty())
		return m_config.m_offline_message;
	if (!m_config.m_anonymous_announce && !user)
		return bts_unregistered_torrent_pass;
	if (!m_config.m_auto_register && !torrent(v.m_info_hash))
		return bts_unregistered_torrent;
	if (v.m_left && user && !user->can_leech)
		return bts_can_not_leech;
	t_torrent& file = m_torrents[v.m_info_hash];
	if (!file.ctime)
		file.ctime = time();
	if (v.m_left && user && user->wait_time && file.ctime + user->wait_time > time())
		return bts_wait_time;
	t_peers::key_type peer_key(v.m_ipa, user ? user->uid : 0);
	t_peer* i = find_ptr(file.peers, peer_key);
	if (i)
	{
		(i->left ? file.leechers : file.seeders)--;
		if (t_user* old_user = find_user_by_uid(i->uid))
			(i->left ? old_user->incompletes : old_user->completes)--;
	}
	else if (v.m_left && user && user->torrents_limit && user->incompletes >= user->torrents_limit)
		return bts_torrents_limit_reached;
	else if (v.m_left && user && user->peers_limit)
	{
		int c = 0;
		BOOST_FOREACH(t_peers::reference j, file.peers)
			c += j.second.left && j.second.uid == user->uid;
		if (c >= user->peers_limit)
			return bts_peers_limit_reached;
	}
	if (m_use_sql && user && file.fid)
	{
		long long downloaded = 0;
		long long uploaded = 0;
		if (i
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
			(time())
			(file.fid)
			(user->uid)
			.read();
		if (downloaded || uploaded)
			m_users_updates_buffer += Csql_query(m_database, "(?,?,?),")(downloaded)(uploaded)(user->uid).read();
	}
	if (v.m_event == Ctracker_input::e_stopped)
		file.peers.erase(peer_key);
	else
	{
		t_peer& peer = file.peers[peer_key];
		peer.downloaded = v.m_downloaded;
		peer.left = v.m_left;
		std::copy(v.m_peer_id.begin(), v.m_peer_id.end(), peer.peer_id.begin());
		peer.port = v.m_port;
		peer.uid = user ? user->uid : 0;
		peer.uploaded = v.m_uploaded;
		(peer.left ? file.leechers : file.seeders)++;
		if (user)
			(peer.left ? user->incompletes : user->completes)++;
		peer.mtime = time();
	}
	if (v.m_event == Ctracker_input::e_completed)
		file.completed++;
	(udp ? m_stats.announced_udp : m_stats.announced_http)++;
	file.dirty = true;
	return "";
}

std::string Cserver::t_torrent::select_peers(const Ctracker_input& ti) const
{
	if (ti.m_event == Ctracker_input::e_stopped)
		return "";

	typedef std::vector<boost::array<char, 6> > t_candidates;

	t_candidates candidates;
	BOOST_FOREACH(t_peers::const_reference i, peers)
	{
		if (!ti.m_left && !i.second.left)
			continue;
		boost::array<char, 6> v;
		memcpy(&v.front(), &i.first.host_, 4);
		memcpy(&v.front() + 4, &i.second.port, 2);
		candidates.push_back(v);
	}
	size_t c = ti.m_num_want < 0 ? 50 : std::min(ti.m_num_want, 50);
	std::string d;
	d.reserve(300);
	if (candidates.size() > c)
	{
		while (c--)
		{
			int i = rand() % candidates.size();
			d.append(candidates[i].begin(), candidates[i].end());
			candidates[i] = candidates.back();
			candidates.pop_back();
		}
	}
	else
	{
		BOOST_FOREACH(t_candidates::reference i, candidates)
			d.append(i.begin(), i.end());
	}
	return d;
}

shared_data Cserver::select_peers(const Ctracker_input& ti) const
{
	const t_torrent* f = torrent(ti.m_info_hash);
	if (!f)
		return shared_data();
	std::string peers = f->select_peers(ti);
	return make_shared_data((boost::format("d8:completei%de10:incompletei%de8:intervali%de12:min intervali%de5:peers%d:%se")
		% f->seeders % f->leechers % config().m_announce_interval % config().m_announce_interval % peers.size() % peers).str());
}

void Cserver::t_torrent::clean_up(time_t t, Cserver& server)
{
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (i->second.mtime < t)
		{
			(i->second.left ? leechers : seeders)--;
			if (t_user* user = server.find_user_by_uid(i->second.uid))
				(i->second.left ? user->incompletes : user->completes)--;
			if (i->second.uid)
				server.m_torrents_users_updates_buffer += Csql_query(server.m_database, "(0,0,0,0,18446744073709551615,0,-1,?,?),")(fid)(i->second.uid).read();
			peers.erase(i++);
			dirty = true;
		}
		else
			i++;
	}
}

void Cserver::clean_up()
{
	BOOST_FOREACH(t_torrents::reference i, m_torrents)
		i.second.clean_up(time() - static_cast<int>(1.5 * m_config.m_announce_interval), *this);
	m_clean_up_time = time();
}

static byte* write_compact_int(byte* w, unsigned int v)
{
	if (v >= 0x200000)
	{
		*w++ = 0xe0 | (v >> 24);
		*w++ = v >> 16;
		*w++ = v >> 8;
	}
	else if (v >= 0x4000)
	{
		*w++ = 0xc0 | (v >> 16);
		*w++ = v >> 8;
	}
	else if (v >= 0x80)
		*w++ = 0x80 | (v >> 8);
	*w++ = v;
	return w;
}

shared_data Cserver::scrape(const Ctracker_input& ti, t_user* user)
{
	if (!m_config.m_anonymous_scrape && !user) 
		return Cbvalue().d(bts_failure_reason, bts_unregistered_torrent_pass).read();
	std::string d;
	d += "d5:filesd";
	if (ti.m_info_hashes.empty())
	{
		if (m_use_sql && m_config.m_log_scrape)
			m_scrape_log_buffer += Csql_query(m_database, "(?,?,?),")(ntohl(ti.m_ipa))(user ? user->uid : 0)(time()).read();
		m_stats.scraped_full++;
		if (ti.m_compact)
		{
			shared_data d(32 * m_torrents.size() + 1);
			byte* w = d.data();
			*w++ = 'x';
			BOOST_FOREACH(t_torrents::reference i, m_torrents)
			{
				if (!i.second.leechers && !i.second.seeders)
					continue;
				memcpy(w, i.first.data(), i.first.size());
				w += i.first.size();
				w = write_compact_int(w, i.second.seeders);
				w = write_compact_int(w, i.second.leechers);
				w = write_compact_int(w, i.second.completed);
			}
			return d.substr(0, w - d.data());
		}
		d.reserve(90 * m_torrents.size());
		BOOST_FOREACH(t_torrents::reference i, m_torrents)
		{
			if (i.second.leechers || i.second.seeders)
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % i.first % i.second.seeders % i.second.completed % i.second.leechers).str();
		}
	}
	else
	{
		m_stats.scraped_http++;
		BOOST_FOREACH(Ctracker_input::t_info_hashes::const_reference j, ti.m_info_hashes)
		{
			if (t_torrent* i = find_ptr(m_torrents, j))
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % j % i->seeders % i->completed % i->leechers).str();
		}
	}
	d += "e";
	if (m_config.m_scrape_interval)
		d += (boost::format("5:flagsd20:min_request_intervali%dee") % m_config.m_scrape_interval).str();
	d += "e";
	return make_shared_data(d);
}

const std::string& Cserver::db_name(const std::string& v) const
{
	return m_database.name(v);
}

void Cserver::read_db_torrents()
{
	m_read_db_torrents_time = time();
	if (m_use_sql)
		read_db_torrents_sql();
	else if (!m_config.m_auto_register)
	{
		std::set<std::string> new_files;
		std::ifstream is("xbt_torrents.txt");
		for (std::string s; getline(is, s); )
		{
			s = hex_decode(s);
			if (s.size() != 20)
				continue;
			m_torrents[s];
			new_files.insert(s);
		}
		for (t_torrents::iterator i = m_torrents.begin(); i != m_torrents.end(); )
		{
			if (new_files.count(i->first))
				i++;
			else
				m_torrents.erase(i++);
		}
	}
}

void Cserver::read_db_torrents_sql()
{
	try
	{
		if (!m_config.m_auto_register)
		{
			Csql_result result = Csql_query(m_database, "select info_hash, @fid from @files where flags & 1").execute();
			while (Csql_row row = result.fetch_row())
			{
				t_torrents::iterator i = m_torrents.find(row[0].s());
				if (i != m_torrents.end())
				{
					BOOST_FOREACH(t_peers::reference j, i->second.peers)
					{
						if (t_user* user = find_user_by_uid(j.second.uid))
							(j.second.left ? user->incompletes : user->completes)--;
					}
					m_torrents.erase(i);
				}
				Csql_query(m_database, "delete from @files where @fid = ?")(row[1].i()).execute();
			}
		}
		if (m_torrents.empty())
			Csql_query(m_database, "update @files set @leechers = 0, @seeders = 0").execute();
		else if (m_config.m_auto_register)
			return;
		Csql_result result = Csql_query(m_database, "select info_hash, @completed, @fid, ctime from @files where @fid >= ?")(m_fid_end).execute();
		while (Csql_row row = result.fetch_row())
		{
			m_fid_end = std::max(m_fid_end, static_cast<int>(row[2].i()) + 1);
			if (row[0].size() != 20 || torrent(row[0].s()))
				continue;
			t_torrent& file = m_torrents[row[0].s()];
			if (file.fid)
				continue;
			file.completed = row[1].i();
			file.dirty = false;
			file.fid = row[2].i();
			file.ctime = row[3].i();
		}
	}
	catch (Cdatabase::exception&)
	{
	}
}

void Cserver::read_db_users()
{
	m_read_db_users_time = time();
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
		if (m_read_users_torrents_limit)
			q += ", torrents_limit";
		if (m_read_users_wait_time)
			q += ", wait_time";
		q += " from @users";
		Csql_result result = q.execute();
		BOOST_FOREACH(t_users::reference i, m_users)
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
				if (row[c].size())
					m_users_torrent_passes[row[c].s()] = &user;
				c++;
			}
			user.torrent_pass_version = row[c++].i();
			if (m_read_users_torrents_limit)
				user.torrents_limit = row[c++].i();
			if (m_read_users_wait_time)
				user.wait_time = row[c++].i();
		}
		for (t_users::iterator i = m_users.begin(); i != m_users.end(); )
		{
			if (i->second.marked)
				m_users.erase(i++);
			else
				i++;
		}
	}
	catch (Cdatabase::exception&)
	{
	}
}

void Cserver::write_db_torrents()
{
	m_write_db_torrents_time = time();
	if (!m_use_sql)
		return;
	try
	{
		std::string buffer;
		BOOST_FOREACH(t_torrents::reference i, m_torrents)
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
		}
		if (!buffer.empty())
		{
			buffer.erase(buffer.size() - 1);
			m_database.query("insert into " + db_name("files") + " (" + db_name("leechers") + ", " + db_name("seeders") + ", " + db_name("completed") + ", " + db_name("fid") + ") values "
				+ buffer
				+ " on duplicate key update"
				+ "  " + db_name("leechers") + " = values(" + db_name("leechers") + "),"
				+ "  " + db_name("seeders") + " = values(" + db_name("seeders") + "),"
				+ "  " + db_name("completed") + " = values(" + db_name("completed") + "),"
				+ "  mtime = unix_timestamp()");
		}
	}
	catch (Cdatabase::exception&)
	{
	}
	if (!m_announce_log_buffer.empty())
	{
		m_announce_log_buffer.erase(m_announce_log_buffer.size() - 1);
		m_database.query_nothrow("insert delayed into " + db_name("announce_log") + " (ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values " + m_announce_log_buffer);
		m_announce_log_buffer.erase();
	}
	if (!m_scrape_log_buffer.empty())
	{
		m_scrape_log_buffer.erase(m_scrape_log_buffer.size() - 1);
		m_database.query_nothrow("insert delayed into " + db_name("scrape_log") + " (ipa, uid, mtime) values " + m_scrape_log_buffer);
		m_scrape_log_buffer.erase();
	}
}

void Cserver::write_db_users()
{
	m_write_db_users_time = time();
	if (!m_use_sql)
		return;
	if (!m_torrents_users_updates_buffer.empty())
	{
		m_torrents_users_updates_buffer.erase(m_torrents_users_updates_buffer.size() - 1);
		m_database.query_nothrow("insert into " + db_name("files_users") + " (active, announced, completed, downloaded, `left`, uploaded, mtime, fid, uid) values "
			+ m_torrents_users_updates_buffer
			+ " on duplicate key update"
			+ "  active = values(active),"
			+ "  announced = announced + values(announced),"
			+ "  completed = completed + values(completed),"
			+ "  downloaded = downloaded + values(downloaded),"
			+ "  `left` = if(values(`left`) = 18446744073709551615, `left`, values(`left`)),"
			+ "  uploaded = uploaded + values(uploaded),"
			+ "  mtime = if(values(mtime) = -1, mtime, values(mtime))");
		m_torrents_users_updates_buffer.erase();
	}
	if (!m_users_updates_buffer.empty())
	{
		m_users_updates_buffer.erase(m_users_updates_buffer.size() - 1);
		m_database.query_nothrow("insert into " + db_name("users") + " (downloaded, uploaded, " + db_name("uid") + ") values "
			+ m_users_updates_buffer
			+ " on duplicate key update"
			+ "  downloaded = downloaded + values(downloaded),"
			+ "  uploaded = uploaded + values(uploaded)");
		m_users_updates_buffer.erase();
	}
}

void Cserver::read_config()
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
		catch (Cdatabase::exception&)
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
	m_read_config_time = time();
}

void Cserver::t_torrent::debug(std::ostream& os) const
{
	BOOST_FOREACH(t_peers::const_reference i, peers)
	{
		os << "<tr><td>" + Csocket::inet_ntoa(i.first.host_)
			<< "<td align=right>" << ntohs(i.second.port)
			<< "<td align=right>" << i.second.uid
			<< "<td align=right>" << i.second.left
			<< "<td align=right>" << ::time(NULL) - i.second.mtime
			<< "<td>" << hex_encode(data_ref(i.second.peer_id.begin(), i.second.peer_id.end()));
	}
}

std::string Cserver::debug(const Ctracker_input& ti) const
{
	std::ostringstream os;
	os << "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	int leechers = 0;
	int seeders = 0;
	int torrents = 0;
	os << "<table>";
	if (ti.m_info_hash.empty())
	{
		BOOST_FOREACH(t_torrents::const_reference i, m_torrents)
		{
			if (!i.second.leechers && !i.second.seeders)
				continue;
			leechers += i.second.leechers;
			seeders += i.second.seeders;
			torrents++;
			os << "<tr><td align=right>" << i.second.fid
				<< "<td><a href=\"?info_hash=" << uri_encode(i.first) << "\">" << hex_encode(i.first) << "</a>"
				<< "<td>" << (i.second.dirty ? '*' : ' ')
				<< "<td align=right>" << i.second.leechers
				<< "<td align=right>" << i.second.seeders;
		}
	}
	else
	{
		if (const t_torrent* i = find_ptr(m_torrents, ti.m_info_hash))
			i->debug(os);
	}
	os << "</table>";
	return os.str();
}

std::string Cserver::statistics() const
{
	std::ostringstream os;
	os << "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"><meta http-equiv=refresh content=60><title>XBT Tracker</title>";
	int leechers = 0;
	int seeders = 0;
	int torrents = 0;
	BOOST_FOREACH(t_torrents::const_reference i, m_torrents)
	{
		leechers += i.second.leechers;
		seeders += i.second.seeders;
		torrents += i.second.leechers || i.second.seeders;
	}
  int peers = leechers + seeders;
	time_t t = time();
	os << "<table>"
		<< "<tr><td>peers<td align=right>" << peers;
  if (peers)
  {
    os << "<tr><td>seeders<td align=right>" << seeders << "<td align=right>" << seeders * 100 / peers << " %"
      << "<tr><td>leechers<td align=right>" << leechers << "<td align=right>" << leechers * 100 / peers << " %";
  }
	os << "<tr><td>torrents<td align=right>" << torrents
		<< "<tr><td>"
		<< "<tr><td>accepted tcp<td align=right>" << m_stats.accepted_tcp
		<< "<tr><td>rejected tcp<td align=right>" << m_stats.rejected_tcp
    << "<tr><td>accept errors<td align=right>" << m_stats.accept_errors
		<< "<tr><td>slow tcp<td align=right>" << m_stats.slow_tcp;
	if (m_stats.announced())
	{
		os << "<tr><td>announced<td align=right>" << m_stats.announced() << "<td align=right>" << m_stats.announced() * 100 / m_stats.accepted_tcp << " %"
		  << "<tr><td>announced http <td align=right>" << m_stats.announced_http << "<td align=right>" << m_stats.announced_http * 100 / m_stats.announced() << " %"
			<< "<tr><td>announced udp<td align=right>" << m_stats.announced_udp << "<td align=right>" << m_stats.announced_udp * 100 / m_stats.announced() << " %";
	}
	os << "<tr><td>scraped full<td align=right>" << m_stats.scraped_full;
	if (m_stats.scraped())
	{
		os << "<tr><td>scraped<td align=right>" << m_stats.scraped() << "<td align=right>" << m_stats.scraped() * 100 / m_stats.accepted_tcp << " %"
		  << "<tr><td>scraped http<td align=right>" << m_stats.scraped_http << "<td align=right>" << m_stats.scraped_http * 100 / m_stats.scraped() << " %"
			<< "<tr><td>scraped udp<td align=right>" << m_stats.scraped_udp << "<td align=right>" << m_stats.scraped_udp * 100 / m_stats.scraped() << " %";
	}
	os << "<tr><td>"
		<< "<tr><td>up time<td align=right>" << duration2a(time() - m_stats.start_time)
		<< "<tr><td>"
		<< "<tr><td>anonymous announce<td align=right>" << m_config.m_anonymous_announce
		<< "<tr><td>anonymous scrape<td align=right>" << m_config.m_anonymous_scrape
		<< "<tr><td>auto register<td align=right>" << m_config.m_auto_register
		<< "<tr><td>full scrape<td align=right>" << m_config.m_full_scrape
		<< "<tr><td>read config time<td align=right>" << t - m_read_config_time << " / " << m_config.m_read_config_interval
		<< "<tr><td>clean up time<td align=right>" << t - m_clean_up_time << " / " << m_config.m_clean_up_interval
		<< "<tr><td>read db files time<td align=right>" << t - m_read_db_torrents_time << " / " << m_config.m_read_db_interval;
	if (m_use_sql)
	{
		os << "<tr><td>read db users time<td align=right>" << t - m_read_db_users_time << " / " << m_config.m_read_db_interval
			<< "<tr><td>write db files time<td align=right>" << t - m_write_db_torrents_time << " / " << m_config.m_write_db_interval
			<< "<tr><td>write db users time<td align=right>" << t - m_write_db_users_time << " / " << m_config.m_write_db_interval;
	}
	os << "</table>";
	return os.str();
}

Cserver::t_user* Cserver::find_user_by_torrent_pass(const std::string& v, const std::string& info_hash)
{
	if (t_user* user = find_user_by_uid(read_int(4, hex_decode(v.substr(0, 8)))))
	{
		if (v.size() >= 8 && Csha1((boost::format("%s %d %d %s") % m_config.m_torrent_pass_private_key % user->torrent_pass_version % user->uid % info_hash).str()).read().substr(0, 12) == hex_decode(v.substr(8)))
			return user;
	}
	return find_ptr2(m_users_torrent_passes, v);
}

Cserver::t_user* Cserver::find_user_by_uid(int v)
{
	return find_ptr(m_users, v);
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

void Cserver::term()
{
	g_sig_term = true;
}

void Cserver::test_announce()
{
	t_user* u = find_ptr(m_users, 1);
	Ctracker_input i;
	i.m_info_hash = "IHIHIHIHIHIHIHIHIHIH";
	i.m_peer_id = "PIPIPIPIPIPIPIPIPIPI";
	i.m_ipa = htonl(0x7f000063);
	i.m_port = 54321;
	std::cout << insert_peer(i, false, u) << std::endl;
	write_db_torrents();
	write_db_users();
	m_time++;
	i.m_uploaded = 1 << 30;
	i.m_downloaded = 1 << 20;
	std::cout << insert_peer(i, false, u) << std::endl;
	write_db_torrents();
	write_db_users();
	m_time += 3600;
	clean_up();
	write_db_torrents();
	write_db_users();
}

int Cserver::test_sql()
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
		m_read_users_can_leech = Csql_query(m_database, "show columns from @users like 'can_leech'").execute();
		m_read_users_peers_limit = Csql_query(m_database, "show columns from @users like 'peers_limit'").execute();
		m_read_users_torrent_pass = Csql_query(m_database, "show columns from @users like 'torrent_pass'").execute();
		m_read_users_torrents_limit = Csql_query(m_database, "show columns from @users like 'torrents_limit'").execute();
		m_read_users_wait_time = Csql_query(m_database, "show columns from @users like 'wait_time'").execute();
		return 0;
	}
	catch (Cdatabase::exception&)
	{
	}
	return 1;
}
