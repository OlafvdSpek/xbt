// config.h: interface for the Cconfig class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_)
#define AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cconfig  
{
public:
	Cconfig();

	bool m_bind_before_connect;
	bool m_end_mode;
	bool m_log_peer_connect_failures;
	bool m_log_peer_connection_closures;
	bool m_log_peer_recv_failures;
	bool m_log_peer_send_failures;
	int m_peer_limit;
	int m_public_ipa;
	int m_seeding_ratio;
	int m_torrent_limit;
	int m_upload_rate;
	int m_upload_slots;
	string m_completes_dir;
	string m_incompletes_dir;
	string m_local_app_data_dir;
	string m_torrents_dir;
};

#endif // !defined(AFX_CONFIG_H__CE8DA4C3_CDFC_46F3_A22E_ECCC9EAFD1DC__INCLUDED_)
