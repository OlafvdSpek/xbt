// XBT ClientDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Client.h"
#include "XBT ClientDlg.h"

#include "bt_misc.h"
#include "resource.h"

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
	ON_WM_CONTEXTMENU()
	//{{AFX_MSG_MAP(CXBTClientDlg)
	ON_WM_PAINT()
	ON_WM_QUERYDRAGICON()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_FILES, OnGetdispinfoFiles)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_PEERS, OnGetdispinfoPeers)
	ON_WM_SIZE()
	ON_NOTIFY(LVN_ITEMCHANGED, IDC_FILES, OnItemchangedFiles)
	ON_WM_TIMER()
	ON_COMMAND(ID_POPUP_OPEN, OnPopupOpen)
	ON_COMMAND(ID_POPUP_CLOSE, OnPopupClose)
	ON_UPDATE_COMMAND_UI(ID_POPUP_CLOSE, OnUpdatePopupClose)
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
	m_files.InsertColumn(0, "Hash");
	m_files.InsertColumn(1, "%", LVCFMT_RIGHT);
	m_files.InsertColumn(2, "Left", LVCFMT_RIGHT);
	m_files.InsertColumn(3, "Downloaded", LVCFMT_RIGHT);
	m_files.InsertColumn(4, "Uploaded", LVCFMT_RIGHT);
	m_files.InsertColumn(5, "Down rate", LVCFMT_RIGHT);
	m_files.InsertColumn(6, "Up rate", LVCFMT_RIGHT);
	m_files.InsertColumn(7, "Leechers", LVCFMT_RIGHT);
	m_files.InsertColumn(8, "Seeders", LVCFMT_RIGHT);
	m_files.InsertColumn(9, "Name");
	m_peers.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_peers.InsertColumn(0, "Host");
	m_peers.InsertColumn(1, "Port", LVCFMT_RIGHT);
	m_peers.InsertColumn(2, "%", LVCFMT_RIGHT);
	m_peers.InsertColumn(3, "Left", LVCFMT_RIGHT);
	m_peers.InsertColumn(4, "Downloaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(5, "Uploaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(6, "Down rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(7, "Up rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(8, "D");
	m_peers.InsertColumn(9, "L");
	m_peers.InsertColumn(10, "L");
	m_peers.InsertColumn(11, "R");
	m_peers.InsertColumn(12, "R");
	m_peers.InsertColumn(13, "Peer ID");
	m_file = NULL;
	auto_size();
	SetTimer(0, 1000, NULL);

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
		m_buffer[m_buffer_w] = hex_encode(e.info_hash);
		break;
	case 1:
		m_buffer[m_buffer_w] = n((e.size - e.left) * 100 / e.size);
		break;
	case 2:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case 3:
		if (e.downloaded)
			m_buffer[m_buffer_w] = b2a(e.downloaded);
		break;
	case 4:
		if (e.uploaded)
			m_buffer[m_buffer_w] = b2a(e.uploaded);
		break;
	case 5:
		if (e.down_rate)
			m_buffer[m_buffer_w] = b2a(e.down_rate);
		break;
	case 6:
		if (e.up_rate)
			m_buffer[m_buffer_w] = b2a(e.up_rate);
		break;
	case 7:
		if (e.c_leechers)
			m_buffer[m_buffer_w] = n(e.c_leechers);
		break;
	case 8:
		if (e.c_seeders)
			m_buffer[m_buffer_w] = n(e.c_seeders);
		break;
	case 9:
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
		m_buffer[m_buffer_w] = inet_ntoa(e.host);
		break;
	case 1:
		m_buffer[m_buffer_w] = n(e.port);
		break;
	case 2:
		m_buffer[m_buffer_w] = n((m_file->size - e.left) * 100 / m_file->size);
		break;
	case 3:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case 4:
		if (e.downloaded)
			m_buffer[m_buffer_w] = b2a(e.downloaded);
		break;
	case 5:
		if (e.uploaded)
			m_buffer[m_buffer_w] = b2a(e.uploaded);
		break;
	case 6:
		if (e.down_rate)
			m_buffer[m_buffer_w] = b2a(e.down_rate);
		break;
	case 7:
		if (e.up_rate)
			m_buffer[m_buffer_w] = b2a(e.up_rate);
		break;
	case 8:
		m_buffer[m_buffer_w] = e.local_link ? 'L' : 'R';
		break;
	case 9:
		if (e.local_choked)
			m_buffer[m_buffer_w] = 'C';
		break;
	case 10:
		if (e.local_interested)
			m_buffer[m_buffer_w] = 'I';
		break;
	case 11:
		if (e.remote_choked)
			m_buffer[m_buffer_w] = 'C';
		break;
	case 12:
		if (e.remote_interested)
			m_buffer[m_buffer_w] = 'I';
		break;
	case 13:
		m_buffer[m_buffer_w] = peer_id2a(e.peer_id);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::auto_size()
{
	auto_size_files();
	auto_size_peers();
}

void CXBTClientDlg::auto_size_files()
{
	for (int i = 0; i < m_files.GetHeaderCtrl()->GetItemCount(); i++)
		m_files.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

void CXBTClientDlg::auto_size_peers()
{
	for (int i = 0; i < m_peers.GetHeaderCtrl()->GetItemCount(); i++)
		m_peers.SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
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
	auto_size_peers();
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

void CXBTClientDlg::read_server_dump(Cstream_reader& sr)
{
	if (sr.d() == sr.d_end())
		return;
	{
		for (t_files::iterator i = m_files_map.begin(); i != m_files_map.end(); i++)
			i->second.removed = true;
	}
	{
		int c_files = sr.read_int32();
		for (int i = 0; i < c_files; i++)
			read_file_dump(sr);
	}
	{
		for (t_files::iterator i = m_files_map.begin(); i != m_files_map.end(); )
		{
			if (i->second.removed)
			{
				if (m_file == &i->second)
				{
					m_peers.DeleteAllItems();
					m_file = NULL;
				}
				LV_FINDINFO fi;
				fi.flags = LVFI_PARAM;
				fi.lParam = i->first;
				m_files.DeleteItem(m_files.FindItem(&fi, -1));
				i = m_files_map.erase(i);
			}
			else
				i++;
		}
	}
}

void CXBTClientDlg::read_file_dump(Cstream_reader& sr)
{
	bool inserted = false;
	string info_hash = sr.read_string();
	for (t_files::iterator i = m_files_map.begin(); i != m_files_map.end(); i++)
	{
		if (i->second.info_hash == info_hash)
			break;
	}
	int id;
	if (i == m_files_map.end())
	{
		m_files_map[id = m_files_map.empty() ? 0 : m_files_map.rbegin()->first + 1];
		m_files.SetItemData(m_files.InsertItem(m_files.GetItemCount(), LPSTR_TEXTCALLBACK), id);
		inserted = true;
	}
	else
	{
		id = i->first;
		LV_FINDINFO fi;
		fi.flags = LVFI_PARAM;
		fi.lParam = id;
		m_files.Update(m_files.FindItem(&fi, -1));
	}
	t_file& f = m_files_map.find(id)->second;
	f.info_hash = info_hash;
	f.downloaded = sr.read_int64();
	f.left = sr.read_int64();
	f.size = sr.read_int64();
	f.uploaded = sr.read_int64();
	f.down_rate = sr.read_int32();
	f.up_rate = sr.read_int32();
	f.c_leechers = sr.read_int32();
	f.c_seeders = sr.read_int32();
	f.removed = false;
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); i++)
			i->second.removed = true;
	}
	{
		int c_peers = sr.read_int32();
		for (int i = 0; i < c_peers; i++)
			read_peer_dump(f, sr);
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); )
		{
			if (i->second.removed)
			{
				if (m_file == &f)
				{
					LV_FINDINFO fi;
					fi.flags = LVFI_PARAM;
					fi.lParam = i->first;
					m_peers.DeleteItem(m_peers.FindItem(&fi, -1));
				}
				i = f.peers.erase(i);
			}
			else
				i++;
		}
	}
	if (inserted)
		auto_size_files();
}

