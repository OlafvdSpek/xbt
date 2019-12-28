#include "stdafx.h"
#include "config.h"

config_t::config_t()
{
	fill_maps(NULL);
}

config_t::config_t(const config_t& v)
{
	fill_maps(&v);
}

const config_t& config_t::operator=(const config_t& v)
{
	fill_maps(&v);
	return *this;
}

void config_t::fill_maps(const config_t* v)
{
	{
		attribute_t<bool> attributes[] =
		{
			{ "auto_register", &auto_register_, false },
			{ "anonymous_announce", &anonymous_announce_, false },
			{ "anonymous_scrape", &anonymous_scrape_, false },
			{ "daemon", &daemon_, true },
			{ "debug", &debug_, false },
			{ "full_scrape", &full_scrape_, false },
			{ "gzip_scrape", &gzip_scrape_, true },
			{ "log_access", &log_access_, false },
			{ "log_announce", &log_announce_, false },
			{ "log_scrape", &log_scrape_, false },
			{ NULL, NULL, false }
		};
		fill_map(attributes, v ? &v->attributes_bool_ : NULL, attributes_bool_);
	}
	{
		attribute_t<int> attributes[] =
		{
			{ "announce_interval", &announce_interval_, 1800 },
			{ "clean_up_interval", &clean_up_interval_, 60 },
			{ "read_config_interval", &read_config_interval_, 60 },
			{ "read_db_interval", &read_db_interval_, 60 },
			{ "scrape_interval", &scrape_interval_, 0 },
			{ "write_db_interval", &write_db_interval_, 15 },
			{ NULL, NULL, 0 }
		};
		fill_map(attributes, v ? &v->attributes_int_ : NULL, attributes_int_);
	}
	{
		attribute_t<std::string> attributes[] =
		{
			{ "column_files_completed", &column_torrents_completed_, "completed" },
			{ "column_files_fid", &column_torrents_tid_, "tid" },
			{ "column_files_leechers", &column_torrents_leechers_, "leechers" },
			{ "column_files_seeders", &column_torrents_seeders_, "seeders" },
			{ "column_users_uid", &column_users_uid_, "uid" },
			{ "mysql_database", &mysql_database_, "xbt" },
			{ "mysql_host", &mysql_host_, "" },
			{ "mysql_password", &mysql_password_, "" },
			{ "mysql_table_prefix", &mysql_table_prefix_, "xbt_" },
			{ "mysql_user", &mysql_user_, "" },
			{ "offline_message", &offline_message_, "" },
			{ "pid_file", &pid_file_, "" },
			{ "query_log", &query_log_, "" },
			{ "redirect_url", &redirect_url_, "" },
			{ "table_announce_log", &table_announce_log_, "" },
			{ "table_files", &table_torrents_, "" },
			{ "table_files_users", &table_torrents_users_, "" },
			{ "table_scrape_log", &table_scrape_log_, "" },
			{ "table_users", &table_users_, "" },
			{ "torrent_pass_private_key", &torrent_pass_private_key_, "" },
			{ NULL, NULL, "" }
		};
		fill_map(attributes, v ? &v->attributes_string_ : NULL, attributes_string_);
	}
	if (v)
	{
		listen_ipas_ = v->listen_ipas_;
		listen_ports_ = v->listen_ports_;
	}
}

int config_t::set(const std::string_view name, const std::string_view value)
{
	if (attribute_t<std::string>* i = find_ptr(attributes_string_, name))
		*i->value = value;
	else if (name == "listen_ipa")
	{
		if (value != "*")
			listen_ipas_.insert(inet_addr(std::string(value).c_str()));
	}
	else
		return set(name, int(to_int(value)));
	return 0;
}

int config_t::set(const std::string_view name, int value)
{
	if (attribute_t<int>* i = find_ptr(attributes_int_, name))
		*i->value = value;
	else if (name == "listen_port")
		listen_ports_.insert(value);
	else
		return set(name, static_cast<bool>(value));
	return 0;
}

int config_t::set(const std::string_view name, bool value)
{
	if (attribute_t<bool>* i = find_ptr(attributes_bool_, name))
		*i->value = value;
	else
		return 1;
	return 0;
}
