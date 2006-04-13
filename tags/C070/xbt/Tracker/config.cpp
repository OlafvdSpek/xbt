#include "stdafx.h"
#include "config.h"

Cconfig::Cconfig()
{
	m_announce_interval = 1800;
	m_anonymous_connect = true;
	m_anonymous_announce = true;
	m_anonymous_scrape = true;
	m_auto_register = true;
	m_clean_up_interval = 60;
	m_daemon = true;
	m_debug = false;
	m_gzip_announce = true;
	m_gzip_debug = true;
	m_gzip_scrape = true;
	m_listen_check = false;
	m_log_access = false;
	m_log_announce = false;
	m_log_scrape = false;
	m_mysql_database = "xbt";
	m_mysql_host = "localhost";
	m_mysql_table_prefix = "xbt_";
	m_mysql_user = "xbt";
	m_pid_file = "xbt_tracker.pid";
	m_read_config_interval = 60;
	m_read_db_interval = 60;
	m_redirect_url.erase();
	m_scrape_interval = 0;
	m_write_db_interval = 60;
}

int Cconfig::set(const string& name, const string& value)
{
	t_attribute<string> attributes[] =
	{
		"column_files_completed", &m_column_files_completed,
		"column_files_fid", &m_column_files_fid, 
		"column_files_leechers", &m_column_files_leechers, 
		"column_files_seeders", &m_column_files_seeders, 
		"column_users_uid", &m_column_users_uid, 
		"mysql_database", &m_mysql_database,
		"mysql_host", &m_mysql_host,
		"mysql_password", &m_mysql_password,
		"mysql_table_prefix", &m_mysql_table_prefix,
		"mysql_user", &m_mysql_user,
		"offline_message", &m_offline_message,
		"pid_file", &m_pid_file,
		"query_log", &m_query_log,
		"redirect_url", &m_redirect_url,
		"table_announce_log", &m_table_announce_log,
		"table_deny_from_hosts", &m_table_deny_from_hosts,
		"table_files", &m_table_files,
		"table_files_users", &m_table_files_users,
		"table_scrape_log", &m_table_scrape_log,
		"table_users", &m_table_users,
		NULL
	};
	if (t_attribute<string>* i = find(attributes, name))
		*i->value = value;
	else if (name == "listen_ipa" && value != "*")
		m_listen_ipas.insert(inet_addr(value.c_str()));
	else
		return set(name, atoi(value.c_str()));
	return 0;
}

int Cconfig::set(const string& name, int value)
{
	t_attribute<int> attributes[] =
	{
		"announce_interval", &m_announce_interval,
		"clean_up_interval", &m_clean_up_interval,
		"read_config_interval", &m_read_config_interval,
		"read_db_interval", &m_read_db_interval,
		"scrape_interval", &m_scrape_interval,
		"write_db_interval", &m_write_db_interval,
		NULL
	};
	if (t_attribute<int>* i = find(attributes, name))
		*i->value = value;
	else if (name == "listen_port")
		m_listen_ports.insert(value);
	else
		return set(name, static_cast<bool>(value));
	return 0;
}

int Cconfig::set(const string& name, bool value)
{
	t_attribute<bool> attributes[] =
	{
		"auto_register", &m_auto_register,
		"anonymous_connect", &m_anonymous_connect,
		"anonymous_announce", &m_anonymous_announce,
		"anonymous_scrape", &m_anonymous_scrape,
		"daemon", &m_daemon,
		"debug", &m_debug,
		"gzip_announce", &m_gzip_announce,
		"gzip_debug", &m_gzip_debug,
		"gzip_scrape", &m_gzip_scrape,
		"listen_check", &m_listen_check,
		"log_access", &m_log_access,
		"log_announce", &m_log_announce,
		"log_scrape", &m_log_scrape,
		NULL
	};
	if (t_attribute<bool>* i = find(attributes, name))
		*i->value = value;
	else
		return 1;
	return 0;
}
