#if !defined(AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <stdexcept>
#include "sql_result.h"

class Cdatabase
{
public:
	class exception: public std::runtime_error
	{
	public:
		exception(const std::string& s): runtime_error(s)
		{
		}
	};

	void open(const std::string& host, const std::string& user, const std::string& password, const std::string& database, bool echo_errors = false);
	Csql_result query(const std::string&);
	void set_query_log(const std::string&);
	int insert_id();
	void close();
	Cdatabase();
	Cdatabase(const std::string& host, const std::string& user, const std::string& password, const std::string& database, bool echo_errors = false);
	~Cdatabase();

	MYSQL& handle()
	{
		return m_handle;
	}
private:
	Cdatabase(const Cdatabase&);

	bool m_echo_errors;
	MYSQL m_handle;
	std::string m_query_log;
};

#endif // !defined(AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_)
