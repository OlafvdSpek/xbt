#pragma once

#include <string.h>
#include <xbt/data_ref.h>

inline float read_float(const void* r)
{
	float v;
	memcpy(&v, r, sizeof(float));
	return v;
}

inline float read_float(const void* r0, const void* /*s_end*/)
{
	return read_float(r0);
}

template <class T>
static T write_float(T w0, float v)
{
	unsigned char* w = reinterpret_cast<unsigned char*>(w0);
	memcpy(w, &v, sizeof(float));
	return w + sizeof(float);
}

inline long long read_int(int cb, const void* r0)
{
	const unsigned char* r = reinterpret_cast<const unsigned char*>(r0);
	long long v = 0;
	while (cb--)
		v = v << 8 | *r++;
	return v;
}

inline long long read_int(size_t cb, data_ref s)
{
	return static_cast<size_t>(s.size()) < cb ? 0 : read_int(cb, s.data());
}

inline long long read_int(int cb, const void* r, const void* s_end)
{
	return read_int(cb, data_ref(r, s_end));
}

template <class T>
T write_int(int cb, T w0, long long v)
{
	unsigned char* w = reinterpret_cast<unsigned char*>(w0);
	w += cb;
	for (int i = 0; i < cb; i++)
	{
		*--w = v & 0xff;
		v >>= 8;
	}
	return reinterpret_cast<T>(w + cb);
}

inline long long read_int_le(int cb, const void* r0)
{
	const unsigned char* r = reinterpret_cast<const unsigned char*>(r0);
	r += cb;
	long long v = 0;
	while (cb--)
		v = v << 8 | *--r;
	return v;
}

inline long long read_int_le(int cb, const void* r, const void* /*s_end*/)
{
	return read_int_le(cb, r);
}

template <class T>
T write_int_le(int cb, T w0, long long v)
{
	unsigned char* w = reinterpret_cast<unsigned char*>(w0);
	for (int i = 0; i < cb; i++)
	{
		*w++ = v & 0xff;
		v >>= 8;
	}
	return reinterpret_cast<T>(w);
}
