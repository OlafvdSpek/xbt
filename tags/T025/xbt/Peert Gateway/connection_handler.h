#if !defined(AFX_CONNECTION_HANDLER_H__8FCC36AB_9F24_4039_9531_6F5E94C93431__INCLUDED_)
#define AFX_CONNECTION_HANDLER_H__8FCC36AB_9F24_4039_9531_6F5E94C93431__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cconnection;

class Cconnection_handler  
{
public:
	virtual int post_recv(Cconnection* con) = 0;
	virtual int post_send(Cconnection* con) = 0;
	Cconnection_handler();
	virtual ~Cconnection_handler();
};

#endif // !defined(AFX_CONNECTION_HANDLER_H__8FCC36AB_9F24_4039_9531_6F5E94C93431__INCLUDED_)
