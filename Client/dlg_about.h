#pragma once

#include "resource.h"
#include "URLStatic.h"

class Cdlg_about: public CDialog
{
public:
	Cdlg_about(CWnd* pParent = NULL);

	enum { IDD = IDD_ABOUT };
	CURLStatic	m_license;
	CURLStatic	m_site;
	CString	m_version;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	afx_msg void OnSite();
	virtual BOOL OnInitDialog();
	afx_msg void OnLicense();
	DECLARE_MESSAGE_MAP()
};
