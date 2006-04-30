#if !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
#define AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "block_list.h"
#include "bt_admin_link.h"
#include "bt_file.h"
#include "bt_link.h"
#include "bt_logger.h"
#include "bt_tracker_account.h"
#include "bt_tracker_link.h"
#include "config.h"
#include "http_link.h"
#include "profiles.h"
#include "scheduler.h"
#include "stream_writer.h"
#include "udp_tracker.h"
#include "version_check_handler.h"

class Cserver
{
public:
	enum
	{
		df_alerts = 1,
		df_files = 2,
		df_peers = 4,
		df_pieces = 16,
		df_trackers = 8,
	};

	Cbt_file* find_torrent(const std::string&);
	bool admin_authenticate(const std::string& user, const std::string& pass) const;
	Cbvalue admin_request(const Cbvalue& s);
	void check_remote_links();
	Chttp_link* http_request(int h, int p, const std::string& request, Chttp_response_handler*);
	Cvirtual_binary get_file_status(const std::string& id, int flags);
	Cvirtual_binary get_status(int flags);
	Cvirtual_binary get_trackers();
	Cvirtual_binary save_state(bool intermediate);
	Cxif_key get_block_list();
	Cxif_key get_profiles();
	Cxif_key get_scheduler();
	bool below_peer_limit() const;
	bool below_torrent_limit() const;
	int announce(const std::string& id);
	int close(const std::string& id, bool erase = false);
	int file_priority(const std::string&, int priority);
	int file_state(const std::string&, Cbt_file::t_state);
	int open(const Cvirtual_binary& info, const std::string& name);
	int open_url(const std::string&);
	int peer_connect(const std::string& id, int ipa, int port);
	int peer_disconnect(const std::string& id, int ipa);
	int peer_block(int ipa);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	static int version();
	std::string completes_dir() const;
	std::string conf_fname() const;
	std::string get_url(const std::string& id);
	std::string incompletes_dir() const;
	std::string local_app_data_dir() const;
	std::string peer_id_prefix() const;
	std::string profiles_fname() const;
	std::string scheduler_fname() const;
	std::string state_fname() const;
	std::string torrents_dir() const;
	std::string trackers_fname() const;
	std::string user_agent() const;
	void admin_port(int);
	void alert(const Calert&);
	void clean_scheduler();
	void completes_dir(const std::string&);
	void incompletes_dir(const std::string&);
	void load_config(const std::string&);
	void load_profile(const Cprofile&);
	void load_profile(const Cxif_key&);
	void load_state(const Cvirtual_binary&);
	void local_app_data_dir(const std::string&);
	void lock();
	void peer_id_prefix(const std::string&);
	void peer_port(int);
	void post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void public_ipa(int);
	void run_scheduler();
	void seeding_ratio(int);
	void send_stop_event(bool);
	void set_block_list(const Cxif_key&);
	void set_profiles(const Cxif_key&);
	void set_scheduler(const Cxif_key&);
	void set_trackers(const Cvirtual_binary& d);
	void sub_file_priority(const std::string& file_id, const std::string& sub_file_id, int priority);
	void torrent_end_mode(const std::string& file_id, bool v);
	void torrent_seeding_ratio(const std::string& file_id, bool override, int v);
	void torrent_trackers(const std::string& file_id, const std::string& v);
	void torrent_upload_slots_max(const std::string& file_id, bool override, int v);
	void torrent_upload_slots_min(const std::string& file_id, bool override, int v);
	void torrents_dir(const std::string&);
	void tracker_port(int);
	void unlock();
	void update_chokes();
	void update_states();
	void upload_rate(int);
	void upload_slots(int);
	void upnp(bool);
	void user_agent(const std::string&);

	typedef std::list<Cbt_admin_link> t_admins;
	typedef std::list<Cbt_file> t_files;
	typedef std::list<Chttp_link> t_http_links;
	typedef std::list<Cbt_link> t_links;

