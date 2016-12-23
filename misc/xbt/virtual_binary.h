#pragma once

#include <boost/make_shared.hpp>
#include <boost/shared_ptr.hpp>
#include <boost/utility.hpp>
#include <cassert>
#include <string>
#include <xbt/data_ref.h>

class Cvirtual_binary_source: boost::noncopyable
{
public:
	Cvirtual_binary_source(size_t v)
	{
		m_range.assign(new char[v], v);
	}

	~Cvirtual_binary_source()
	{
		delete[] m_range.begin();
	}

	unsigned char* begin() const
	{
		return m_range.begin();
	}

	unsigned char* end() const
	{
		return m_range.end();
	}

	void resize(size_t v)
	{
		assert(v <= m_range.size());
		m_range.assign(m_range.begin(), v);
	}
private:
	mutable_data_ref m_range;
};

class Cvirtual_binary
{
public:
	void assign(data_ref);
	unsigned char* write_start(size_t cb_d);

	Cvirtual_binary()
	{
	}

	explicit Cvirtual_binary(size_t v)
	{
		assign(v);
	}

	explicit Cvirtual_binary(data_ref v)
	{
		assign(v);
	}

	void assign(size_t v)
	{
		assign(data_ref(NULL, v));
	}

	const unsigned char* begin() const
	{
		return range().begin();
	}

	unsigned char* mutable_begin()
	{
		return mutable_range().begin();
	}

	const unsigned char* data() const
	{
		return range().begin();
	}

	unsigned char* data_edit()
	{
		return mutable_range().begin();
	}

	const unsigned char* end() const
	{
		return range().end();
	}

	unsigned char* mutable_end()
	{
		return mutable_range().end();
	}

	data_ref range() const
	{
		return m_source ? *m_source : data_ref();
	}

	mutable_data_ref mutable_range()
	{
		if (!m_source)
			return mutable_data_ref();
		if (!m_source.unique())
			assign(range());
		return *m_source;
	}

	void clear()
	{
		m_source.reset();
	}

	bool empty() const
	{
		return range().empty();
	}

	size_t size() const
	{
		return range().size();
	}

	void resize(size_t v)
	{
		if (!m_source)
			write_start(v);
		mutable_range();
		m_source->resize(v);
	}

	operator const unsigned char*() const
	{
		return data();
	}

	operator data_ref() const
	{
		return range();
	}

	operator mutable_data_ref()
	{
		return mutable_range();
	}
private:
	boost::shared_ptr<Cvirtual_binary_source> m_source;
};
