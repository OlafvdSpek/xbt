// dlg_files.cpp : implementation file
//

#include "stdafx.h"
#include "xbt client.h"
#include "dlg_files.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_files dialog


Cdlg_files::Cdlg_files(CWnd* pParent, Cserver& server, const string& info_hash):
	ETSLayoutDialog(Cdlg_files::IDD, pParent, "Cdlg_files"),
	m_server(server),
	m_info_hash(info_hash)
{
	//{{AFX_DATA_INIT(Cdlg_files)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_files::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_files)
	DDX_Control(pDX, IDC_FILES, m_files);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_files, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_files)
	ON_WM_SIZE()
	ON_WM_TIMER()
	ON_BN_CLICKED(IDC_DECREASE_PRIORITY, OnDecreasePriority)
	ON_BN_CLICKED(IDC_INCREASE_PRIORITY, OnIncreasePriority)
	ON_WM_CHAR()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_FILES, OnGetdispinfoFiles)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_files message handlers

BOOL Cdlg_files::OnInitDialog() 
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< item (IDC_FILES, GREEDY)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item (IDC_DECREASE_PRIORITY, NORESIZE)
			<< item (IDC_INCREASE_PRIORITY, NORESIZE)
			)
		;
	UpdateLayout();

	m_files.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_files.InsertColumn(0, "Name");
	m_files.InsertColumn(1, "Size", LVCFMT_RIGHT);
	m_files.InsertColumn(2, "Priority");
	load_data();
	SetTimer(0, 15000, NULL);
	return TRUE;  // return TRUE unless you set the focus to a control
	              // EXCEPTION: OCX Property Pages should return FALSE
}

void Cdlg_files::load_data()
{
	Cstream_reader sr(m_server.get_file_status(m_info_hash, Cserver::df_files));
	if (sr.d() == sr.d_end())
		return;
	string info_hash = sr.read_string();
	info_hash = info_hash;
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
	sr.read_int32();
	int c_files = sr.read_int32();
	for (int i = 0; i < c_files; i++)
	{
		t_map_entry& e = m_map[i];
		e.name = sr.read_string();
		e.priority = sr.read_int32();
		e.size = sr.read_int64();
	}
	if (m_files.GetItemCount())
		m_files.Invalidate();
	else
	{
		for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
			m_files.SetItemData(m_files.InsertItem(m_files.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
	}
	auto_size();
}

void Cdlg_files::auto_size()
{
	for (int i = 0; i < m_files.GetHeaderCtrl()->GetItemCount(); i++)
		m_files.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

void Cdlg_files::OnSize(UINT nType, int cx, int cy) 
{
	ETSLayoutDialog::OnSize(nType, cx, cy);
	if (m_files.GetSafeHwnd())
		auto_size();
}

void Cdlg_files::OnTimer(UINT nIDEvent) 
{
	load_data();	
	ETSLayoutDialog::OnTimer(nIDEvent);
}

void Cdlg_files::OnDecreasePriority() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
	m_server.sub_file_priority(m_info_hash, e.name, e.priority - 1);
	load_data();
}

void Cdlg_files::OnIncreasePriority() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
	m_server.sub_file_priority(m_info_hash, e.name, e.priority + 1);
	load_data();
}

void Cdlg_files::OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_map_entry& e = m_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = e.name;
		break;
	case 1:
		m_buffer[m_buffer_w] = n(e.size);
		break;
	case 2:
		m_buffer[m_buffer_w] = n(e.priority);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}
