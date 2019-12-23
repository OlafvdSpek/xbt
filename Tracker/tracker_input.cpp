#include "stdafx.h"
#include "tracker_input.h"

void tracker_input_t::set(std::string_view name, std::string_view value)
{
	if (name.empty())
		return;
	switch (name[0])
	{
	case 'd':
		if (name == "downloaded")
			downloaded_ = to_int(value);
		break;
	case 'e':
		if (name == "event")
		{
			if (value == "completed")
				event_ = e_completed;
			else if (value == "started")
				event_ = e_started;
			else if (value == "stopped")
				event_ = e_stopped;
			else
				event_ = e_none;
		}
		break;
	case 'i':
		if (name == "info_hash" && value.size() == 20)
		{
			info_hash_ = value;
			info_hashes_.emplace_back(value);
		}
		else if (name == "ip")
			ipa_ = inet_addr(std::string(value).c_str());
		break;
	case 'l':
		if (name == "left")
			left_ = to_int(value);
		break;
	case 'p':
		if (name == "peer_id" && value.size() == 20)
			memcpy(peer_id_, value);
		else if (name == "port")
			port_ = htons(to_int(value));
		break;
	case 'u':
		if (name == "uploaded")
			uploaded_ = to_int(value);
		break;
	}
}

bool tracker_input_t::valid() const
{
	return downloaded_ >= 0
		&& (event_ != e_completed || !left_)
		&& info_hash_.size() == 20
		&& left_ >= -1
		&& peer_id_.size() == 20
		&& port_ >= 0
		&& uploaded_ >= 0;
}
