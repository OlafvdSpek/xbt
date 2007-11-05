#if !defined(AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_)
#define AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "resource.h"

class Cdlg_peer_connect: public ETSLayoutDialog
{
public:
	Cdlg_peer_connect(CWnd* pParent = NULL);

	//{{AFX_DATA(Cdlg_peer_connect)
	enum { IDD = IDD_PEER_CONNECT };
	CString	m_host;
	int		m_port;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_peer_connect)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	//{{AFX_MSG(Cdlg_peer_connect)
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_PEER_CONNECT_H__48D0653F_0506_4F16_B484_3A7AD195F970__INCLUDED_)
