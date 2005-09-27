#include "stdafx.h"
#include "static_config.h"

Cxcc_error Cstatic_config::read(const char* fname)
{
	ifstream f(fname);
	f >> mysql_db >> mysql_host >> mysql_user >> mysql_password >> mysql_table_prefix;
	return f ? Cxcc_error() : Cxcc_error("Unable to read static config");
}
