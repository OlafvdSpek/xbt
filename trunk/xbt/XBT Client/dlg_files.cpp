// dlg_files.cpp : implementation file
//

#include "stdafx.h"
#include "dlg_files.h"

#include "../bt test/server.h"

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
	ON_NOTIFY(LVN_GETDISPINFO, IDC_FILES, OnGetdispinfoFiles)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_FILES, OnColumnclickFiles)
	ON_BN_CLICKED(IDC_OPEN, OnOpen)
	ON_NOTIFY(NM_DBLCLK, IDC_FILES, OnDblclkFiles)
	ON_BN_CLICKED(IDC_EXPLORE, OnExplore)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_files message handlers

BOOL Cdlg_files::OnInitDialog() 
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< item(IDC_FILES, GREEDY)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item(IDC_EXPLORE, NORESIZE)
			<< item(IDC_OPEN, NORESIZE)
			<< item(IDC_EXCLUDE, NORESIZE)
			<< item(IDC_DECREASE_PRIORITY, NORESIZE)
			<< item(IDC_INCREASE_PRIORITY, NORESIZE)
			)
		;
	UpdateLayout();

	m_files.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_files.InsertColumn(0, "Name");
	m_files.InsertColumn(1, "%", LVCFMT_RIGHT);
	m_files.InsertColumn(2, "Left", LVCFMT_RIGHT);
	m_files.InsertColumn(3, "Size", LVCFMT_RIGHT);
	m_files.InsertColumn(4, "Priority");
	m_files.InsertColumn(5, "Hash");
	m_sort_column = 0;
	m_sort_reverse = false;
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
	m_name = sr.read_string();
	sr.read_int(4);
	__int64 downloaded = sr.read_int(8);
	__int64 left = sr.read_int(8);
	__int64 size = sr.read_int(8);
	__int64 uploaded = sr.read_int(8);
	__int64 total_downloaded = sr.read_int(8);
	__int64 total_uploaded = sr.read_int(8);
	int down_rate = sr.read_int(4);
	int up_rate = sr.read_int(4);
	int c_leechers = sr.read_int(4);
	int c_seeders = sr.read_int(4);
	sr.read_int(4);
	sr.read_int(4);
	bool run = sr.read_int(4);
	sr.read_int(4);
	sr.read_int(4);
	int c_files = sr.read_int(4);
	for (int i = 0; i < c_files; i++)
	{
		t_map_entry& e = m_map[i];
		e.left = sr.read_int(8);
		e.name = sr.read_string();
		e.priority = sr.read_int(4);
		e.size = sr.read_int(8);
		e.hash = sr.read_string();
	}
	if (m_files.GetItemCount())
		m_files.Invalidate();
	else
	{
		for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
			m_files.SetItemData(m_files.InsertItem(m_files.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
	}
	sort();
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
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
		m_server.sub_file_priority(m_info_hash, e.name, e.priority - 1);
	}
	load_data();
}

void Cdlg_files::OnIncreasePriority() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
		m_server.sub_file_priority(m_info_hash, e.name, e.priority + 1);
	}
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
		if (e.name.empty())
		{
			int i = m_name.rfind('\\');
			m_buffer[m_buffer_w] = i == string::npos ? m_name : m_name.substr(i + 1);
		}
		else
			m_buffer[m_buffer_w] = e.name;
		break;
	case 1:
		if (e.size)
			m_buffer[m_buffer_w] = n((e.size - e.left) * 100 / e.size);
		break;
	case 2:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case 3:
		m_buffer[m_buffer_w] = b2a(e.size);
		break;
	case 4:
		if (e.priority)
			m_buffer[m_buffer_w] = n(e.priority);
		break;
	case 5:
		m_buffer[m_buffer_w] = hex_encode(e.hash);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void Cdlg_files::OnColumnclickFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	m_sort_reverse = pNMListView->iSubItem == m_sort_column && !m_sort_reverse;
	m_sort_column = pNMListView->iSubItem;
	sort();
	*pResult = 0;
}

template <class T>
static int compare(const T& a, const T& b)
{
	return a < b ? -1 : a != b;
}

int Cdlg_files::compare(int id_a, int id_b) const
{
	if (m_sort_reverse)
		swap(id_a, id_b);
	const t_map_entry& a = m_map.find(id_a)->second;
	const t_map_entry& b = m_map.find(id_b)->second;
	switch (m_sort_column)
	{
	case 0:
		return ::compare(a.name, b.name);
	case 1:
		return ::compare(b.left * 1000 / b.size, a.left * 1000 / a.size);
	case 2:
		return ::compare(a.left, b.left);
	case 3:
		return ::compare(a.size, b.size);
	case 4:
		return ::compare(b.priority, a.priority);
	case 5:
		return ::compare(a.hash, b.hash);
	}
	return 0;
}

static int CALLBACK compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<Cdlg_files*>(lParamSort)->compare(lParam1, lParam2);
}

void Cdlg_files::sort()
{
	m_files.SortItems(::compare, reinterpret_cast<DWORD>(this));	
}

void Cdlg_files::OnExplore() 
{
	string name = m_name;
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
	{
		const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
		name += e.name;
	}
	for (int i = 0; (i = name.find('/', i)) != string::npos; i++)
		name[i] = '\\';
	struct _stati64 b;
	if (_stati64(name.c_str(), &b) || ~b.st_mode & S_IFDIR)
	{
		int i = name.rfind('\\');
		if (i != string::npos)
			name.erase(i);
	}
	ShellExecute(m_hWnd, "open", name.c_str(), NULL, NULL, SW_SHOW);
}

void Cdlg_files::OnOpen() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	const t_map_entry& e = m_map.find(m_files.GetItemData(index))->second;
	string name = m_name + e.name;
	for (int i = 0; (i = name.find('/', i)) != string::npos; i++)
		name[i] = '\\';
	ShellExecute(m_hWnd, "open", name.c_str(), NULL, NULL, SW_SHOW);
}

void Cdlg_files::OnDblclkFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	OnOpen();	
	*pResult = 0;
}
