// sql_query.cpp: implementation of the Csql_query class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "sql_query.h"

#include <cstdio>
#include "database.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Csql_query::Csql_query(Cdatabase& database, const string& v):
	m_database(database)
{
	m_data = v;
}

Csql_result Csql_query::execute() const
{
	return m_database.query(read());
}

string Csql_query::read() const
{
	string r;
	t_list::const_iterator l = m_list.begin();
	for (int i = 0; i < m_data.length(); i++)
	{
		if (m_data[i] == '%')
		{
			if (++i < m_data.length())
			{
				switch (m_data[i])
				{
				case 'd':
				case 's':
					assert(l != m_list.end());
					r += *l++;
					break;
				default:
					r += m_data[i];
				}
			}
		}
		else
			r += m_data[i];
	}
	assert(l == m_list.end());
	return r;
}


void Csql_query::operator=(const string& v)
{
	m_data = v;
	m_list.clear();
}


void Csql_query::p(const string& v)
{
	m_list.push_back(v);
}

void Csql_query::p(int v)
{
	char b[12];
	sprintf(b, "%d", v);	
	p(b);
}

void Csql_query::pe(const string& v)
{
	char* r = new char[2 * v.length() + 3];
	r[0] = '\"';
	mysql_real_escape_string(&m_database.handle(), r + 1, v.c_str(), v.length());
	strcat(r, "\"");
	p(r);
	delete[] r;
}

void Csql_query::pe(const Cvirtual_binary& v)
{
	char* r = new char[2 * v.size() + 3];
	r[0] = '\"';
	mysql_real_escape_string(&m_database.handle(), r + 1, reinterpret_cast<const char*>(v.data()), v.size());
	strcat(r, "\"");
	p(r);
	delete[] r;
}
