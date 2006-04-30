#include "stdafx.h"
#include "sql_query.h"

#include <cstdio>
#include "database.h"

Csql_query::Csql_query(Cdatabase& database, const std::string& v):
	m_database(database)
{
	m_data = v;
}

Csql_result Csql_query::execute() const
{
	return m_database.query(read());
}

std::string Csql_query::read() const
{
	std::string r;
	t_list::const_iterator l = m_list.begin();
	for (size_t i = 0; i < m_data.length(); i++)
	{
		if (m_data[i] == '?')
		{
			assert(l != m_list.end());
			r += *l++;
		}
		else
			r += m_data[i];
	}
	assert(l == m_list.end());
	return r;
}

void Csql_query::operator=(const std::string& v)
{
	m_data = v;
	m_list.clear();
}

void Csql_query::operator+=(const std::string& v)
{
	m_data += v;
}

void Csql_query::p_raw(const std::string& v)
{
	m_list.push_back(v);
}

void Csql_query::p(long long v)
{
	char b[21];
#ifdef WIN32
	sprintf(b, "%I64d", v);
#else
	sprintf(b, "%lld", v);
#endif
	p_raw(b);
}

void Csql_query::p(const std::string& v)
{
	char* r = new char[2 * v.length() + 3];
	r[0] = '\'';
	mysql_real_escape_string(&m_database.handle(), r + 1, v.data(), v.length());
	strcat(r, "\'");
	p_raw(r);
	delete[] r;
}

void Csql_query::p(const Cvirtual_binary& v)
{
	char* r = new char[2 * v.size() + 3];
	r[0] = '\'';
	mysql_real_escape_string(&m_database.handle(), r + 1, reinterpret_cast<const char*>(v.data()), v.size());
	strcat(r, "\'");
	p_raw(r);
	delete[] r;
}
