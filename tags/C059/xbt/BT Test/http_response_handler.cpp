// http_response_handler.cpp: implementation of the Chttp_response_handler class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "http_response_handler.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

int Chttp_response_handler::get_status_code(const string& v)
{
	int a = v.find_first_of("\n\r ");
	if (a == string::npos)
		return 1;
	if (v[a] != ' ')
		return 2;
	return atoi(v.substr(a).c_str());
}

string Chttp_response_handler::get_message_body(const string& v)
{
	int a = v.find("\r\n\r\n");
	if (a == string::npos)
		return "";
	return v.substr(a + 4);
}

void Chttp_response_handler::alert(const Calert&)
{
}

void Chttp_response_handler::handle(const string& response)
{
}
