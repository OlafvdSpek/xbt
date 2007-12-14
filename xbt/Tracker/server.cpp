#include "stdafx.h"
#include "server.h"

#include <boost/format.hpp>
#include <iostream>
#include <signal.h>
#include "sql/sql_query.h"
#include "bt_misc.h"
#include "bt_strings.h"
#include "stream_int.h"
#include "transaction.h"

static volatile bool g_sig_hup = false;
static volatile bool g_sig_term = false;

Cserver::Cserver(Cdatabase& database, const std::string& table_prefix, bool use_sql, const std::string& conf_file):
	m_database(database)
{
	m_fid_end = 0;

	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
	m_conf_file = conf_file;
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
	for (Cconfig::t_listen_ipas::const_iterator j = m_config.m_listen_ipas.begin(); j != m_config.m_listen_ipas.end(); j++)
	{
		for (Cconfig::t_listen_ports::const_iterator i = m_config.m_listen_ports.begin(); i != m_config.m_listen_ports.end(); i++)
		{
			Csocket l;
			if (l.open(SOCK_STREAM) == INVALID_SOCKET)
				std::cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(*j, htons(*i)))
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
				if (l.setsockopt(IPPROTO_TCP, TCP_DEFER_ACCEPT, true))
					std::cerr << "setsockopt failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
#endif
				lt.push_back(Ctcp_listen_socket(this, l));
				if (!m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &lt.back()))
					continue;
			}
			return 1;
		}
		for (Cconfig::t_listen_ports::const_iterator i = m_config.m_listen_ports.begin(); i != m_config.m_listen_ports.end(); i++)
		{
			Csocket l;
			if (l.open(SOCK_DGRAM) == INVALID_SOCKET)
				std::cerr << "socket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else if (l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true),
				l.bind(*j, htons(*i)))
				std::cerr << "bind failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			else
			{
				lu.push_back(Cudp_listen_socket(this, l));
				if (!m_epoll.ctl(EPOLL_CTL_ADD, l, EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &lu.back()))
					continue;
			}
			return 1;
		}
	}
#ifndef WIN32
	if (m_config.m_daemon)
	{
#if 1
		if (daemon(true, false))
			std::cerr << "daemon failed" << std::endl;
#else
		switch (fork())
		{
		case -1:
			std::cerr << "fork failed" << std::endl;
			break;
		case 0:
			break;
		default:
			exit(0);
		}
		if (setsid() == -1)
			std::cerr << "setsid failed" << std::endl;
#endif
		std::ofstream(m_config.m_pid_file.c_str()) << getpid() << std::endl;
		struct sigaction act;
		act.sa_handler = sig_handler;
		sigemptyset(&act.sa_mask);
		act.sa_flags = 0;
		if (sigaction(SIGHUP, &act, NULL)
			|| sigaction(SIGTERM, &act, NULL))
			std::cerr << "sigaction failed" << std::endl;
		act.sa_handler = SIG_IGN;
		if (sigaction(SIGPIPE, &act, NULL))
			std::cerr << "sigaction failed" << std::endl;
	}
#endif
	clean_up();
	read_db_deny_from_hosts();
	read_db_files();
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
		int r = m_epoll.wait(events, c_events, 5000);
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
		for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); i++)
		{
			int z = i->pre_select(&fd_read_set, &fd_write_set);
			n = std::max(n, z);
		}
		for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); i++)
		{
			int z = i->pre_select(&fd_write_set, &fd_except_set);
			n = std::max(n, z);
		}
		for (t_tcp_sockets::iterator i = lt.begin(); i != lt.end(); i++)
		{
			FD_SET(i->s(), &fd_read_set);
			n = std::max<int>(n, i->s());
		}
		for (t_udp_sockets::iterator i = lu.begin(); i != lu.end(); i++)
		{
			FD_SET(i->s(), &fd_read_set);
			n = std::max<int>(n, i->s());
		}
		timeval tv;
		tv.tv_sec = 5;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
			std::cerr << "select failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
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
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
			{
				if (i->post_select(&fd_read_set, &fd_write_set))
					i = m_connections.erase(i);
				else
					i++;
			}
			for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); )
			{
				if (i->post_select(&fd_write_set, &fd_except_set))
					i = m_peer_links.erase(i);
				else
					i++;
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
				std::cerr << "accept failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			break;
		}
		t_deny_from_hosts::const_iterator i = m_deny_from_hosts.lower_bound(ntohl(a.sin_addr.s_addr));
		if (i != m_deny_from_hosts.end() && ntohl(a.sin_addr.s_addr) <= i->second.end)
			continue;
		if (s.blocking(false))
			std::cerr << "ioctlsocket failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
		std::auto_ptr<Cconnection> connection(new Cconnection(this, s, a));
		connection->process_events(EPOLLIN);
		if (connection->s() != INVALID_SOCKET)
		{
			m_connections.push_back(connection.get());
			m_epoll.ctl(EPOLL_CTL_ADD, m_connections.back().s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_connections.back());
		}
	}
}

