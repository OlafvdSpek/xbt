// XBT ClientDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT Client.h"
#include "XBT ClientDlg.h"

#include "bt_misc.h"
#include "bt_torrent.h"
#include "dlg_files.h"
#include "dlg_options.h"
#include "dlg_torrent.h"
#include "dlg_trackers.h"
#include "resource.h"

#define for if (0) {} else for

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

const static UINT g_tray_message_id = RegisterWindowMessage("XBT Client Tray Message");

enum
{
	fc_hash,
	fc_done,
	fc_left,
	fc_total_downloaded,
	fc_total_uploaded,
	fc_down_rate,
	fc_up_rate,
	fc_leechers,
	fc_seeders,
	fc_state,
	fc_name,
};

enum
{
	pc_host,
	pc_port,
	pc_done,
	pc_left,
	pc_downloaded,
	pc_uploaded,
	pc_down_rate,
	pc_up_rate,
	pc_link_direction,
	pc_local_choked,
	pc_local_interested,
	pc_remote_choked,
	pc_remote_interested,
	pc_peer_id,
};

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg dialog

CXBTClientDlg::CXBTClientDlg(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(CXBTClientDlg::IDD, pParent, "CXBTClientDlg")
{
	//{{AFX_DATA_INIT(CXBTClientDlg)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
	m_hIcon = AfxGetApp()->LoadIcon(IDR_MAINFRAME);

	m_reg_key = "Options";
	m_initial_hide = false;
	m_server_thread = NULL;
	char path[MAX_PATH];
	if (SUCCEEDED(SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true)))
	{
		strcat(path, "\\XBT");
		m_dir = path;
		m_completes_dir = path;
		m_completes_dir += "\\Completes";
		m_incompletes_dir = path;
		m_incompletes_dir += "\\Incompletes";
		m_torrents_dir = path;
		m_torrents_dir += "\\Torrents";
		CreateDirectory(path, NULL);
	}
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
	ON_COMMAND(ID_POPUP_OPTIONS, OnPopupOptions)
	ON_WM_DROPFILES()
	ON_COMMAND(ID_POPUP_EXIT, OnPopupExit)
	ON_COMMAND(ID_POPUP_EXPLORE, OnPopupExplore)
	ON_WM_DESTROY()
	ON_COMMAND(ID_POPUP_START, OnPopupStart)
	ON_COMMAND(ID_POPUP_STOP, OnPopupStop)
	ON_WM_WINDOWPOSCHANGING()
	ON_WM_ENDSESSION()
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_FILES, OnColumnclickFiles)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_PEERS, OnColumnclickPeers)
	ON_NOTIFY(NM_DBLCLK, IDC_FILES, OnDblclkFiles)
	ON_COMMAND(ID_POPUP_COPY, OnPopupCopy)
	ON_COMMAND(ID_POPUP_PASTE, OnPopupPaste)
	ON_COMMAND(ID_POPUP_FILES, OnPopupFiles)
	ON_COMMAND(ID_POPUP_TRACKERS, OnPopupTrackers)
	ON_COMMAND(ID_POPUP_ANNOUNCE, OnPopupAnnounce)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg message handlers

