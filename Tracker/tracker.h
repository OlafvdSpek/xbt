#pragma once

#include "config.h"
#include "tracker_input.h"

class stats_t
{
public:
	long long announced() const
	{
		return announced_http + announced_udp;
	}

	long long scraped() const
	{
		return scraped_http + scraped_udp;
	}

	long long accept_errors = 0;
	long long accepted_tcp = 0;
	long long announced_http = 0;
	long long announced_udp = 0;
	long long received_udp = 0;
	long long rejected_tcp = 0;
	long long scraped_full = 0;
	long long scraped_http = 0;
	long long scraped_multi = 0;
	long long scraped_udp = 0;
	long long sent_udp = 0;
	long long slow_tcp = 0;
	time_t start_time = time(NULL);
};

struct peer_t
{
	long long downloaded;
	long long uploaded;
	time_t mtime = 0;
	int uid;
	short port;
	bool left;
	std::array<char, 4> ipv4 = {};
	std::array<char, 16> ipv6 = {};
};

struct torrent_t
{
	void select_peers(mutable_str_ref& d, const tracker_input_t&) const;
	void select_peers6(mutable_str_ref& d, const tracker_input_t&) const;

	boost::unordered_map<std::array<char, 20>, peer_t> peers;
	time_t ctime;
	int completed = 0;
	int tid = 0;
	int leechers = 0;
	int seeders = 0;
	bool dirty = true;
};

struct user_t
{
	int uid;
	int peers_limit = 0;
	int torrent_pass_version = 0;
	int wait_time = 0;
	bool can_leech = true;
	bool marked;
};

const torrent_t* find_torrent(std::string_view info_hash);
user_t* find_user_by_torrent_pass(std::string_view, std::string_view info_hash);
user_t* find_user_by_uid(int);
long long srv_secret();
const config_t& srv_config();
stats_t& srv_stats();
time_t srv_time();

std::string srv_debug(const tracker_input_t&);
std::string srv_insert_peer(const tracker_input_t&, bool udp, user_t*);
std::string srv_scrape(const tracker_input_t&, user_t*);
std::string srv_select_peers(const tracker_input_t&);
std::string srv_select_peers6(const tracker_input_t&);
std::string srv_statistics();
