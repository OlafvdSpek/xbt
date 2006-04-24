#if !defined(AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_)
#define AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <cassert>
#include <string>
#include "vartypes.h"

using namespace std;

class Cvirtual_binary_source
{
public:
	Cvirtual_binary_source(const void* d, size_t cb_d);
	Cvirtual_binary_source* attach();
	void detach();
	Cvirtual_binary_source* pre_edit();

	const byte* data() const
	{
		return m_data;
	}

	const byte* data_end() const
	{
		return data() + size();
	}

	byte* data_edit()
	{
		assert(mc_references == 1);
		return m_data;
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
	byte* m_data;
	size_t m_size;
	int mc_references;
};

class Cvirtual_binary  
{
public:
	int save(const string& fname) const;
	int load(const string& fname);
	void clear();
	size_t read(void* d) const;
	byte* write_start(size_t cb_d);
	void write(const void* d, size_t cb_d);
	const Cvirtual_binary& operator=(const Cvirtual_binary& v);
	Cvirtual_binary();
	Cvirtual_binary(const Cvirtual_binary& v);
	Cvirtual_binary(const void* d, size_t cb_d);
	explicit Cvirtual_binary(const string& fname);
	~Cvirtual_binary();

	const byte* data() const
	{
		return m_source ? m_source->data() : NULL;
	}

	const byte* data_end() const
	{
		return m_source ? m_source->data_end() : NULL;
	}

	byte* data_edit()
	{
		assert(m_source);
		m_source = m_source->pre_edit();
		return m_source->data_edit();
	}

	size_t size() const
	{
		return m_source ? m_source->size() : 0;
	}

	void size(size_t v)
	{
		assert(m_source);
		m_source = m_source->pre_edit();
		m_source->size(v);
	}

	operator const byte*() const
	{
		return data();
	}
private:
	Cvirtual_binary_source* m_source;
};

class const_memory_range
{
public:
	const_memory_range()
	{
		begin_ = NULL;
		end_ = NULL;
	}

	const_memory_range(const void* begin, const void* end)
	{
		begin_ = reinterpret_cast<const byte*>(begin);
		end_ = reinterpret_cast<const byte*>(end);
	}

	const_memory_range(const void* begin, size_t size)
	{
		begin_ = reinterpret_cast<const byte*>(begin);
		end_ = begin_ + size;
	}

	const_memory_range(const string& v)
	{
		begin_ = reinterpret_cast<const byte*>(v.data());
		end_ = reinterpret_cast<const byte*>(v.data() + v.size());
	}

	const_memory_range(string& v)
	{
		begin_ = reinterpret_cast<const byte*>(v.data());
		end_ = reinterpret_cast<const byte*>(v.data() + v.size());
	}

	const_memory_range(const Cvirtual_binary& v)
	{
		begin_ = v.data();
		end_ = v.data_end();
	}

	const_memory_range(Cvirtual_binary& v)
	{
		begin_ = v.data();
		end_ = v.data_end();
	}

	const byte* begin() const
	{
		return begin_;
	}

	const byte* end() const
	{
		return end_;
	}

	size_t size() const
	{
		return end() - begin();
	}
private:
	const byte* begin_;
	const byte* end_;
};

#endif // !defined(AFX_VIRTUAL_BINARY_H__B59C9DC0_DB25_11D4_A95D_0050042229FC__INCLUDED_)