void CXBTClientDlg::read_peer_dump(t_file& f, Cstream_reader& sr)
{
	bool inserted = false;
	in_addr host;
	host.s_addr = htonl(sr.read_int32());
	t_peer p1;
	p1.port = sr.read_int32();
	p1.peer_id = sr.read_string();
	p1.downloaded = sr.read_int64();
	p1.left = sr.read_int64();
	p1.uploaded = sr.read_int64();
	p1.down_rate = sr.read_int32();
	p1.up_rate = sr.read_int32();
	p1.local_link = sr.read_int8();
	p1.local_choked = sr.read_int8();
	p1.local_interested = sr.read_int8();
	p1.remote_choked = sr.read_int8();
	p1.remote_interested = sr.read_int8();
	if (p1.peer_id.empty())
		return;
	for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); i++)
	{
		if (i->second.host.s_addr == host.s_addr)
			break;
	}
	int id;
	if (i == f.peers.end())
	{
		f.peers[id = f.peers.empty() ? 0 : f.peers.rbegin()->first + 1];
		if (m_file == &f)
		{
			m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), id);
			inserted = true;
		}
	}
	else
		id = i->first;
	t_peer& p0 = f.peers.find(id)->second;
	p0.host = host;
	if (p0.port != p1.port
		|| p0.peer_id != p1.peer_id
		|| p0.downloaded != p1.downloaded
		|| p0.left != p1.left
		|| p0.uploaded != p1.uploaded
		|| p0.down_rate != p1.down_rate
		|| p0.up_rate != p1.up_rate
		|| p0.local_link != p1.local_link
		|| p0.local_choked != p1.local_choked
		|| p0.local_interested != p1.local_interested
		|| p0.remote_choked != p1.remote_choked
		|| p0.remote_interested != p1.remote_interested)
	{
		p0.port = p1.port;
		p0.peer_id = p1.peer_id;
		p0.downloaded = p1.downloaded;
		p0.left = p1.left;
		p0.uploaded = p1.uploaded;
		p0.down_rate = p1.down_rate;
		p0.up_rate = p1.up_rate;
		p0.local_link = p1.local_link;
		p0.local_choked = p1.local_choked;
		p0.local_interested = p1.local_interested;
		p0.remote_choked = p1.remote_choked;
		p0.remote_interested = p1.remote_interested;
		if (m_file == &f)
		{
			LV_FINDINFO fi;
			fi.flags = LVFI_PARAM;
			fi.lParam = id;
			m_peers.Update(m_peers.FindItem(&fi, -1));
		}
	}
	p0.removed = false;
	if (inserted)
		auto_size_peers();
}

