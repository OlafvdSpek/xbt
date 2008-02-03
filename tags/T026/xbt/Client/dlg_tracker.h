#pragma once

#include "resource.h"

class Cdlg_tracker: public ETSLayoutDialog
{
public:
	Cdlg_tracker(CWnd* pParent = NULL);

	enum { IDD = IDD_TRACKER };
	CString	m_pass;
	CString	m_tracker;
	CString	m_user;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	DECLARE_MESSAGE_MAP()
};
