// XBT ClientDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT ClientDlg.h"

#include <sys/stat.h>
#include "bt_misc.h"
#include "bt_torrent.h"
#include "dlg_about.h"
#include "dlg_files.h"
#include "dlg_make_torrent.h"
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
	fc_name,
	fc_done,
	fc_left,
	fc_size,
	fc_total_downloaded,
	fc_total_uploaded,
	fc_down_rate,
	fc_up_rate,
	fc_leechers,
	fc_seeders,
	fc_peers,
	fc_state,
	fc_hash,
};

enum
{
	dc_name,
	dc_value,

	ec_time,
	ec_level,
	ec_source,
	ec_message,

	pc_peer_id,
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
	pc_host,
	pc_port,
	pc_client,
	pc_end,

	sfc_name,
	sfc_done,
	sfc_left,
	sfc_size,
	sfc_priority,
	sfc_hash,

	tc_url,
};

enum
{
	v_details,
	v_events,
	v_files,
	v_peers,
	v_trackers,
};

enum
{
	dr_completed_at,
	dr_downloaded,
	dr_hash,
	dr_leechers,
	dr_left,
	dr_name,
	dr_peers,
	dr_pieces,
	dr_seeders,
	dr_size,
	dr_started_at,
	dr_tracker,
	dr_uploaded,
	dr_count
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
#ifdef _DEBUG
	m_initial_hide = false;
#else
	m_initial_hide = AfxGetApp()->GetProfileInt(m_reg_key, "start_minimized", false);
#endif
	m_server_thread = NULL;
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
	ON_MESSAGE(WM_HOTKEY, OnHotKey)
	ON_NOTIFY(NM_CUSTOMDRAW, IDC_FILES, OnCustomdrawFiles)
	ON_NOTIFY(NM_CUSTOMDRAW, IDC_PEERS, OnCustomdrawPeers)
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
	ON_COMMAND(ID_POPUP_EXPLORE_TRACKER, OnPopupExploreTracker)
	ON_COMMAND(ID_POPUP_ABOUT, OnPopupAbout)
	ON_COMMAND(ID_POPUP_MAKE_TORRENT, OnPopupMakeTorrent)
	ON_COMMAND(ID_POPUP_TORRENT_DELETE, OnPopupTorrentDelete)
	ON_COMMAND(ID_POPUP_TORRENT_CLIPBOARD_COPY_ANNOUNCE_URL, OnPopupTorrentClipboardCopyAnnounceUrl)
	ON_COMMAND(ID_POPUP_TORRENT_CLIPBOARD_COPY_HASH, OnPopupTorrentClipboardCopyHash)
	ON_COMMAND(ID_POPUP_TORRENT_ALERTS, OnPopupTorrentAlerts)
	ON_COMMAND(ID_POPUP_VIEW_DETAILS, OnPopupViewDetails)
	ON_COMMAND(ID_POPUP_VIEW_FILES, OnPopupViewFiles)
	ON_COMMAND(ID_POPUP_VIEW_PEERS, OnPopupViewPeers)
	ON_COMMAND(ID_POPUP_VIEW_TRACKERS, OnPopupViewTrackers)
	ON_COMMAND(ID_POPUP_VIEW_EVENTS, OnPopupViewEvents)
	ON_COMMAND(ID_POPUP_PRIORITY_EXCLUDE, OnPopupPriorityExclude)
	ON_COMMAND(ID_POPUP_PRIORITY_HIGH, OnPopupPriorityHigh)
	ON_COMMAND(ID_POPUP_PRIORITY_LOW, OnPopupPriorityLow)
	ON_COMMAND(ID_POPUP_PRIORITY_NORMAL, OnPopupPriorityNormal)
	ON_COMMAND(ID_POPUP_VIEW_ADVANCED_COLUMNS, OnPopupViewAdvancedColumns)
	ON_COMMAND(ID_POPUP_VIEW_TRAY_ICON, OnPopupViewTrayIcon)
	ON_NOTIFY(NM_DBLCLK, IDC_PEERS, OnDblclkPeers)
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

