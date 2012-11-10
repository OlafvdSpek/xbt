#include "stdafx.h"
#include "xbt/xif_key_r.h"

#include <stream_int.h>
#include <xbt/shared_data.h>
#include <xbt/xif_key.h>
#include <zlib.h>

static int read_int(const byte*& r)
{
	r += 4;
	return read_int_le(4, r - 4);
}

int Cxif_key_r::import(data_ref s)
{
	const t_xif_header_fast& h = *reinterpret_cast<const t_xif_header_fast*>(s.data());
	if (s.size() < sizeof(t_xif_header_fast) + 8
		|| h.id != file_id
		|| h.version != file_version_fast)
		return 1;
	unsigned long cb_d = h.size_uncompressed;
	if (cb_d)
	{
		shared_data d(cb_d);
		if (Z_OK != uncompress(d.data(), &cb_d, &s[sizeof(t_xif_header_fast)], h.size_compressed))
			return 1;
		load(d.data());
	}
	else
	{
		load(&s[sizeof(t_xif_header_fast)]);
	}
	return 0;
}

int Cxif_key_r::load(const byte* s)
{
	const byte* r = s;
	{
		int count = read_int(r);
		int id = 0;
		m_keys.reserve(count);
		while (count--)
		{
			id += read_int(r);
			m_keys.push_back(std::make_pair(id, Cxif_key_r()));
			r += m_keys.rbegin()->second.load(r);
		}
	}
	{
		int count = read_int(r);
		int id = 0;
		m_values.reserve(count);
		while (count--)
		{
			id += read_int(r);
			m_values.push_back(std::make_pair(id, Cxif_value()));
			m_values.rbegin()->second.load_new(r);
		}
	}
	return r - s;
}

const Cxif_key_r* Cxif_key_r::find_key(int id) const
{
	t_key_map::const_iterator i = keys().begin();
	while (i != keys().end() && i->first != id)
		i++;
	return i == keys().end() ? NULL : &i->second;
}

const Cxif_value* Cxif_key_r::find_value(int id) const
{
	t_value_map::const_iterator i = values().begin();
	while (i != values().end() && i->first != id)
		i++;
	return i == values().end() ? NULL : &i->second;
}
