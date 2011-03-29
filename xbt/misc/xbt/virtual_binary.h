#pragma once

#include <boost/version.hpp>
#if BOOST_VERSION >= 104200
#include <boost/make_shared.hpp>
#endif
#include <boost/shared_ptr.hpp>
#include <boost/utility.hpp>
#include <cassert>
#include <string>
#include <xbt/data_ref.h>

class Cvirtual_binary_source: boost::noncopyable
{
public:
	Cvirtual_binary_source(data_ref);

	~Cvirtual_binary_source()
	{
		delete[] m_range.begin;
	}

	mutable_data_ref range()
	{
		return m_range;
	}

	void resize(size_t v)
	{
		assert(v <= m_range.size());
		m_range.end = m_range.begin + v;
	}
private:
	mutable_data_ref m_range;
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
	void write(data_ref);
	Cvirtual_binary(size_t);
	Cvirtual_binary(data_ref);

	Cvirtual_binary()
	{
	}

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
		return mutable_range().begin;
	}

	const unsigned char* end() const
	{
		return range().end;
	}

	unsigned char* mutable_end()
	{
		return mutable_range().end;
	}

	data_ref range() const
	{
		return m_source ? m_source->range() : mutable_data_ref();
	}

	mutable_data_ref mutable_range()
	{
		if (!m_source)
			return mutable_data_ref();
		if (!m_source.unique())
#if BOOST_VERSION >= 104200
			m_source = boost::make_shared<Cvirtual_binary_source>(range());
#else
			m_source.reset(new Cvirtual_binary_source(range()));
#endif
		return m_source->range();
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