	m_bottom_view = v_peers;
	m_server.admin_port(AfxGetApp()->GetProfileInt(m_reg_key, "admin_port", m_server.admin_port()));
	m_ask_for_location = AfxGetApp()->GetProfileInt(m_reg_key, "ask_for_location", false);
	m_server.bind_before_connect(AfxGetApp()->GetProfileInt(m_reg_key, "bind_before_connect", false));
	set_dir(static_cast<string>(AfxGetApp()->GetProfileString(m_reg_key, "files_location")));
	m_server.dir(static_cast<string>(m_dir));
	lower_process_priority(AfxGetApp()->GetProfileInt(m_reg_key, "lower_process_priority", true));
	m_server.peer_limit(AfxGetApp()->GetProfileInt(m_reg_key, "peer_limit", m_server.peer_limit()));
	m_server.peer_port(AfxGetApp()->GetProfileInt(m_reg_key, "peer_port", m_server.peer_port()));
	string public_ipa = AfxGetApp()->GetProfileString(m_reg_key, "public_ipa", "");
	if (!public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(public_ipa));
	m_server.seeding_ratio(AfxGetApp()->GetProfileInt(m_reg_key, "seeding_ratio", m_server.seeding_ratio()));
	m_show_advanced_columns = AfxGetApp()->GetProfileInt(m_reg_key, "show_advanced_columns", false);
	m_show_tray_icon = AfxGetApp()->GetProfileInt(m_reg_key, "show_tray_icon", true);
	m_server.tracker_port(AfxGetApp()->GetProfileInt(m_reg_key, "tracker_port", m_server.tracker_port()));
	m_server.upload_rate(AfxGetApp()->GetProfileInt(m_reg_key, "upload_rate", m_server.upload_rate()));
	m_server.upload_slots(AfxGetApp()->GetProfileInt(m_reg_key, "upload_slots", m_server.upload_slots()));	
	start_server();
	m_files.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	m_peers.SetExtendedStyle(m_files.GetExtendedStyle() | LVS_EX_FULLROWSELECT);
	insert_columns();
	m_files_sort_column = fc_name;
	m_files_sort_reverse = false;
	m_peers_sort_column = pc_client;
	m_peers_sort_reverse = false;
	m_file = NULL;
	auto_size();
	register_tray();
	RegisterHotKey(GetSafeHwnd(), 0, MOD_CONTROL | MOD_SHIFT, 'Q');
	SetTimer(0, 1000, NULL);
	SetTimer(1, 60000, NULL);
	CCommandLineInfo cmdInfo;
	AfxGetApp()->ParseCommandLine(cmdInfo);
	if (cmdInfo.m_nShellCommand == CCommandLineInfo::FileOpen)
		open(static_cast<string>(cmdInfo.m_strFileName), m_ask_for_location);
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

void CXBTClientDlg::open(const string& name, bool ask_for_location)
{
	Cvirtual_binary d(name);
	Cbt_torrent torrent(d);
	if (!torrent.valid())
		return;
	if (!m_dir.IsEmpty())
	{
		string path = m_dir + "\\Torrents";
		CreateDirectory(path.c_str(), NULL);
		d.save(path + "\\" + torrent.name() + ".torrent");
	}
	string path;
	if (!m_dir.IsEmpty() && !ask_for_location && ~GetAsyncKeyState(VK_SHIFT) < 0)
	{
		path = m_dir + "\\Completes\\" + torrent.name().c_str();
		struct _stati64 b;
		if (_stati64(path.c_str(), &b))
			path = m_dir + "\\Incompletes\\" + torrent.name().c_str();
	}
	else if (torrent.files().size() == 1)
	{
		SetForegroundWindow();
		CFileDialog dlg(false, NULL, torrent.name().c_str(), OFN_HIDEREADONLY | OFN_PATHMUSTEXIST, "All files|*|", this);
		if (!m_dir.IsEmpty())
			dlg.m_ofn.lpstrInitialDir = m_dir;
		if (IDOK != dlg.DoModal())
			return;
		path = dlg.GetPathName();
	}
	else
	{
		SetForegroundWindow();
		BROWSEINFO bi;
		ZeroMemory(&bi, sizeof(BROWSEINFO));
		bi.hwndOwner = GetSafeHwnd();
		bi.lpszTitle = torrent.name().c_str();
		bi.ulFlags = BIF_RETURNONLYFSDIRS | BIF_USENEWUI;
		ITEMIDLIST* idl = SHBrowseForFolder(&bi);
		if (!idl)
			return;
		char path1[MAX_PATH];
		if (!SHGetPathFromIDList(idl, path1))
			*path1 = 0;
		LPMALLOC lpm;
		if (SHGetMalloc(&lpm) == NOERROR)
			lpm->Free(idl);
		if (!*path1)
			return;
		path = static_cast<string>(path1) + "\\" + torrent.name();
	}
	CWaitCursor wc;
	if (!m_server.open(d, path))
		update_tray("Opened", torrent.name().c_str());
}

void CXBTClientDlg::open_url(const string& v)
{
	m_server.open_url(v);
}

void CXBTClientDlg::OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const t_file& e = m_files_map.find(pDispInfo->item.lParam)->second;
	switch (m_torrents_columns[pDispInfo->item.iSubItem])
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
	case fc_size:
		if (e.size)
			m_buffer[m_buffer_w] = b2a(e.size);
		break;
	case fc_total_downloaded:
		if (e.total_downloaded)
		{
			m_buffer[m_buffer_w] = b2a(e.total_downloaded);
			if (e.size)
				m_buffer[m_buffer_w] += " (" + n(e.total_downloaded * 100 / e.size) + " %)";
		}
		break;
	case fc_total_uploaded:
		if (e.total_uploaded)
		{
			m_buffer[m_buffer_w] = b2a(e.total_uploaded);
			if (e.size)
				m_buffer[m_buffer_w] += " (" + n(e.total_uploaded * 100 / e.size) + " %)";
		}
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
	case fc_peers:
		if (e.c_leechers || e.c_leechers_total || e.c_seeders || e.c_seeders_total)
			m_buffer[m_buffer_w] = n(e.c_leechers + e.c_seeders);
		if (e.c_leechers_total || e.c_seeders_total)
			m_buffer[m_buffer_w] += " / " + n(e.c_leechers_total + e.c_seeders_total);
		break;
	case fc_seeders:
		if (e.c_seeders || e.c_seeders_total)
			m_buffer[m_buffer_w] = n(e.c_seeders);
		if (e.c_seeders_total)
			m_buffer[m_buffer_w] += " / " + n(e.c_seeders_total);
		break;
	case fc_state:
		if (e.hashing)
			m_buffer[m_buffer_w] = 'H';
		else if (e.running)
			m_buffer[m_buffer_w] = 'R';
		break;
	case fc_name:
		m_buffer[m_buffer_w] = e.display_name;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoDetails(NMHDR* pNMHDR, LRESULT* pResult) 
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const char* row_names[] =
	{
		"Completed at",
		"Downloaded",
		"Hash",
		"Leechers",
		"Left",
		"Name",
		"Peers",
		"Pieces",
		"Seeders",
		"Size",
		"Started at",
		"Tracker",
		"Uploaded",
	};
	switch (m_torrents_columns[pDispInfo->item.iSubItem])
	{
	case dc_name:
		m_buffer[m_buffer_w] = row_names[pDispInfo->item.iItem];
		break;
	case dc_value:
		switch (pDispInfo->item.iItem)
		{
		case dr_completed_at:
			if (m_file->completed_at)
				m_buffer[m_buffer_w] = time2a(m_file->completed_at);
			break;
		case dr_downloaded:
			m_buffer[m_buffer_w] = b2a(m_file->downloaded, "b");
			if (m_file->total_downloaded != m_file->downloaded)
				m_buffer[m_buffer_w] += " / " + b2a(m_file->total_downloaded, "b");
			if (m_file->size)
				m_buffer[m_buffer_w] += " (" + n(m_file->total_downloaded * 100 / m_file->size) + " %)";
			break;
		case dr_hash:
			m_buffer[m_buffer_w] = hex_encode(m_file->info_hash);
			break;
		case dr_leechers:
			m_buffer[m_buffer_w] = n(m_file->c_leechers);
			if (m_file->c_leechers_total)
				m_buffer[m_buffer_w] += " / " + n(m_file->c_leechers_total);
			break;
		case dr_left:
			m_buffer[m_buffer_w] = b2a(m_file->left, "b");
			break;
		case dr_name:
			m_buffer[m_buffer_w] = m_file->name;
			break;
		case dr_peers:
			m_buffer[m_buffer_w] = n(m_file->c_leechers + m_file->c_seeders);
			if (m_file->c_leechers_total + m_file->c_seeders_total)
				m_buffer[m_buffer_w] += " / " + n(m_file->c_leechers_total + m_file->c_seeders_total);
			break;
		case dr_pieces:
			m_buffer[m_buffer_w] = n(m_file->c_valid_pieces) + " / " + n(m_file->c_invalid_pieces + m_file->c_valid_pieces) + " x " + b2a(m_file->cb_piece, "b");
			break;
		case dr_seeders:
			m_buffer[m_buffer_w] = n(m_file->c_seeders);
			if (m_file->c_seeders_total)
				m_buffer[m_buffer_w] += " / " + n(m_file->c_seeders_total);
			break;
		case dr_size:
			m_buffer[m_buffer_w] = b2a(m_file->size, "b");
			break;
		case dr_started_at:
			if (m_file->started_at)
				m_buffer[m_buffer_w] = time2a(m_file->started_at);
			break;
		case dr_tracker:
			if (!m_file->trackers.empty())
				m_buffer[m_buffer_w] = m_file->trackers.front().url;
			break;
		case dr_uploaded:
			m_buffer[m_buffer_w] = b2a(m_file->uploaded, "b");
			if (m_file->total_uploaded != m_file->uploaded)
				m_buffer[m_buffer_w] += " / " + b2a(m_file->total_uploaded, "b");
			if (m_file->size)
				m_buffer[m_buffer_w] += " (" + n(m_file->total_uploaded * 100 / m_file->size) + " %)";
			break;
		}
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoEvents(NMHDR* pNMHDR, LRESULT* pResult) 
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const t_event& e = m_file->events[pDispInfo->item.lParam];
	switch (m_peers_columns[pDispInfo->item.iSubItem])
	{
	case ec_time:
		{
			tm* time = localtime(&e.time);
			if (!time)
				break;
			char time_string[16];
			sprintf(time_string, "%02d:%02d:%02d", time->tm_hour, time->tm_min, time->tm_sec);
			m_buffer[m_buffer_w] = time_string;
		}
		break;
	case ec_level:
		m_buffer[m_buffer_w] = n(e.level);
		break;
	case ec_source:
		m_buffer[m_buffer_w] = e.source;
		break;
	case ec_message:
		m_buffer[m_buffer_w] = e.message;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoPeers(NMHDR* pNMHDR, LRESULT* pResult) 
{
	switch (m_bottom_view)
	{
	case v_details:
		OnGetdispinfoDetails(pNMHDR, pResult);
		return;
	case v_events:
		OnGetdispinfoEvents(pNMHDR, pResult);
		return;
	case v_files:
		OnGetdispinfoSubFiles(pNMHDR, pResult);
		return;
	case v_trackers:
		OnGetdispinfoTrackers(pNMHDR, pResult);
		return;
	}
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const t_peer& e = m_file->peers.find(pDispInfo->item.lParam)->second;
	switch (m_peers_columns[pDispInfo->item.iSubItem])
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
		m_buffer[m_buffer_w] = hex_encode(e.peer_id);
		break;
	case pc_client:
		m_buffer[m_buffer_w] = peer_id2a(e.peer_id);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoSubFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const t_sub_file& e = m_file->sub_files[pDispInfo->item.lParam];
	switch (m_peers_columns[pDispInfo->item.iSubItem])
	{
	case sfc_name:
		if (e.name.empty())
		{
			int i = m_file->name.rfind('\\');
			m_buffer[m_buffer_w] = i == string::npos ? m_file->name : m_file->name.substr(i + 1);
		}
		else
			m_buffer[m_buffer_w] = e.name;
		break;
	case sfc_done:
		if (e.size)
			m_buffer[m_buffer_w] = n((e.size - e.left) * 100 / e.size);
		break;
	case sfc_left:
		if (e.left)
			m_buffer[m_buffer_w] = b2a(e.left);
		break;
	case sfc_size:
		m_buffer[m_buffer_w] = b2a(e.size);
		break;
	case sfc_priority:
		if (e.priority)
			m_buffer[m_buffer_w] = n(e.priority);
		break;
	case sfc_hash:
		m_buffer[m_buffer_w] = hex_encode(e.hash);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoTrackers(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	const t_tracker& e = m_file->trackers[pDispInfo->item.lParam];
	switch (m_peers_columns[pDispInfo->item.iSubItem])
	{
	case tc_url:
		m_buffer[m_buffer_w] = e.url;
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
	if (nType == SIZE_MINIMIZED && m_show_tray_icon)
		ShowWindow(SW_HIDE);
	else if (m_files.GetSafeHwnd())
		auto_size();
}

void CXBTClientDlg::fill_peers()
{
	m_peers.DeleteAllItems();
	switch (m_bottom_view)
	{
	case v_details:
		for (int i = 0; i < dr_count; i++)
			m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i);
		break;
	case v_events:
		for (int i = 0; i < m_file->events.size(); i++)
			m_peers.SetItemData(m_peers.InsertItem(0, LPSTR_TEXTCALLBACK), i);
		break;
	case v_files:
		for (int i = 0; i < m_file->sub_files.size(); i++)
			m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i);
		break;
	case v_peers:
		for (t_peers::const_iterator i = m_file->peers.begin(); i != m_file->peers.end(); i++)
			m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
		break;
	case v_trackers:
		for (t_trackers::const_iterator i = m_file->trackers.begin(); i != m_file->trackers.end(); i++)
			m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), i - m_file->trackers.begin());
		break;
	}
	sort_peers();
	auto_size_peers();
}

void CXBTClientDlg::OnItemchangedFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = reinterpret_cast<NM_LISTVIEW*>(pNMHDR);
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
		int c_files = sr.read_int(4);
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
	f.display_name = f.name = sr.read_string();
	f.info_hash = info_hash;
	f.trackers.clear();
	for (int c_trackers = sr.read_int(4); c_trackers--; )
	{
		t_tracker e;
		e.url = sr.read_string();
		f.trackers.push_back(e);
	}
	f.downloaded = sr.read_int(8);
	f.left = sr.read_int(8);
	f.size = sr.read_int(8);
	f.uploaded = sr.read_int(8);
	f.total_downloaded = sr.read_int(8);
	f.total_uploaded = sr.read_int(8);
	f.down_rate = sr.read_int(4);
	f.up_rate = sr.read_int(4);
	f.c_leechers = sr.read_int(4);
	f.c_seeders = sr.read_int(4);
	f.c_leechers_total = sr.read_int(4);
	f.c_seeders_total = sr.read_int(4);
	f.c_invalid_pieces = sr.read_int(4);
	f.c_valid_pieces = sr.read_int(4);
	f.cb_piece = sr.read_int(4);
	f.hashing = false;
	f.running = false;
	switch (sr.read_int(4))
	{
	case 1:
		f.running = true;
		break;
	case 2:
		f.hashing = true;
		break;
	}
	f.started_at = sr.read_int(4);
	f.completed_at = sr.read_int(4);
	f.removed = false;
	{
		int i = f.display_name.rfind('\\');
		if (i != string::npos)
			f.display_name.erase(0, i + 1);
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); i++)
			i->second.removed = true;
	}
	{
		int c_peers = sr.read_int(4);
		while (c_peers--)
			read_peer_dump(f, sr);
	}
	f.events.clear();
	for (int c_alerts = sr.read_int(4); c_alerts--; )
	{
		t_event e;
		e.time = sr.read_int(4);
		e.level = sr.read_int(4);
		e.message = sr.read_string();
		e.source = sr.read_string();
		f.events.push_back(e);
	}
	f.sub_files.clear();
	for (int c_files = sr.read_int(4); c_files--; )
	{
		t_sub_file e;
		e.left = sr.read_int(8);
		e.name = sr.read_string();
		e.priority = sr.read_int(4);
		e.size = sr.read_int(8);
		e.hash = sr.read_string();
		f.sub_files.push_back(e);
	}
	if (m_file == &f)
	{
		switch (m_bottom_view)
		{
		case v_events:
			while (m_peers.GetItemCount() < f.events.size())
			{
				int id = m_peers.GetItemCount();
				m_peers.SetItemData(m_peers.InsertItem(0, LPSTR_TEXTCALLBACK), id);
				inserted = true;
			}
			while (m_peers.GetItemCount() > f.events.size())
				m_peers.DeleteItem(0);
			break;
		case v_files:
			while (m_peers.GetItemCount() < f.sub_files.size())
			{
				int id = m_peers.GetItemCount();
				m_peers.SetItemData(m_peers.InsertItem(m_peers.GetItemCount(), LPSTR_TEXTCALLBACK), id);
				inserted = true;
			}
			break;
		}
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); )
		{
			if (i->second.removed)
			{
				if (m_bottom_view == v_peers && m_file == &f)
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
	p.host.s_addr = htonl(sr.read_int(4));
	p.port = htons(sr.read_int(4));
	p.peer_id = sr.read_string();
	p.downloaded = sr.read_int(8);
	p.left = sr.read_int(8);
	p.uploaded = sr.read_int(8);
	p.down_rate = sr.read_int(4);
	p.up_rate = sr.read_int(4);
	p.local_link = sr.read_int(1);
	p.local_choked = sr.read_int(1);
	p.local_interested = sr.read_int(1);
	p.remote_choked = sr.read_int(1);
	p.remote_interested = sr.read_int(1);
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
		if (m_bottom_view == v_peers && m_file == &f)
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
	m_initial_hide = false;
	switch (nIDEvent)
	{
	case 0:
		if (!IsWindowVisible())
			break;
		m_files.SetRedraw(false);
		m_peers.SetRedraw(false);
		read_server_dump(Cstream_reader(m_server.get_status(Cserver::df_alerts | Cserver::df_files | Cserver::df_peers | Cserver::df_trackers)));
		sort_files();
		sort_peers();
		m_files.SetRedraw(true);
		m_peers.SetRedraw(true);
		m_files.Invalidate();
		m_peers.Invalidate();
		update_tray();
		break;
	case 1:
		if (IsWindowVisible()
			|| !m_show_tray_icon)
			break;
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
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
	{
		ShellExecute(m_hWnd, "open", m_dir, NULL, NULL, SW_SHOW);
		return;
	}
	string name = m_files_map.find(m_files.GetItemData(index))->second.name;
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

void CXBTClientDlg::OnPopupExploreTracker() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	const t_file& f = m_files_map.find(m_files.GetItemData(index))->second;
	if (f.trackers.empty())
		return;
	Cbt_tracker_url url = f.trackers.front().url;
	ShellExecute(m_hWnd, "open", ("http://" + url.m_host).c_str(), NULL, NULL, SW_SHOW);
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

void CXBTClientDlg::OnPopupTorrentClipboardCopyAnnounceUrl() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
	{
		const t_file& file = m_files_map.find(m_files.GetItemData(index))->second;
		if (!file.trackers.empty())
			set_clipboard(file.trackers.front().url);
	}
}

void CXBTClientDlg::OnPopupTorrentClipboardCopyHash() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
		set_clipboard(hex_encode(m_files_map.find(m_files.GetItemData(index))->second.info_hash));
}

void CXBTClientDlg::OnPopupCopy() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index != -1)
		set_clipboard(m_server.get_url(m_files_map.find(m_files.GetItemData(index))->second.info_hash));
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
		open(static_cast<string>(dlg.GetPathName()), m_ask_for_location);
}

