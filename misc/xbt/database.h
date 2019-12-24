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
	std::string_view name(std::string_view) const;
	Csql_result query(std::string_view);
	int query_nothrow(std::string_view);
	void set_name(const std::string&, std::string);
	void set_query_log(std::ostream*);
	int affected_rows();
	int insert_id();
	std::string replace_names(std::string_view) const;
	int select_db(const std::string&);
	void close();
	Cdatabase();
	~Cdatabase();

	operator MYSQL*()
	{
		return &handle_;
	}
private:
	MYSQL handle_;
	std::map<std::string, std::string, std::less<>> names_;
	std::ostream* query_log_ = NULL;
};
