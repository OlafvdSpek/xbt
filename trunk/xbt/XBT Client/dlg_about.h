#if !defined(AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_)
#define AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_about.h : header file
//

#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_about dialog

class Cdlg_about : public CDialog
{
// Construction
public:
	Cdlg_about(CWnd* pParent = NULL);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_about)
	enum { IDD = IDD_ABOUT };
	CStatic	m_site;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_about)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_about)
	afx_msg void OnSite();
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_)
