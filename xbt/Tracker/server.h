#pragma once

#include <boost/array.hpp>
#include <map>
#include "sql/database.h"
#include "config.h"
#include "connection.h"
#include "epoll.h"
#include "peer_link.h"
#include "stats.h"
#include "tcp_listen_socket.h"
#include "tracker_input.h"
#include "udp_listen_socket.h"
#include "virtual_binary.h"

class Cserver
{
public:
	struct t_peer
	{
		t_peer()
		{
			listening = false;
			mtime = 0;
		}

		long long downloaded;
		long long uploaded;
		time_t mtime;
		int port;
		int uid;
		bool left;
		bool listening;
		boost::array<char, 20> peer_id;
	};

	typedef std::map<int, t_peer> t_peers;

	struct t_deny_from_host
	{
		unsigned int end;
		bool marked;
	};

	struct t_file
	{
		void clean_up(time_t t, Cserver&);
		std::string debug() const;
		std::string select_peers(const Ctracker_input&) const;

		t_file()
		{
			completed = 0;
			dirty = true;
			fid = 0;
			leechers = 0;
			seeders = 0;
		}

		t_peers peers;
		int completed;
		bool dirty;
		int fid;
		int leechers;
		int seeders;
		time_t ctime;
	};


	struct t_user
	{
		t_user()
		{
			completes = 0;
			incompletes = 0;
		}

		bool can_leech;
		bool marked;
		int uid;
		int completes;
		int incompletes;
		int peers_limit;
		int torrents_limit;
		int wait_time;
		std::string pass;
		long long torrent_pass_secret;
	};

	typedef std::map<std::string, t_file> t_files;
	typedef std::map<unsigned int, t_deny_from_host> t_deny_from_hosts;
	typedef std::map<int, t_user> t_users;
	typedef std::map<std::string, t_user*> t_users_names;
	typedef std::map<std::string, t_user*> t_users_torrent_passes;

	int test_sql();
	void accept(const Csocket&);
	t_user* find_user_by_name(const std::string&);
	t_user* find_user_by_torrent_pass(const std::string&);
	t_user* find_user_by_uid(int);
	void read_config();
	void write_db_files();
	void write_db_users();
	void read_db_deny_from_hosts();
	void read_db_files();
	void read_db_files_sql();
	void read_db_users();
	void clean_up();
	std::string insert_peer(const Ctracker_input&, bool listen_check, bool udp, t_user*);
	void update_peer(const std::string& file_id, t_peers::key_type peer_id, bool listening);
	std::string debug(const Ctracker_input&) const;
	std::string statistics() const;
	Cvirtual_binary select_peers(const Ctracker_input&) const;
	Cvirtual_binary scrape(const Ctracker_input&);
	int run();
	static void term();
	Cserver(Cdatabase&, const std::string& table_prefix, bool use_sql, const std::string& conf_file);

	const t_file* file(const std::string& id) const
	{
		t_files::const_iterator i = m_files.find(id);
		return i == m_files.end() ? NULL : &i->second;
	}

	const Cconfig& config() const
	{
		return m_config;
	}

	long long secret() const
	{
		return m_secret;
	}

	Cstats& stats()
	{
		return m_stats;
	}

	time_t time() const
	{
		return m_time;
	}
private:
	enum
	{
		column_files_completed,
		column_files_fid,
		column_files_leechers,
		column_files_seeders,
		column_users_uid,
		table_announce_log,
		table_config,
		table_deny_from_hosts,
		table_files,
		table_files_users,
		table_scrape_log,
		table_users,
	};

	typedef std::list<Cconnection> t_connections;
	typedef std::list<Cpeer_link> t_peer_links;
	typedef std::list<Ctcp_listen_socket> t_tcp_sockets;
	typedef std::list<Cudp_listen_socket> t_udp_sockets;

	static void sig_handler(int v);
	std::string column_name(int v) const;
	std::string table_name(int) const;

	Cconfig m_config;
	Cstats m_stats;
	bool m_use_sql;
	time_t m_clean_up_time;
	time_t m_read_config_time;
	time_t m_read_db_deny_from_hosts_time;
	time_t m_read_db_files_time;
	time_t m_read_db_users_time;
	time_t m_time;
	time_t m_write_db_files_time;
	time_t m_write_db_users_time;
	int m_fid_end;
	long long m_secret;
	t_connections m_connections;
	t_peer_links m_peer_links;
	Cdatabase& m_database;
	Cepoll m_epoll;
	t_deny_from_hosts m_deny_from_hosts;
	t_files m_files;
	t_users m_users;
	t_users_names m_users_names;
	t_users_torrent_passes m_users_torrent_passes;
	std::string m_announce_log_buffer;
	std::string m_conf_file;
	std::string m_files_users_updates_buffer;
	std::string m_scrape_log_buffer;
	std::string m_table_prefix;
	std::string m_users_updates_buffer;
};
