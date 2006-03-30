#if !defined(AFX_PROFILES_H__70020ECF_1C5D_4352_9726_6F34081BAFC5__INCLUDED_)
#define AFX_PROFILES_H__70020ECF_1C5D_4352_9726_6F34081BAFC5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <map>
#include <string>
#include "xif_key.h"

using namespace std;

class Cprofile
{
public:
	Cprofile& load(const Cxif_key&);
	Cxif_key save() const;
	Cprofile();

	string name;
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

class Cprofiles: public map<int, Cprofile>
{
public:
	Cprofiles& load(const Cxif_key&);
	Cxif_key save() const;
};

#endif // !defined(AFX_PROFILES_H__70020ECF_1C5D_4352_9726_6F34081BAFC5__INCLUDED_)
