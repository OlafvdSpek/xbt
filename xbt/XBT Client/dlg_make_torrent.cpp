// dlg_make_torrent.cpp : implementation file
//

#include "stdafx.h"
#include "xbt client.h"
#include "dlg_make_torrent.h"

#include "bt_strings.h"

#include <sys/stat.h>
#include <fcntl.h>
#include <io.h>
#include "bvalue.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

#define for if (0) {} else for

/////////////////////////////////////////////////////////////////////////////
// Cdlg_make_torrent dialog


Cdlg_make_torrent::Cdlg_make_torrent(CWnd* pParent):
	ETSLayoutDialog(Cdlg_make_torrent::IDD, pParent, "Cdlg_make_torrent")
{
	//{{AFX_DATA_INIT(Cdlg_make_torrent)
	m_tracker = _T("");
	m_name = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_make_torrent::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_make_torrent)
	DDX_Control(pDX, IDC_SAVE, m_save);
	DDX_Control(pDX, IDC_LIST, m_list);
	DDX_Text(pDX, IDC_TRACKER, m_tracker);
	DDX_Text(pDX, IDC_NAME, m_name);
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
	m_tracker = AfxGetApp()->GetProfileString(m_strRegStore, "tracker");
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item (IDC_NAME_STATIC, NORESIZE)
			<< item (IDC_NAME, GREEDY)
			)
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
	m_sort_column = 0;
	m_sort_reverse = false;
	
	return true;
}

static string base_name(const string& v)
{
	int i = v.rfind('\\');
	return i == string::npos ? v : v.substr(i + 1);
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
	if (m_map.size() == 1)
	{
		m_name = base_name(m_map.begin()->second.name).c_str();
		UpdateData(false);
	}
	auto_size();
	sort();
}

void Cdlg_make_torrent::insert(const string& name)
{
	struct stat b;
	if (stat(name.c_str(), &b))
		return;
	if (b.st_mode & _S_IFDIR)
	{
		if (m_map.empty())
		{
			m_name = base_name(name).c_str();
			UpdateData(false);
		}
		WIN32_FIND_DATA finddata;
		HANDLE findhandle = FindFirstFile((name + "\\*").c_str(), &finddata);
		if (findhandle != INVALID_HANDLE_VALUE)
		{
			do
			{
				if (*finddata.cFileName != '.')
					insert(name + "\\" + finddata.cFileName);
			}
			while (FindNextFile(findhandle, &finddata));
			FindClose(findhandle);
		}
		return;
	}
	if (!b.st_size)
		return;
	int id = m_map.empty() ? 0 : m_map.rbegin()->first + 1;
	t_map_entry& e = m_map[id];
	e.name = name;
	e.size = b.st_size;
	m_list.SetItemData(m_list.InsertItem(m_list.GetItemCount(), LPSTR_TEXTCALLBACK), id);
	m_save.EnableWindow(m_map.size() < 256);
}

void Cdlg_make_torrent::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_map_entry& e = m_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = base_name(e.name);
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
	if (!UpdateData())
		return;
	CFileDialog dlg(false, "torrent", m_name, OFN_HIDEREADONLY | OFN_PATHMUSTEXIST | OFN_OVERWRITEPROMPT, "Torrents|*.torrent|", this);
	if (IDOK != dlg.DoModal())
		return;
	AfxGetApp()->WriteProfileString(m_strRegStore, "tracker", m_tracker);
	__int64 cb_total = 0;
	for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
		cb_total += i->second.size;
	int cb_piece = 256 << 10;
	while (cb_total / cb_piece > 4 << 10)
		cb_piece <<= 1;
	Cbvalue files;
	string pieces;
	Cvirtual_binary d;
	byte* w = d.write_start(cb_piece);
	for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
	{
		int f = _open(i->second.name.c_str(), _O_BINARY | _O_RDONLY);
		if (!f)
			continue;
		__int64 cb_f = 0;
		int cb_d;
		while (cb_d = _read(f, w, d.data_end() - w))
		{
			if (cb_d < 0)
				break;
			w += cb_d;
			if (w == d.data_end())
			{
				pieces += Csha1(d, w - d).read();
				w = d.data_edit();
			}
			cb_f += cb_d;
		}
		_close(f);
		files.l(Cbvalue().d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(i->second.name))));
	}
	if (w != d)
		pieces += Csha1(d, w - d).read();
	Cbvalue info;
	info.d(bts_piece_length, cb_piece);
	info.d(bts_pieces, pieces);
	if (m_map.size() == 1)
	{
		info.d(bts_length, files.l().front().d(bts_length).i());
		info.d(bts_name, files.l().front().d(bts_path).l().front().s());
	}
	else
	{
		info.d(bts_files, files);
		info.d(bts_name, static_cast<string>(m_name));
	}
	Cbvalue torrent;
	torrent.d(bts_announce, static_cast<string>(m_tracker));
	torrent.d(bts_info, info);
	torrent.read().save(static_cast<string>(dlg.GetPathName()));
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
