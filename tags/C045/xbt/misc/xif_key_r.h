// xif_key_r.h: interface for the Cxif_key_r class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_XIF_KEY_R_H__817C3F05_EB76_483D_929B_5547F4EB9B58__INCLUDED_)
#define AFX_XIF_KEY_R_H__817C3F05_EB76_483D_929B_5547F4EB9B58__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <vector>
#include "virtual_binary.h"
#include "xif_value.h"

using namespace std;

class Cxif_key_r
{
public:
	typedef vector<pair<int, Cxif_key_r> > t_key_map;
	typedef vector<pair<int, Cxif_value> > t_value_map;

	const Cxif_key_r& get_key(int id) const
	{
		return find_key(id)->second;
	}

	const Cxif_value& get_value(int id) const
	{
		static Cxif_value z;
		t_value_map::const_iterator i = find_value(id);
		return i == values().end() ? z : i->second;
	}

	float get_value_float(int id) const
	{
		return get_value(id).get_float();
	}

	float get_value_float(int id, float v) const
	{
		return get_value(id).get_float(v);
	}

	int get_value_int(int id) const
	{
		return get_value(id).get_int();
	}

	int get_value_int(int id, int v) const
	{
		return get_value(id).get_int(v);
	}

	__int64 get_value_int64(int id) const
	{
		return *reinterpret_cast<const __int64*>(get_value(id).get_data());
	}

	string get_value_string(int id) const
	{
		return get_value(id).get_string();
	}

	string get_value_string(int id, const string& v) const
	{
		return get_value(id).get_string(v);
	}

	const t_key_map& keys() const
	{
		return m_keys;
	}
	
	const t_value_map& values() const
	{
		return m_values;
	}

	int c_keys() const
	{
		return keys().size();
	}

	int c_values() const
	{
		return values().size();
	}

	bool has_key(int id) const
	{
		return find_key(id) != keys().end();
	}

	bool has_value(int id) const
	{
		return find_value(id) != values().end();
	}

	t_key_map::const_iterator find_key(int id) const;
	t_value_map::const_iterator find_value(int id) const;
	int import(Cvirtual_binary s);
private:
	int load(const byte* s);

	t_key_map m_keys;
	t_value_map m_values;
};

#endif // !defined(AFX_XIF_KEY_R_H__817C3F05_EB76_483D_929B_5547F4EB9B58__INCLUDED_)
