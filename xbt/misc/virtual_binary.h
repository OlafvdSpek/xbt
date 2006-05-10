#if !defined(AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_)
#define AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <cassert>
#include <string>
#include "const_memory_range.h"

class Cvirtual_binary_source
{
public:
	Cvirtual_binary_source(const_memory_range);
	Cvirtual_binary_source* attach();
	void detach();
	Cvirtual_binary_source* pre_edit();

	unsigned char* data_edit()
	{
		assert(mc_references == 1);
		return m_data;
	}

	const_memory_range range() const
	{
		return const_memory_range(m_data, m_size);
	}

	size_t size() const
	{
		return m_size;
	}

	void size(size_t v)
	{
		assert(mc_references == 1 && v <= m_size);
		m_size = v;
	}
private:
	unsigned char* m_data;
	size_t m_size;
	int mc_references;
};

class Cvirtual_binary
{
public:
	int save(const std::string&) const;
	int load(const std::string&);
	Cvirtual_binary& load1(const std::string&);
	void clear();
	size_t read(void* d) const;
	unsigned char* write_start(size_t cb_d);
	void write(const_memory_range);
	const Cvirtual_binary& operator=(const Cvirtual_binary&);
	Cvirtual_binary();
	Cvirtual_binary(const Cvirtual_binary&);
	Cvirtual_binary(const_memory_range);
	~Cvirtual_binary();

	const unsigned char* begin() const
	{
		return range().begin;
	}

	const unsigned char* data() const
	{
		return range().begin;
	}

	unsigned char* data_edit()
	{
		assert(m_source);
		m_source = m_source->pre_edit();
		return m_source->data_edit();
	}

	const unsigned char* end() const
	{
		return range().end;
	}

	const_memory_range range() const
	{
		return m_source ? m_source->range() : const_memory_range();
	}

	size_t size() const
	{
		return range().size();
	}

	void size(size_t v)
	{
		assert(m_source);
		m_source = m_source->pre_edit();
		m_source->size(v);
	}

	operator const unsigned char*() const
	{
		return data();
	}

	operator const_memory_range() const
	{
		return range();
	}
private:
	Cvirtual_binary_source* m_source;
};

#endif // !defined(AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_)
