// scheduler.cpp: implementation of the Cscheduler class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "scheduler.h"

enum
{
	v_time,
	v_profile,
};

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cscheduler_entry::Cscheduler_entry()
{
	time = 0;
	profile = 0;
}

Cscheduler_entry& Cscheduler_entry::load(const Cxif_key& v)
{
	time = v.get_value_int(v_time, 0);
	profile = v.get_value_int(v_profile, 0);
	return *this;
}

Cxif_key Cscheduler_entry::save() const
{
	Cxif_key v;
	v.set_value_int(v_time, time);
	v.set_value_int(v_profile, profile);
	return v;
}

Cscheduler& Cscheduler::load(const Cxif_key& v)
{
	clear();
	for (int i = 0; i < v.c_keys(); i++)
		(*this)[i].load(v.get_key(i));
	return *this;
}

Cxif_key Cscheduler::save() const
{
	Cxif_key v;
	for (const_iterator i = begin(); i != end(); i++)
		v.open_key_edit(i->first) = i->second.save();
	return v;
}

int Cscheduler::find_active_profile(int time) const
{
	int profile = -1;
	int profile_time = 0;
	for (const_iterator i = begin(); i != end(); i++)
	{
		if (profile_time < i->second.time && i->second.time < time)
		{
			profile = i->second.profile;
			profile_time = i->second.time;
		}
	}
	return profile == -1 && time != INT_MAX ? find_active_profile(INT_MAX) : profile;
}
