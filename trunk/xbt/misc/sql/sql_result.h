#if !defined(AFX_SQL_RESULT_H__EA1254C8_2222_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_SQL_RESULT_H__EA1254C8_2222_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#ifdef _MSC_VER
#include <winsock.h>
#include <mysql.h>
#else
#include <mysql/mysql.h>
#endif
#include "const_memory_range.h"

class Csql_result_source
{
public:
	Csql_result_source(MYSQL_RES* h)
	{
		m_h = h;
		mc_references = 1;
	}

	Csql_result_source* attach()
	{
		this && mc_references++;
		return this;
	}

	void detach()
	{
		if (this && !--mc_references)
			mysql_free_result(m_h);
	}

	MYSQL_RES* h() const
	{
		return m_h;
	}
private:
	MYSQL_RES* m_h;
	int mc_references;
};

class Csql_field
{
public:
	Csql_field(const char* begin, int size)
	{
		m_begin = begin;
		m_size = size;
	}

	const char* raw() const
	{
		return m_begin;
	}

	int size() const
	{
		return m_size;
	}

	long long i(long long d = 0) const
	{
#ifdef WIN32
		return raw() ? _atoi64(raw()) : d;
#else
		return raw() ? atoll(raw()) : d;
#endif
	}

	const std::string s(const std::string& d = "") const
	{
		return raw() ? std::string(raw(), size()) : d;
	}

	const_memory_range vdata() const
	{
		return const_memory_range(raw(), size());
	}
private:
	const char* m_begin;
	int m_size;
};

class Csql_row
{
public:
	const Csql_row& operator=(const Csql_row& v);
	Csql_row();
	Csql_row(MYSQL_ROW data, unsigned long* sizes, Csql_result_source* source);
	Csql_row(const Csql_row& v);
	~Csql_row();

	operator bool() const
	{
		return m_data;
	}

	Csql_field operator[](size_t i) const
	{
		return Csql_field(m_data[i], m_sizes[i]);
	}
private:
	MYSQL_ROW m_data;
	unsigned long* m_sizes;
	Csql_result_source* m_source;
};

class Csql_result
{
public:
	int c_fields() const;
	int c_rows() const;
	void data_seek(int i);
	Csql_row fetch_row() const;
	const Csql_result& operator=(const Csql_result& v);
	Csql_result(MYSQL_RES* h);
	Csql_result(const Csql_result& v);
	~Csql_result();
private:
	MYSQL_RES* h() const
	{
		return m_source->h();
	}

	Csql_result_source* m_source;
};

#endif // !defined(AFX_SQL_RESULT_H__EA1254C8_2222_11D5_B606_0000B4936994__INCLUDED_)
