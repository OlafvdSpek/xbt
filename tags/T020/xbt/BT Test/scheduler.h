#if !defined(AFX_SCHEDULER_H__86853B35_06D7_4678_A9AB_C966ECA148C0__INCLUDED_)
#define AFX_SCHEDULER_H__86853B35_06D7_4678_A9AB_C966ECA148C0__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <map>
#include "xif_key.h"

using namespace std;

class Cscheduler_entry
{
public:
	Cscheduler_entry& load(const Cxif_key&);
	Cxif_key save() const;
	Cscheduler_entry();

	int time;
	int profile;
};

class Cscheduler: public map<int, Cscheduler_entry>  
{
public:
	int find_active_profile(int time) const;
	Cscheduler& load(const Cxif_key&);
	Cxif_key save() const;
};

#endif // !defined(AFX_SCHEDULER_H__86853B35_06D7_4678_A9AB_C966ECA148C0__INCLUDED_)
