// stream_writer.h: interface for the Cstream_writer class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_STREAM_WRITER_H__283B8C8E_68DD_4E16_9122_42ADD010E634__INCLUDED_)
#define AFX_STREAM_WRITER_H__283B8C8E_68DD_4E16_9122_42ADD010E634__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cstream_writer  
{
public:
	byte* w() const
	{
		return m_w;
	}

	byte* write(int size)
	{
		byte* v = w();
		skip(size);
		return v;
	}

	void write_int8(int v)
	{
		*reinterpret_cast<char*>(write(1)) = v;
	}

	void write_int32(int v)
	{
		*reinterpret_cast<int*>(write(4)) = v;
	}

	void write_int64(__int64 v)
	{
		*reinterpret_cast<__int64*>(write(8)) = v;
	}

	void write_string(const string& v)
	{
		write_int32(v.length());
		memcpy(write(v.length()), v.c_str(), v.length());
	}

	void skip(int o)
	{
		m_w += o;
	}

	Cstream_writer()
	{
	}

	Cstream_writer(byte* w)
	{
		m_w = w;
	}
private:
	byte* m_w;
};

#endif // !defined(AFX_STREAM_WRITER_H__283B8C8E_68DD_4E16_9122_42ADD010E634__INCLUDED_)
