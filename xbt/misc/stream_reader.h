// stream_reader.h: interface for the Cstream_reader class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_STREAM_READER_H__FC96F4EB_360D_4836_9D31_2C0D0D0377A8__INCLUDED_)
#define AFX_STREAM_READER_H__FC96F4EB_360D_4836_9D31_2C0D0D0377A8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

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
		return *reinterpret_cast<const char*>(read(1));
	}

	int read_int32()
	{
		return *reinterpret_cast<const int*>(read(4));
	}

	__int64 read_int64()
	{
		return *reinterpret_cast<const __int64*>(read(8));
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
