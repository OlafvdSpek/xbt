// XBT Manager.h : main header file for the XBT MANAGER application
//

#if !defined(AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_)
#define AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#ifndef __AFXWIN_H__
	#error include 'stdafx.h' before including this file for PCH
#endif

#include "resource.h"		// main symbols

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerApp:
// See XBT Manager.cpp for the implementation of this class
//

class CXBTManagerApp: public CWinApp
{
public:
	CXBTManagerApp();

// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(CXBTManagerApp)
	public:
	virtual BOOL InitInstance();
	//}}AFX_VIRTUAL

// Implementation

	//{{AFX_MSG(CXBTManagerApp)
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};


/////////////////////////////////////////////////////////////////////////////

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_)