	int pre_file_dump(const std::string& id, int flags) const;
	void file_dump(Cstream_writer&, const std::string& id, int flags) const;
	int pre_dump(int flags) const;
	void dump(Cstream_writer&, int flags) const;
	void insert_peer(const char* r, const sockaddr_in& a, const Csocket& s);
	int run();
	void stop();
	static void term();
	Cserver();
	~Cserver();

	bool m_upload_rate_enabled;

	int admin_port() const
	{
		return m_admin_port;
	}

	bool bind_before_connect() const
	{
		return m_config.m_bind_before_connect;
	}

	void bind_before_connect(bool v)
	{
		m_config.m_bind_before_connect = v;
	}

	bool block_list_has(int ipa) const
	{
		return m_block_list.count(ipa);
	}

	const Cconfig& config() const
	{
		return m_config;
	}

	bool log_peer_connect_failures() const
	{
		return m_config.m_log_peer_connect_failures;
	}

	bool log_peer_connection_closures() const
	{
		return m_config.m_log_peer_connection_closures;
	}

	bool log_peer_recv_failures() const
	{
		return m_config.m_log_peer_recv_failures;
	}

	bool log_peer_send_failures() const
	{
		return m_config.m_log_peer_send_failures;
	}

	bool log_piece_valid() const
	{
		return m_config.m_log_piece_valid;
	}

	Cbt_logger& logger()
	{
		return m_logger;
	}

	const std::string& peer_id() const
	{
		return m_peer_id;
	}

	const std::string& peer_key() const
	{
		return m_peer_key;
	}

	int peer_limit() const
	{
		return m_config.m_peer_limit;
	}

	void peer_limit(int v)
	{
		m_config.m_peer_limit = v;
	}
	
	int peer_port() const
	{
		return m_peer_port;
	}

	int public_ipa() const
	{
		return m_config.m_public_ipa;
	}

	int seeding_ratio() const
	{
		return m_config.m_seeding_ratio;
	}

	bool send_stop_event() const
	{
		return m_config.m_send_stop_event;
	}

	time_t time() const
	{
		return m_time;
	}

	int torrent_limit() const
	{
		return m_config.m_torrent_limit;
	}

	void torrent_limit(int v)
	{
		m_config.m_torrent_limit = v;
	}

	int torrent_upload_slots_max() const
	{
		return m_config.m_torrent_upload_slots_max;
	}

	int torrent_upload_slots_min() const
	{
		return m_config.m_torrent_upload_slots_min;
	}
	
	const Cbt_tracker_accounts& tracker_accounts()
	{
		return m_tracker_accounts;
	}

	int tracker_port() const
	{
		return m_tracker_port;
	}

	int upload_rate() const
	{
		return m_config.m_upload_rate;
	}

	int upload_slots() const
	{
		return m_config.m_upload_slots;
	}
private:
	static void sig_handler(int v);

	t_admins m_admins;
	Calerts m_alerts;
	Cblock_list m_block_list;
	t_files m_files;
	t_http_links m_http_links;
	t_links m_links;
	Cbt_logger m_logger;
	Cbt_tracker_accounts m_tracker_accounts;
	Cconfig m_config;
	Cprofiles m_profiles;
	Cscheduler m_scheduler;
	Cudp_tracker m_udp_tracker;

	int m_admin_port;
	int m_peer_port;
	int m_send_quota;
	time_t m_check_remote_links_time;
	bool m_run;
	time_t m_run_scheduler_time;
	time_t m_save_state_time;
	time_t m_start_time;
	time_t m_time;
	int m_tracker_port;
	time_t m_update_chokes_time;
	time_t m_update_send_quotas_time;
	time_t m_update_states_time;
	std::string m_peer_id;
	std::string m_peer_key;
	Cversion_check_handler m_version_check_handler;

#ifdef WIN32
	CRITICAL_SECTION m_cs;
#else
	int m_cs;
#endif
};

#endif // !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
