// config.cpp: implementation of the Cconfig class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "config.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cconfig::Cconfig()
{
	m_announce_interval = 1800;
	m_anonymous_connect = true;
	m_anonymous_announce = true;
	m_anonymous_scrape = true;
	m_auto_register = true;
	m_clean_up_interval = 60;
	m_daemon = true;
	m_gzip_announce = true;
	m_gzip_debug = true;
	m_gzip_scrape = true;
	m_listen_check = true;
	m_listen_ipas.clear();
	m_listen_ports.clear();
	m_log_access = false;
	m_log_announce = false;
	m_log_scrape = false;
	m_read_config_interval = 300;
	m_read_db_interval = 60;
	m_redirect_url.erase();
	m_scrape_interval = 0;
	m_update_files_method = 0;
	m_write_db_interval = 60;
}

void Cconfig::set(const string& name, const string& value)
{
	if (name == "listen_ipa" && value != "*")
		m_listen_ipas.insert(inet_addr(value.c_str()));
	else if (name == "redirect_url")
		m_redirect_url = value;
	else
		set(name, atoi(value.c_str()));
}

void Cconfig::set(const string& name, int value)
{
	if (name == "announce_interval")
		m_announce_interval = value;
	else if (name == "auto_register")
		m_auto_register = value;
	else if (name == "anonymous_connect")
		m_anonymous_connect = value;
	else if (name == "anonymous_announce")
		m_anonymous_announce = value;
	else if (name == "anonymous_scrape")
		m_anonymous_scrape = value;
	else if (name == "clean_up_interval")
		m_clean_up_interval = value;
	else if (name == "daemon")
		m_daemon = value;
	else if (name == "gzip_announce")
		m_gzip_announce = value;
	else if (name == "gzip_debug")
		m_gzip_debug = value;
	else if (name == "gzip_scrape")
		m_gzip_scrape = value;
	else if (name == "listen_check")
		m_listen_check = value;
	else if (name == "listen_port")
		m_listen_ports.insert(value);
	else if (name == "log_access")
		m_log_access = value;
	else if (name == "log_announce")
		m_log_announce = value;
	else if (name == "log_scrape")
		m_log_scrape = value;
	else if (name == "read_config_interval")
		m_read_config_interval = value;
	else if (name == "read_db_interval")
		m_read_db_interval = value;
	else if (name == "scrape_interval")
		m_scrape_interval = value;
	else if (name == "update_files_method")
		m_update_files_method = value;
	else if (name == "write_db_interval")
		m_write_db_interval = value;
}
