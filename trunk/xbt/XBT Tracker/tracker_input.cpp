// tracker_input.cpp: implementation of the Ctracker_input class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "tracker_input.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Ctracker_input::Ctracker_input()
{
	m_downloaded = 0;
	m_event = e_none;
	m_ipa = 0;
	m_left = 0;
	m_port = 0;
	m_uploaded = 0;
	m_num_want = -1;
	m_no_peer_id = false;
}

void Ctracker_input::set(const string& name, const string& value)
{
	if (name.empty())
		return;
	switch (name[0])
	{
	case 'd':
		if (name == "downloaded")
			m_downloaded = atoi(value.c_str());
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
		if (name == "info_hash" && value.length() == 20)
			m_info_hash = value;
		else if (name == "ip")
			m_ipa = inet_addr(value.c_str());
		break;
	case 'l':
		if (name == "left")
			m_left = atoi(value.c_str());
		break;
	case 'n':
		if (name == "no_peer_id")
			m_no_peer_id = atoi(value.c_str());
		else if (name == "numwant")
			m_num_want = atoi(value.c_str());
		break;
	case 'p':
		if (name == "peer_id" )
			m_peer_id = value;
		else if (name == "port")
			m_port = htons(atoi(value.c_str()));
		break;
	case 'u':
		if (name == "uploaded")
			m_uploaded = atoi(value.c_str());
		break;
	}
}

bool Ctracker_input::valid() const
{
	return m_info_hash.length() == 20
		&& m_peer_id.length() == 20;
}
