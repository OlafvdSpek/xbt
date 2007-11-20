#pragma once

#include "alerts.h"

class Chttp_response_handler
{
public:
	static std::string get_message_body(const std::string&);
	static int get_status_code(const std::string&);
	virtual void alert(const Calert&);
	virtual void handle(const std::string& response);
};
