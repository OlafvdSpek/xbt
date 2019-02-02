#pragma once

#include <boost/shared_ptr.hpp>
#include <boost/utility.hpp>
#include <xbt/data_ref.h>
#ifdef _MSC_VER
#include <winsock2.h>
#endif
#include <mysql/mysql.h>

class Csql_row
{
public:
	Csql_row() = default;

	Csql_row(MYSQL_ROW data, unsigned long* sizes, std::shared_ptr<MYSQL_RES> source)
	{
		m_data = data;
		m_sizes = sizes;
		m_source = std::move(source);
	}

	operator const void*() const
	{
		return m_data;
	}

	str_ref operator[](size_t i) const
	{
		return m_data ? str_ref(m_data[i], m_sizes[i]) : str_ref();
	}
private:
	MYSQL_ROW m_data;
	unsigned long* m_sizes;
	std::shared_ptr<MYSQL_RES> m_source;
};

class Csql_result
{
public:
	class iterator
	{
	public:
		iterator() = default;
		iterator(Csql_result& v) : res_(&v), row_(mysql_fetch_row(res_->h())) { }
		bool operator!=(iterator) { return row_; }
		Csql_row operator*() { return Csql_row(row_, mysql_fetch_lengths(res_->h()), res_->m_source); }
		void operator++() { row_ = mysql_fetch_row(res_->h()); }
	private:
		Csql_result* res_ = NULL;
		MYSQL_ROW row_;
	};

	Csql_row fetch_row() const
	{
		MYSQL_ROW data = mysql_fetch_row(h());
		return Csql_row(data, mysql_fetch_lengths(h()), m_source);
	}

	str_ref fetch_value() const
	{
		return fetch_row()[0];
	}

	long long fetch_int() const
	{
		return fetch_value().i();
	}

	Csql_result(MYSQL_RES* h) : m_source(h, mysql_free_result)
	{
	}

	explicit operator bool() const
	{
		return size();
	}

	int c_fields() const
	{
		return mysql_num_fields(h());
	}

	int size() const
	{
		return mysql_num_rows(h());
	}

	void data_seek(int i)
	{
		mysql_data_seek(h(), i);
	}

	iterator begin() { return iterator(*this); }
	iterator end() { return iterator(); }
private:
	MYSQL_RES* h() const
	{
		return m_source.get();
	}

	std::shared_ptr<MYSQL_RES> m_source;
};
