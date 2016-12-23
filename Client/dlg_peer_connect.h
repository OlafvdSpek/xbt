#pragma once

#include "resource.h"

class Cdlg_peer_connect: public ETSLayoutDialog
{
public:
	Cdlg_peer_connect(CWnd* pParent = NULL);

	enum { IDD = IDD_PEER_CONNECT };
	CString	m_host;
	int		m_port;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	DECLARE_MESSAGE_MAP()
};
