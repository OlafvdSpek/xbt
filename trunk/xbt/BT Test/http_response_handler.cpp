#include "stdafx.h"
#include "http_response_handler.h"

int Chttp_response_handler::get_status_code(const std::string& v)
{
	size_t a = v.find_first_of("\n\r ");
	if (a == std::string::npos)
		return 1;
	if (v[a] != ' ')
		return 2;
	return atoi(v.substr(a).c_str());
}

std::string Chttp_response_handler::get_message_body(const std::string& v)
{
	size_t a = v.find("\r\n\r\n");
	if (a == std::string::npos)
		return "";
	return v.substr(a + 4);
}

void Chttp_response_handler::alert(const Calert&)
{
}

void Chttp_response_handler::handle(const std::string& response)
{
}
