// XBT ManagerDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Manager.h"
#include "XBT ManagerDlg.h"

#include "bt_misc.h"
#include "bt_strings.h"
#include "bvalue.h"
#include "sha1.h"
#include "virtual_binary.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

const char* list_fname = "xbt manager list.txt";

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerDlg dialog

CXBTManagerDlg::CXBTManagerDlg(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(CXBTManagerDlg::IDD, pParent, "CXBTManagerDlg")
{
	//{{AFX_DATA_INIT(CXBTManagerDlg)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
	m_hIcon = AfxGetApp()->LoadIcon(IDR_MAINFRAME);
	m_buffer_w = 0;
}

void CXBTManagerDlg::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(CXBTManagerDlg)
	DDX_Control(pDX, IDC_LIST, m_list);
	//}}AFX_DATA_MAP
}

BEGIN_MESSAGE_MAP(CXBTManagerDlg, ETSLayoutDialog)
	//{{AFX_MSG_MAP(CXBTManagerDlg)
	ON_WM_PAINT()
	ON_WM_QUERYDRAGICON()
	ON_WM_DROPFILES()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_WM_SIZE()
	ON_NOTIFY(NM_DBLCLK, IDC_LIST, OnDblclkList)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_LIST, OnColumnclickList)
	ON_NOTIFY(LVN_KEYDOWN, IDC_LIST, OnKeydownList)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerDlg message handlers

BOOL CXBTManagerDlg::OnInitDialog()
{
	SetIcon(m_hIcon, TRUE);			// Set big icon
	SetIcon(m_hIcon, FALSE);		// Set small icon

	ETSLayoutDialog::OnInitDialog();

	m_list.SetExtendedStyle(m_list.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_list.InsertColumn(0, "Name");
	m_list.InsertColumn(1, "Leechers", LVCFMT_RIGHT);
	m_list.InsertColumn(2, "Seeders", LVCFMT_RIGHT);
	m_list.InsertColumn(3, "Tracker");
	m_list.InsertColumn(4, "Error");
	
	CreateRoot(VERTICAL)
		<< item(IDC_LIST, GREEDY)
		;
	UpdateLayout();

	load(list_fname);
	sort(0);

	return TRUE;  // return TRUE  unless you set the focus to a control
}

// If you add a minimize button to your dialog, you will need the code below
//  to draw the icon.  For MFC applications using the document/view model,
//  this is automatically done for you by the framework.

void CXBTManagerDlg::OnPaint() 
{
	if (IsIconic())
	{
		CPaintDC dc(this); // device context for painting

		SendMessage(WM_ICONERASEBKGND, (WPARAM) dc.GetSafeHdc(), 0);

		// Center icon in client rectangle
		int cxIcon = GetSystemMetrics(SM_CXICON);
		int cyIcon = GetSystemMetrics(SM_CYICON);
		CRect rect;
		GetClientRect(&rect);
		int x = (rect.Width() - cxIcon + 1) / 2;
		int y = (rect.Height() - cyIcon + 1) / 2;

		// Draw the icon
		dc.DrawIcon(x, y, m_hIcon);
	}
	else
	{
		ETSLayoutDialog::OnPaint();
	}
}

HCURSOR CXBTManagerDlg::OnQueryDragIcon()
{
	return (HCURSOR) m_hIcon;
}

void CXBTManagerDlg::OnDropFiles(HDROP hDropInfo) 
{
	char name[MAX_PATH];
	for (int i = 0; i < DragQueryFile(hDropInfo, 0xFFFFFFFF, NULL, 0); i++)
	{
		DragQueryFile(hDropInfo, i, name, MAX_PATH);
		insert(name);
	}	
	ETSLayoutDialog::OnDropFiles(hDropInfo);
}

void CXBTManagerDlg::auto_resize()
{
	for (int i = 0; i < m_list.GetHeaderCtrl()->GetItemCount(); i++)
		m_list.SetColumnWidth(i, m_map.empty() ? LVSCW_AUTOSIZE_USEHEADER : LVSCW_AUTOSIZE);
}

void CXBTManagerDlg::insert(const string& name)
{
	Cbvalue v;
	if (v.write(Cvirtual_binary(name)))
		return;
	t_map_entry& e = m_map[m_map.empty() ? 0 : m_map.rbegin()->first + 1];
	e.fname = name;
	{
		int i = name.rfind('\\');
		e.name = name.substr(i == string::npos ? 0 : i + 1);
		i = e.name.rfind('.');
		if (i != string::npos)
			e.name.erase(i);
	}
	Cvirtual_binary d = v.d("info").read();
	char h[20];
	compute_sha1(d, d.size(), h);
	e.info_hash.assign(h, 20);
	e.tracker = v.d(bts_announce).s();
	e.leechers = -1;
	e.seeders = -1;
	e.s = new Ctracker_socket;
	e.s->dlg(this);
	e.s->hash(e.info_hash);
	e.s->connect(e.tracker);
	m_list.SetItemData(m_list.InsertItem(m_list.GetItemCount(), LPSTR_TEXTCALLBACK), m_map.rbegin()->first);
	auto_resize();
	save(list_fname);	
}

void CXBTManagerDlg::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	const t_map_entry& e = m_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = e.name;
		break;
	case 1:
		m_buffer[m_buffer_w] = e.leechers == -1 ? "" : n(e.leechers);
		break;
	case 2:
		m_buffer[m_buffer_w] = e.seeders == -1 ? "" : n(e.seeders);
		break;
	case 3:
		m_buffer[m_buffer_w] = e.tracker;
		break;
	case 4:
		m_buffer[m_buffer_w] = e.error;
		break;
	default:
		m_buffer[m_buffer_w].erase();
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w--].c_str());
	if (m_buffer_w < 0)
		m_buffer_w += 4;
	*pResult = 0;
}

