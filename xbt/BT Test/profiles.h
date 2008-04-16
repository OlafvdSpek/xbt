#pragma once

#include <map>
#include <string>
#include <xif_key.h>

class Cprofile
{
public:
	Cprofile& load(const Cxif_key&);
	Cxif_key save() const;
	Cprofile();

	std::string name;
	int seeding_ratio;
	bool seeding_ratio_enable;
	int upload_rate;
	bool upload_rate_enable;
	int upload_slots;
	bool upload_slots_enable;
	int peer_limit;
	bool peer_limit_enable;
	int torrent_limit;
	bool torrent_limit_enable;
};

class Cprofiles: public std::map<int, Cprofile>
{
public:
	Cprofiles& load(const Cxif_key&);
	Cxif_key save() const;
};
