#pragma once

#include <map>
#include <stdexcept>
#include "sql_result.h"

class bad_query : public std::runtime_error
{
public:
	bad_query(const std::string& s) : runtime_error(s)
	{
	}
};

class Cdatabase : boost::noncopyable
{
public:
	void open(const std::string& host, const std::string& user, const std::string& password, const std::string& database, bool echo_errors = false);
	const std::string& name(const std::string&) const;
	Csql_result query(const std::string&);
	int query_nothrow(const std::string&);
	void set_name(const std::string&, std::string);
	void set_query_log(std::ostream*);
	int affected_rows();
	int insert_id();
	int select_db(const std::string&);
	void close();
	Cdatabase();
	~Cdatabase();

	operator MYSQL*()
	{
		return &m_handle;
	}
private:
	bool m_echo_errors;
	MYSQL m_handle;
	std::map<std::string, std::string> m_names;
	std::ostream* m_query_log = NULL;
};
