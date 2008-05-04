#include "stdafx.h"
#include "config.h"

#include <bvalue.h>

Cconfig::Cconfig()
{
	fill_maps(NULL);
}

Cconfig::Cconfig(const Cconfig& v)
{
	fill_maps(&v);
}

const Cconfig& Cconfig::operator=(const Cconfig& v)
{
	fill_maps(&v);
	return *this;
}

void Cconfig::fill_maps(const Cconfig* v)
{
	{
		t_attribute<bool> attributes[] =
		{
			"bind_before_connect", &m_bind_before_connect, false,
			"log_peer_connect_failures", &m_log_peer_connect_failures, false,
			"log_peer_connection_closures", &m_log_peer_connection_closures, false,
			"log_peer_recv_failures", &m_log_peer_recv_failures, false,
			"log_peer_send_failures", &m_log_peer_send_failures, false,
			"log_piece_valid", &m_log_piece_valid, false,
			"send_stop_event", &m_send_stop_event, false,
			"upnp", &m_upnp, true,
			NULL
		};
		fill_map(attributes, v ? &v->m_attributes_bool : NULL, m_attributes_bool);
	}
	{
		t_attribute<int> attributes[] =
		{
			"admin_port", &m_admin_port, 6879,
			"peer_limit", &m_peer_limit, 0,
			"peer_port", &m_peer_port, 6881,
			"seeding_ratio", &m_seeding_ratio, 0,
			"torrent_limit", &m_torrent_limit, 0,
			"torrent_upload_slots_max", &m_torrent_upload_slots_max, 0,
			"torrent_upload_slots_min", &m_torrent_upload_slots_min, 0,
			"upload_rate", &m_upload_rate, 0,
			"upload_slots", &m_upload_slots, 8,
			NULL
		};
		fill_map(attributes, v ? &v->m_attributes_int : NULL, m_attributes_int);
	}
	{
		t_attribute<std::string> attributes[] =
		{
			"admin_user", &m_admin_user, "xbt",
			"admin_pass", &m_admin_pass, "",
			"completes_dir", &m_completes_dir, "Completes",
			"incompletes_dir", &m_incompletes_dir, "Incompletes",
			"peer_id_prefix", &m_peer_id_prefix, "",
			"public_ipa", &m_public_ipa, "",
			"torrents_dir", &m_torrents_dir, "Torrents",
			"user_agent", &m_user_agent, "",
			NULL, NULL, ""
		};
		fill_map(attributes, v ? &v->m_attributes_string : NULL, m_attributes_string);
	}
	if (v)
	{
		m_local_app_data_dir = v->m_local_app_data_dir;
	}
	else
	{
#ifdef WIN32
		char path[MAX_PATH];
		std::string home = SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true) ? path : "C:";
#else
		std::string home = get_env("HOME");
#endif
		if (home.empty())
			m_local_app_data_dir = ".";
		else
		{
			m_completes_dir = home + "/XBT/Completes";
			m_local_app_data_dir = home + "/XBT";
			m_incompletes_dir = home + "/XBT/Incompletes";
			m_torrents_dir = home + "/XBT/Torrents";
		}
	}
}

std::ostream& Cconfig::operator<<(std::ostream& os) const
{
	return save(os);
}

std::ostream& operator<<(std::ostream& os, const Cconfig& v)
{
	return v.operator<<(os);
}
