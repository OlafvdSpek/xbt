#if !defined(AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_)
#define AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "resource.h"
#include "URLStatic.h"

class Cdlg_about: public CDialog
{
public:
	Cdlg_about(CWnd* pParent = NULL);

	//{{AFX_DATA(Cdlg_about)
	enum { IDD = IDD_ABOUT };
	CURLStatic	m_license;
	CURLStatic	m_site;
	CString	m_version;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_about)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	//{{AFX_MSG(Cdlg_about)
	afx_msg void OnSite();
	virtual BOOL OnInitDialog();
	afx_msg void OnLicense();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_ABOUT_H__AE1E2497_F493_4315_8B7E_A2AF7E8801EF__INCLUDED_)
