// virtual_binary.cpp: implementation of the Cvirtual_binary class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "virtual_binary.h"

#include <cstdio>
#include <sys/stat.h>

Cvirtual_binary_source::Cvirtual_binary_source(const void* d, int cb_d)
{
	m_data = new byte[cb_d];
	m_size = cb_d;
	if (d)
		memcpy(m_data, d, cb_d);
	mc_references = 1;
}

void Cvirtual_binary_source::detach()
{
	if (!--mc_references)
	{
		delete[] m_data;
		delete this;
	}
}

Cvirtual_binary_source* Cvirtual_binary_source::pre_edit()
{
	if (mc_references == 1)
		return this;
	Cvirtual_binary_source t = *this;
	detach();
	return new Cvirtual_binary_source(t.data(), t.size());
}	

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cvirtual_binary::Cvirtual_binary()
{
	m_source = NULL;
}

Cvirtual_binary::Cvirtual_binary(const Cvirtual_binary& v)
{
	m_source = v.m_source ? v.m_source->attach() : NULL;
}

Cvirtual_binary::Cvirtual_binary(const void* d, int cb_d)
{
	m_source = new Cvirtual_binary_source(d, cb_d);
}

Cvirtual_binary::Cvirtual_binary(const string& fname)
{
	m_source = NULL;
	load(fname);
}

Cvirtual_binary::~Cvirtual_binary()
{
	if (m_source)
		m_source->detach();
}

const Cvirtual_binary& Cvirtual_binary::operator=(const Cvirtual_binary& v)
{
	if (this != &v)
	{
		if (m_source)
			m_source->detach();
		m_source = v.m_source ? v.m_source->attach() : NULL;
	}
	return *this;
}

int Cvirtual_binary::save(const string& fname) const
{
	FILE* f = fopen(fname.c_str(), "wb");
	if (!f)
		return 1;
	int error = fwrite(data(), 1, size(), f) != size();
	fclose(f);
	return error;
}

int Cvirtual_binary::load(const string& fname)
{
	FILE* f = fopen(fname.c_str(), "rb");
	if (!f)
		return 1;
	struct stat b;
	int error = fstat(fileno(f), &b) ? 1 : fread(write_start(b.st_size), 1, b.st_size, f) != b.st_size;
	fclose(f);
	return error;
}

void Cvirtual_binary::clear()
{
	if (!m_source)
		return;
	m_source->detach();
	m_source = NULL;
}

int Cvirtual_binary::read(void* d) const
{
	memcpy(d, data(), size());
	return size();
}

byte* Cvirtual_binary::write_start(int cb_d)
{
	if (data() && size() == cb_d)
		return data_edit();
	if (m_source)
		m_source->detach();
	m_source = new Cvirtual_binary_source(NULL, cb_d);
	return data_edit();
}

void Cvirtual_binary::write(const void* d, int cb_d)
{
	memcpy(write_start(cb_d), d, cb_d);
}