void CXBTClientDlg::OnPopupClose() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.close(m_files_map.find(m_files.GetItemData(index))->second.info_hash);
}

void CXBTClientDlg::OnPopupTorrentDelete() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.close(m_files_map.find(m_files.GetItemData(index))->second.info_hash, true);
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

void CXBTClientDlg::OnPopupMakeTorrent() 
{
	Cdlg_make_torrent dlg;
	if (IDOK == dlg.DoModal() && dlg.m_seed_after_making)
		open(dlg.torrent_fname(), true);
}

void CXBTClientDlg::OnPopupOptions() 
{
	Cdlg_options dlg(this);
	Cdlg_options::t_data data;
	data.admin_port = AfxGetApp()->GetProfileInt(m_reg_key, "admin_port", m_server.admin_port());
	data.ask_for_location = AfxGetApp()->GetProfileInt(m_reg_key, "ask_for_location", false);
	data.bind_before_connect = AfxGetApp()->GetProfileInt(m_reg_key, "bind_before_connect", false);
	data.end_mode = m_server.end_mode();
	data.files_location = m_dir;
	data.lower_process_priority = AfxGetApp()->GetProfileInt(m_reg_key, "lower_process_priority", true);
	data.peer_limit = AfxGetApp()->GetProfileInt(m_reg_key, "peer_limit", m_server.peer_limit());
	data.peer_port = AfxGetApp()->GetProfileInt(m_reg_key, "peer_port", m_server.peer_port());
	data.public_ipa = AfxGetApp()->GetProfileString(m_reg_key, "public_ipa", "");
	data.seeding_ratio = AfxGetApp()->GetProfileInt(m_reg_key, "seeding_ratio", m_server.seeding_ratio());
	data.show_advanced_columns = AfxGetApp()->GetProfileInt(m_reg_key, "show_advanced_columns", false);
	data.show_tray_icon = AfxGetApp()->GetProfileInt(m_reg_key, "show_tray_icon", true);
	data.start_minimized = AfxGetApp()->GetProfileInt(m_reg_key, "start_minimized", false);
	data.tracker_port = AfxGetApp()->GetProfileInt(m_reg_key, "tracker_port", m_server.tracker_port());
	data.upload_rate = AfxGetApp()->GetProfileInt(m_reg_key, "upload_rate", m_server.upload_rate());
	data.upload_slots = AfxGetApp()->GetProfileInt(m_reg_key, "upload_slots", m_server.upload_slots());
	dlg.set(data);
	if (IDOK != dlg.DoModal())
		return;
	data = dlg.get();
	m_server.admin_port(data.admin_port);
	m_ask_for_location = data.ask_for_location;
	m_server.bind_before_connect(data.bind_before_connect);
	m_server.end_mode(data.end_mode);
	set_dir(data.files_location);
	lower_process_priority(data.lower_process_priority);
	m_server.peer_limit(data.peer_limit);
	m_server.peer_port(data.peer_port);
	if (!data.public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(data.public_ipa));
	m_server.seeding_ratio(data.seeding_ratio);
	m_show_advanced_columns = data.show_advanced_columns;
	m_show_tray_icon = data.show_tray_icon;
	m_server.tracker_port(data.tracker_port);
	m_server.upload_rate(data.upload_rate);
	m_server.upload_slots(data.upload_slots);
	AfxGetApp()->WriteProfileInt(m_reg_key, "admin_port", data.admin_port);
	AfxGetApp()->WriteProfileInt(m_reg_key, "ask_for_location", data.ask_for_location);
	AfxGetApp()->WriteProfileInt(m_reg_key, "bind_before_connect", data.bind_before_connect);
	AfxGetApp()->WriteProfileString(m_reg_key, "files_location", data.files_location.c_str());
	AfxGetApp()->WriteProfileInt(m_reg_key, "lower_process_priority", data.lower_process_priority);
	AfxGetApp()->WriteProfileInt(m_reg_key, "peer_limit", data.peer_limit);
	AfxGetApp()->WriteProfileInt(m_reg_key, "peer_port", data.peer_port);
	AfxGetApp()->WriteProfileString(m_reg_key, "public_ipa", data.public_ipa.c_str());
	AfxGetApp()->WriteProfileInt(m_reg_key, "seeding_ratio", data.seeding_ratio);
	AfxGetApp()->WriteProfileInt(m_reg_key, "show_advanced_columns", data.show_advanced_columns);
	AfxGetApp()->WriteProfileInt(m_reg_key, "show_tray_icon", data.show_tray_icon);
	AfxGetApp()->WriteProfileInt(m_reg_key, "start_minimized", data.start_minimized);
	AfxGetApp()->WriteProfileInt(m_reg_key, "tracker_port", data.tracker_port);
	AfxGetApp()->WriteProfileInt(m_reg_key, "upload_rate", data.upload_rate);
	AfxGetApp()->WriteProfileInt(m_reg_key, "upload_slots", data.upload_slots);
	insert_columns();
	auto_size();
	if (m_show_tray_icon)
		register_tray();
	else
		unregister_tray();
}

