// server.h: interface for the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
#define AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_admin_link.h"
#include "bt_file.h"
#include "bt_link.h"
#include "bt_tracker_account.h"
#include "bt_tracker_link.h"
#include "stream_writer.h"

class Cserver  
{
public:
	enum
	{
		df_alerts = 1,
		df_files = 2,
		df_peers = 4,
		df_trackers = 8,
	};

	void sub_file_priority(const string& file_id, const string& sub_file_id, int priority);
	string completes_dir();
	string incompletes_dir();
	string torrents_dir();
	void update_chokes();
	void update_send_quotas();
	void alert(const Calert&);
	void admin_port(int);
	void peer_port(int);
	void public_ipa(int);
	void seeding_ratio(int);
	void tracker_port(int);
	void upload_rate(int);
	void upload_slots(int);
	string state_fname() const;
	string trackers_fname() const;
	Cvirtual_binary save_state(bool intermediate);
	void load_state(const Cvirtual_binary&);
	int close(const string& id);
	int announce(const string& id);
	int start_file(const string& id);
	int stop_file(const string& id);
	string get_url(const string& id);
	int open(const Cvirtual_binary& info, const string& name);
	int open_url(const string&);
	Cvirtual_binary get_file_status(const string& id, int flags);
	Cvirtual_binary get_status(int flags);
	Cvirtual_binary get_trackers();
	void set_trackers(const Cvirtual_binary& d);
	void unlock();
	void lock();

	typedef list<Cbt_admin_link> t_admins;
	typedef list<Cbt_file> t_files;
	typedef list<Cbt_link> t_links;

	int pre_file_dump(const string& id, int flags) const;
	void file_dump(Cstream_writer&, const string& id, int flags) const;
	int pre_dump(int flags) const;
	void dump(Cstream_writer&, int flags) const;
	ostream& dump(ostream&) const;
	void insert_peer(const char* r, const sockaddr_in& a, const Csocket& s);
	int run();
	void stop();
	Cserver();
	~Cserver();

	int admin_port() const
	{
		return m_admin_port;
	}

	string dir() const
	{
		return m_dir;
	}

	void dir(const string& v)
	{
		m_dir = v;
	}	

	int peer_port() const
	{
		return m_peer_port;
	}

	int public_ipa() const
	{
		return m_public_ipa;
	}

	int seeding_ratio() const
	{
		return m_seeding_ratio;
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
		return m_upload_rate;
	}

	int upload_slots() const
	{
		return m_upload_slots;
	}
private:
	t_admins m_admins;
	Calerts m_alerts;
	t_files m_files;
	t_links m_links;
	Cbt_tracker_accounts m_tracker_accounts;

	int m_admin_port;
	string m_dir;
	int m_new_admin_port;
	int m_new_peer_port;
	int m_new_tracker_port;
	int m_peer_port;
	int m_public_ipa;
	int m_send_quota;
	bool m_run;
	int m_seeding_ratio;
	int m_tracker_port;
	int m_update_chokes_time;
	int m_update_send_quotas_time;
	int m_upload_rate;
	int m_upload_slots;

	CRITICAL_SECTION m_cs;
};

ostream& operator<<(ostream&, const Cserver&);

#endif // !defined(AFX_SERVER_H__4D905E0B_7206_44A7_B536_848E7E677429__INCLUDED_)
