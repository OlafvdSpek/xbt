#include "stdafx.h"
#include "database.h"

#include <fstream>
#include <iostream>

Cxcc_error Cdatabase::open(const char* host, const char* user, const char* password, const char* database, bool echo_errors)
{
	m_echo_errors = echo_errors;
	bool a0 = true;
	return !mysql_init(&m_handle)
		|| !mysql_real_connect(&m_handle, host, user, password, database, MYSQL_PORT, NULL, 0)
#ifdef MYSQL_OPT_RECONNECT
		|| !mysql_options(&m_handle, MYSQL_OPT_RECONNECT, reinterpret_cast<const char*>(&a0))
#endif
		? Cxcc_error(mysql_error(&m_handle)) : Cxcc_error();
}

Cxcc_error Cdatabase::open(const string& host, const string& user, const string& password, const string& database, bool echo_errors)
{
	return open(host.c_str(), user.c_str(), password.c_str(), database.c_str(), echo_errors);
}

Cxcc_error Cdatabase::open(const string& conf_file, bool echo_errors)
{
	string host;
	string user;
	string password;
	string database;
	ifstream is(conf_file.c_str());
	is >> database >> host >> user >> password;
	return is ? open(host, user, password, database, echo_errors) : Cxcc_error("Unable to read static config");
}

Csql_result Cdatabase::query(const string& q)
{
#ifndef NDEBUG
	static ofstream f("/temp/query_log.txt");
	f << q << endl;
#endif
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