void CXBTClientDlg::OnPopupTrackers() 
{
	Cdlg_trackers dlg(this);
	Cstream_reader r(m_server.get_trackers());
	for (int count = r.read_int(4); count--; )
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
	w.write_int(4, dlg.trackers().size());
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

void CXBTClientDlg::OnPopupAbout() 
{
	Cdlg_about().DoModal();	
}

void CXBTClientDlg::OnPopupTorrentAlerts() 
{
	int index = m_files.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	Cdlg_torrent(this, m_server, m_files_map.find(m_files.GetItemData(index))->second.info_hash).DoModal();
}

void CXBTClientDlg::OnDblclkFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	OnPopupExplore();
	*pResult = 0;
}

void CXBTClientDlg::OnDropFiles(HDROP hDropInfo) 
{
	typedef set<string> t_names;

	int c_files = DragQueryFile(hDropInfo, 0xFFFFFFFF, NULL, 0);
	t_names names;
	
	for (int i = 0; i < c_files; i++)
	{
		char name[MAX_PATH];
		DragQueryFile(hDropInfo, i, name, MAX_PATH);
		struct _stati64 b;
		if (_stati64(name, &b))
			continue;
		if (b.st_mode & S_IFDIR
			|| b.st_size > 512 << 10)
			names.insert(name);
		else
			open(name, m_ask_for_location);
	}
	ETSLayoutDialog::OnDropFiles(hDropInfo);
	if (names.empty())
		return;
	Cdlg_make_torrent dlg;
	for (t_names::const_iterator i = names.begin(); i != names.end(); i++)
		dlg.insert(*i);
	if (IDOK == dlg.DoModal() && dlg.m_seed_after_making)
		open(dlg.torrent_fname(), true);
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
	if (!m_show_tray_icon)
		return;
	NOTIFYICONDATA nid;
	nid.cbSize = NOTIFYICONDATA_V1_SIZE;
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
	nid.cbSize = NOTIFYICONDATA_V1_SIZE;
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = 0;
	Shell_NotifyIcon(NIM_DELETE, &nid);
}

