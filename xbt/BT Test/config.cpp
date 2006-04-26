#include "stdafx.h"
#include "config.h"

#include "bvalue.h"

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
	m_log_peer_send_failures = false;
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
	string home = SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true) ? path : "C:";
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

int Cconfig::set(const string& name, const string& value)
{
	t_attribute<string> attributes[] =
	{
		"admin_user", &m_admin_user, 
		"admin_pass", &m_admin_pass, 
		"completes_dir", &m_completes_dir, 
		"incompletes_dir", &m_incompletes_dir, 
		"peer_id_prefix", &m_peer_id_prefix, 
		"torrents_dir", &m_torrents_dir, 
		"user_agent", &m_user_agent, 
		NULL
	};
	if (t_attribute<string>* i = find(attributes, name))
		*i->value = value;
	else
		return set(name, atoi(value.c_str()));
	return 0;
}

int Cconfig::set(const string& name, int value)
{
	t_attribute<int> attributes[] =
	{
		"admin_port", &m_admin_port, 
		"peer_limit", &m_peer_limit, 
		"peer_port", &m_peer_port, 
		"public_ipa", &m_public_ipa, 
		"seeding_ratio", &m_seeding_ratio, 
		"torrent_limit", &m_torrent_limit, 
		"tracker_port", &m_tracker_port, 
		"upload_rate", &m_upload_rate, 
		"upload_slots", &m_upload_slots, 
		NULL
	};
	if (t_attribute<int>* i = find(attributes, name))
		*i->value = value;
	else
		return set(name, static_cast<bool>(value));
	return 0;
}

int Cconfig::set(const string& name, bool value)
{
	t_attribute<bool> attributes[] =
	{
		"bind_before_connect", &m_bind_before_connect, 
		"log_peer_connect_failures", &m_log_peer_connect_failures, 
		"log_peer_connection_closures", &m_log_peer_connection_closures, 
		"log_peer_recv_failures", &m_log_peer_recv_failures, 
		"log_peer_send_failures", &m_log_peer_send_failures, 
		"log_piece_valid", &m_log_piece_valid, 
		"send_stop_event", &m_send_stop_event, 
		"upnp", &m_upnp, 
		NULL
	};
	if (t_attribute<bool>* i = find(attributes, name))
		*i->value = value;
	else
		return 1;
	return 0;
}

ostream& Cconfig::operator<<(ostream& os) const
{
	return os
		<< "admin_port = " << m_admin_port << endl
		<< "admin_user = " << m_admin_user << endl
		<< "admin_pass = " << m_admin_pass << endl
		<< "bind_before_connect = " << m_bind_before_connect << endl
		<< "completes_dir = " << m_completes_dir << endl
		<< "incompletes_dir = " << m_incompletes_dir << endl
		<< "log_peer_connect_failures = " << m_log_peer_connect_failures << endl
		<< "log_peer_connection_closures = " << m_log_peer_connection_closures << endl
		<< "log_peer_recv_failures = " << m_log_peer_recv_failures << endl
		<< "log_peer_send_failures = " << m_log_peer_send_failures << endl
		<< "log_piece_valid = " << m_log_piece_valid << endl
		<< "peer_id_prefix = " << m_peer_id_prefix << endl
		<< "peer_limit = " << m_peer_limit << endl
		<< "peer_port = " << m_peer_port << endl
		<< "public_ipa = " << m_public_ipa << endl
		<< "seeding_ratio = " << m_seeding_ratio << endl
		<< "send_stop_event = " << m_send_stop_event << endl
		<< "torrent_limit = " << m_torrent_limit << endl
		<< "torrents_dir = " << m_torrents_dir << endl
		<< "tracker_port = " << m_tracker_port << endl
		<< "upload_rate = " << m_upload_rate << endl
		<< "upload_slots = " << m_upload_slots << endl
		<< "upnp = " << m_upnp << endl
		<< "user_agent = " << m_user_agent << endl
		;
}

ostream& operator<<(ostream& os, const Cconfig& v)
{
	return v.operator<<(os);
}
