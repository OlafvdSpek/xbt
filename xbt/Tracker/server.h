#pragma once

#include "config.h"
#include "connection.h"
#include "tracker_input.h"

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

class Cstats
{
public:
	Cstats()
	{
    accept_errors = 0;
		accepted_tcp = 0;
		announced_http = 0;
		announced_udp = 0;
		rejected_tcp = 0;
		scraped_full = 0;
		scraped_http = 0;
		scraped_multi = 0;
		scraped_udp = 0;
		slow_tcp = 0;
		start_time = time(NULL);
	}

	long long announced() const
	{
		return announced_http + announced_udp;
	}

	long long scraped() const
	{
		return scraped_http + scraped_udp;
	}

  long long accept_errors;
	long long accepted_tcp;
	long long announced_http;
	long long announced_udp;
	long long rejected_tcp;
	long long scraped_full;
	long long scraped_http;
	long long scraped_multi;
	long long scraped_udp;
	long long slow_tcp;
	time_t start_time;
};

class Cserver
{
public:
	class peer_key_c
	{
	public:
		peer_key_c()
		{
		}

		peer_key_c(int host, int uid)
		{
			host_ = host;
#ifdef PEERS_KEY
			uid_ = uid;
#else
			(void)uid;
#endif
		}

		bool operator==(peer_key_c v) const
		{
#ifdef PEERS_KEY
			return host_ == v.host_ && uid_ == v.uid_;
#else
			return host_ == v.host_;
#endif
		}

		bool operator<(peer_key_c v) const
		{
#ifdef PEERS_KEY
			return host_ < v.host_ || host_ == v.host_ && uid_ < v.uid_;
#else
			return host_ < v.host_;
#endif
		}

		friend std::size_t hash_value(const peer_key_c& v)
		{
			std::size_t seed = boost::hash_value(v.host_);
#ifdef PEERS_KEY
			boost::hash_combine(seed, v.uid_);
#endif
			return seed;
		}

		int host_;
#ifdef PEERS_KEY
		int uid_;
#endif
	};

	struct t_peer
	{
		t_peer()
		{
			mtime = 0;
		}

		long long downloaded;
		long long uploaded;
		time_t mtime;
		int uid;
		short port;
		bool left;
		std::array<char, 20> peer_id;
	};

	typedef boost::unordered_map<peer_key_c, t_peer> t_peers;

	struct t_torrent
	{
		void clean_up(time_t t, Cserver&);
		void debug(std::ostream&) const;
		std::string select_peers(const Ctracker_input&) const;

		t_torrent()
		{
			completed = 0;
			dirty = true;
			fid = 0;
			leechers = 0;
			seeders = 0;
		}

		t_peers peers;
		time_t ctime;
		int completed;
		int fid;
		int leechers;
		int seeders;
		bool dirty;
	};

	struct t_user
	{
		t_user()
		{
			can_leech = true;
			completes = 0;
			incompletes = 0;
			peers_limit = 0;
			torrent_pass_version = 0;
			torrents_limit = 0;
			wait_time = 0;
		}

		bool can_leech;
		bool marked;
		int uid;
		int completes;
		int incompletes;
		int peers_limit;
		int torrent_pass_version;
		int torrents_limit;
		int wait_time;
	};

	void test_announce();
	int test_sql();
	void accept(const Csocket&);
	t_user* find_user_by_torrent_pass(str_ref, str_ref info_hash);
	void read_config();
	void write_db_torrents();
	void write_db_users();
	void read_db_torrents();
	void read_db_torrents_sql();
	void read_db_users();
	void clean_up();
	std::string insert_peer(const Ctracker_input&, bool udp, t_user*);
	std::string debug(const Ctracker_input&) const;
	std::string statistics() const;
	std::string select_peers(const Ctracker_input&) const;
	std::string scrape(const Ctracker_input&, t_user*);
	int run();
	static void term();
	Cserver(Cdatabase&, const std::string& table_prefix, bool use_sql, const std::string& conf_file);

	const t_torrent* find_torrent(const std::string& id) const
	{
		return find_ptr(m_torrents, id);
	}

	t_user* find_user_by_uid(int v)
	{
		return find_ptr(m_users, v);
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
	const std::string& db_name(const std::string&) const;

	Cconfig m_config;
	Cstats m_stats;
	long long m_secret;
	Cdatabase& m_database;
	boost::unordered_map<std::string, t_torrent> m_torrents;
	boost::unordered_map<int, t_user> m_users;
	time_t m_time;
};
