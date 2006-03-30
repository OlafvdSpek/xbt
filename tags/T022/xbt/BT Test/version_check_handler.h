#if !defined(AFX_VERSION_CHECK_HANDLER_H__4B9397C3_6058_46EA_B1EA_AC260B78CC18__INCLUDED_)
#define AFX_VERSION_CHECK_HANDLER_H__4B9397C3_6058_46EA_B1EA_AC260B78CC18__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "http_response_handler.h"

class Cserver;

class Cversion_check_handler: public Chttp_response_handler
{
public:
	virtual void alert(const Calert&);
	virtual void handle(const string& response);
	Cversion_check_handler(Cserver&);
private:
	Cserver& m_server;
	int m_version;
};

#endif // !defined(AFX_VERSION_CHECK_HANDLER_H__4B9397C3_6058_46EA_B1EA_AC260B78CC18__INCLUDED_)
