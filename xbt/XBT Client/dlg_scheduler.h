#if !defined(AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_)
#define AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_scheduler.h : header file
//

#include "ListCtrlEx.h"
#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_scheduler dialog

class Cdlg_scheduler: public ETSLayoutDialog
{
// Construction
public:
	Cdlg_scheduler(CWnd* pParent = NULL);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_scheduler)
	enum { IDD = IDD_SCHEDULER };
	CListCtrlEx	m_list;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_scheduler)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_scheduler)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnEdit();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	string m_buffer[4];
	int m_buffer_w;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_)
