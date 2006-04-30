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
		m_r += size;
		return m_r - size;
	}

	long long read_int(int cb)
	{
		m_r += cb;
		return ::read_int(cb, m_r - cb);
	}

	Cvirtual_binary read_data()
	{
		int l = read_int(4);
		return Cvirtual_binary(read(l), l);
	}

	std::string read_string()
	{
		int l = read_int(4);
		return std::string(reinterpret_cast<const char*>(read(l)), l);
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