void CXBTClientDlg::update_tray()
{
	if (!m_show_tray_icon)
		return;
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
	nid.cbSize = NOTIFYICONDATA_V1_SIZE;
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = NIF_TIP;
	if (size)
		sprintf(nid.szTip, "XBT Client - %d %%, %s left, %d leechers, %d seeders", static_cast<int>((size - left) * 100 / size), b2a(left).c_str(), leechers, seeders);
	else
		strcpy(nid.szTip, "XBT Client");
	Shell_NotifyIcon(NIM_MODIFY, &nid);
}

void CXBTClientDlg::update_tray(const char* info_title, const char* info)
{
	NOTIFYICONDATA nid;
	nid.cbSize = sizeof(NOTIFYICONDATA);
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = NIF_INFO;
	nid.dwInfoFlags = NIIF_INFO;
	if (info_title && strlen(info_title) < sizeof(nid.szInfoTitle))
		strcpy(nid.szInfoTitle, info_title);
	else
		*nid.szInfoTitle = 0;
	if (info && strlen(info) < sizeof(nid.szInfo))
		strcpy(nid.szInfo, info);
	else
		*nid.szInfo = 0;
	nid.uTimeout = 10;
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
				open(string(reinterpret_cast<const char*>(cds.lpData), cds.cbData), m_ask_for_location);
				return true;
			}				
		}
		break;
	default:
		if (message == g_tray_message_id)
		{	
			switch (lParam)
			{
			case WM_LBUTTONUP:
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
	case fc_size:
		return compare(a.size, b.size);
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
	case fc_peers:
		return compare(a.c_leechers + a.c_seeders, b.c_leechers + b.c_seeders);
	case fc_state:
		return compare(a.running, b.running);
	case fc_name:
		return compare(a.display_name, b.display_name);
	}
	return 0;
}

