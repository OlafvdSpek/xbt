#include <xbt/sql_result.h>

Csql_row Csql_result::fetch_row() const
{
	MYSQL_ROW data = mysql_fetch_row(h());
	return Csql_row(data, mysql_fetch_lengths(h()), m_source);
}
