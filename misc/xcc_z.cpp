#include "xbt/xcc_z.h"

#include <cstdio>
#include <string.h>
#include <zlib.h>
#include "stream_int.h"

shared_data xcc_z::gunzip(data_ref s)
{
  if (s.size() < 18)
    return shared_data();
  shared_data d(read_int_le(4, s.end() - 4));
  z_stream stream;
  stream.zalloc = NULL;
  stream.zfree = NULL;
  stream.opaque = NULL;
  stream.next_in = const_cast<unsigned char*>(s.begin()) + 10;
  stream.avail_in = s.size() - 18;
  stream.next_out = d.data();
  stream.avail_out = d.size();
  return stream.next_out
    && Z_OK == inflateInit2(&stream, -MAX_WBITS)
    && Z_STREAM_END == inflate(&stream, Z_FINISH)
    && Z_OK == inflateEnd(&stream)
    ? d
    : shared_data();
}

shared_data xcc_z::gzip(data_ref s)
{
  unsigned long cb_d = s.size() + (s.size() + 999) / 1000 + 12;
  shared_data d(10 + cb_d + 8);
  unsigned char* w = d.data();
  *w++ = 0x1f;
  *w++ = 0x8b;
  *w++ = Z_DEFLATED;
  *w++ = 0;
  *w++ = 0;
  *w++ = 0;
  *w++ = 0;
  *w++ = 0;
  *w++ = 0;
  *w++ = 3;
  {
    z_stream stream;
    stream.zalloc = NULL;
    stream.zfree = NULL;
    stream.opaque = NULL;
    deflateInit2(&stream, Z_DEFAULT_COMPRESSION, Z_DEFLATED, -MAX_WBITS, MAX_MEM_LEVEL, Z_DEFAULT_STRATEGY);
    stream.next_in = const_cast<unsigned char*>(s.begin());
    stream.avail_in = s.size();
    stream.next_out = w;
    stream.avail_out = cb_d;
    deflate(&stream, Z_FINISH);
    deflateEnd(&stream);
    w = stream.next_out;
  }
  w = write_int_le(4, w, crc32(crc32(0, NULL, 0), s.data(), s.size()));
  w = write_int_le(4, w, s.size());
  return d.substr(0, w - d.data());
}

/*
void xcc_z::gzip_out(data_ref s)
{
  gzFile f = gzdopen(fileno(stdout), "wb");
  gzwrite(f, s.data(), s.size());
  gzflush(f, Z_FINISH);
}
*/