void CXBTManagerDlg::OnSize(UINT nType, int cx, int cy) 
{
	ETSLayoutDialog::OnSize(nType, cx, cy);
	if (m_list.GetSafeHwnd())
		auto_resize();
}

void CXBTManagerDlg::load(const char* name)
{
	ifstream is(name);
	string s;
	while (getline(is, s))
		insert(s);
}

void CXBTManagerDlg::save(const char* name)
{
	ofstream os(name);
	for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
		os << i->second.fname << endl;
}

void CXBTManagerDlg::OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNM = reinterpret_cast<NM_LISTVIEW*>(pNMHDR);
	if (pNM->iItem == -1)
		return;
	const t_map_entry& e = m_map.find(pNM->iItem)->second;
	ShellExecute(m_hWnd, "open", e.fname.c_str(), NULL, NULL, SW_SHOW);	
	*pResult = 0;
}

void CXBTManagerDlg::OnOK() 
{
}

static int CALLBACK compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTManagerDlg*>(lParamSort)->compare(lParam1, lParam2);
}

void CXBTManagerDlg::OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	sort(pNMListView->iSubItem);
	*pResult = 0;
}

template <class T>
static int compare(const T& a, const T& b)
{
	return a < b ? -1 : a != b;
}

int CXBTManagerDlg::compare(LPARAM lParam1, LPARAM lParam2)
{
	const t_map_entry& a = m_map.find(lParam1)->second;
	const t_map_entry& b = m_map.find(lParam2)->second;
	switch (m_sort_column)
	{
	case 0:
		return ::compare(a.name, b.name);
	case 1:
		return ::compare(a.leechers, b.leechers);
	case 2:
		return ::compare(a.seeders, b.seeders);
	case 3:
		return ::compare(a.tracker, b.tracker);
	case 4:
		return ::compare(a.error, b.error);
	}
	return 0;
}

void CXBTManagerDlg::sort(int column)
{
	m_sort_column = column;
	m_list.SortItems(::compare, reinterpret_cast<DWORD>(this));
}

void CXBTManagerDlg::OnKeydownList(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_KEYDOWN* pLVKeyDow = (LV_KEYDOWN*)pNMHDR;
	switch (pLVKeyDow->wVKey)
	{
	case VK_DELETE:
		{
			for (int i; (i = m_list.GetNextItem(-1, LVNI_ALL | LVNI_SELECTED)) != -1; )
			{
				m_map.erase(m_list.GetItemData(i));
				m_list.DeleteItem(i);
			}
			save(list_fname);
		}
		break;
	}
	*pResult = 0;
}

void CXBTManagerDlg::tracker_output(const string& hash, const Cbvalue& v)
{
	for (t_map::iterator i = m_map.begin(); i != m_map.end(); i++)
	{
		if (i->second.info_hash != hash)
			continue;
		if (v.d(bts_failure_reason).s().empty())
		{
			const Cbvalue& file = v.d(bts_files).d(hash);
			i->second.leechers = file.d(bts_incomplete).i();
			i->second.seeders = file.d(bts_complete).i();
		}
		else
			i->second.error = v.d(bts_failure_reason).s();
		i->second.mtime = time(NULL);
		LVFINDINFO lvf;
		lvf.flags = LVFI_PARAM;
		lvf.lParam = i->first;
		m_list.Update(m_list.FindItem(&lvf, -1));
		delete i->second.s;
		i->second.s = NULL;
		auto_resize();
		return;
	}
}
