// xcc_z.cpp: implementation of the xcc_z class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "xcc_z.h"

#include <cstdio>
#ifdef _MSC_VER
#include <zlib/zlib.h>
#else
#include <zlib.h>
#endif

Cvirtual_binary xcc_z::gzip(const byte* s, int cb_s)
{
	Cvirtual_binary d;
	unsigned long cb_d = cb_s + (cb_s + 999) / 1000 + 12;
	byte* w = d.write_start(10 + cb_d + 8);
	*w++ = 0x1f;
	*w++ = 0x8b;
	*w++ = Z_DEFLATED;
	*w++ = *w++ = *w++ = *w++ = *w++ = *w++ = 0;
	*w++ = 3;
	{
		z_stream stream;
		stream.zalloc = NULL;
		stream.zfree = NULL;
		stream.opaque = NULL;
		deflateInit2(&stream, Z_DEFAULT_COMPRESSION, Z_DEFLATED, -MAX_WBITS, MAX_MEM_LEVEL, Z_DEFAULT_STRATEGY);
		stream.next_in = const_cast<byte*>(s);
		stream.avail_in = cb_s;
		stream.next_out = w;
		stream.avail_out = cb_d;
		deflate(&stream, Z_FINISH);
		deflateEnd(&stream);
		w = stream.next_out;
	}
	*reinterpret_cast<int*>(w) = crc32(crc32(0, NULL, 0), s, cb_s);
	w += 4;
	*reinterpret_cast<int*>(w) = cb_s;
	w += 4;
	d.size(w - d.data());
	return d;
}

Cvirtual_binary xcc_z::gzip(const string& v)
{
	return gzip(reinterpret_cast<const byte*>(v.c_str()), v.length());
}

Cvirtual_binary xcc_z::gzip(const Cvirtual_binary& s)
{
	return gzip(s, s.size());
}

void xcc_z::gzip_out(const void* s, int cb_s)
{
	gzFile f = gzdopen(fileno(stdout), "wb");
	gzwrite(f, const_cast<void*>(s), cb_s);
	gzflush(f, Z_FINISH);
}

void xcc_z::gzip_out(const string& v)
{
	gzip_out(v.c_str(), v.length());
}

void xcc_z::gzip_out(const Cvirtual_binary& s)
{
	gzip_out(s, s.size());
}
