// XBT ClientDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Client.h"
#include "XBT ClientDlg.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg dialog

CXBTClientDlg::CXBTClientDlg(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(CXBTClientDlg::IDD, pParent, "CXBTClientDlg")
{
	//{{AFX_DATA_INIT(CXBTClientDlg)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
	m_hIcon = AfxGetApp()->LoadIcon(IDR_MAINFRAME);
}

void CXBTClientDlg::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(CXBTClientDlg)
	DDX_Control(pDX, IDC_PEERS, m_peers);
	DDX_Control(pDX, IDC_FILES, m_files);
	//}}AFX_DATA_MAP
}

BEGIN_MESSAGE_MAP(CXBTClientDlg, ETSLayoutDialog)
	//{{AFX_MSG_MAP(CXBTClientDlg)
	ON_WM_PAINT()
	ON_WM_QUERYDRAGICON()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_FILES, OnGetdispinfoFiles)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_PEERS, OnGetdispinfoPeers)
	ON_WM_SIZE()
	ON_NOTIFY(LVN_ITEMCHANGED, IDC_FILES, OnItemchangedFiles)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg message handlers

BOOL CXBTClientDlg::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();

	CreateRoot(VERTICAL)
		<< item (IDC_FILES, GREEDY)
		<< item (IDC_PEERS, GREEDY)
		;
	UpdateLayout();

	SetIcon(m_hIcon, TRUE);			// Set big icon
	SetIcon(m_hIcon, FALSE);		// Set small icon
	
	m_files.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_files.InsertColumn(0, "");
	m_files.InsertColumn(1, "Downloaded", LVCFMT_RIGHT);
	m_files.InsertColumn(2, "Left", LVCFMT_RIGHT);
	m_files.InsertColumn(3, "Uploaded", LVCFMT_RIGHT);
	m_files.InsertColumn(4, "Down rate", LVCFMT_RIGHT);
	m_files.InsertColumn(5, "Up rate", LVCFMT_RIGHT);
	m_files.InsertColumn(6, "Name");
	m_peers.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_peers.InsertColumn(0, "");
	m_peers.InsertColumn(1, "Downloaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(2, "Left", LVCFMT_RIGHT);
	m_peers.InsertColumn(3, "Uploaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(4, "Down rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(5, "Up rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(6, "");
	m_file = NULL;
	auto_size();

	return TRUE;  // return TRUE  unless you set the focus to a control
}

// If you add a minimize button to your dialog, you will need the code below
//  to draw the icon.  For MFC applications using the document/view model,
//  this is automatically done for you by the framework.

void CXBTClientDlg::OnPaint() 
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

HCURSOR CXBTClientDlg::OnQueryDragIcon()
{
	return (HCURSOR) m_hIcon;
}

void CXBTClientDlg::open(const string& name)
{
}

void CXBTClientDlg::OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_file& e = m_files_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = n(pDispInfo->item.lParam);
		break;
	case 1:
		m_buffer[m_buffer_w] = n(e.downloaded);
		break;
	case 2:
		m_buffer[m_buffer_w] = n(e.left);
		break;
	case 3:
		m_buffer[m_buffer_w] = n(e.uploaded);
		break;
	case 4:
		m_buffer[m_buffer_w] = n(e.down_rate);
		break;
	case 5:
		m_buffer[m_buffer_w] = n(e.up_rate);
		break;
	case 6:
		m_buffer[m_buffer_w] = e.name;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoPeers(NMHDR* pNMHDR, LRESULT* pResult) 
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_peer& e = m_file->peers.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = n(pDispInfo->item.lParam);
		break;
	case 1:
		m_buffer[m_buffer_w] = n(e.downloaded);
		break;
	case 2:
		m_buffer[m_buffer_w] = n(e.left);
		break;
	case 3:
		m_buffer[m_buffer_w] = n(e.uploaded);
		break;
	case 4:
		m_buffer[m_buffer_w] = n(e.down_rate);
		break;
	case 5:
		m_buffer[m_buffer_w] = n(e.up_rate);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::auto_size()
{
	for (int i = 1; i < 9; i++)
	{
		m_files.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
		m_peers.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
	}
}

void CXBTClientDlg::OnSize(UINT nType, int cx, int cy) 
{
	ETSLayoutDialog::OnSize(nType, cx, cy);
	if (m_files.GetSafeHwnd())
		auto_size();
}

void CXBTClientDlg::fill_peers()
{
	m_peers.DeleteAllItems();
	for (t_peers::const_iterator i = m_file->peers.begin(); i != m_file->peers.end(); i++)
		m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
	auto_size();
}

void CXBTClientDlg::OnItemchangedFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	if (pNMListView->uNewState & LVIS_FOCUSED && m_file != &m_files_map.find(pNMListView->lParam)->second)
	{
		m_file = &m_files_map.find(pNMListView->lParam)->second;
		fill_peers();
	}	
	*pResult = 0;
}
