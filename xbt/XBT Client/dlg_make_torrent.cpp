// dlg_make_torrent.cpp : implementation file
//

#include "stdafx.h"
#include "xbt client.h"
#include "dlg_make_torrent.h"

#include <sys/stat.h>
#include <fcntl.h>
#include <io.h>
#include "bvalue.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_make_torrent dialog


Cdlg_make_torrent::Cdlg_make_torrent(CWnd* pParent):
	ETSLayoutDialog(Cdlg_make_torrent::IDD, pParent, "Cdlg_make_torrent")
{
	//{{AFX_DATA_INIT(Cdlg_make_torrent)
	m_tracker = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_make_torrent::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_make_torrent)
	DDX_Control(pDX, IDC_SAVE, m_save);
	DDX_Control(pDX, IDC_LIST, m_list);
	DDX_Text(pDX, IDC_TRACKER, m_tracker);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_make_torrent, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_make_torrent)
	ON_WM_DROPFILES()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_WM_SIZE()
	ON_BN_CLICKED(IDC_SAVE, OnSave)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_LIST, OnColumnclickList)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_make_torrent message handlers

BOOL Cdlg_make_torrent::OnInitDialog() 
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item (IDC_TRACKER_STATIC, NORESIZE)
			<< item (IDC_TRACKER, GREEDY)
			)
		<< item (IDC_LIST, GREEDY)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item (IDC_SAVE, NORESIZE)
			)
		;
	UpdateLayout();
	
	m_list.SetExtendedStyle(m_list.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_list.InsertColumn(0, "Name");
	m_list.InsertColumn(1, "Size", LVCFMT_RIGHT);
	m_list.InsertColumn(2, "");
	m_tracker = AfxGetApp()->GetProfileString(m_strRegStore, "tracker");
	m_sort_column = 0;
	m_sort_reverse = false;
	
	return true;
}

void Cdlg_make_torrent::OnDropFiles(HDROP hDropInfo) 
{
	int c_files = DragQueryFile(hDropInfo, 0xFFFFFFFF, NULL, 0);
	
	for (int i = 0; i < c_files; i++)
	{
		char name[MAX_PATH];
		DragQueryFile(hDropInfo, i, name, MAX_PATH);
		insert(name);
	}
	ETSLayoutDialog::OnDropFiles(hDropInfo);
	auto_size();
	sort();
}

void Cdlg_make_torrent::insert(const string& name)
{
	struct stat b;
	if (stat(name.c_str(), &b) || !b.st_size)
		return;
	/*
	int f = _open(name.c_str(), _O_BINARY | _O_RDONLY);
	if (!f)
		return;

	_close(f);
	*/
	int id = m_map.empty() ? 0 : m_map.rbegin()->first + 1;
	t_map_entry& e = m_map[id];
	e.name = name;
	e.size = b.st_size;
	m_list.SetItemData(m_list.InsertItem(m_list.GetItemCount(), LPSTR_TEXTCALLBACK), id);
	m_save.EnableWindow();
}

void Cdlg_make_torrent::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult) 
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
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void Cdlg_make_torrent::auto_size()
{
	for (int i = 0; i < m_list.GetHeaderCtrl()->GetItemCount(); i++)
		m_list.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

void Cdlg_make_torrent::OnSize(UINT nType, int cx, int cy) 
{
	ETSLayoutDialog::OnSize(nType, cx, cy);
	if (m_list.GetSafeHwnd())
		auto_size();	
}

void Cdlg_make_torrent::OnSave() 
{
	CFileDialog dlg(false, "torrent", NULL, OFN_HIDEREADONLY | OFN_PATHMUSTEXIST | OFN_OVERWRITEPROMPT, "Torrents|*.torrent|", this);
	if (IDOK != dlg.DoModal())
		return;
	AfxGetApp()->WriteProfileString(m_strRegStore, "tracker", m_tracker);
	EndDialog(IDOK);
}

void Cdlg_make_torrent::OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult) 
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

int Cdlg_make_torrent::compare(int id_a, int id_b) const
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
		return ::compare(a.size, b.size);
	}
	return 0;
}

static int CALLBACK compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<Cdlg_make_torrent*>(lParamSort)->compare(lParam1, lParam2);
}

void Cdlg_make_torrent::sort()
{
	m_list.SortItems(::compare, reinterpret_cast<DWORD>(this));	
}
