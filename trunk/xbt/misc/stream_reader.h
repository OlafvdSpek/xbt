// stream_reader.h: interface for the Cstream_reader class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_STREAM_READER_H__FC96F4EB_360D_4836_9D31_2C0D0D0377A8__INCLUDED_)
#define AFX_STREAM_READER_H__FC96F4EB_360D_4836_9D31_2C0D0D0377A8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "stream_int.h"
#include "virtual_binary.h"

class Cstream_reader  
{
public:
	const byte* d() const
	{
		return m_d;
	}

	const byte* d_end() const
	{
		return m_d.data_end();
	}

	const byte* r() const
	{
		return m_r;
	}

	const byte* read(int size)
	{
		const byte* v = r();
		skip(size);
		return v;
	}

	int read_int8()
	{
		m_r++;
		return read_int(1, m_r - 1);
	}

	int read_int16()
	{
		m_r += 2;
		return read_int(2, m_r - 2);
	}

	int read_int32()
	{
		m_r += 4;
		return read_int(4, m_r - 4);
	}

	__int64 read_int64()
	{
		m_r += 8;
		return read_int(8, m_r - 8);
	}

	Cvirtual_binary read_data()
	{
		int l = read_int32();
		return Cvirtual_binary(read(l), l);
	}

	string read_string()
	{
		int l = read_int32();
		return string(reinterpret_cast<const char*>(read(l)), l);
	}

	void skip(int o)
	{
		m_r += o;
	}

	Cstream_reader()
	{
	}

	Cstream_reader(const Cvirtual_binary& d)
	{
		m_r = m_d = d;
	}
private:
	Cvirtual_binary m_d;
	const byte* m_r;
};

#endif // !defined(AFX_STREAM_READER_H__FC96F4EB_360D_4836_9D31_2C0D0D0377A8__INCLUDED_)
