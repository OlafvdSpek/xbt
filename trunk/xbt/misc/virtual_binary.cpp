#include "xbt/virtual_binary.h"

#include <sys/stat.h>
#include <cstdio>
#include <cstring>

void Cvirtual_binary::assign(data_ref v)
{
  if (v.size())
  {
    m_source = boost::make_shared<Cvirtual_binary_source>(v.size());
    if (v.begin())
      memcpy(data_edit(), v.data(), v.size());
  }
  else
    m_source.reset();
}

int file_put(const std::string& fname, data_ref v)
{
	FILE* f = fopen(fname.c_str(), "wb");
	if (!f)
		return 1;
	int error = fwrite(v.data(), v.size(), 1, f) != 1;
	fclose(f);
	return error;
}

Cvirtual_binary file_get(const std::string& fname)
{
  Cvirtual_binary d;
	FILE* f = fopen(fname.c_str(), "rb");
	if (!f)
		return d;
	struct stat b;
	if (fstat(fileno(f), &b) ? 1 : fread(d.write_start(b.st_size), b.st_size, 1, f) != 1)
    d.clear();
	fclose(f);
	return d;
}

unsigned char* Cvirtual_binary::write_start(size_t cb_d)
{
	if (size() != cb_d)
    assign(cb_d);
	return data_edit();
}
