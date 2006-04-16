#if !defined(AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <list>
#include "virtual_binary.h"

class Cdatabase;
class Csql_result;

class Csql_query
{
public:
	Csql_result execute() const;
	string read() const;
	void operator=(const string&);
	void operator+=(const string&);
	void p_raw(const string&);
	void p(long long);
	void p(const string&);
	void p(const Cvirtual_binary&);
	Csql_query(Cdatabase&, const string& = "");
private:
	typedef list<string> t_list;

	string m_data;
	Cdatabase& m_database;
	t_list m_list;
};

#endif // !defined(AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_)
