// static_config.h: interface for the Cstatic_config class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_STATIC_CONFIG_H__FA584986_2EA1_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_STATIC_CONFIG_H__FA584986_2EA1_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "xcc_error.h"

class Cstatic_config
{
public:
	Cxcc_error read(const char* fname);

	string mysql_db;
	string mysql_host;
	string mysql_user;
	string mysql_password;
	string mysql_table_prefix;
};

#endif // !defined(AFX_STATIC_CONFIG_H__FA584986_2EA1_11D5_B606_0000B4936994__INCLUDED_)
