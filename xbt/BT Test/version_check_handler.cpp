// version_check_handler.cpp: implementation of the Cversion_check_handler class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "version_check_handler.h"

#include "bt_misc.h"
#include "server.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cversion_check_handler::Cversion_check_handler(Cserver& server):
	m_server(server)
{
	m_version = 0;
}

void Cversion_check_handler::alert(const Calert& v)
{
	m_server.alert(v);
}

void Cversion_check_handler::handle(const string& response)
{
	int status_code = get_status_code(response);
	if (status_code != 200)
		alert(Calert(Calert::error, "Version check: HTTP error: " + n(status_code)));
	m_version = atoi(get_message_body(response).c_str());
	if (m_version > m_server.version())
		alert(Calert(Calert::info, "Version " + xbt_version2a(m_version) + " is now available!"));
}