BOOL CXBTClientDlg::OnInitDialog()
{
	SetIcon(m_hIcon, TRUE);			// Set big icon
	SetIcon(m_hIcon, FALSE);		// Set small icon
	
	CreateRoot(VERTICAL)
		<< item (IDC_FILES, GREEDY)
		<< item (IDC_PEERS, GREEDY)
		;
	ETSLayoutDialog::OnInitDialog();

	m_server.admin_port(AfxGetApp()->GetProfileInt(m_reg_key, "admin_port", m_server.admin_port()));
	m_server.dir(static_cast<string>(m_dir));
	m_server.peer_port(AfxGetApp()->GetProfileInt(m_reg_key, "peer_port", m_server.peer_port()));
	string public_ipa = AfxGetApp()->GetProfileString(m_reg_key, "public_ipa", "");
	if (!public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(public_ipa));
	m_server.seeding_ratio(AfxGetApp()->GetProfileInt(m_reg_key, "seeding_ratio", m_server.seeding_ratio()));
	m_server.upload_rate(AfxGetApp()->GetProfileInt(m_reg_key, "upload_rate", m_server.upload_rate()));
	m_server.upload_slots(AfxGetApp()->GetProfileInt(m_reg_key, "upload_slots", m_server.upload_slots()));	
	start_server();
	CCommandLineInfo cmdInfo;
	AfxGetApp()->ParseCommandLine(cmdInfo);
	if (cmdInfo.m_nShellCommand == CCommandLineInfo::FileOpen)
		open(static_cast<string>(cmdInfo.m_strFileName));
	m_files.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_files.InsertColumn(fc_hash, "Hash");
	m_files.InsertColumn(fc_done, "%", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_left, "Left", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_total_downloaded, "Downloaded", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_total_uploaded, "Uploaded", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_down_rate, "Down rate", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_up_rate, "Up rate", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_leechers, "Leechers", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_seeders, "Seeders", LVCFMT_RIGHT);
	m_files.InsertColumn(fc_state, "State");
	m_files.InsertColumn(fc_name, "Name");
	m_peers.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_peers.InsertColumn(pc_host, "Host");
	m_peers.InsertColumn(pc_port, "Port", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_done, "%", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_left, "Left", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_downloaded, "Downloaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_uploaded, "Uploaded", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_down_rate, "Down rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_up_rate, "Up rate", LVCFMT_RIGHT);
	m_peers.InsertColumn(pc_link_direction, "D");
	m_peers.InsertColumn(pc_local_choked, "L");
	m_peers.InsertColumn(pc_local_interested, "L");
	m_peers.InsertColumn(pc_remote_choked, "R");
	m_peers.InsertColumn(pc_remote_interested, "R");
	m_peers.InsertColumn(pc_peer_id, "Peer ID");
	m_files_sort_column = -1;
	m_peers_sort_column = -1;
	m_file = NULL;
	auto_size();
	register_tray();
	SetTimer(0, 1000, NULL);
	SetTimer(1, 60000, NULL);
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
	m_initial_hide = false;
	Cvirtual_binary d(name);
	Cbt_torrent torrent(d);
	if (!torrent.valid())
		return;
	char path[MAX_PATH];
	strcpy(path, m_torrents_dir);
	if (*path)
	{
		CreateDirectory(m_torrents_dir, NULL);
		strcat(path, "\\");
		strcat(path, torrent.name().c_str());
		strcat(path, ".torrent");
		d.save(path);
	}
	strcpy(path, m_incompletes_dir);
	if (*path)
	{
		strcat(path, "\\");
		strcat(path, torrent.name().c_str());
	}
	else
	{
		CFileDialog dlg(false, NULL, torrent.name().c_str(), OFN_HIDEREADONLY | OFN_PATHMUSTEXIST, "All files|*|", this);
		if (path)
			dlg.m_ofn.lpstrInitialDir = path;
		if (IDOK != dlg.DoModal())
			return;
		strcpy(path, dlg.GetPathName());
	}
	CWaitCursor wc;
	m_server.open(d, path);
}

void CXBTClientDlg::open_url(const string& v)
{
	m_server.open_url(v);
}

