#pragma once

#include <stdexcept>
#include "sql_result.h"

class Cdatabase: boost::noncopyable
{
public:
	class exception: public std::runtime_error
	{
	public:
		exception(const std::string& s): runtime_error(s)
		{
		}
	};

	typedef std::map<std::string, std::string> names_t;

	void open(const std::string& host, const std::string& user, const std::string& password, const std::string& database, bool echo_errors = false);
	const std::string& name(const std::string&) const;
	Csql_result query(const std::string&);
	void set_name(const std::string&, const std::string&);
	void set_query_log(const std::string&);
	int affected_rows();
	int insert_id();
	int select_db(const std::string&);
	void close();
	Cdatabase();
	~Cdatabase();

	MYSQL* handle()
	{
		return &m_handle;
	}
private:
	bool m_echo_errors;
	MYSQL m_handle;
	names_t m_names;
	std::string m_query_log;
};
