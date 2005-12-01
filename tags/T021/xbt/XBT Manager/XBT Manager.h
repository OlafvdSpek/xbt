#if !defined(AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_)
#define AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#ifndef __AFXWIN_H__
	#error include 'stdafx.h' before including this file for PCH
#endif

#include "resource.h"

class CXBTManagerApp: public CWinApp
{
public:
	CXBTManagerApp();

	//{{AFX_VIRTUAL(CXBTManagerApp)
	public:
	virtual BOOL InitInstance();
	//}}AFX_VIRTUAL

	//{{AFX_MSG(CXBTManagerApp)
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_XBTMANAGER_H__F07050CF_3876_47CA_B44F_8533F90AC7F2__INCLUDED_)
