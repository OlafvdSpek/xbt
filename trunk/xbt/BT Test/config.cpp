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
	m_admin_port = 6879;
	m_bind_before_connect = false;
	m_completes_dir = "Completes";
	m_end_mode = false;
	m_incompletes_dir = "Incompletes";
	m_local_app_data_dir = ".";
	m_log_peer_connect_failures = false;
	m_log_peer_connection_closures = false;
	m_log_peer_recv_failures = false;
	m_log_peer_send_failures = true;
	m_peer_limit = 0;
	m_peer_port = 6881;
	m_public_ipa = 0;
	m_seeding_ratio = 0;
	m_torrent_limit = 0;
	m_torrents_dir = "Torrents";
	m_tracker_port = 2710;
	m_upload_rate = 0;
	m_upload_slots = 8;
#ifndef WIN32
	string home = get_env("HOME");
	if (!home.empty())
	{
		m_completes_dir = home + "/XBT/Completes";
		m_local_app_data_dir = home + "/XBT";
		m_incompletes_dir = home + "/XBT/Incompletes";
		m_torrents_dir = home + "/XBT/Torrents";
	}
#endif
}
