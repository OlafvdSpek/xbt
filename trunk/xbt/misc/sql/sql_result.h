#pragma once

#include <boost/intrusive_ptr.hpp>
#include <boost/utility.hpp>
#include <const_memory_range.h>
#ifdef _MSC_VER
#include <winsock2.h>
#endif
#include <mysql/mysql.h>

class Csql_result_source: boost::noncopyable
{
public:
	Csql_result_source(MYSQL_RES* h)
	{
		m_h = h;
		mc_references = 0;
	}

	~Csql_result_source()
	{
		mysql_free_result(m_h);
	}

	MYSQL_RES* h() const
	{
		return m_h;
	}

	friend void intrusive_ptr_add_ref(Csql_result_source*);
	friend void intrusive_ptr_release(Csql_result_source*);
private:
	MYSQL_RES* m_h;
	int mc_references;
};

inline void intrusive_ptr_add_ref(Csql_result_source* v)
{
	v->mc_references++;
}

inline void intrusive_ptr_release(Csql_result_source* v)
{
	v->mc_references--;
	if (!v->mc_references)
		delete v;
}

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

	float f(float d = 0) const
	{
		return raw() ? atof(raw()) : d;
	}

	long long i(long long d = 0) const
	{
		return raw() ? atoll(raw()) : d;
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
	Csql_row(MYSQL_ROW, unsigned long* sizes, boost::intrusive_ptr<Csql_result_source>);

	Csql_row()
	{
	}

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
	boost::intrusive_ptr<Csql_result_source> m_source;
};

class Csql_result
{
public:
	Csql_row fetch_row() const;

	Csql_result(MYSQL_RES* h)
	{
		m_source = new Csql_result_source(h);
	}

	operator bool() const
	{
		return c_rows();
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
		return m_source->h();
	}

	boost::intrusive_ptr<Csql_result_source> m_source;
};
