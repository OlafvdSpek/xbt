#if !defined(AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_)
#define AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbvalue;

class Cconfig
{
public:
	Cconfig();
	Cbvalue read() const;
	const Cconfig& write(const Cbvalue&);

	bool m_bind_before_connect;
	bool m_log_peer_connect_failures;
	bool m_log_peer_connection_closures;
	bool m_log_peer_recv_failures;
	bool m_log_peer_send_failures;
	bool m_log_piece_valid;
	bool m_send_stop_event;
	bool m_upnp;
	int m_admin_port;
	int m_peer_limit;
	int m_peer_port;
	int m_public_ipa;
	int m_seeding_ratio;
	int m_torrent_limit;
	int m_torrent_upload_slots_max;
	int m_torrent_upload_slots_min;
	int m_tracker_port;
	int m_upload_rate;
	int m_upload_slots;
	string m_completes_dir;
	string m_incompletes_dir;
	string m_local_app_data_dir;
	string m_peer_id_prefix;
	string m_torrents_dir;
	string m_user_agent;
};

#endif // !defined(AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_)
