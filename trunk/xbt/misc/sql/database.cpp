// database.cpp: implementation of the Cdatabase class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "database.h"

#include <fstream>

Cxcc_error Cdatabase::open(const char* host, const char* user, const char* password, const char* database, bool echo_errors)
{
	m_echo_errors = echo_errors;
	return !mysql_init(&m_handle)
		|| !mysql_real_connect(&m_handle, host, user, password, database, MYSQL_PORT, NULL, 0)
		? Cxcc_error(mysql_error(&m_handle)) : Cxcc_error();
}

Cxcc_error Cdatabase::open(const string& host, const string& user, const string& password, const string& database, bool echo_errors)
{
	return open(host.c_str(), user.c_str(), password.c_str(), database.c_str(), echo_errors);
}

Csql_result Cdatabase::query(const string& q)
{
	if (mysql_real_query(&m_handle, q.c_str(), q.size()))
	{
		if (m_echo_errors)
			cerr << mysql_error(&m_handle) << endl;
		throw Cxcc_error(mysql_error(&m_handle));
	}
	return Csql_result(mysql_store_result(&m_handle));
}

void Cdatabase::close()
{
	mysql_close(&m_handle);
}

int Cdatabase::insert_id()
{
	return mysql_insert_id(&m_handle);
}
