// bt_tracker_account.cpp: implementation of the Cbt_tracker_account class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_tracker_account.h"

#include "stream_reader.h"

#define for if (0) {} else for

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_tracker_account::Cbt_tracker_account()
{

}

Cbt_tracker_account::Cbt_tracker_account(const string& tracker, const string& user, const string& pass)
{
	m_tracker = tracker;
	m_user = user;
	m_pass = pass;
}

int Cbt_tracker_account::pre_dump() const
{
	return tracker().size() + user().size() + pass().size() + 12;
}

void Cbt_tracker_account::dump(Cstream_writer& w) const
{
	w.write_string(tracker());
	w.write_string(user());
	w.write_string(pass());
}

Cvirtual_binary Cbt_tracker_accounts::dump() const
{
	int cb_d = 4;
	for (const_iterator i = begin(); i != end(); i++)
		cb_d += i->pre_dump();
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(cb_d));
	w.write_int32(size());
	for (const_iterator i = begin(); i != end(); i++)
		i->dump(w);
	assert(w.w() == d.data_end());
	return d;

}

const Cbt_tracker_account* Cbt_tracker_accounts::find(const string& v) const
{
	for (const_iterator i = begin(); i != end(); i++)
	{
		if (i->tracker() == v)
			return &*i;
	}
	return NULL;
}

void Cbt_tracker_accounts::load(const Cvirtual_binary& s)
{
	clear();
	if (s.size() < 4)
		return;
	Cstream_reader r(s);
	for (int count = r.read_int(4); count--; )
	{
		string tracker = r.read_string();
		string name = r.read_string();
		string pass = r.read_string();
		push_back(Cbt_tracker_account(tracker, name, pass));
	}
}
