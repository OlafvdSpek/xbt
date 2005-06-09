#if !defined(AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_)
#define AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_peer_connect.h : header file
//

#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_peer_connect dialog

class Cdlg_peer_connect: public ETSLayoutDialog
{
// Construction
public:
	Cdlg_peer_connect(CWnd* pParent = NULL);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_peer_connect)
	enum { IDD = IDD_PEER_CONNECT };
	CString	m_host;
	int		m_port;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_peer_connect)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_peer_connect)
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_)
