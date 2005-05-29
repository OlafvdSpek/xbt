// server.h: interface for the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_)
#define AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "sql/database.h"
#include "config.h"
#include "connection.h"
#include "epoll.h"
#include "peer_link.h"
#include "stats.h"
#include "tcp_listen_socket.h"
#include "tracker_input.h"
#include "udp_listen_socket.h"

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

		__int64 downloaded;
		__int64 left;
		string peer_id;
		int port;
		int uid;
		__int64 uploaded;

		bool listening;
		time_t mtime;
	};

	typedef map<int, t_peer> t_peers;

	class Cannounce_output
	{
	public:
		virtual void peer(int h, const t_peer&) = 0;

		void complete(int v)
		{
			m_complete = v;
		}

		void incomplete(int v)
		{
			m_incomplete = v;
		}

		void interval(int v)
		{
			m_interval = v;
		}

		Cannounce_output()
		{
			m_complete = 0;
			m_incomplete = 0;
			m_interval = 1800;
		}
	protected:
		int m_complete;
		int m_incomplete;
		int m_interval;
	};

	struct t_file
	{
		void clean_up(time_t t, Cserver&);
		string debug() const;
		void select_peers(const Ctracker_input&, Cannounce_output&) const;
		Cbvalue scrape() const;

		t_file()
		{
			announced_http = 0;
			announced_http_compact = 0;
			announced_http_no_peer_id = 0;
			announced_udp = 0;
			completed = 0;
			dirty = true;
			fid = 0;
			leechers = 0;
			scraped_http = 0;
			scraped_udp = 0;
			seeders = 0;
			started = 0;
			stopped = 0;
		}

		t_peers peers;
		int announced_http;
		int announced_http_compact;
		int announced_http_no_peer_id;
		int announced_udp;
		int completed;
		bool dirty;
		int fid;
		int leechers;
		int scraped_http;
		int scraped_udp;
		int seeders;
		int started;
		int stopped;
	};

	typedef map<string, t_file> t_files;
	typedef map<int, int> t_ipas;

	struct t_user
	{
		t_user()
		{
			completes = 0;
			incompletes = 0;
		}

		bool marked;
		int uid;
		int fid_end;
		int completes;
		int incompletes;
		int peers_limit;
		int torrents_limit;
		string pass;
		__int64 torrent_pass_secret;
	};

	typedef map<int, t_user> t_users;
	typedef map<string, t_user*> t_users_names;
	typedef map<string, t_user*> t_users_torrent_passes;

	int test_sql();
	void accept(const Csocket& l);
	t_user* find_user_by_name(const string&);
	t_user* find_user_by_ipa(int);
	t_user* find_user_by_torrent_pass(const string&);
	t_user* find_user_by_uid(int);
	void read_config();
	void write_db_files();
	void write_db_users();
	void read_db_files();
	void read_db_files_sql();
	void read_db_ipas();
	void read_db_users();
	void clean_up();
	string insert_peer(const Ctracker_input&, bool listen_check, bool udp, t_user*);
	void update_peer(const string& file_id, int peer_id, bool listening);
	string debug(const Ctracker_input&) const;
	string statistics() const;
	Cbvalue select_peers(const Ctracker_input&, const t_user*);
	Cbvalue scrape(const Ctracker_input&);
	int run();
	static void term();
	Cserver(Cdatabase&, bool use_sql);

	int announce_interval() const
	{
		return m_config.m_announce_interval;
	}

	const t_file* file(const string& id) const
	{
		t_files::const_iterator i = m_files.find(id);
		return i == m_files.end() ? NULL : &i->second;
	}

	bool anonymous_connect() const
	{
		return m_config.m_anonymous_connect;
	}

	bool anonymous_announce() const
	{
		return m_config.m_anonymous_announce;
	}

	bool anonymous_scrape() const
	{
		return m_config.m_anonymous_scrape;
	}

	bool debug() const
	{
		return m_config.m_debug;
	}

	bool gzip_announce() const
	{
		return m_config.m_gzip_announce;
	}

	bool gzip_debug() const
	{
		return m_config.m_gzip_debug;
	}

	bool gzip_scrape() const
	{
		return m_config.m_gzip_scrape;
	}

	const string& redirect_url() const
	{
		return m_config.m_redirect_url;
	}

	__int64 secret() const
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
	typedef list<Cconnection> t_connections;
	typedef list<Cpeer_link> t_peer_links;
	typedef list<Ctcp_listen_socket> t_tcp_sockets;
	typedef list<Cudp_listen_socket> t_udp_sockets;

	static void sig_handler(int v);

	Cconfig m_config;
	Cstats m_stats;
	bool m_use_sql;
	time_t m_clean_up_time;
	time_t m_read_config_time;
	time_t m_read_db_files_time;
	time_t m_read_db_ipas_time;
	time_t m_read_db_users_time;
	time_t m_time;
	time_t m_write_db_files_time;
	time_t m_write_db_users_time;
	int m_fid_end;
	__int64 m_secret;
	t_connections m_connections;
	t_peer_links m_peer_links;
	Cdatabase& m_database;
	Cepoll m_epoll;
	t_files m_files;
	t_ipas m_ipas;
	t_users m_users;
	t_users_names m_users_names;
	t_users_torrent_passes m_users_torrent_passes;
	string m_announce_log_buffer;
	string m_files_users_updates_buffer;
	string m_scrape_log_buffer;
	string m_users_updates_buffer;
};

#endif // !defined(AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_)
