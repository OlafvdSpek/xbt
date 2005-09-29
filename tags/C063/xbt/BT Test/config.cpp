#include "stdafx.h"
#include "config.h"

#include "bvalue.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cconfig::Cconfig()
{
	m_admin_port = 6879;
	m_bind_before_connect = false;
	m_completes_dir = "Completes";
	m_incompletes_dir = "Incompletes";
	m_local_app_data_dir = ".";
	m_log_peer_connect_failures = false;
	m_log_peer_connection_closures = false;
	m_log_peer_recv_failures = false;
	m_log_peer_send_failures = true;
	m_log_piece_valid = false;
	m_peer_limit = 0;
	m_peer_port = 6881;
	m_public_ipa = 0;
	m_seeding_ratio = 0;
	m_send_stop_event = false;
	m_torrent_limit = 0;
	m_torrent_upload_slots_max = 0;
	m_torrent_upload_slots_min = 0;
	m_torrents_dir = "Torrents";
	m_tracker_port = 2710;
	m_upload_rate = 0;
	m_upload_slots = 8;
	m_upnp = true;
#ifdef WIN32
	char path[MAX_PATH];
	string home = FAILED(SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true)) ? "C:" : path;
#else
	string home = get_env("HOME");
#endif
	if (!home.empty())
	{
		m_completes_dir = home + "/XBT/Completes";
		m_local_app_data_dir = home + "/XBT";
		m_incompletes_dir = home + "/XBT/Incompletes";
		m_torrents_dir = home + "/XBT/Torrents";
	}
}

const Cconfig& Cconfig::write(const Cbvalue& v)
{
	if (v.d_has("admin_port"))
		m_admin_port = v.d("admin_port").i();
	if (v.d_has("bind_before_connect"))
		m_bind_before_connect = v.d("bind_before_connect").i();
	if (v.d_has("completes_dir"))
		m_completes_dir = v.d("completes_dir").s();
	if (v.d_has("incompletes_dir"))
		m_incompletes_dir = v.d("incompletes_dir").s();
	if (v.d_has("log_peer_connect_failures"))
		m_log_peer_connect_failures = v.d("log_peer_connect_failures").i();
	if (v.d_has("log_peer_connection_closures"))
		m_log_peer_connection_closures = v.d("log_peer_connection_closures").i();
	if (v.d_has("log_peer_recv_failures"))
		m_log_peer_recv_failures = v.d("log_peer_recv_failures").i();
	if (v.d_has("log_peer_send_failures"))
		m_log_peer_send_failures = v.d("log_peer_send_failures").i();
	if (v.d_has("log_piece_valid"))
		m_log_piece_valid = v.d("log_piece_valid").i();
	if (v.d_has("peer_limit"))
		m_peer_limit = v.d("peer_limit").i();
	if (v.d_has("peer_port"))
		m_peer_port = v.d("peer_port").i();
	if (v.d_has("public_ipa"))
		m_public_ipa = v.d("public_ipa").i();
	if (v.d_has("seeding_ratio"))
		m_seeding_ratio = v.d("seeding_ratio").i();
	if (v.d_has("torrent_limit"))
		m_torrent_limit = v.d("torrent_limit").i();
	if (v.d_has("torrents_dir"))
		m_torrents_dir = v.d("torrents_dir").s();
	if (v.d_has("tracker_port"))
		m_tracker_port = v.d("tracker_port").i();
	if (v.d_has("upload_rate"))
		m_upload_rate = v.d("upload_rate").i();
	if (v.d_has("upload_slots"))
		m_upload_slots = v.d("upload_slots").i();
	if (v.d_has("upnp"))
		m_upnp = v.d("upnp").i();
	if (v.d_has("user_agent"))
		m_user_agent = v.d("user_agent").s();
	return *this;
}

Cbvalue Cconfig::read() const
{
	Cbvalue v;
	v.d("admin_port", m_admin_port);
	v.d("bind_before_connect", m_bind_before_connect);
	v.d("completes_dir", m_completes_dir);
	v.d("incompletes_dir", m_incompletes_dir);
	v.d("log_peer_connect_failures", m_log_peer_connect_failures);
	v.d("log_peer_connection_closures", m_log_peer_connection_closures);
	v.d("log_peer_recv_failures", m_log_peer_recv_failures);
	v.d("log_peer_send_failures", m_log_peer_send_failures);
	v.d("log_piece_valid", m_log_piece_valid);
	v.d("peer_limit", m_peer_limit);
	v.d("peer_port", m_peer_port);
	v.d("public_ipa", m_public_ipa);
	v.d("seeding_ratio", m_seeding_ratio);
	v.d("torrent_limit", m_torrent_limit);
	v.d("torrents_dir", m_torrents_dir);
	v.d("tracker_port", m_tracker_port);
	v.d("upload_rate", m_upload_rate);
	v.d("upload_slots", m_upload_slots);
	v.d("upnp", m_upnp);
	v.d("user_agent", m_user_agent);
	return v;
}