static int CALLBACK files_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->files_compare(lParam1, lParam2);
}

void CXBTClientDlg::OnColumnclickFiles(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = reinterpret_cast<NM_LISTVIEW*>(pNMHDR);
	m_files_sort_reverse = m_torrents_columns[pNMListView->iSubItem] == m_files_sort_column && !m_files_sort_reverse;
	m_files_sort_column = m_torrents_columns[pNMListView->iSubItem];
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
	case pc_client:
		return compare(peer_id2a(a.peer_id), peer_id2a(b.peer_id));
	}
	return 0;
}

int CXBTClientDlg::sub_files_compare(int id_a, int id_b) const
{
	if (!m_file)
		return 0;
	if (m_peers_sort_reverse)
		swap(id_a, id_b);
	const t_sub_file& a = m_file->sub_files[id_a];
	const t_sub_file& b = m_file->sub_files[id_b];
	switch (m_peers_sort_column)
	{
	case sfc_name:
		return compare(a.name, b.name);
	case sfc_done:
		return compare(b.left, a.left);
	case sfc_left:
		return compare(a.left, b.left);
	case sfc_size:
		return compare(a.size, b.size);
	case sfc_priority:
		return compare(b.priority, a.priority);
	case sfc_hash:
		return compare(a.hash, b.hash);
	}
	return 0;
}

static int CALLBACK peers_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->peers_compare(lParam1, lParam2);
}

