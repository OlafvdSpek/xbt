// dlg_torrent.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Client.h"
#include "dlg_torrent.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_torrent dialog


Cdlg_torrent::Cdlg_torrent(CWnd* pParent, Cserver& server, const string& info_hash):
	ETSLayoutDialog(Cdlg_torrent::IDD, pParent, "Cdlg_torrent"),
	m_server(server),
	m_info_hash(info_hash)
{
	//{{AFX_DATA_INIT(Cdlg_torrent)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_torrent::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_torrent)
	DDX_Control(pDX, IDC_ALERTS, m_alerts);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_torrent, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_torrent)
	ON_WM_SIZE()
	ON_WM_TIMER()
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_torrent message handlers

BOOL Cdlg_torrent::OnInitDialog() 
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< item (IDC_ALERTS, GREEDY)
		;
	UpdateLayout();

	m_alerts.SetExtendedStyle(m_alerts.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_alerts.InsertColumn(0, "Time");
	m_alerts.InsertColumn(1, "Level");
	m_alerts.InsertColumn(2, "Source");
	m_alerts.InsertColumn(3, "Message");
	load_data();
	SetTimer(0, 15000, NULL);
	return TRUE;  // return TRUE unless you set the focus to a control
	              // EXCEPTION: OCX Property Pages should return FALSE
}

void Cdlg_torrent::load_data()
{
	Cstream_reader sr(m_server.get_file_status(m_info_hash, Cserver::df_alerts));
	if (sr.d() == sr.d_end())
		return;
	string info_hash = sr.read_string();
	string name = sr.read_string();
	__int64 downloaded = sr.read_int64();
	__int64 left = sr.read_int64();
	__int64 size = sr.read_int64();
	__int64 uploaded = sr.read_int64();
	__int64 total_downloaded = sr.read_int64();
	__int64 total_uploaded = sr.read_int64();
	int down_rate = sr.read_int32();
	int up_rate = sr.read_int32();
	int c_leechers = sr.read_int32();
	int c_seeders = sr.read_int32();
	sr.read_int32();
	sr.read_int32();
	bool run = sr.read_int32();
	sr.read_int32();
	m_alerts.DeleteAllItems();
	for (int c_alerts = sr.read_int32(); c_alerts--; )
	{
		time_t timer = sr.read_int32();
		tm* time = localtime(&timer);
		int level = sr.read_int32();
		string message = sr.read_string();
		string source = sr.read_string();
		char time_string[16];
		sprintf(time_string, "%02d:%02d:%02d", time->tm_hour, time->tm_min, time->tm_sec);
		int index = m_alerts.InsertItem(0, time_string);
		m_alerts.SetItemText(index, 1, n(level).c_str());
		m_alerts.SetItemText(index, 2, source.c_str());
		m_alerts.SetItemText(index, 3, message.c_str());
	}
	auto_size();
}

void Cdlg_torrent::auto_size()
{
	for (int i = 0; i < m_alerts.GetHeaderCtrl()->GetItemCount(); i++)
		m_alerts.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

void Cdlg_torrent::OnSize(UINT nType, int cx, int cy) 
{
	ETSLayoutDialog::OnSize(nType, cx, cy);
	if (m_alerts.GetSafeHwnd())
		auto_size();
}

void Cdlg_torrent::OnTimer(UINT nIDEvent) 
{
	load_data();	
	ETSLayoutDialog::OnTimer(nIDEvent);
}
