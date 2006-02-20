#if !defined(AFX_HTTP_RESPONSE_HANDLER_H__765E9C6F_C59E_4A87_9B8A_8AA52F815B18__INCLUDED_)
#define AFX_HTTP_RESPONSE_HANDLER_H__765E9C6F_C59E_4A87_9B8A_8AA52F815B18__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"

class Chttp_response_handler
{
public:
	static string get_message_body(const string&);
	static int get_status_code(const string&);
	virtual void alert(const Calert&);
	virtual void handle(const string& response);
};

#endif // !defined(AFX_HTTP_RESPONSE_HANDLER_H__765E9C6F_C59E_4A87_9B8A_8AA52F815B18__INCLUDED_)