static int CALLBACK sub_files_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->sub_files_compare(lParam1, lParam2);
}

void CXBTClientDlg::OnColumnclickPeers(NMHDR* pNMHDR, LRESULT* pResult) 
{
	NM_LISTVIEW* pNMListView = reinterpret_cast<NM_LISTVIEW*>(pNMHDR);
	m_peers_sort_reverse = m_peers_columns[pNMListView->iSubItem] == m_peers_sort_column && !m_peers_sort_reverse;
	m_peers_sort_column = m_peers_columns[pNMListView->iSubItem];
	sort_peers();	
	*pResult = 0;
}

void CXBTClientDlg::sort_files()
{
	m_files.SortItems(::files_compare, reinterpret_cast<DWORD>(this));	
}

void CXBTClientDlg::sort_peers()
{
	switch (m_bottom_view)
	{
	case v_files:
		m_peers.SortItems(::sub_files_compare, reinterpret_cast<DWORD>(this));
		break;
	case v_peers:
		m_peers.SortItems(::peers_compare, reinterpret_cast<DWORD>(this));
		break;
	}
}

void CXBTClientDlg::insert_top_columns()
{
	m_torrents_columns.clear();
	m_torrents_columns.push_back(fc_name);
	m_torrents_columns.push_back(fc_done);
	m_torrents_columns.push_back(fc_left);
	m_torrents_columns.push_back(fc_size);
	m_torrents_columns.push_back(fc_total_downloaded);
	m_torrents_columns.push_back(fc_total_uploaded);
	m_torrents_columns.push_back(fc_down_rate);
	m_torrents_columns.push_back(fc_up_rate);
	m_torrents_columns.push_back(fc_leechers);
	m_torrents_columns.push_back(fc_seeders);
	m_torrents_columns.push_back(fc_peers);
	m_torrents_columns.push_back(fc_state);
	if (m_show_advanced_columns)
	{
		m_torrents_columns.push_back(fc_hash);
	}
	const char* torrents_columns_names[] =
	{
		"Name",
		"%",
		"Left",
		"Size",
		"Downloaded",
		"Uploaded",
		"Down rate",
		"Up rate",
		"Leechers",
		"Seeders",
		"Peers",
		"State",
		"Hash"
	};
	const int torrents_columns_formats[] =
	{
		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
	};
	while (m_files.GetHeaderCtrl()->GetItemCount())
		m_files.DeleteColumn(0);
	for (t_columns::const_iterator i = m_torrents_columns.begin(); i != m_torrents_columns.end(); i++)
		m_files.InsertColumn(99, torrents_columns_names[*i], torrents_columns_formats[*i]);
}

void CXBTClientDlg::insert_bottom_columns()
{
	m_peers_columns.clear();
	switch (m_bottom_view)
	{
	case v_details:
		m_peers_columns.push_back(dc_name);
		m_peers_columns.push_back(dc_value);
		break;
	case v_events:
		m_peers_columns.push_back(ec_time);
		m_peers_columns.push_back(ec_level);
		m_peers_columns.push_back(ec_source);
		m_peers_columns.push_back(ec_message);
		break;
	case v_files:
		m_peers_columns.push_back(sfc_name);
		m_peers_columns.push_back(sfc_done);
		m_peers_columns.push_back(sfc_left);
		m_peers_columns.push_back(sfc_size);
		m_peers_columns.push_back(sfc_priority);
		m_peers_columns.push_back(sfc_hash);
		break;
	case v_peers:
		m_peers_columns.push_back(pc_client);
		m_peers_columns.push_back(pc_done);
		m_peers_columns.push_back(pc_left);
		m_peers_columns.push_back(pc_downloaded);
		m_peers_columns.push_back(pc_uploaded);
		m_peers_columns.push_back(pc_down_rate);
		m_peers_columns.push_back(pc_up_rate);
		m_peers_columns.push_back(pc_link_direction);
		m_peers_columns.push_back(pc_local_choked);
		m_peers_columns.push_back(pc_local_interested);
		m_peers_columns.push_back(pc_remote_choked);
		m_peers_columns.push_back(pc_remote_interested);
		if (m_show_advanced_columns)
		{
			m_peers_columns.push_back(pc_host);
			m_peers_columns.push_back(pc_port);
			m_peers_columns.push_back(pc_peer_id);
		}
		break;
	case v_trackers:
		m_peers_columns.push_back(tc_url);
		break;
	}
	const char* peers_columns_names[] =
	{
		"Name",
		"Value",

		"Time",
		"Level",
		"Source",
		"Message",

		"Peer ID",
		"%",
		"Left",
		"Downloaded",
		"Uploaded",
		"Down rate",
		"Up rate",
		"D",
		"L",
		"L",
		"R",
		"R",
		"Host",
		"Port",
		"Client",
		"",
		"Name",
		"%",
		"Left",
		"Size",
		"Priority",
		"Hash",

		"URL",
	};
	const int peers_columns_formats[] =
	{
		LVCFMT_LEFT,
		LVCFMT_LEFT,

		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,

		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,

		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,

		LVCFMT_LEFT,
	};
	while (m_peers.GetHeaderCtrl()->GetItemCount())
		m_peers.DeleteColumn(0);
	for (t_columns::const_iterator i = m_peers_columns.begin(); i != m_peers_columns.end(); i++)
		m_peers.InsertColumn(99, peers_columns_names[*i], peers_columns_formats[*i]);
}

void CXBTClientDlg::insert_columns()
{
	insert_top_columns();
	insert_bottom_columns();
}

void CXBTClientDlg::set_dir(const string& v)
{
	if (v.empty())
	{
		char path[MAX_PATH];
		if (FAILED(SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true)))
			strcpy(path, "C:");
		strcat(path, "\\XBT");	
		m_dir = path;
	}
	else
		m_dir = v.c_str();
	CreateDirectory(m_dir, NULL);
}

