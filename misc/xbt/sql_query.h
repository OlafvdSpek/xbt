#pragma once

#include <xbt/data_ref.h>

class Cdatabase;
class Csql_result;

class Csql_query
{
public:
	Csql_result execute() const;
	int execute_nothrow() const;
	std::string read() const;
	void operator=(std::string);
	void operator+=(const std::string&);
	Csql_query& p_name(const std::string&);
	Csql_query& p_raw(data_ref);
	Csql_query& operator()(long long);
	Csql_query& operator()(str_ref);
	Csql_query(Cdatabase&, std::string = "");

#if 1
	Csql_query& p(long long v)
	{
		return (*this)(v);
	}

	Csql_query& p(str_ref v)
	{
		return (*this)(v);
	}
#endif
private:
	std::string replace_names(const std::string&) const;

	Cdatabase& m_database;
	std::string m_in;
	std::string m_out;
};