void CXBTClientDlg::OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_file& e = m_files_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case fc_hash:
		m_buffer[m_buffer_w] = hex_encode(e.info_hash);
		break;
	case fc_done:
		if (e.size)
			m_buffer[m_buffer_w] = n((e.size - e.left) * 100 / e.size);
		break;
	case fc_left:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case fc_total_downloaded:
		if (e.total_downloaded)
			m_buffer[m_buffer_w] = b2a(e.total_downloaded);
		break;
	case fc_total_uploaded:
		if (e.total_uploaded)
			m_buffer[m_buffer_w] = b2a(e.total_uploaded);
		break;
	case fc_down_rate:
		if (e.down_rate)
			m_buffer[m_buffer_w] = b2a(e.down_rate);
		break;
	case fc_up_rate:
		if (e.up_rate)
			m_buffer[m_buffer_w] = b2a(e.up_rate);
		break;
	case fc_leechers:
		if (e.c_leechers || e.c_leechers_total)
			m_buffer[m_buffer_w] = n(e.c_leechers);
		if (e.c_leechers_total)
			m_buffer[m_buffer_w] += " / " + n(e.c_leechers_total);
		break;
	case fc_seeders:
		if (e.c_seeders || e.c_seeders_total)
			m_buffer[m_buffer_w] = n(e.c_seeders);
		if (e.c_seeders_total)
			m_buffer[m_buffer_w] += " / " + n(e.c_seeders_total);
		break;
	case fc_state:
		if (e.run)
			m_buffer[m_buffer_w] = 'R';
		break;
	case fc_name:
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
	case pc_host:
		m_buffer[m_buffer_w] = inet_ntoa(e.host);
		break;
	case pc_port:
		m_buffer[m_buffer_w] = n(ntohs(e.port));
		break;
	case pc_done:
		if (m_file->size)
			m_buffer[m_buffer_w] = n((m_file->size - e.left) * 100 / m_file->size);
		break;
	case pc_left:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case pc_downloaded:
		if (e.downloaded)
			m_buffer[m_buffer_w] = b2a(e.downloaded);
		break;
	case pc_uploaded:
		if (e.uploaded)
			m_buffer[m_buffer_w] = b2a(e.uploaded);
		break;
	case pc_down_rate:
		if (e.down_rate)
			m_buffer[m_buffer_w] = b2a(e.down_rate);
		break;
	case pc_up_rate:
		if (e.up_rate)
			m_buffer[m_buffer_w] = b2a(e.up_rate);
		break;
	case pc_link_direction:
		m_buffer[m_buffer_w] = e.local_link ? 'L' : 'R';
		break;
	case pc_local_choked:
		if (e.local_choked)
			m_buffer[m_buffer_w] = 'C';
		break;
	case pc_local_interested:
		if (e.local_interested)
			m_buffer[m_buffer_w] = 'I';
		break;
	case pc_remote_choked:
		if (e.remote_choked)
			m_buffer[m_buffer_w] = 'C';
		break;
	case pc_remote_interested:
		if (e.remote_interested)
			m_buffer[m_buffer_w] = 'I';
		break;
	case pc_peer_id:
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
	if (nType == SIZE_MINIMIZED)
		ShowWindow(SW_HIDE);
	else if (m_files.GetSafeHwnd())
		auto_size();
}

void CXBTClientDlg::fill_peers()
{
	m_peers.DeleteAllItems();
	for (t_peers::const_iterator i = m_file->peers.begin(); i != m_file->peers.end(); i++)
		m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
	sort_peers();
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
	t_files::iterator i;
	for (i = m_files_map.begin(); i != m_files_map.end(); i++)
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
		id = i->first;
	t_file& f = m_files_map.find(id)->second;
	f.info_hash = info_hash;
	f.name = sr.read_string();
	f.downloaded = sr.read_int64();
	f.left = sr.read_int64();
	f.size = sr.read_int64();
	f.uploaded = sr.read_int64();
	f.total_downloaded = sr.read_int64();
	f.total_uploaded = sr.read_int64();
	f.down_rate = sr.read_int32();
	f.up_rate = sr.read_int32();
	f.c_leechers = sr.read_int32();
	f.c_seeders = sr.read_int32();
	f.c_leechers_total = sr.read_int32();
	f.c_seeders_total = sr.read_int32();
	f.run = sr.read_int32();
	f.removed = false;
	{
		int i = f.name.rfind('\\');
		if (i != string::npos)
			f.name.erase(0, i + 1);
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); i++)
			i->second.removed = true;
	}
	{
		int c_peers = sr.read_int32();
		while (c_peers--)
			read_peer_dump(f, sr);
	}
	sr.read_int32();
	sr.read_int32();
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
	if (!m_file)
	{
		m_file = &f;
		fill_peers();
	}
}