void CXBTClientDlg::lower_process_priority(bool v)
{
	SetPriorityClass(GetCurrentProcess(), v ? BELOW_NORMAL_PRIORITY_CLASS : NORMAL_PRIORITY_CLASS);
}

long CXBTClientDlg::OnHotKey(WPARAM, LPARAM)
{
	ShowWindow(IsWindowVisible() ? SW_HIDE : SW_SHOWMAXIMIZED);
	if (IsWindowVisible())
		SetForegroundWindow();
	return 0;
}

void CXBTClientDlg::set_clipboard(const string& v)
{
	if (v.empty())
		return;
	void* h = GlobalAlloc(GMEM_MOVEABLE, v.size() + 1);
	void* p = GlobalLock(h);
	if (p)
	{
		memcpy(p, v.c_str(), v.size() + 1);
		GlobalUnlock(h);
		if (OpenClipboard())
		{
			EmptyClipboard();
			SetClipboardData(CF_TEXT, h);
			CloseClipboard();
			return;
		}
	}
	GlobalFree(h);
}


void CXBTClientDlg::OnPopupViewDetails() 
{
	m_bottom_view = v_details;
	m_peers_sort_column = -1;
	insert_bottom_columns();
	fill_peers();
	auto_size_peers();
}	

void CXBTClientDlg::OnPopupViewEvents() 
{
	m_bottom_view = v_events;
	m_peers_sort_column = -1;
	insert_bottom_columns();
	fill_peers();
	auto_size_peers();
}

void CXBTClientDlg::OnPopupViewFiles() 
{
	m_bottom_view = v_files;
	m_peers_sort_column = -1;
	insert_bottom_columns();
	fill_peers();
	auto_size_peers();
}

void CXBTClientDlg::OnPopupViewPeers() 
{
	m_bottom_view = v_peers;
	m_peers_sort_column = pc_client;
	insert_bottom_columns();
	fill_peers();
	auto_size_peers();
}

void CXBTClientDlg::OnPopupViewTrackers() 
{
	m_bottom_view = v_trackers;
	m_peers_sort_column = -1;
	insert_bottom_columns();
	fill_peers();
	auto_size_peers();
}

void CXBTClientDlg::OnCustomdrawFiles(NMHDR* pNMHDR, LRESULT* pResult)
{
	NMLVCUSTOMDRAW* pCustomDraw = reinterpret_cast<NMLVCUSTOMDRAW*>(pNMHDR);
	switch (pCustomDraw->nmcd.dwDrawStage)
	{
	case CDDS_PREPAINT:
		*pResult = CDRF_NOTIFYITEMDRAW;
		break;
	case CDDS_ITEMPREPAINT:
		pCustomDraw->clrTextBk = pCustomDraw->nmcd.dwItemSpec & 1 ? RGB(0xf8, 0xf8, 0xf8) : RGB(0xff, 0xff, 0xff);
		*pResult = CDRF_DODEFAULT;
		break;
	}
}

void CXBTClientDlg::OnCustomdrawPeers(NMHDR* pNMHDR, LRESULT* pResult)
{
	NMLVCUSTOMDRAW* pCustomDraw = reinterpret_cast<NMLVCUSTOMDRAW*>(pNMHDR);
	switch (pCustomDraw->nmcd.dwDrawStage)
	{
	case CDDS_PREPAINT:
		*pResult = CDRF_NOTIFYITEMDRAW;
		break;
	case CDDS_ITEMPREPAINT:
		pCustomDraw->clrTextBk = pCustomDraw->nmcd.dwItemSpec & 1 ? RGB(0xf8, 0xf8, 0xf8) : RGB(0xff, 0xff, 0xff);
		*pResult = CDRF_DODEFAULT;
		break;
	}
}

void CXBTClientDlg::OnPopupPriorityHigh() 
{
	set_priority(1);
}

void CXBTClientDlg::OnPopupPriorityNormal() 
{
	set_priority(0);
}

void CXBTClientDlg::OnPopupPriorityLow() 
{
	set_priority(-1);
}

void CXBTClientDlg::OnPopupPriorityExclude() 
{
	set_priority(-10);
}

void CXBTClientDlg::set_priority(int v)
{
	if (m_bottom_view != v_files || !m_file)
		return;
	for (int index = -1; (index = m_peers.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_sub_file& e = m_file->sub_files[m_peers.GetItemData(index)];
		m_server.sub_file_priority(m_file->info_hash, e.name, v);
	}
}

void CXBTClientDlg::OnDblclkPeers(NMHDR* pNMHDR, LRESULT* pResult) 
{
	if (m_bottom_view != v_files || !m_file)
		return;
	int index = m_peers.GetNextItem(-1, LVNI_FOCUSED);
	const t_sub_file& e = m_file->sub_files[m_peers.GetItemData(index)];
	ShellExecute(m_hWnd, "open", (m_file->name + e.name).c_str(), NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnPopupViewAdvancedColumns() 
{
	m_show_advanced_columns = !AfxGetApp()->GetProfileInt(m_reg_key, "show_advanced_columns", false);
	AfxGetApp()->WriteProfileInt(m_reg_key, "show_advanced_columns", m_show_advanced_columns);
	insert_columns();
	auto_size();
}

void CXBTClientDlg::OnPopupViewTrayIcon() 
{
	m_show_tray_icon = !AfxGetApp()->GetProfileInt(m_reg_key, "show_tray_icon", true);
	AfxGetApp()->WriteProfileInt(m_reg_key, "show_tray_icon", m_show_tray_icon);
	if (m_show_tray_icon)
		register_tray();
	else
		unregister_tray();
}
