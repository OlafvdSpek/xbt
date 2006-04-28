#if !defined(AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "sql_result.h"

class Cdatabase
{
public:
	class exception: public runtime_error
	{
	public:
		exception(const string& s): runtime_error(s)
		{
		}
	};

	void open(const string& host, const string& user, const string& password, const string& database, bool echo_errors = false);
	Csql_result query(const string& q);
	void set_query_log(const string&);
	int insert_id();
	void close();
	Cdatabase();
	Cdatabase(const string& host, const string& user, const string& password, const string& database, bool echo_errors = false);
	~Cdatabase();

	MYSQL& handle()
	{
		return m_handle;
	}
private:
	Cdatabase(const Cdatabase&);

	bool m_echo_errors;
	MYSQL m_handle;
	string m_query_log;
};

#endif // !defined(AFX_DATABASE_H__EA1254C6_2222_11D5_B606_0000B4936994__INCLUDED_)
