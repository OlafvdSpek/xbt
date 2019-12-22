#pragma once

#include <config_base.h>

class config_t: public Cconfig_base
{
public:
	int set(const std::string& name, const std::string& value);
	int set(const std::string& name, int value);
	int set(const std::string& name, bool value);
	config_t();
	config_t(const config_t&);
	const config_t& operator=(const config_t&);

	bool anonymous_announce_;
	bool anonymous_scrape_;
	bool auto_register_;
	bool daemon_;
	bool debug_;
	bool full_scrape_;
	bool gzip_scrape_;
	bool log_access_;
	bool log_announce_;
	bool log_scrape_;
	int announce_interval_;
	int clean_up_interval_;
	int read_config_interval_;
	int read_db_interval_;
	int scrape_interval_;
	int write_db_interval_;
	std::string column_torrents_completed_;
	std::string column_torrents_tid_;
	std::string column_torrents_leechers_;
	std::string column_torrents_seeders_;
	std::string column_users_uid_;
	std::string mysql_database_;
	std::string mysql_host_;
	std::string mysql_password_;
	std::string mysql_table_prefix_;
	std::string mysql_user_;
	std::string offline_message_;
	std::string query_log_;
	std::string pid_file_;
	std::string redirect_url_;
	std::string table_announce_log_;
	std::string table_torrents_;
	std::string table_torrents_users_;
	std::string table_scrape_log_;
	std::string table_users_;
	std::string torrent_pass_private_key_;
	std::set<int> listen_ipas_;
	std::set<int> listen_ports_;
private:
	void fill_maps(const config_t*);
};
