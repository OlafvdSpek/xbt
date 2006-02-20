#if !defined(AFX_CONNECTION_HANDLER_HTTP_SERVER_H__2A0320E2_2CD9_4D16_8B46_EE6565DC8B5D__INCLUDED_)
#define AFX_CONNECTION_HANDLER_HTTP_SERVER_H__2A0320E2_2CD9_4D16_8B46_EE6565DC8B5D__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "connection_handler.h"

class Cconnection_handler_http_server: public Cconnection_handler
{
public:
	virtual int post_recv(Cconnection* con);
	virtual int post_send(Cconnection* con);
	Cconnection_handler_http_server();
	virtual ~Cconnection_handler_http_server();
private:
	void read(Cconnection* con, const string&);
};

#endif // !defined(AFX_CONNECTION_HANDLER_HTTP_SERVER_H__2A0320E2_2CD9_4D16_8B46_EE6565DC8B5D__INCLUDED_)
