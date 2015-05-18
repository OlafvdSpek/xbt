#include "stdafx.h"
#include "tracker_input.h"

void Ctracker_input::set(const std::string& name, const std::string& value)
{
	if (name.empty())
		return;
	switch (name[0])
	{
	case 'd':
		if (name == "downloaded")
			m_downloaded = to_int(value);
		break;
	case 'e':
		if (name == "event")
		{
			if (value == "completed")
				m_event = e_completed;
			else if (value == "started")
				m_event = e_started;
			else if (value == "stopped")
				m_event = e_stopped;
			else
				m_event = e_none;
		}
		break;
	case 'i':
		if (name == "info_hash" && value.size() == 20)
		{
			m_info_hash = value;
			m_info_hashes.push_back(value);
		}
		else if (name == "ip")
			m_ipa = inet_addr(value.c_str());
		break;
	case 'l':
		if (name == "left")
			m_left = to_int(value);
		break;
	case 'p':
		if (name == "peer_id" && value.size() == 20)
			memcpy(m_peer_id, value);
		else if (name == "port")
			m_port = htons(to_int(value));
		break;
	case 'u':
		if (name == "uploaded")
			m_uploaded = to_int(value);
		break;
	}
}

bool Ctracker_input::valid() const
{
	return m_downloaded >= 0
		&& (m_event != e_completed || !m_left)
		&& m_info_hash.size() == 20
		&& m_left >= -1
		&& m_peer_id.size() == 20
		&& m_port >= 0
		&& m_uploaded >= 0;
}
