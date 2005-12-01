#if !defined(AFX_DLG_TRACKER_H__084A6FF5_1A5D_43A3_AE79_50350834DA3B__INCLUDED_)
#define AFX_DLG_TRACKER_H__084A6FF5_1A5D_43A3_AE79_50350834DA3B__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "resource.h"

class Cdlg_tracker: public ETSLayoutDialog
{
public:
	Cdlg_tracker(CWnd* pParent = NULL);

	//{{AFX_DATA(Cdlg_tracker)
	enum { IDD = IDD_TRACKER };
	CString	m_pass;
	CString	m_tracker;
	CString	m_user;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_tracker)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	//{{AFX_MSG(Cdlg_tracker)
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_TRACKER_H__084A6FF5_1A5D_43A3_AE79_50350834DA3B__INCLUDED_)
