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
