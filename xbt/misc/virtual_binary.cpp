#include "xbt/virtual_binary.h"

#include <sys/stat.h>
#include <cstdio>
#include <cstring>

void Cvirtual_binary::assign(data_ref v)
{
  if (v.size())
  {
#if BOOST_VERSION >= 104200
    m_source = boost::make_shared<Cvirtual_binary_source>(v.size());
#else
    m_source.reset(new Cvirtual_binary_source(v.size()));
#endif
    memcpy(data_edit(), v, v.size());
  }
  else
    m_source.reset();
}

int Cvirtual_binary::save(const std::string& fname) const
{
	FILE* f = fopen(fname.c_str(), "wb");
	if (!f)
		return 1;
	int error = fwrite(data(), 1, size(), f) != size();
	fclose(f);
	return error;
}

int Cvirtual_binary::load(const std::string& fname)
{
	FILE* f = fopen(fname.c_str(), "rb");
	if (!f)
		return 1;
	struct stat b;
	int error = fstat(fileno(f), &b) ? 1 : fread(write_start(b.st_size), 1, b.st_size, f) != b.st_size;
	fclose(f);
	return error;
}

Cvirtual_binary& Cvirtual_binary::load1(const std::string& fname)
{
	load(fname);
	return *this;
}

unsigned char* Cvirtual_binary::write_start(size_t cb_d)
{
	if (size() != cb_d)
    assign(cb_d);
	return data_edit();
}
