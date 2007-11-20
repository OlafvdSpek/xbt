#pragma once

#include "http_response_handler.h"

class Cserver;

class Cversion_check_handler: public Chttp_response_handler
{
public:
	virtual void alert(const Calert&);
	virtual void handle(const std::string& response);
	Cversion_check_handler(Cserver&);
private:
	Cserver& m_server;
	int m_version;
};
