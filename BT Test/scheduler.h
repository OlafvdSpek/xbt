#pragma once

#include <xif_key.h>

class Cscheduler_entry
{
public:
	Cscheduler_entry& load(const Cxif_key&);
	Cxif_key save() const;
	Cscheduler_entry();

	int time;
	int profile;
};

class Cscheduler: public std::map<int, Cscheduler_entry>
{
public:
	int find_active_profile(int time) const;
	Cscheduler& load(const Cxif_key&);
	Cxif_key save() const;
};
