#pragma once

#include <map>
#include <stdexcept>
#include <xbt/string_view.h>
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
	void open(const std::string& host, const std::string& user, const std::string& password, const std::string& database);
	const std::string& name(const std::string&) const;
	Csql_result query(std::string_view);
	int query_nothrow(std::string_view);
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
	MYSQL m_handle;
	std::map<std::string, std::string> m_names;
	std::ostream* m_query_log = NULL;
};
