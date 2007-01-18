#include "stdafx.h"
#include "profiles.h"

enum
{
	v_name,
	v_seeding_ratio,
	v_seeding_ratio_enable,
	v_upload_rate,
	v_upload_rate_enable,
	v_upload_slots,
	v_upload_slots_enable,
	v_peer_limit,
	v_peer_limit_enable,
	v_torrent_limit,
	v_torrent_limit_enable,
};

Cprofile::Cprofile()
{
	seeding_ratio = 0;
	seeding_ratio_enable = false;
	upload_rate = false;
	upload_rate_enable = false;
	upload_slots = 0;
	upload_slots_enable = false;
	peer_limit = 0;
	peer_limit_enable = false;
	torrent_limit = 0;
	torrent_limit_enable = false;
}

Cprofile& Cprofile::load(const Cxif_key& v)
{
	name = v.get_value_string(v_name);
	seeding_ratio = v.get_value_int(v_seeding_ratio, 0);
	seeding_ratio_enable = v.get_value_int(v_seeding_ratio_enable, false);
	upload_rate = v.get_value_int(v_upload_rate, 0);
	upload_rate_enable = v.get_value_int(v_upload_rate_enable, false);
	upload_slots = v.get_value_int(v_upload_slots, 0);
	upload_slots_enable = v.get_value_int(v_upload_slots_enable, false);
	peer_limit = v.get_value_int(v_peer_limit, 0);
	peer_limit_enable = v.get_value_int(v_peer_limit_enable, false);
	torrent_limit = v.get_value_int(v_torrent_limit, 0);
	torrent_limit_enable = v.get_value_int(v_torrent_limit_enable, false);
	return *this;
}

Cxif_key Cprofile::save() const
{
	Cxif_key v;
	v.set_value_string(v_name, name);
	v.set_value_int(v_seeding_ratio, seeding_ratio);
	v.set_value_int(v_seeding_ratio_enable, seeding_ratio_enable);
	v.set_value_int(v_upload_rate, upload_rate);
	v.set_value_int(v_upload_rate_enable, upload_rate_enable);
	v.set_value_int(v_upload_slots, upload_slots);
	v.set_value_int(v_upload_slots_enable, upload_slots_enable);
	v.set_value_int(v_peer_limit, peer_limit);
	v.set_value_int(v_peer_limit_enable, peer_limit_enable);
	v.set_value_int(v_torrent_limit, torrent_limit);
	v.set_value_int(v_torrent_limit_enable, torrent_limit_enable);
	return v;
}

Cprofiles& Cprofiles::load(const Cxif_key& v)
{
	clear();
	for (int i = 0; i < v.c_keys(); i++)
		(*this)[i].load(v.get_key(i));
	return *this;
}

Cxif_key Cprofiles::save() const
{
	Cxif_key v;
	for (const_iterator i = begin(); i != end(); i++)
		v.open_key_edit(i->first) = i->second.save();
	return v;
}
