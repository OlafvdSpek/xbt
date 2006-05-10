#include "stdafx.h"
#include "virtual_binary.h"

#include <cstdio>
#include <sys/stat.h>

Cvirtual_binary_source::Cvirtual_binary_source(const_memory_range d)
{
	m_data = new byte[d.size()];
	m_size = d.size();
	if (d)
		memcpy(m_data, d, d.size());
	mc_references = 1;
}

Cvirtual_binary_source* Cvirtual_binary_source::attach()
{
	if (this)
		mc_references++;
	return this;
}

void Cvirtual_binary_source::detach()
{
	if (!this || --mc_references)
		return;
	delete[] m_data;
	delete this;
}

Cvirtual_binary_source* Cvirtual_binary_source::pre_edit()
{
	if (mc_references == 1)
		return this;
	Cvirtual_binary_source t = *this;
	detach();
	return new Cvirtual_binary_source(t.range());
}	

Cvirtual_binary::Cvirtual_binary()
{
	m_source = NULL;
}

Cvirtual_binary::Cvirtual_binary(const Cvirtual_binary& v)
{
	m_source = v.m_source->attach();
}

Cvirtual_binary::Cvirtual_binary(const_memory_range d)
{
	m_source = new Cvirtual_binary_source(d);
}

Cvirtual_binary::~Cvirtual_binary()
{
	m_source->detach();
}

const Cvirtual_binary& Cvirtual_binary::operator=(const Cvirtual_binary& v)
{
	if (this != &v)
	{
		m_source->detach();
		m_source = v.m_source->attach();
	}
	return *this;
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
	m_source->detach();
	m_source = NULL;
}

size_t Cvirtual_binary::read(void* d) const
{
	memcpy(d, data(), size());
	return size();
}

byte* Cvirtual_binary::write_start(size_t cb_d)
{
	if (data() && size() == cb_d)
		return data_edit();
	if (m_source)
		m_source->detach();
	m_source = new Cvirtual_binary_source(const_memory_range(NULL, cb_d));
	return data_edit();
}

void Cvirtual_binary::write(const_memory_range d)
{
	memcpy(write_start(d.size()), d, d.size());
}