void CXBTClientDlg::OnTimer(UINT nIDEvent) 
{
	read_server_dump(Cstream_reader(m_server->get_status()));
	ETSLayoutDialog::OnTimer(nIDEvent);
}

void CXBTClientDlg::server(Cserver& server)
{
	m_server = &server;
}

void CXBTClientDlg::OnContextMenu(CWnd*, CPoint point)
{
	if (point.x == -1 && point.y == -1)
	{
		CRect rect;
		GetClientRect(rect);
		ClientToScreen(rect);

		point = rect.TopLeft();
		point.Offset(5, 5);
	}

	CMenu menu;
	VERIFY(menu.LoadMenu(CG_IDR_POPUP_XBTCLIENT_DLG));

	CMenu* pPopup = menu.GetSubMenu(0);
	ASSERT(pPopup != NULL);
	CWnd* pWndPopupOwner = this;

	while (pWndPopupOwner->GetStyle() & WS_CHILD)
		pWndPopupOwner = pWndPopupOwner->GetParent();

	pPopup->TrackPopupMenu(TPM_LEFTALIGN | TPM_RIGHTBUTTON, point.x, point.y, pWndPopupOwner);
}

void CXBTClientDlg::OnCancel() 
{
	ETSLayoutDialog::OnCancel();
}

void CXBTClientDlg::OnOK() 
{
}

void CXBTClientDlg::OnPopupOpen() 
{
	CFileDialog dlg(true, "torrent", NULL, OFN_HIDEREADONLY | OFN_FILEMUSTEXIST, "Torrents|*.torrent|", this);
	if (IDOK != dlg.DoModal())
		return;
	Cvirtual_binary d(static_cast<string>(dlg.GetPathName()));
	{
		CFileDialog dlg(false, NULL, NULL, OFN_HIDEREADONLY | OFN_PATHMUSTEXIST, "All files|*|", this);
		if (IDOK != dlg.DoModal())
			return;
		CWaitCursor wc;
		m_server->open(d, static_cast<string>(dlg.GetPathName()));
	}
}

void CXBTClientDlg::OnPopupClose() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
		m_server->close(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnUpdatePopupClose(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_FOCUSED) != -1);
}
