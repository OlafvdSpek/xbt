// config.h: interface for the Cconfig class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_CONFIG_H__9BC23017_7D28_49AE_BDBC_6B920E27CA97__INCLUDED_)
#define AFX_CONFIG_H__9BC23017_7D28_49AE_BDBC_6B920E27CA97__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cconfig  
{
public:
	typedef set<int> t_listen_ipas;
	typedef set<int> t_listen_ports;

	void set(const string& name, const string& value);
	void set(const string& name, int value);
	Cconfig();

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
	int m_announce_interval;
	int m_clean_up_interval;
	int m_read_config_interval;
	int m_read_db_interval;
	int m_scrape_interval;
	int m_write_db_interval;
	int m_update_files_method;
	string m_redirect_url;
	t_listen_ipas m_listen_ipas;
	t_listen_ports m_listen_ports;
};

#endif // !defined(AFX_CONFIG_H__9BC23017_7D28_49AE_BDBC_6B920E27CA97__INCLUDED_)
