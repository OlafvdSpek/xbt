// server.h: interface for the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_)
#define AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "sql/database.h"
#include "connection.h"
#include "peer_link.h"
#include "tracker_input.h"

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

		// __int64 downloaded;
		__int64 left;
		string peer_id;
		int port;
		// __int64 uploaded;

		bool listening;
		int mtime;
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
		void clean_up(int t);
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
		int uid;
		string pass;
	};

	typedef map<string, t_user> t_users;

	const t_user* find_user(const string&) const;
	int get_user_id(int) const;
	void read_config();
	void write_db_files();
	void write_db_users();
	void read_db_files();
	void read_db_ipas();
	void read_db_users();
	void clean_up();
	void insert_peer(const Ctracker_input&, bool listen_check, bool udp, int uid);
	void update_peer(const string& file_id, int peer_id, bool listening);
	string debug(const Ctracker_input&) const;
	Cbvalue select_peers(const Ctracker_input&);
	Cbvalue scrape(const Ctracker_input&);
	int run();
	static void term();
	Cserver(Cdatabase&);

	int announce_interval() const
	{
		return m_announce_interval;
	}

	const t_file* file(const string& id) const
	{
		t_files::const_iterator i = m_files.find(id);
		return i == m_files.end() ? NULL : &i->second;
	}

	bool anonymous_connect() const
	{
		return m_anonymous_connect;
	}

	bool anonymous_announce() const
	{
		return m_anonymous_announce;
	}

	bool anonymous_scrape() const
	{
		return m_anonymous_scrape;
	}

	bool gzip_announce() const
	{
		return m_gzip_announce;
	}

	bool gzip_debug() const
	{
		return m_gzip_debug;
	}

	bool gzip_scrape() const
	{
		return m_gzip_scrape;
	}

	const string& redirect_url() const
	{
		return m_redirect_url;
	}

	__int64 secret() const
	{
		return m_secret;
	}
private:
	typedef list<Cconnection> t_connections;
	typedef set<int> t_listen_ipas;
	typedef set<int> t_listen_ports;
	typedef list<Cpeer_link> t_peer_links;
	typedef vector<Csocket> t_sockets;

	static void sig_handler(int v);

	int m_clean_up_time;
	int m_read_config_time;
	int m_read_db_files_time;
	int m_read_db_ipas_time;
	int m_read_db_users_time;
	int m_write_db_files_time;
	int m_write_db_users_time;
	int m_announce_interval;
	int m_clean_up_interval;
	int m_read_config_interval;
	int m_read_db_interval;
	int m_write_db_interval;
	int m_fid_end;
	int m_update_files_method;
	bool m_anonymous_connect;
	bool m_anonymous_announce;
	bool m_anonymous_scrape;
	bool m_auto_register;
	bool m_daemon;
	bool m_gzip_announce;
	bool m_gzip_debug;
	bool m_gzip_scrape;
	bool m_listen_check;
	bool m_log_access;
	bool m_log_announce;
	bool m_log_scrape;
	__int64 m_secret;
	t_connections m_connections;
	t_listen_ipas m_listen_ipas;
	t_listen_ports m_listen_ports;
	t_peer_links m_peer_links;
	Cdatabase& m_database;
	t_files m_files;
	t_ipas m_ipas;
	t_users m_users;
	string m_announce_log_buffer;
	string m_scrape_log_buffer;
	string m_redirect_url;
	string m_users_updates_buffer;
};

#endif // !defined(AFX_SERVER_H__B9726CD5_D101_4193_A555_69102FC058E9__INCLUDED_)
