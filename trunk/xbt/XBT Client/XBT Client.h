// XBT Client.h : main header file for the XBT CLIENT application
//

#if !defined(AFX_XBTCLIENT_H__6B17AE51_6DFB_4F6A_B9AF_170611514AFE__INCLUDED_)
#define AFX_XBTCLIENT_H__6B17AE51_6DFB_4F6A_B9AF_170611514AFE__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#ifndef __AFXWIN_H__
	#error include 'stdafx.h' before including this file for PCH
#endif

#include "resource.h"		// main symbols
#include "../bt test/server.h"

/////////////////////////////////////////////////////////////////////////////
// CXBTClientApp:
// See XBT Client.cpp for the implementation of this class
//

class CXBTClientApp : public CWinApp
{
public:
	static unsigned int backend_thread(void* p);
	CXBTClientApp();

// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(CXBTClientApp)
	public:
	virtual BOOL InitInstance();
	virtual int ExitInstance();
	//}}AFX_VIRTUAL

// Implementation

	//{{AFX_MSG(CXBTClientApp)
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	Cserver m_server;
	CWinThread* m_server_thread;
};


/////////////////////////////////////////////////////////////////////////////

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTCLIENT_H__6B17AE51_6DFB_4F6A_B9AF_170611514AFE__INCLUDED_)
