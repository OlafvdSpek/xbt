// dlg_about.cpp : implementation file
//

#include "stdafx.h"
#include "xbt client.h"
#include "dlg_about.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_about dialog


Cdlg_about::Cdlg_about(CWnd* pParent /*=NULL*/)
	: CDialog(Cdlg_about::IDD, pParent)
{
	//{{AFX_DATA_INIT(Cdlg_about)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_about::DoDataExchange(CDataExchange* pDX)
{
	CDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_about)
	DDX_Control(pDX, IDC_SITE, m_site);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_about, CDialog)
	//{{AFX_MSG_MAP(Cdlg_about)
	ON_BN_CLICKED(IDC_SITE, OnSite)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_about message handlers

void Cdlg_about::OnSite() 
{
	ShellExecute(m_hWnd, "open", "http://xbtt.sourceforge.net/", NULL, NULL, SW_SHOW);	
}

BOOL Cdlg_about::OnInitDialog() 
{
	CDialog::OnInitDialog();
	
	m_site.ModifyStyle(0, SS_NOTIFY);
	
	return true;
}
