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

class CXBTClientApp: public CWinApp
{
public:
	static BOOL CALLBACK enumerator(HWND, LPARAM);
	//{{AFX_VIRTUAL(CXBTClientApp)
	public:
	virtual BOOL InitInstance();
	//}}AFX_VIRTUAL

	//{{AFX_MSG(CXBTClientApp)
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_XBTCLIENT_H__6B17AE51_6DFB_4F6A_B9AF_170611514AFE__INCLUDED_)
