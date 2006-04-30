#if !defined(AFX_CLIENT_H__2D721F56_3253_48C2_8EED_FE0181AD999A__INCLUDED_)
#define AFX_CLIENT_H__2D721F56_3253_48C2_8EED_FE0181AD999A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "socket.h"

class Cserver;

class Cclient
{
public:
	virtual void process_events(int) = 0;
	virtual ~Cclient();
protected:
	const Csocket& s() const
	{
		return m_s;
	}

	void s(const Csocket& s)
	{
		m_s = s;
	}

	Csocket m_s;
	Cserver* m_server;
};

#endif // !defined(AFX_CLIENT_H__2D721F56_3253_48C2_8EED_FE0181AD999A__INCLUDED_)
