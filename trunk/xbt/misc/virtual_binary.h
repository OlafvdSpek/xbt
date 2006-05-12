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
		return m_range;
	}

	memory_range range()
	{
		return m_range;
	}

	void resize(size_t v)
	{
		assert(mc_references == 1 && v <= m_range.size());
		m_range.end = m_range.begin + v;
	}
private:
	memory_range m_range;
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
	Cvirtual_binary(size_t);
	Cvirtual_binary(const_memory_range);
	~Cvirtual_binary();

	const unsigned char* begin() const
	{
		return range().begin;
	}

	unsigned char* mutable_begin()
	{
		return mutable_range().begin;
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

	unsigned char* mutable_end()
	{
		return mutable_range().end;
	}

	const_memory_range range() const
	{
		return m_source ? m_source->range() : memory_range();
	}

	memory_range mutable_range()
	{
		if (m_source)
			m_source = m_source->pre_edit();
		return m_source ? m_source->range() : memory_range();
	}

	size_t size() const
	{
		return range().size();
	}

	void resize(size_t v)
	{
		assert(m_source);
		m_source = m_source->pre_edit();
		m_source->resize(v);
	}

	operator const unsigned char*() const
	{
		return data();
	}

	operator const_memory_range() const
	{
		return range();
	}

	operator memory_range()
	{
		return memory_range(data_edit(), size());
	}
private:
	Cvirtual_binary_source* m_source;
};

#endif // !defined(AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_)
