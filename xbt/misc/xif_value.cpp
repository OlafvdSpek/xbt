// xif_value.cpp: implementation of the Cxif_value class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "xif_value.h"

#include <minmax.h>
#include <zlib.h>

template <class T>
static T read(const byte*& r)
{
	T v = *reinterpret_cast<const T*>(r);
	r += sizeof(T);
	return v;
}

template <class T>
static void write(byte*& w, T v)
{
	*reinterpret_cast<T*>(w) = v;
	w += sizeof(T);
}

static int read_int(const byte*& r)
{
	return read<int>(r);
}

t_vt Cxif_value::get_type() const
{
	if (m_type != vt_unknown)
		return m_type;
	const byte* data = m_data.data();
	if (!data)
		return vt_binary;
	int size = m_data.size();
	if (!data[size - 1])
	{
		const byte* r = data;
		int c = size - 1;
		while (c--)
		{
			if (*r != 9 && *r < 0x20)
				break;
			r++;
		}
		if (c == -1)
			return vt_string;
	}	
	if (size == 4)
		return vt_int32;
	return vt_binary;
}

void Cxif_value::load_old(const byte*& data)
{
	m_data.clear();
	int size = read_int(data);
	if (size == 4)
		memcpy(m_value, data, size);
	memcpy(m_data.write_start(size), data, size);
	data += size;
	m_type = vt_unknown;
	m_type = get_type();
}

void Cxif_value::load_new(const byte*& data)
{
	m_data.clear();
	m_type = static_cast<t_vt>(read<__int8>(data));
	switch (m_type)
	{
	case vt_bin32:
		m_value_int = read<unsigned __int32>(data);
		break;
	case vt_int32:
		m_value_int = read<__int32>(data);
		break;
	case vt_float:
		m_value_float = read<float>(data);
		break;
	case vt_external_binary:
		m_data.write_start(read_int(data));
		break;
	default:
		{
			int size = read_int(data);
			memcpy(m_data.write_start(size), data, size);
			data += size;
		}
	}
}

void Cxif_value::load_external(const byte*& data)
{
	if (!external_data())
		return;
	memcpy(m_data.data_edit(), data, get_size());
	data += get_size();
}

int Cxif_value::skip(const byte* s)
{
	const byte* r = s;
	t_vt type = static_cast<t_vt>(read<__int8>(r));
	switch (type)
	{
	case vt_bin32:
		read<unsigned __int32>(r);
		break;
	case vt_int32:
		read<__int32>(r);
		break;
	case vt_float:
		read<float>(r);
		break;
	case vt_external_binary:
		read_int(r);
		break;
	default:
		{
			int size = read_int(r);
			r += size;
		}
	}
	return r - s;
}

void Cxif_value::save(byte*& data) const
{
	*reinterpret_cast<__int8*>(data++) = external_data() ? vt_external_binary : m_type;
	switch (m_type)
	{
	case vt_bin32:
	case vt_int32:
		write(data, get_int());
		break;
	case vt_float:
		write(data, get_float());
		break;
	default:
		{
			int size = get_size();
			write(data, size);
			if (!external_data())
			{
				memcpy(data, get_data(), size);
				data += size;
			}
		}
	}
}

bool Cxif_value::external_data() const
{
	return m_type == vt_external_binary;
}

void Cxif_value::external_save(byte*& data) const
{
	if (!external_data())
		return;
	memcpy(data, get_data(), get_size());
	data += get_size();
}
