#if !defined(AFX_STREAM_INT_H__57F9BAC0_D02F_4067_9891_5C484F35B67F__INCLUDED_)
#define AFX_STREAM_INT_H__57F9BAC0_D02F_4067_9891_5C484F35B67F__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

__int64 read_int(int cb, const void* r0)
{
	const unsigned char* r = reinterpret_cast<const unsigned char*>(r0);
	__int64 v = 0;
	while (cb--)
		v = v << 8 | *r++;
	return v;
}

template <class T>
T write_int(int cb, T w0, __int64 v)
{
	unsigned char* w = reinterpret_cast<unsigned char*>(w0);
	w += cb;
	for (int i = 0; i < cb; i++)
	{
		*--w = v & 0xff;
		v >>= 8;
	}
	return w + cb;
}

__int64 read_int_le(int cb, const void* r0)
{
	const unsigned char* r = reinterpret_cast<const unsigned char*>(r0);
	r += cb;
	__int64 v = 0;
	while (cb--)
		v = v << 8 | *--r;
	return v;
}

template <class T>
T write_int_le(int cb, T w0, __int64 v)
{
	unsigned char* w = reinterpret_cast<unsigned char*>(w0);
	for (int i = 0; i < cb; i++)
	{
		*w++ = v & 0xff;
		v >>= 8;
	}
	return w;
}

#endif // !defined(AFX_STREAM_INT_H__57F9BAC0_D02F_4067_9891_5C484F35B67F__INCLUDED_)
