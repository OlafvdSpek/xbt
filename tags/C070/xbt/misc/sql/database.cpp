#include "stdafx.h"
#include "database.h"

#include <fstream>
#include <iostream>

Cdatabase::Cdatabase()
{
	mysql_init(&m_handle);
}

Cdatabase::~Cdatabase()
{
	close();
}

Cxcc_error Cdatabase::open(const char* host, const char* user, const char* password, const char* database, bool echo_errors)
{
	m_echo_errors = echo_errors;
	bool a0 = true;
	return !mysql_init(&m_handle)
		|| !mysql_real_connect(&m_handle, host, user, password, database, MYSQL_PORT, NULL, 0)
#if MYSQL_VERSION_ID >= 50000
		|| !mysql_options(&m_handle, MYSQL_OPT_RECONNECT, reinterpret_cast<const char*>(&a0))
#endif
		? Cxcc_error(mysql_error(&m_handle)) : Cxcc_error();
}

Cxcc_error Cdatabase::open(const string& host, const string& user, const string& password, const string& database, bool echo_errors)
{
	return open(host.c_str(), user.c_str(), password.c_str(), database.c_str(), echo_errors);
}

Csql_result Cdatabase::query(const string& q)
{
	if (!m_query_log.empty())
	{
		static ofstream f(m_query_log.c_str());
		f << q << endl;
	}
	if (mysql_real_query(&m_handle, q.c_str(), q.size()))
	{
		if (m_echo_errors)
		{
			cerr << mysql_error(&m_handle) << endl
				<< q.substr(0, 79) << endl;
		}
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

void Cdatabase::set_query_log(const string& v)
{
	m_query_log = v;
}
