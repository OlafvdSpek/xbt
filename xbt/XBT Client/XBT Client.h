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

/////////////////////////////////////////////////////////////////////////////
// CXBTClientApp:
// See XBT Client.cpp for the implementation of this class
//

class CXBTClientApp : public CWinApp
{
public:
	CXBTClientApp();

// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(CXBTClientApp)
	public:
	virtual BOOL InitInstance();
	//}}AFX_VIRTUAL

// Implementation

	//{{AFX_MSG(CXBTClientApp)
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};


/////////////////////////////////////////////////////////////////////////////

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTCLIENT_H__6B17AE51_6DFB_4F6A_B9AF_170611514AFE__INCLUDED_)
