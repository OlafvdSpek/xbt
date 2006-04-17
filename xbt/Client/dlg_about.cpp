#include "stdafx.h"
#include "dlg_about.h"

#include "../bt test/server.h"

Cdlg_about::Cdlg_about(CWnd* pParent /*=NULL*/)
	: CDialog(Cdlg_about::IDD, pParent)
{
	//{{AFX_DATA_INIT(Cdlg_about)
	m_version = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_about::DoDataExchange(CDataExchange* pDX)
{
	CDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_about)
	DDX_Control(pDX, IDC_LICENSE, m_license);
	DDX_Control(pDX, IDC_SITE, m_site);
	DDX_Text(pDX, IDC_VERSION, m_version);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_about, CDialog)
	//{{AFX_MSG_MAP(Cdlg_about)
	ON_BN_CLICKED(IDC_SITE, OnSite)
	ON_BN_CLICKED(IDC_LICENSE, OnLicense)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

void Cdlg_about::OnSite()
{
	ShellExecute(m_hWnd, "open", "http://xbtt.sourceforge.net/", NULL, NULL, SW_SHOW);
}

BOOL Cdlg_about::OnInitDialog()
{
	m_version = ("Version: " + xbt_version2a(Cserver::version())).c_str();

	CDialog::OnInitDialog();

	m_license.ModifyStyle(0, SS_NOTIFY);
	m_site.ModifyStyle(0, SS_NOTIFY);

	return true;
}

void Cdlg_about::OnLicense()
{
	ShellExecute(m_hWnd, "open", "http://gnu.org/copyleft/gpl.html", NULL, NULL, SW_SHOW);
}
