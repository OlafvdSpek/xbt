// sql_result.cpp: implementation of the Csql_result class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "sql_result.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Csql_row::Csql_row()
{
	m_source = NULL;
}

Csql_row::Csql_row(MYSQL_ROW data, unsigned long* sizes, Csql_result_source* source)
{
	m_data = data;
	m_sizes = sizes;
	m_source = source->attach();
}

Csql_row::Csql_row(const Csql_row& v)
{
	m_data = v.m_data;
	m_sizes = v.m_sizes;
	m_source = v.m_source->attach();
}

Csql_row::~Csql_row()
{
	m_source->detach();
}

const Csql_row& Csql_row::operator=(const Csql_row& v)
{
	if (this != &v)
	{
		m_source->detach();
		m_data = v.m_data;
		m_sizes = v.m_sizes;
		m_source = v.m_source->attach();
	}
	return *this;
}

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Csql_result::Csql_result(MYSQL_RES* h)
{
	m_source = new Csql_result_source(h);
}

Csql_result::Csql_result(const Csql_result& v)
{
	m_source = v.m_source->attach();
}

Csql_result::~Csql_result()
{
	m_source->detach();
}

const Csql_result& Csql_result::operator=(const Csql_result& v)
{
	if (this != &v)
	{
		m_source->detach();
		m_source = v.m_source->attach();
	}
	return *this;
}

int Csql_result::c_fields() const
{
	return mysql_num_fields(h());
}

int Csql_result::c_rows() const
{
	return mysql_num_rows(h());
}

void Csql_result::data_seek(int i)
{
	mysql_data_seek(h(), i);
}

Csql_row Csql_result::fetch_row() const
{
	MYSQL_ROW data = mysql_fetch_row(h());
	return Csql_row(data, mysql_fetch_lengths(h()), m_source);
}
