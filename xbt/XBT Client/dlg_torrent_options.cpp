// dlg_torrent_options.cpp : implementation file
//

#include "stdafx.h"
#include "dlg_torrent_options.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_torrent_options dialog


Cdlg_torrent_options::Cdlg_torrent_options(CWnd* pParent /*=NULL*/)
	: CDialog(Cdlg_torrent_options::IDD, pParent)
{
	//{{AFX_DATA_INIT(Cdlg_torrent_options)
	m_seeding_ratio_value = 0;
	//}}AFX_DATA_INIT
}


void Cdlg_torrent_options::DoDataExchange(CDataExchange* pDX)
{
	CDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_torrent_options)
	DDX_Control(pDX, IDC_SEEDING_RATIO_OVERRIDE, m_seeding_ratio_override);
	DDX_Control(pDX, IDC_SEEDING_RATIO, m_seeding_ratio);
	DDX_Text(pDX, IDC_SEEDING_RATIO, m_seeding_ratio_value);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_torrent_options, CDialog)
	//{{AFX_MSG_MAP(Cdlg_torrent_options)
	ON_BN_CLICKED(IDC_SEEDING_RATIO_OVERRIDE, OnSeedingRatioOverride)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_torrent_options message handlers

BOOL Cdlg_torrent_options::OnInitDialog() 
{
	m_seeding_ratio_value = m_data.seeding_ratio;
	CDialog::OnInitDialog();
	m_seeding_ratio_override.SetCheck(m_data.seeding_ratio_override);
	update_controls();	
	return true;
}

void Cdlg_torrent_options::update_controls()
{
	m_seeding_ratio.EnableWindow(m_seeding_ratio_override.GetState() & 3);
}

void Cdlg_torrent_options::OnSeedingRatioOverride() 
{
	update_controls();
}

void Cdlg_torrent_options::OnOK() 
{
	CDialog::OnOK();
	m_data.seeding_ratio = m_seeding_ratio_value;
	m_data.seeding_ratio_override = m_seeding_ratio_override.GetState() & 3;
}

const Cdlg_torrent_options::t_data& Cdlg_torrent_options::get() const
{
	return m_data;
}

void Cdlg_torrent_options::set(const t_data& v)
{
	m_data = v;
}