void CXBTClientDlg::read_peer_dump(t_file& f, Cstream_reader& sr)
{
	bool inserted = false;
	t_peer p;
	p.host.s_addr = htonl(sr.read_int32());
	p.port = htons(sr.read_int32());
	p.peer_id = sr.read_string();
	p.downloaded = sr.read_int64();
	p.left = sr.read_int64();
	p.uploaded = sr.read_int64();
	p.down_rate = sr.read_int32();
	p.up_rate = sr.read_int32();
	p.local_link = sr.read_int8();
	p.local_choked = sr.read_int8();
	p.local_interested = sr.read_int8();
	p.remote_choked = sr.read_int8();
	p.remote_interested = sr.read_int8();
	if (p.peer_id.empty())
		return;
	t_peers::iterator i;
	for (i = f.peers.begin(); i != f.peers.end(); i++)
	{
		if (i->second.host.s_addr == p.host.s_addr)
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
	p.removed = false;
	f.peers.find(id)->second = p;
	if (inserted)
		auto_size_peers();
}

void CXBTClientDlg::OnTimer(UINT nIDEvent) 
{
	switch (nIDEvent)
	{
	case 0:
		if (!IsWindowVisible())
			break;
		m_files.SetRedraw(false);
		m_peers.SetRedraw(false);
		read_server_dump(Cstream_reader(m_server.get_status(Cserver::df_peers)));
		sort_files();
		sort_peers();
		m_files.SetRedraw(true);
		m_peers.SetRedraw(true);
		m_files.Invalidate();
		m_peers.Invalidate();
		update_tray();
		break;
	case 1:
		read_server_dump(Cstream_reader(m_server.get_status(0)));
		update_tray();
		break;
	}
	ETSLayoutDialog::OnTimer(nIDEvent);
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

void CXBTClientDlg::OnPopupExplore() 
{
	ShellExecute(m_hWnd, "open", m_dir, NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnPopupAnnounce() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
		m_server.announce(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnPopupStart() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.start_file(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnPopupStop() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.stop_file(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnPopupCopy() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	string v = m_server.get_url(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
	if (v.empty())
		return;
	void* h = GlobalAlloc(GMEM_MOVEABLE | GMEM_DDESHARE, v.size() + 1);
	void* p = GlobalLock(h);
	if (!p)
		return;
	memcpy(p, v.c_str(), v.size() + 1);
	GlobalUnlock(h);
	if (!OpenClipboard())
		return;
	if (EmptyClipboard())
		SetClipboardData(CF_TEXT, h);
	CloseClipboard();	
}

void CXBTClientDlg::OnPopupPaste() 
{
	if (!OpenClipboard())
		return;
	void* h = GetClipboardData(CF_TEXT);
	void* p = GlobalLock(h);
	if (p)
		open_url(reinterpret_cast<char*>(p));
	CloseClipboard();	
}

void CXBTClientDlg::OnPopupOpen() 
{
	CFileDialog dlg(true, "torrent", NULL, OFN_HIDEREADONLY | OFN_FILEMUSTEXIST, "Torrents|*.torrent|", this);
	if (IDOK == dlg.DoModal())
		open(static_cast<string>(dlg.GetPathName()));
}

void CXBTClientDlg::OnPopupClose() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.close(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnUpdatePopupClose(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_FOCUSED) != -1);
}

void CXBTClientDlg::OnPopupFiles() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	Cdlg_files dlg(this, m_server, m_files_map.find(m_files.GetItemData(index))->second.info_hash);
	dlg.DoModal();
}

void CXBTClientDlg::OnPopupOptions() 
{
	Cdlg_options dlg(this);
	Cdlg_options::t_data data;
	data.admin_port = AfxGetApp()->GetProfileInt(m_reg_key, "admin_port", m_server.admin_port());
	data.peer_port = AfxGetApp()->GetProfileInt(m_reg_key, "peer_port", m_server.peer_port());
	data.public_ipa = AfxGetApp()->GetProfileString(m_reg_key, "public_ipa", "");
	data.seeding_ratio = AfxGetApp()->GetProfileInt(m_reg_key, "seeding_ratio", m_server.seeding_ratio());
	data.upload_rate = AfxGetApp()->GetProfileInt(m_reg_key, "upload_rate", m_server.upload_rate());
	data.upload_slots = AfxGetApp()->GetProfileInt(m_reg_key, "upload_slots", m_server.upload_slots());
	dlg.set(data);
	if (IDOK != dlg.DoModal())
		return;
	data = dlg.get();
	m_server.admin_port(data.admin_port);
	m_server.peer_port(data.peer_port);
	if (!data.public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(data.public_ipa));
	m_server.seeding_ratio(data.seeding_ratio);
	m_server.upload_rate(data.upload_rate);
	m_server.upload_slots(data.upload_slots);
	AfxGetApp()->WriteProfileInt(m_reg_key, "admin_port", data.admin_port);
	AfxGetApp()->WriteProfileInt(m_reg_key, "peer_port", data.peer_port);
	AfxGetApp()->WriteProfileString(m_reg_key, "public_ipa", data.public_ipa.c_str());
	AfxGetApp()->WriteProfileInt(m_reg_key, "seeding_ratio", data.seeding_ratio);
	AfxGetApp()->WriteProfileInt(m_reg_key, "upload_rate", data.upload_rate);
	AfxGetApp()->WriteProfileInt(m_reg_key, "upload_slots", data.upload_slots);
}

void CXBTClientDlg::OnPopupTrackers() 
{
	Cdlg_trackers dlg(this);
	Cstream_reader r(m_server.get_trackers());
	for (int count = r.read_int32(); count--; )
	{
		Cdlg_trackers::t_tracker e;
		e.m_tracker = r.read_string();
		e.m_user = r.read_string();
		e.m_pass = r.read_string();
		dlg.insert(e);
	}
	if (IDOK != dlg.DoModal())
		return;
	int cb_d = 4;
	for (Cdlg_trackers::t_trackers::const_iterator i = dlg.trackers().begin(); i != dlg.trackers().end(); i++)
		cb_d += i->second.m_tracker.size() + i->second.m_user.size() + i->second.m_pass.size() + 12;
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(cb_d));
	w.write_int32(dlg.trackers().size());
	for (Cdlg_trackers::t_trackers::const_iterator i = dlg.trackers().begin(); i != dlg.trackers().end(); i++)
	{
		w.write_string(i->second.m_tracker);
		w.write_string(i->second.m_user);
		w.write_string(i->second.m_pass);
	}
	m_server.set_trackers(d);
}

void CXBTClientDlg::OnPopupExit() 
{
	EndDialog(IDCANCEL);
}

void CXBTClientDlg::OnDblclkFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	Cdlg_torrent dlg(this, m_server, m_files_map.find(m_files.GetItemData(index))->second.info_hash);
	dlg.DoModal();
	*pResult = 0;
}

void CXBTClientDlg::OnDropFiles(HDROP hDropInfo) 
{
	int c_files = DragQueryFile(hDropInfo, 0xFFFFFFFF, NULL, 0);
	
	for (int i = 0; i < c_files; i++)
	{
		char name[MAX_PATH];
		DragQueryFile(hDropInfo, i, name, MAX_PATH);
		open(name);
	}
	ETSLayoutDialog::OnDropFiles(hDropInfo);
}

BOOL CXBTClientDlg::PreTranslateMessage(MSG* pMsg) 
{
	if (pMsg->message == WM_KEYDOWN)
	{
		switch (pMsg->wParam)
		{
		case VK_CANCEL:
		case VK_ESCAPE:
			ShowWindow(SW_HIDE);
		case VK_RETURN:
			return true;
		}
	}
	return ETSLayoutDialog::PreTranslateMessage(pMsg);
}

void CXBTClientDlg::OnDestroy() 
{
	stop_server();
	unregister_tray();
	ETSLayoutDialog::OnDestroy();
}

void CXBTClientDlg::register_tray()
{
	NOTIFYICONDATA nid;
	nid.cbSize = sizeof(NOTIFYICONDATA);
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = NIF_ICON | NIF_MESSAGE;
	nid.uCallbackMessage = g_tray_message_id;
	nid.hIcon = AfxGetApp()->LoadIcon(IDR_MAINFRAME);
	Shell_NotifyIcon(NIM_ADD, &nid);
}

void CXBTClientDlg::unregister_tray()
{
	NOTIFYICONDATA nid;
	nid.cbSize = sizeof(NOTIFYICONDATA);
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = 0;
	Shell_NotifyIcon(NIM_DELETE, &nid);
}

void CXBTClientDlg::update_tray()
{
	__int64 left = 0;
	__int64 size = 0;
	int leechers = 0;
	int seeders = 0;
	for (t_files::const_iterator i = m_files_map.begin(); i != m_files_map.end(); i++)
	{
		left += i->second.left;
		size += i->second.size;
		leechers += i->second.c_leechers;
		seeders += i->second.c_seeders;
	}
	NOTIFYICONDATA nid;
	nid.cbSize = sizeof(NOTIFYICONDATA);
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = NIF_TIP;
	if (size)
		sprintf(nid.szTip, "XBT Client - %d %%, %s left, %d leechers, %d seeders", static_cast<int>((size - left) * 100 / size), b2a(left).c_str(), leechers, seeders);
	else
		strcpy(nid.szTip, "XBT Client");
	Shell_NotifyIcon(NIM_MODIFY, &nid);
}

LRESULT CXBTClientDlg::WindowProc(UINT message, WPARAM wParam, LPARAM lParam) 
{
	switch (message)
	{
	case WM_COPYDATA:
		{
			const COPYDATASTRUCT& cds = *reinterpret_cast<COPYDATASTRUCT*>(lParam);
			switch (cds.dwData)
			{
			case 0:
				open(string(reinterpret_cast<const char*>(cds.lpData), cds.cbData));
				return true;
			}				
		}
		break;
	default:
		if (message == g_tray_message_id)
		{	
			switch (lParam)
			{
			case WM_LBUTTONDBLCLK:
				m_initial_hide = false;
				ShowWindow(IsWindowVisible() ? SW_HIDE : SW_SHOWMAXIMIZED);
				if (IsWindowVisible())
					SetForegroundWindow();
				return 0;
			}
		}
	}
	return ETSLayoutDialog::WindowProc(message, wParam, lParam);
}

void CXBTClientDlg::OnWindowPosChanging(WINDOWPOS FAR* lpwndpos) 
{
	if (m_initial_hide)
		lpwndpos->flags &= ~SWP_SHOWWINDOW;
	ETSLayoutDialog::OnWindowPosChanging(lpwndpos);
}

void CXBTClientDlg::OnEndSession(BOOL bEnding) 
{
	stop_server();
	ETSLayoutDialog::OnEndSession(bEnding);
}

unsigned int CXBTClientDlg::server_thread(void* p)
{
	reinterpret_cast<CXBTClientDlg*>(p)->m_server.run();
	return 0;
}

void CXBTClientDlg::start_server()
{
	if (m_server_thread)
		return;
	m_server_thread = AfxBeginThread(server_thread, this);
	m_server_thread->m_bAutoDelete = false;
}

void CXBTClientDlg::stop_server()
{
	m_server.stop();
	if (m_server_thread)
		WaitForSingleObject(m_server_thread->m_hThread, INFINITE);	
	delete m_server_thread;
	m_server_thread = NULL;
}

template <class T>
static int compare(const T& a, const T& b)
{
	return a < b ? -1 : a != b;
}

int CXBTClientDlg::files_compare(int id_a, int id_b) const
{
	if (m_files_sort_reverse)
		swap(id_a, id_b);
	const t_file& a = m_files_map.find(id_a)->second;
	const t_file& b = m_files_map.find(id_b)->second;
	switch (m_files_sort_column)
	{
	case fc_hash:
		return compare(a.info_hash, b.info_hash);
	case fc_done:
		return compare(b.left * 1000 / b.size, a.left * 1000 / a.size);
	case fc_left:
		return compare(a.left, b.left);
	case fc_total_downloaded:
		return compare(b.total_downloaded, a.total_downloaded);
	case fc_total_uploaded:
		return compare(b.total_uploaded, a.total_uploaded);
	case fc_down_rate:
		return compare(b.down_rate, a.down_rate);
	case fc_up_rate:
		return compare(b.up_rate, a.up_rate);
	case fc_leechers:
		return compare(a.c_leechers, b.c_leechers);
	case fc_seeders:
		return compare(a.c_seeders, b.c_seeders);
	case fc_state:
		return compare(a.run, b.run);
	case fc_name:
		return compare(a.name, b.name);
	}
	return 0;
}

static int CALLBACK files_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->files_compare(lParam1, lParam2);
}

void CXBTClientDlg::OnColumnclickFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	m_files_sort_reverse = pNMListView->iSubItem == m_files_sort_column && !m_files_sort_reverse;
	m_files_sort_column = pNMListView->iSubItem;
	sort_files();
	*pResult = 0;
}

int CXBTClientDlg::peers_compare(int id_a, int id_b) const
{
	if (!m_file)
		return 0;
	if (m_peers_sort_reverse)
		swap(id_a, id_b);
	const t_peer& a = m_file->peers.find(id_a)->second;
	const t_peer& b = m_file->peers.find(id_b)->second;
	switch (m_peers_sort_column)
	{
	case pc_host:
		return compare(ntohl(a.host.s_addr), ntohl(b.host.s_addr));
	case pc_port:
		return compare(ntohs(a.port), ntohs(b.port));
	case pc_done:
		return compare(b.left, a.left);
	case pc_left:
		return compare(a.left, b.left);
	case pc_downloaded:
		return compare(b.downloaded, a.downloaded);
	case pc_uploaded:
		return compare(b.uploaded, a.uploaded);
	case pc_down_rate:
		return compare(b.down_rate, a.down_rate);
	case pc_up_rate:
		return compare(b.up_rate, a.up_rate);
	case pc_link_direction:
		return compare(a.local_link, b.local_link);
	case pc_local_choked:
		return compare(a.local_choked, b.local_choked);
	case pc_local_interested:
		return compare(a.local_interested, b.local_interested);
	case pc_remote_choked:
		return compare(a.remote_choked, b.remote_choked);
	case pc_remote_interested:
		return compare(a.remote_interested, b.remote_interested);
	case pc_peer_id:
		return compare(a.peer_id, b.peer_id);
	}
	return 0;
}

static int CALLBACK peers_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->peers_compare(lParam1, lParam2);
}

void CXBTClientDlg::OnColumnclickPeers(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	m_peers_sort_reverse = pNMListView->iSubItem == m_peers_sort_column && !m_peers_sort_reverse;
	m_peers_sort_column = pNMListView->iSubItem;
	sort_peers();	
	*pResult = 0;
}

void CXBTClientDlg::sort_files()
{
	m_files.SortItems(::files_compare, reinterpret_cast<DWORD>(this));	
}

void CXBTClientDlg::sort_peers()
{
	m_peers.SortItems(::peers_compare, reinterpret_cast<DWORD>(this));
}

