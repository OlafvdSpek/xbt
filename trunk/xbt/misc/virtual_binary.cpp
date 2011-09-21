#include "xbt/virtual_binary.h"

#include <sys/stat.h>
#include <cstdio>
#include <string.h>

Cvirtual_binary_source::Cvirtual_binary_source(data_ref d)
{
	m_range.assign(new unsigned char[d.size()], d.size());
	if (d)
		memcpy(m_range, d, d.size());
}

Cvirtual_binary::Cvirtual_binary(size_t v)
{
#if BOOST_VERSION >= 104200
	m_source = boost::make_shared<Cvirtual_binary_source>(data_ref(NULL, v));
#else
	m_source.reset(new Cvirtual_binary_source(data_ref(NULL, v)));
#endif
}

Cvirtual_binary::Cvirtual_binary(data_ref d)
{
#if BOOST_VERSION >= 104200
	m_source = boost::make_shared<Cvirtual_binary_source>(d);
#else
	m_source.reset(new Cvirtual_binary_source(d));
#endif
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

void Cvirtual_binary::clear()
{
	m_source.reset();
}

size_t Cvirtual_binary::read(void* d) const
{
	memcpy(d, data(), size());
	return size();
}

unsigned char* Cvirtual_binary::write_start(size_t cb_d)
{
	if (data() && size() == cb_d)
		return data_edit();
#if BOOST_VERSION >= 104200
	m_source = boost::make_shared<Cvirtual_binary_source>(data_ref(NULL, cb_d));
#else
	m_source.reset(new Cvirtual_binary_source(data_ref(NULL, cb_d)));
#endif
	return data_edit();
}

void Cvirtual_binary::write(data_ref d)
{
	memcpy(write_start(d.size()), d, d.size());
}