std::string Cserver::insert_peer(const Ctracker_input& v, bool listen_check, bool udp, t_user* user)
{
	if (m_use_sql && m_config.m_log_announce)
	{
		m_announce_log_buffer += Csql_query(m_database, "(?,?,?,?,?,?,?,?,?,?),")
			.p(ntohl(v.m_ipa)).p(ntohs(v.m_port)).p(v.m_event).p(v.m_info_hash).p(v.m_peer_id).p(v.m_downloaded).p(v.m_left).p(v.m_uploaded).p(user ? user->uid : 0).p(time()).read();
	}
	if (!m_config.m_offline_message.empty())
		return m_config.m_offline_message;
	if (!m_config.m_auto_register && m_files.find(v.m_info_hash) == m_files.end())
		return bts_unregistered_torrent;
	if (v.m_left && user && !user->can_leech)
		return bts_can_not_leech;
	t_file& file = m_files[v.m_info_hash];
	if (!file.ctime)
		file.ctime = time();
	if (v.m_left && user && user->wait_time && file.ctime + user->wait_time > time())
		return bts_wait_time;
	t_peers::key_type peer_key = v.m_ipa;
	t_peers::iterator i = file.peers.find(peer_key);
	if (i != file.peers.end())
	{
		(i->second.left ? file.leechers : file.seeders)--;
		if (t_user* old_user = find_user_by_uid(i->second.uid))
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
	if (m_use_sql && user && file.fid)
	{
		long long downloaded = 0;
		long long uploaded = 0;
		if (i != file.peers.end()
			&& boost::equals(i->second.peer_id, v.m_peer_id)
			&& v.m_downloaded >= i->second.downloaded
			&& v.m_uploaded >= i->second.uploaded)
		{
			downloaded = v.m_downloaded - i->second.downloaded;
			uploaded = v.m_uploaded - i->second.uploaded;
		}
		m_files_users_updates_buffer += Csql_query(m_database, "(?,1,?,?,?,?,?,?,?),")
			.p(v.m_event != Ctracker_input::e_stopped)
			.p(v.m_event == Ctracker_input::e_completed)
			.p(downloaded)
			.p(v.m_left)
			.p(uploaded)
			.p(time())
			.p(file.fid)
			.p(user->uid)
			.read();
		if (downloaded || uploaded)
			m_users_updates_buffer += Csql_query(m_database, "(?,?,?),").p(downloaded).p(uploaded).p(user->uid).read();
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

		if (!m_config.m_listen_check || !listen_check)
			peer.listening = true;
		else if (!peer.listening && time() - peer.mtime > 7200)
		{
			Cpeer_link peer_link(v.m_ipa, v.m_port, this, v.m_info_hash, peer_key);
			if (peer_link.s() != INVALID_SOCKET)
			{
				m_peer_links.push_back(peer_link);
				m_epoll.ctl(EPOLL_CTL_ADD, peer_link.s(), EPOLLIN | EPOLLOUT | EPOLLPRI | EPOLLERR | EPOLLHUP | EPOLLET, &m_peer_links.back());
			}
		}
		peer.mtime = time();
	}
	if (v.m_event == Ctracker_input::e_completed)
		file.completed++;
	(udp ? m_stats.announced_udp : m_stats.announced_http)++;
	file.dirty = true;
	return "";
}

void Cserver::update_peer(const std::string& file_id, t_peers::key_type peer_id, bool listening)
{
	t_files::iterator i = m_files.find(file_id);
	if (i == m_files.end())
		return;
	t_peers::iterator j = i->second.peers.find(peer_id);
	if (j == i->second.peers.end())
		return;
	j->second.listening = listening;
}

std::string Cserver::t_file::select_peers(const Ctracker_input& ti) const
{
	if (ti.m_event == Ctracker_input::e_stopped)
		return "";

	typedef std::vector<t_peers::const_iterator> t_candidates;

	t_candidates candidates;
	for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
	{
		if ((ti.m_left || i->second.left) && i->second.listening)
			candidates.push_back(i);
	}
	size_t c = ti.m_num_want < 0 ? 50 : std::min(ti.m_num_want, 50);
	std::string d;
	d.reserve(300);
	if (candidates.size() > c)
	{
		while (c--)
		{
			int i = rand() % candidates.size();
			d.append(reinterpret_cast<const char*>(&candidates[i]->first), 4);
			d.append(reinterpret_cast<const char*>(&candidates[i]->second.port), 2);
			candidates[i] = candidates.back();
			candidates.pop_back();
		}
	}
	else
	{
		for (t_candidates::const_iterator i = candidates.begin(); i != candidates.end(); i++)
		{
			d.append(reinterpret_cast<const char*>(&(*i)->first), 4);
			d.append(reinterpret_cast<const char*>(&(*i)->second.port), 2);
		}
	}
	return d;
}

Cvirtual_binary Cserver::select_peers(const Ctracker_input& ti) const
{
	t_files::const_iterator i = m_files.find(ti.m_info_hash);
	if (i == m_files.end())
		return Cvirtual_binary();
	std::string peers = i->second.select_peers(ti);
	return Cvirtual_binary((boost::format("d8:completei%de10:incompletei%de8:intervali%de12:min intervali%de5:peers%d:%se") 
		% i->second.seeders % i->second.leechers % config().m_announce_interval % config().m_announce_interval % peers.size() % peers).str());	
}

void Cserver::t_file::clean_up(time_t t, Cserver& server)
{
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (i->second.mtime < t)
		{
			(i->second.left ? leechers : seeders)--;
			if (t_user* user = server.find_user_by_uid(i->second.uid))
				(i->second.left ? user->incompletes : user->completes)--;
			if (i->second.uid)
				server.m_files_users_updates_buffer += Csql_query(server.m_database, "(0,0,0,0,-1,0,-1,?,?),").p(fid).p(i->second.uid).read();
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

Cvirtual_binary Cserver::scrape(const Ctracker_input& ti)
{
	if (m_use_sql && m_config.m_log_scrape)
	{
		Csql_query q(m_database, "(?,?,?),");
		q.p(ntohl(ti.m_ipa));
		if (ti.m_info_hash.empty())
			q.p_raw("null");
		else
			q.p(ti.m_info_hash);
		q.p(time());
		m_scrape_log_buffer += q.read();
	}
	std::string d;
	d += "d5:filesd";
	if (ti.m_info_hashes.empty())
	{
		m_stats.scraped_full++;
		d.reserve(90 * m_files.size());
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (i->second.leechers || i->second.seeders)
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % i->first % i->second.seeders % i->second.completed % i->second.leechers).str();
		}
	}
	else
	{
		m_stats.scraped_http++;
		for (Ctracker_input::t_info_hashes::const_iterator j = ti.m_info_hashes.begin(); j != ti.m_info_hashes.end(); j++)
		{
			t_files::const_iterator i = m_files.find(*j);
			if (i != m_files.end())			
				d += (boost::format("20:%sd8:completei%de10:downloadedi%de10:incompletei%dee") % i->first % i->second.seeders % i->second.completed % i->second.leechers).str();
		}
	}
	d += "e";
	if (m_config.m_scrape_interval)
		d += (boost::format("5:flagsd20:min_request_intervali%dee") % m_config.m_scrape_interval).str();
	d += "e";
	return Cvirtual_binary(d);
}

void Cserver::read_db_deny_from_hosts()
{
	m_read_db_deny_from_hosts_time = time();
	if (!m_use_sql)
		return;
	try
	{
		Csql_result result = Csql_query(m_database, "select begin, end from ?").p_name(table_name(table_deny_from_hosts)).execute();
		for (t_deny_from_hosts::iterator i = m_deny_from_hosts.begin(); i != m_deny_from_hosts.end(); i++)
			i->second.marked = true;
		for (Csql_row row; row = result.fetch_row(); )
		{
			t_deny_from_host& deny_from_host = m_deny_from_hosts[row[0].i()];
			deny_from_host.marked = false;
			deny_from_host.end = row[1].i();
		}
		for (t_deny_from_hosts::iterator i = m_deny_from_hosts.begin(); i != m_deny_from_hosts.end(); )
		{
			if (i->second.marked)
				m_deny_from_hosts.erase(i++);
			else
				i++;
		}
	}
	catch (Cdatabase::exception&)
	{
	}
}

void Cserver::read_db_files()
{
	m_read_db_files_time = time();
	if (m_use_sql)
		read_db_files_sql();
	else if (!m_config.m_auto_register)
	{
		std::set<std::string> new_files;
		std::ifstream is("xbt_files.txt");
		std::string s;
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
		if (!m_config.m_auto_register)
		{
			Csql_result result = Csql_query(m_database, "select info_hash, ? from ? where flags & 1").p_name(column_name(column_files_fid)).p_name(table_name(table_files)).execute();
			for (Csql_row row; row = result.fetch_row(); )
			{
				t_files::iterator i = m_files.find(row[0].s());
				if (i != m_files.end())
				{
					for (t_peers::iterator j = i->second.peers.begin(); j != i->second.peers.end(); j++)
					{
						if (t_user* user = find_user_by_uid(j->second.uid))
							(j->second.left ? user->incompletes : user->completes)--;
					}
					m_files.erase(i);
				}
				Csql_query(m_database, "delete from ? where ? = ?").p_name(table_name(table_files)).p_name(column_name(column_files_fid)).p(row[1].i()).execute();
			}
		}
		if (m_files.empty())
			m_database.query("update " + table_name(table_files) + " set " + column_name(column_files_leechers) + " = 0, " + column_name(column_files_seeders) + " = 0");
		else if (m_config.m_auto_register)
			return;
		Csql_result result = Csql_query(m_database, "select info_hash, ?, ?, ctime from ? where ? >= ?")
			.p_name(column_name(column_files_completed))
			.p_name(column_name(column_files_fid))
			.p_name(table_name(table_files))
			.p_name(column_name(column_files_fid))
			.p(m_fid_end)
			.execute();
		for (Csql_row row; row = result.fetch_row(); )
		{
			m_fid_end = std::max(m_fid_end, static_cast<int>(row[2].i()) + 1);
			if (row[0].size() != 20 || m_files.find(row[0].s()) != m_files.end())
				continue;
			t_file& file = m_files[row[0].s()];
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
		Csql_query q(m_database, "select ?, torrent_pass_version");
		if (m_read_users_can_leech)
			q += ", can_leech";
		if (m_read_users_name_pass)
			q += ", name, pass";
		if (m_read_users_peers_limit)
			q += ", peers_limit";
		if (m_read_users_torrent_pass)
			q += ", torrent_pass, torrent_pass_secret";
		if (m_read_users_torrents_limit)
			q += ", torrents_limit";
		if (m_read_users_wait_time)
			q += ", wait_time";
		q += " from ?";
		q.p_name(column_name(column_users_uid));
		q.p_name(table_name(table_users));
		Csql_result result = q.execute();
		for (t_users::iterator i = m_users.begin(); i != m_users.end(); i++)
			i->second.marked = true;
		m_users_names.clear();
		m_users_torrent_passes.clear();
		for (Csql_row row; row = result.fetch_row(); )
		{
			t_user& user = m_users[row[0].i()];
			user.marked = false;
			int c = 0;
			user.uid = row[c++].i();
			user.torrent_pass_version = row[c++].i();
			user.can_leech = m_read_users_can_leech ? row[c++].i() : true;
			if (m_read_users_name_pass)
			{
				if (row[c].size() && row[c + 1].size())
					m_users_names[row[c].s()] = &user;
				c++;
				user.pass = row[c++].s();
			}
			user.peers_limit = m_read_users_peers_limit ? row[c++].i() : 0;
			if (m_read_users_torrent_pass)
			{
				if (row[c].size())
					m_users_torrent_passes[row[c].s()] = &user;
				c++;
			}
			user.torrent_pass_secret = m_read_users_torrent_pass ? row[c++].i() : 0;
			user.torrents_limit = m_read_users_torrents_limit ? row[c++].i() : 0;
			user.wait_time = m_read_users_wait_time ? row[c++].i() : 0;
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

void Cserver::write_db_files()
{
	m_write_db_files_time = time();
	if (!m_use_sql)
		return;
	try
	{
		std::string buffer;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			t_file& file = i->second;
			if (!file.dirty)
				continue;
			if (!file.fid)
			{
				Csql_query(m_database, "insert into ? (info_hash, mtime, ctime) values (?, unix_timestamp(), unix_timestamp())").p_name(table_name(table_files)).p(i->first).execute();
				file.fid = m_database.insert_id();
			}
			buffer += Csql_query(m_database, "(?,?,?,?),").p(file.leechers).p(file.seeders).p(file.completed).p(file.fid).read();
			file.dirty = false;
		}
		if (!buffer.empty())
		{
			buffer.erase(buffer.size() - 1);
			m_database.query("insert into " + table_name(table_files) + " (" + column_name(column_files_leechers) + ", " + column_name(column_files_seeders) + ", " + column_name(column_files_completed) + ", " + column_name(column_files_fid) + ") values "
				+ buffer
				+ " on duplicate key update"
				+ "  " + column_name(column_files_leechers) + " = values(" + column_name(column_files_leechers) + "),"
				+ "  " + column_name(column_files_seeders) + " = values(" + column_name(column_files_seeders) + "),"
				+ "  " + column_name(column_files_completed) + " = values(" + column_name(column_files_completed) + "),"
				+ "  mtime = unix_timestamp()");
		}
	}
	catch (Cdatabase::exception&)
	{
	}
	if (!m_announce_log_buffer.empty())
	{
		try
		{
			m_announce_log_buffer.erase(m_announce_log_buffer.size() - 1);
			m_database.query("insert delayed into " + table_name(table_announce_log) + " (ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime) values " + m_announce_log_buffer);
		}
		catch (Cdatabase::exception&)
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
		catch (Cdatabase::exception&)
		{
		}
		m_scrape_log_buffer.erase();
	}
}

void Cserver::write_db_users()
{
	m_write_db_users_time = time();
	if (!m_use_sql)
		return;
	if (!m_files_users_updates_buffer.empty())
	{
		m_files_users_updates_buffer.erase(m_files_users_updates_buffer.size() - 1);
		try
		{
			m_database.query("insert into " + table_name(table_files_users) + " (active, announced, completed, downloaded, `left`, uploaded, mtime, fid, uid) values "
				+ m_files_users_updates_buffer
				+ " on duplicate key update"
				+ "  active = values(active),"
				+ "  announced = announced + values(announced),"
				+ "  completed = completed + values(completed),"
				+ "  downloaded = downloaded + values(downloaded),"
				+ "  `left` = if(values(`left`) = -1, `left`, values(`left`)),"
				+ "  uploaded = uploaded + values(uploaded),"
				+ "  mtime = if(values(mtime) = -1, mtime, values(mtime))");
		}
		catch (Cdatabase::exception&)
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
		catch (Cdatabase::exception&)
		{
		}
		m_users_updates_buffer.erase();
	}
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
				config.set(row[0].s(), row[1].s());
			config.load(m_conf_file);
			if (config.m_torrent_pass_private_key.empty())
			{
				config.m_torrent_pass_private_key = generate_random_string(27);
				Csql_query(m_database, "insert into xbt_config (name, value) values ('torrent_pass_private_key', ?)").p(config.m_torrent_pass_private_key).execute();
			}
			m_config = config;
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

std::string Cserver::t_file::debug() const
{
	std::string page;
	for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
	{
		page += "<tr><td>" + Csocket::inet_ntoa(i->first)
			+ "<td align=right>" + n(ntohs(i->second.port))
			+ "<td>" + (i->second.listening ? '*' : ' ')
			+ "<td align=right>" + n(i->second.left)
			+ "<td align=right>" + n(::time(NULL) - i->second.mtime)
			+ "<td>" + hex_encode(const_memory_range(i->second.peer_id.begin(), i->second.peer_id.end()));
	}
	return page;
}

std::string Cserver::debug(const Ctracker_input& ti) const
{
	std::string page;
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

std::string Cserver::statistics() const
{
	std::string page;
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
		page += "<tr><td>announced http <td align=right>" + n(m_stats.announced_http) + "<td align=right>" + n(m_stats.announced_http * 100 / m_stats.announced()) + " %"
			+ "<tr><td>announced udp<td align=right>" + n(m_stats.announced_udp) + "<td align=right>" + n(m_stats.announced_udp * 100 / m_stats.announced()) + " %";
	}
	page += "<tr><td>scraped full<td align=right>" + n(m_stats.scraped_full)
		+ "<tr><td>scraped<td align=right>" + n(m_stats.scraped());
	if (m_stats.scraped())
	{
		page += "<tr><td>scraped http<td align=right>" + n(m_stats.scraped_http) + "<td align=right>" + n(m_stats.scraped_http * 100 / m_stats.scraped()) + " %"
			+ "<tr><td>scraped udp<td align=right>" + n(m_stats.scraped_udp) + "<td align=right>" + n(m_stats.scraped_udp * 100 / m_stats.scraped()) + " %";
	}
	page += std::string("<tr><td>")
		+ "<tr><td>up time<td align=right>" + duration2a(time() - m_stats.start_time)
		+ "<tr><td>"
		+ "<tr><td>anonymous connect<td align=right>" + n(m_config.m_anonymous_connect)
		+ "<tr><td>anonymous announce<td align=right>" + n(m_config.m_anonymous_announce)
		+ "<tr><td>anonymous scrape<td align=right>" + n(m_config.m_anonymous_scrape)
		+ "<tr><td>auto register<td align=right>" + n(m_config.m_auto_register)
		+ "<tr><td>full scrape<td align=right>" + n(m_config.m_full_scrape)
		+ "<tr><td>listen check<td align=right>" + n(m_config.m_listen_check)
		+ "<tr><td>read config time<td align=right>" + n(t - m_read_config_time) + " / " + n(m_config.m_read_config_interval)
		+ "<tr><td>clean up time<td align=right>" + n(t - m_clean_up_time) + " / " + n(m_config.m_clean_up_interval)
		+ "<tr><td>read db files time<td align=right>" + n(t - m_read_db_files_time) + " / " + n(m_config.m_read_db_interval);
	if (m_use_sql)
	{
		page += "<tr><td>read db users time<td align=right>" + n(t - m_read_db_users_time) + " / " + n(m_config.m_read_db_interval)
			+ "<tr><td>write db files time<td align=right>" + n(t - m_write_db_files_time) + " / " + n(m_config.m_write_db_interval)
			+ "<tr><td>write db users time<td align=right>" + n(t - m_write_db_users_time) + " / " + n(m_config.m_write_db_interval);
	}
	page += "</table>";
	return page;
}

Cserver::t_user* Cserver::find_user_by_name(const std::string& v)
{
	t_users_names::const_iterator i = m_users_names.find(v);
	return i == m_users_names.end() ? NULL : i->second;
}

Cserver::t_user* Cserver::find_user_by_torrent_pass(const std::string& v, const std::string& info_hash)
{
	if (t_user* user = find_user_by_uid(read_int(4, hex_decode(v.substr(0, 8)))))
	{
		if (v.size() >= 8 && Csha1((boost::format("%s %d %d %s") % m_config.m_torrent_pass_private_key % user->torrent_pass_version % user->uid % info_hash).str()).read().substr(0, 12) == hex_decode(v.substr(8)))
			return user;
	}
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

std::string Cserver::column_name(int v) const
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

std::string Cserver::table_name(int v) const
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
	case table_files_users:
		return m_config.m_table_files_users.empty() ? m_table_prefix + "files_users" : m_config.m_table_files_users;
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
		mysql_get_server_version(&m_database.handle());
		m_database.query("select id, ipa, port, event, info_hash, peer_id, downloaded, left0, uploaded, uid, mtime from " + table_name(table_announce_log) + " where 0 = 1");
		m_database.query("select name, value from " + table_name(table_config) + " where 0 = 1");
		m_database.query("select begin, end from " + table_name(table_deny_from_hosts) + " where 0 = 1");
		m_database.query("select " + column_name(column_files_fid) + ", info_hash, " + column_name(column_files_leechers) + ", " + column_name(column_files_seeders) + ", flags, mtime, ctime from " + table_name(table_files) + " where 0 = 1");
		m_database.query("select fid, uid, active, announced, completed, downloaded, `left`, uploaded from " + table_name(table_files_users) + " where 0 = 1");
		m_database.query("select id, ipa, info_hash, uid, mtime from " + table_name(table_scrape_log) + " where 0 = 1");
		m_database.query("select " + column_name(column_users_uid) + ", torrent_pass_version, downloaded, uploaded from " + table_name(table_users) + " where 0 = 1");
		m_read_users_can_leech = m_database.query("show columns from " + table_name(table_users) + " like 'can_leech'");
		m_read_users_name_pass = m_database.query("show columns from " + table_name(table_users) + " like 'pass'");
		m_read_users_peers_limit = m_database.query("show columns from " + table_name(table_users) + " like 'peers_limit'");
		m_read_users_torrent_pass = m_database.query("show columns from " + table_name(table_users) + " like 'torrent_pass'");
		m_read_users_torrents_limit = m_database.query("show columns from " + table_name(table_users) + " like 'torrents_limit'");
		m_read_users_wait_time = m_database.query("show columns from " + table_name(table_users) + " like 'wait_time'");
		return 0;
	}
	catch (Cdatabase::exception&)
	{
	}
	return 1;
}
