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
	m_peer_port = 0;
	m_admin_port = 0;
	m_upload_rate = 0;
	m_public_ipa = _T("");
	m_upload_slots = 0;
	m_seeding_ratio = 0;
	m_show_tray_icon = FALSE;
	m_show_advanced_columns = FALSE;
	//}}AFX_DATA_INIT
}


void Cdlg_options::DoDataExchange(CDataExchange* pDX)
{
	CDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_options)
	DDX_Text(pDX, IDC_PEER_PORT, m_peer_port);
	DDV_MinMaxInt(pDX, m_peer_port, 0, 65535);
	DDX_Text(pDX, IDC_ADMIN_PORT, m_admin_port);
	DDV_MinMaxInt(pDX, m_admin_port, 0, 65535);
	DDX_Text(pDX, IDC_UPLOAD_RATE, m_upload_rate);
	DDX_Text(pDX, IDC_PUBLIC_IPA, m_public_ipa);
	DDX_Text(pDX, IDC_UPLOAD_SLOTS, m_upload_slots);
	DDX_Text(pDX, IDC_SEEDING_RATIO, m_seeding_ratio);
	DDX_Check(pDX, IDC_SHOW_TRAY_ICON, m_show_tray_icon);
	DDX_Check(pDX, IDC_SHOW_ADVANCED_COLUMNS, m_show_advanced_columns);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_options, CDialog)
	//{{AFX_MSG_MAP(Cdlg_options)
		// NOTE: the ClassWizard will add message map macros here
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_options message handlers

Cdlg_options::t_data Cdlg_options::get() const
{
	t_data v;
	v.admin_port = m_admin_port;
	v.peer_port = m_peer_port;
	v.public_ipa = m_public_ipa;
	v.seeding_ratio = m_seeding_ratio;
	v.show_advanced_columns = m_show_advanced_columns;
	v.show_tray_icon = m_show_tray_icon;
	v.upload_rate = m_upload_rate << 10;
	v.upload_slots = m_upload_slots;
	return v;
}

void Cdlg_options::set(const t_data& v)
{
	m_admin_port = v.admin_port;
	m_peer_port = v.peer_port;
	m_public_ipa = v.public_ipa.c_str();
	m_seeding_ratio = v.seeding_ratio;
	m_show_advanced_columns = v.show_advanced_columns;
	m_show_tray_icon = v.show_tray_icon;
	m_upload_rate = v.upload_rate >> 10;
	m_upload_slots = v.upload_slots;
}
