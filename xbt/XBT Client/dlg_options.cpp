// dlg_options.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Client.h"
#include "dlg_options.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_options dialog


Cdlg_options::Cdlg_options(CWnd* pParent /*=NULL*/)
	: CDialog(Cdlg_options::IDD, pParent)
{
	//{{AFX_DATA_INIT(Cdlg_options)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_options::DoDataExchange(CDataExchange* pDX)
{
	CDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_options)
		// NOTE: the ClassWizard will add DDX and DDV calls here
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_options, CDialog)
	//{{AFX_MSG_MAP(Cdlg_options)
		// NOTE: the ClassWizard will add message map macros here
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_options message handlers
