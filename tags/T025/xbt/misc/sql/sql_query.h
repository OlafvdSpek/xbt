#if !defined(AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <list>
#include "const_memory_range.h"

class Cdatabase;
class Csql_result;

class Csql_query
{
public:
	Csql_result execute() const;
	std::string read() const;
	void operator=(const std::string&);
	void operator+=(const std::string&);
	Csql_query& p_name(const std::string&);
	Csql_query& p_raw(const std::string&);
	Csql_query& p(long long);
	Csql_query& p(const_memory_range);
	Csql_query(Cdatabase&, const std::string& = "");
private:
	typedef std::list<std::string> t_list;

	std::string m_data;
	Cdatabase& m_database;
	t_list m_list;
};

#endif // !defined(AFX_SQL_QUERY_H__D925D760_22F7_11D5_B606_0000B4936994__INCLUDED_)
