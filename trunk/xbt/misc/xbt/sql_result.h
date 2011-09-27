#pragma once

#include <boost/shared_ptr.hpp>
#include <boost/utility.hpp>
#include <xbt/data_ref.h>
#ifdef _MSC_VER
#include <winsock2.h>
#endif
#include <mysql/mysql.h>

class Csql_row;

class Csql_result
{
public:
	typedef boost::shared_ptr<MYSQL_RES> ptr_t;

	Csql_row fetch_row() const;

	Csql_result(MYSQL_RES* h) : m_source(h, mysql_free_result)
	{
	}

	operator const void*() const
	{
		return c_rows() ? this : NULL;
	}

	int c_fields() const
	{
		return mysql_num_fields(h());
	}

	int c_rows() const
	{
		return mysql_num_rows(h());
	}

	void data_seek(int i)
	{
		mysql_data_seek(h(), i);
	}
private:
	MYSQL_RES* h() const
	{
		return m_source.get();
	}

	ptr_t m_source;
};

class Csql_field
{
public:
	Csql_field(const char* begin, int size)
	{
		m_begin = begin;
		m_size = size;
	}

	const char* begin() const
  {
    return m_begin;
  }

  const char* end() const
  {
    return m_begin + m_size;
  }

  const char* data() const
	{
		return m_begin;
	}

	int size() const
	{
		return m_size;
	}

	float f(float d = 0) const
	{
		return data() ? atof(data()) : d;
	}

	long long i(long long d = 0) const
	{
#ifdef WIN32
		return data() ? _atoi64(data()) : d;
#else
		return data() ? atoll(data()) : d;
#endif
	}

	const std::string s(const std::string& d = "") const
	{
		return data() ? std::string(data(), size()) : d;
	}
private:
	const char* m_begin;
	int m_size;
};

class Csql_row
{
public:
	Csql_row()
	{
	}

	Csql_row(MYSQL_ROW data, unsigned long* sizes, const Csql_result::ptr_t& source)
	{
		m_data = data;
		m_sizes = sizes;
		m_source = source;
	}

	operator const void*() const
	{
		return m_data;
	}

	Csql_field operator[](size_t i) const
	{
		return m_data ? Csql_field(m_data[i], m_sizes[i]) : Csql_field(NULL, 0);
	}
private:
	MYSQL_ROW m_data;
	unsigned long* m_sizes;
	Csql_result::ptr_t m_source;
};
