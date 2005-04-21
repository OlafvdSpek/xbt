// XBT ClientDlg.cpp : implementation file
//

#include "stdafx.h"
#include "XBT ClientDlg.h"

#include <sys/stat.h>
#include "windows/browse_for_directory.h"
#include "bt_misc.h"
#include "bt_torrent.h"
#include "dlg_about.h"
#include "dlg_make_torrent.h"
#include "dlg_options.h"
#include "dlg_profiles.h"
#include "dlg_scheduler.h"
#include "dlg_torrent_options.h"
#include "dlg_trackers.h"
#include "resource.h"

#define for if (0) {} else for

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

const extern UINT g_are_you_me_message_id = RegisterWindowMessage("XBT Client Are You Me Message");
const static UINT g_taskbar_created_message_id = RegisterWindowMessage("TaskbarCreated");
const static UINT g_tray_message_id = RegisterWindowMessage("XBT Client Tray Message");

enum
{
	fc_name,
	fc_done,
	fc_left,
	fc_size,
	fc_total_downloaded,
	fc_total_downloaded_rel,
	fc_total_uploaded,
	fc_total_uploaded_rel,
	fc_down_rate,
	fc_up_rate,
	fc_leechers,
	fc_seeders,
	fc_peers,
	fc_priority,
	fc_state,
	fc_hash,
	fc_end,
};

enum
{
	dc_name,
	dc_value,

	ec_time,
	ec_level,
	ec_source,
	ec_message,
	ec_end,

	pc_client,
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
	pc_local_requests,
	pc_remote_requests,
	pc_pieces,
	pc_recv_time,
	pc_send_time,
	pc_host,
	pc_port,
	pc_peer_id,
	pc_end,

	sfc_name,
	sfc_extension,
	sfc_done,
	sfc_left,
	sfc_size,
	sfc_priority,
	sfc_hash,
	sfc_end,

	pic_index,
	pic_c_chunks,
	pic_c_peers,
	pic_priority,
	pic_valid,
	pic_rank,
	pic_end,

	tc_url,

	gdc_name,
	gdc_value,

	gec_time,
	gec_level,
	gec_source,
	gec_message,
	gec_end,
};

enum
{
	v_details,
	v_events,
	v_files,
	v_peers,
	v_pieces,
	v_trackers,
	v_global_details,
	v_global_events,
};

enum
{
	dr_chunks,
	dr_completed_at,
	dr_distributed_copies,
	dr_downloaded,
	dr_downloaded_l5_overhead,
	dr_files,
	dr_hash,
	dr_leechers,
	dr_left,
	dr_name,
	dr_peers,
	dr_pieces,
	dr_rejected_chunks,
	dr_rejected_pieces,
	dr_seeders,
	dr_seeding_ratio,
	dr_size,
	dr_started_at,
	dr_tracker,
	dr_upload_slots_min,
	dr_upload_slots_max,
	dr_uploaded,
	dr_uploaded_l5_overhead,
	dr_count
};

enum
{
	gdr_downloaded,
	gdr_down_rate,
	gdr_files,
	gdr_leechers,
	gdr_left,
	gdr_peers,
	gdr_seeders,
	gdr_size,
	gdr_started_at,
	gdr_torrents_complete,
	gdr_torrents_incomplete,
	gdr_uploaded,
	gdr_up_rate,
	gdr_count
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
	m_initial_hide = GetProfileInt("start_minimized", false);
#endif
	m_server_thread = NULL;
	update_global_details();
}

void CXBTClientDlg::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(CXBTClientDlg)
	DDX_Control(pDX, IDC_TAB, m_tab);
	DDX_Control(pDX, IDC_PEERS, m_peers);
	DDX_Control(pDX, IDC_FILES, m_files);
	//}}AFX_DATA_MAP
}

BEGIN_MESSAGE_MAP(CXBTClientDlg, ETSLayoutDialog)
	ON_REGISTERED_MESSAGE(g_are_you_me_message_id, OnAreYouMe)
	ON_REGISTERED_MESSAGE(g_taskbar_created_message_id, OnTaskbarCreated)
	ON_REGISTERED_MESSAGE(g_tray_message_id, OnTray)
	ON_MESSAGE(WM_HOTKEY, OnHotKey)
	ON_NOTIFY(NM_CUSTOMDRAW, IDC_FILES, OnCustomdrawFiles)
	ON_NOTIFY(NM_CUSTOMDRAW, IDC_PEERS, OnCustomdrawPeers)
	ON_WM_CONTEXTMENU()
	ON_WM_SYSCOMMAND()
	//{{AFX_MSG_MAP(CXBTClientDlg)
	ON_WM_PAINT()
	ON_WM_QUERYDRAGICON()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_FILES, OnGetdispinfoFiles)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_PEERS, OnGetdispinfoPeers)
	ON_NOTIFY(LVN_ITEMCHANGED, IDC_FILES, OnItemchangedFiles)
	ON_WM_TIMER()
	ON_WM_DROPFILES()
	ON_COMMAND(ID_POPUP_EXPLORE, OnPopupExplore)
	ON_WM_DESTROY()
	ON_WM_WINDOWPOSCHANGING()
	ON_WM_ENDSESSION()
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_FILES, OnColumnclickFiles)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_PEERS, OnColumnclickPeers)
	ON_NOTIFY(NM_DBLCLK, IDC_FILES, OnDblclkFiles)
	ON_COMMAND(ID_POPUP_ANNOUNCE, OnPopupAnnounce)
	ON_COMMAND(ID_POPUP_EXPLORE_TRACKER, OnPopupExploreTracker)
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
	ON_WM_INITMENUPOPUP()
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_ADVANCED_COLUMNS, OnUpdatePopupViewAdvancedColumns)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_TRAY_ICON, OnUpdatePopupViewTrayIcon)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_DETAILS, OnUpdatePopupViewDetails)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_EVENTS, OnUpdatePopupViewEvents)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_FILES, OnUpdatePopupViewFiles)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_PEERS, OnUpdatePopupViewPeers)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_TRACKERS, OnUpdatePopupViewTrackers)
	ON_UPDATE_COMMAND_UI(ID_POPUP_ANNOUNCE, OnUpdatePopupAnnounce)
	ON_UPDATE_COMMAND_UI(ID_POPUP_EXPLORE_TRACKER, OnUpdatePopupExploreTracker)
	ON_COMMAND(ID_POPUP_UPLOAD_RATE_LIMIT, OnPopupUploadRateLimit)
	ON_UPDATE_COMMAND_UI(ID_POPUP_UPLOAD_RATE_LIMIT, OnUpdatePopupUploadRateLimit)
	ON_UPDATE_COMMAND_UI(ID_POPUP_PRIORITY_EXCLUDE, OnUpdatePopupPriorityExclude)
	ON_UPDATE_COMMAND_UI(ID_POPUP_PRIORITY_HIGH, OnUpdatePopupPriorityHigh)
	ON_UPDATE_COMMAND_UI(ID_POPUP_PRIORITY_LOW, OnUpdatePopupPriorityLow)
	ON_UPDATE_COMMAND_UI(ID_POPUP_PRIORITY_NORMAL, OnUpdatePopupPriorityNormal)
	ON_COMMAND(ID_POPUP_TORRENT_PRIORITY_HIGH, OnPopupTorrentPriorityHigh)
	ON_UPDATE_COMMAND_UI(ID_POPUP_TORRENT_PRIORITY_HIGH, OnUpdatePopupTorrentPriorityHigh)
	ON_COMMAND(ID_POPUP_TORRENT_PRIORITY_LOW, OnPopupTorrentPriorityLow)
	ON_UPDATE_COMMAND_UI(ID_POPUP_TORRENT_PRIORITY_LOW, OnUpdatePopupTorrentPriorityLow)
	ON_COMMAND(ID_POPUP_TORRENT_PRIORITY_NORMAL, OnPopupTorrentPriorityNormal)
	ON_UPDATE_COMMAND_UI(ID_POPUP_TORRENT_PRIORITY_NORMAL, OnUpdatePopupTorrentPriorityNormal)
	ON_COMMAND(ID_POPUP_VIEW_PIECES, OnPopupViewPieces)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_PIECES, OnUpdatePopupViewPieces)
	ON_COMMAND(ID_POPUP_STATE_PAUSED, OnPopupStatePaused)
	ON_UPDATE_COMMAND_UI(ID_POPUP_STATE_PAUSED, OnUpdatePopupStatePaused)
	ON_COMMAND(ID_POPUP_STATE_QUEUED, OnPopupStateQueued)
	ON_UPDATE_COMMAND_UI(ID_POPUP_STATE_QUEUED, OnUpdatePopupStateQueued)
	ON_COMMAND(ID_POPUP_STATE_STARTED, OnPopupStateStarted)
	ON_UPDATE_COMMAND_UI(ID_POPUP_STATE_STARTED, OnUpdatePopupStateStarted)
	ON_COMMAND(ID_POPUP_STATE_STOPPED, OnPopupStateStopped)
	ON_UPDATE_COMMAND_UI(ID_POPUP_STATE_STOPPED, OnUpdatePopupStateStopped)
	ON_COMMAND(ID_POPUP_TORRENT_OPTIONS, OnPopupTorrentOptions)
	ON_UPDATE_COMMAND_UI(ID_POPUP_TORRENT_OPTIONS, OnUpdatePopupTorrentOptions)
	ON_WM_ACTIVATEAPP()
	ON_COMMAND(ID_FILE_EXIT, OnFileExit)
	ON_COMMAND(ID_HELP_ABOUT, OnHelpAbout)
	ON_COMMAND(ID_FILE_OPEN, OnFileOpen)
	ON_COMMAND(ID_FILE_CLOSE, OnFileClose)
	ON_UPDATE_COMMAND_UI(ID_FILE_CLOSE, OnUpdateFileClose)
	ON_COMMAND(ID_EDIT_COPY_ANNOUNCE_URL, OnEditCopyAnnounceUrl)
	ON_UPDATE_COMMAND_UI(ID_EDIT_COPY_ANNOUNCE_URL, OnUpdateEditCopyAnnounceUrl)
	ON_COMMAND(ID_EDIT_COPY_HASH, OnEditCopyHash)
	ON_UPDATE_COMMAND_UI(ID_EDIT_COPY_HASH, OnUpdateEditCopyHash)
	ON_COMMAND(ID_EDIT_COPY_URL, OnEditCopyUrl)
	ON_UPDATE_COMMAND_UI(ID_EDIT_COPY_URL, OnUpdateEditCopyUrl)
	ON_COMMAND(ID_EDIT_PASTE_URL, OnEditPasteUrl)
	ON_COMMAND(ID_FILE_NEW, OnFileNew)
	ON_COMMAND(ID_EDIT_SELECT_ALL, OnEditSelectAll)
	ON_COMMAND(ID_TOOLS_OPTIONS, OnToolsOptions)
	ON_COMMAND(ID_TOOLS_PROFILES, OnToolsProfiles)
	ON_COMMAND(ID_TOOLS_SCHEDULER, OnToolsScheduler)
	ON_COMMAND(ID_TOOLS_TRACKERS, OnToolsTrackers)
	ON_NOTIFY(TCN_SELCHANGE, IDC_TAB, OnSelchangeTab)
	ON_COMMAND(ID_FILE_DELETE, OnFileDelete)
	ON_UPDATE_COMMAND_UI(ID_FILE_DELETE, OnUpdateFileDelete)
	ON_COMMAND(ID_HELP_HOME_PAGE, OnHelpHomePage)
	ON_WM_SYSCOMMAND()
	ON_WM_COPYDATA()
	ON_COMMAND(ID_POPUP_VIEW_GLOBAL_EVENTS, OnPopupViewGlobalEvents)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_GLOBAL_EVENTS, OnUpdatePopupViewGlobalEvents)
	ON_COMMAND(ID_POPUP_VIEW_GLOBAL_DETAILS, OnPopupViewGlobalDetails)
	ON_UPDATE_COMMAND_UI(ID_POPUP_VIEW_GLOBAL_DETAILS, OnUpdatePopupViewGlobalDetails)
	ON_WM_SIZE()
	ON_WM_INITMENU()
	ON_UPDATE_COMMAND_UI(ID_POPUP_EXPLORE, OnUpdatePopupExplore)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg message handlers

BOOL CXBTClientDlg::OnInitDialog()
{
	SetIcon(m_hIcon, true);

	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< item(IDC_FILES)
		<< (paneTab(&m_tab, HORIZONTAL)
			<< item(IDC_PEERS)
			)
		;
	m_tab.InsertItem(v_details, "Details");
	m_tab.InsertItem(v_events, "Events");
	m_tab.InsertItem(v_files, "Files");
	m_tab.InsertItem(v_peers, "Peers");
	m_tab.InsertItem(v_pieces, "Pieces");
	m_tab.InsertItem(v_trackers, "Trackers");
	m_tab.InsertItem(v_global_details, "Global Details");
	m_tab.InsertItem(v_global_events, "Global Events");
	UpdateLayout();
	VERIFY(m_hAccel = LoadAccelerators(AfxGetInstanceHandle(), MAKEINTRESOURCE(IDR_MAINFRAME)));

	m_bottom_view = GetProfileInt("bottom_view", v_peers);
	m_server.admin_port(GetProfileInt("admin_port", m_server.admin_port()));
	m_ask_for_location = GetProfileInt("ask_for_location", false);
	m_server.bind_before_connect(GetProfileInt("bind_before_connect", m_server.bind_before_connect()));
	set_dir(GetProfileString("completes_dir"),
		GetProfileString("incompletes_dir"),
		GetProfileString("local_app_data_dir"),
		GetProfileString("torrents_dir"));
	m_hide_on_deactivate = GetProfileInt("hide_on_deactivate", false);
	lower_process_priority(GetProfileInt("lower_process_priority", false));
	m_server.peer_limit(GetProfileInt("peer_limit", m_server.peer_limit()));
	m_server.peer_port(GetProfileInt("peer_port", m_server.peer_port()));
	string public_ipa = GetProfileString("public_ipa");
	if (!public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(public_ipa));
	m_server.seeding_ratio(GetProfileInt("seeding_ratio", m_server.seeding_ratio()));
	m_server.send_stop_event(GetProfileInt("send_stop_event", m_server.send_stop_event()));
	m_show_advanced_columns = GetProfileInt("show_advanced_columns", false);
	m_show_tray_icon = GetProfileInt("show_tray_icon", true);
	m_server.torrent_limit(GetProfileInt("torrent_limit", m_server.torrent_limit()));
	m_server.tracker_port(GetProfileInt("tracker_port", m_server.tracker_port()));
	m_server.upload_rate(GetProfileInt("upload_rate", m_server.upload_rate()));
	m_server.upload_slots(GetProfileInt("upload_slots", m_server.upload_slots()));
	m_tab.SetCurSel(m_bottom_view);
	start_server();
	insert_columns(true);
	m_events_sort_column = GetProfileInt("events_sort_column", -1);
	m_events_sort_reverse = GetProfileInt("events_sort_reverse", false);
	m_files_sort_column = GetProfileInt("files_sort_column", -1);
	m_files_sort_reverse = GetProfileInt("files_sort_reverse", false);
	m_global_events_sort_column = GetProfileInt("global_events_sort_column", -1);
	m_global_events_sort_reverse = GetProfileInt("global_events_sort_reverse", false);
	m_peers_sort_column = GetProfileInt("peers_sort_column", pc_client);
	m_peers_sort_reverse = GetProfileInt("peers_sort_reverse", false);
	m_pieces_sort_column = GetProfileInt("pieces_sort_column", -1);
	m_pieces_sort_reverse = GetProfileInt("pieces_sort_reverse", false);
	m_torrents_sort_column = GetProfileInt("torrents_sort_column", fc_name);
	m_torrents_sort_reverse = GetProfileInt("torrents_sort_reverse", false);
	m_file = NULL;
	register_hot_key(GetProfileInt("hot_key", (HOTKEYF_CONTROL | HOTKEYF_SHIFT) << 8 |'Q'));
	register_tray();
	SetTimer(0, 1000, NULL);
	SetTimer(1, 60000, NULL);
	CCommandLineInfo cmdInfo;
	AfxGetApp()->ParseCommandLine(cmdInfo);
	if (cmdInfo.m_nShellCommand == CCommandLineInfo::FileOpen)
		open(static_cast<string>(cmdInfo.m_strFileName), m_ask_for_location);
	return true;
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
	string path;
	if (!m_server.incompletes_dir().empty() && !ask_for_location && ~GetAsyncKeyState(VK_SHIFT) < 0)
	{
		path = m_server.completes_dir() + '/' + torrent.name().c_str();
		struct _stati64 b;
		if (_stati64(path.c_str(), &b))
			path = m_server.incompletes_dir() + '/' + torrent.name().c_str();
	}
	else if (torrent.files().size() == 1)
	{
		SetForegroundWindow();
		CFileDialog dlg(false, NULL, torrent.name().c_str(), OFN_HIDEREADONLY | OFN_PATHMUSTEXIST, "All files|*|", this);
		if (!m_server.incompletes_dir().empty())
			dlg.m_ofn.lpstrInitialDir = m_server.incompletes_dir().c_str();
		if (IDOK != dlg.DoModal())
			return;
		path = dlg.GetPathName();
	}
	else
	{
		SetForegroundWindow();
		string path1 = m_server.incompletes_dir();
		if (browse_for_directory(GetSafeHwnd(), torrent.name(), path1))
			return;
		path = path1 + '/' + torrent.name();
	}
	CWaitCursor wc;
	if (!m_server.open(d, path))
		update_tray("Opened", torrent.name().c_str());
}

void CXBTClientDlg::open_url(const string& v)
{
	m_server.open_url(v);
}

static string get_extension(const string& v)
{
	int i = v.rfind('.');
	return i == string::npos ? "" : v.substr(i + 1);
}

static string priority2a(int v)
{
	switch (v)
	{
	case -10:
		return "Excluded";
	case -1:
		return "Low";
	case 0:
		return "";
	case 1:
		return "High";
	}
	return n(v);
}

void CXBTClientDlg::OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_files.get_buffer();
	const t_file& e = m_files_map.find(pDispInfo->item.lParam)->second;
	switch (m_files.GetColumnID(pDispInfo->item.iSubItem))
	{
	case fc_hash:
		buffer = hex_encode(e.m_info_hash);
		break;
	case fc_done:
		if (e.m_size)
			buffer = n((e.m_size - e.m_left) * 100 / e.m_size);
		break;
	case fc_left:
		if (e.m_left)
			buffer = e.m_left == -1 ? "?" : b2a(e.m_left);
		break;
	case fc_size:
		if (e.m_size)
			buffer = b2a(e.m_size);
		break;
	case fc_total_downloaded:
		if (e.m_total_downloaded)
			buffer = b2a(e.m_total_downloaded);
		break;
	case fc_total_downloaded_rel:
		if (e.m_total_downloaded && e.m_size)
			buffer = n(e.m_total_downloaded * 100 / e.m_size);
		break;
	case fc_total_uploaded:
		if (e.m_total_uploaded)
			buffer = b2a(e.m_total_uploaded);
		break;
	case fc_total_uploaded_rel:
		if (e.m_total_uploaded && e.m_size)
			buffer += n(e.m_total_uploaded * 100 / e.m_size);
		break;
	case fc_down_rate:
		if (e.m_down_rate)
			buffer = b2a(e.m_down_rate);
		break;
	case fc_up_rate:
		if (e.m_up_rate)
			buffer = b2a(e.m_up_rate);
		break;
	case fc_leechers:
		if (e.mc_leechers || e.mc_leechers_total)
			buffer = n(e.mc_leechers);
		if (e.mc_leechers_total)
			buffer += " / " + n(e.mc_leechers_total);
		break;
	case fc_peers:
		if (e.mc_leechers || e.mc_leechers_total || e.mc_seeders || e.mc_seeders_total)
			buffer = n(e.mc_leechers + e.mc_seeders);
		if (e.mc_leechers_total || e.mc_seeders_total)
			buffer += " / " + n(e.mc_leechers_total + e.mc_seeders_total);
		break;
	case fc_priority:
		if (e.m_priority)
			buffer = priority2a(e.m_priority);
		break;
	case fc_seeders:
		if (e.mc_seeders || e.mc_seeders_total)
			buffer = n(e.mc_seeders);
		if (e.mc_seeders_total)
			buffer += " / " + n(e.mc_seeders_total);
		break;
	case fc_state:
		switch (e.m_state)
		{
		case Cbt_file::s_queued:
			buffer = "Queued";
			break;
		case Cbt_file::s_hashing:
			buffer = "Hashing";
			break;
		case Cbt_file::s_running:
			buffer = "Running";
			break;
		case Cbt_file::s_paused:
			buffer = "Paused";
			break;
		case Cbt_file::s_stopped:
			buffer = "Stopped";
			break;
		}
		break;
	case fc_name:
		buffer = e.m_display_name;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoDetails(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const char* row_names[] =
	{
		"Chunks",
		"Completed at",
		"Distributed Copies",
		"Downloaded",
		"Downloaded (layer 5 overhead)",
		"Files",
		"Hash",
		"Leechers",
		"Left",
		"Name",
		"Peers",
		"Pieces",
		"Rejected Chunks",
		"Rejected Pieces",
		"Seeders",
		"Seeding Ratio",
		"Size",
		"Started at",
		"Tracker",
		"Upload Slots Min",
		"Upload Slots Max",
		"Uploaded",
		"Uploaded (layer 5 overhead)",
	};
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case dc_name:
		buffer = row_names[pDispInfo->item.iItem];
		break;
	case dc_value:
		switch (pDispInfo->item.iItem)
		{
		case dr_chunks:
			if (!m_file->mc_valid_chunks)
				break;
			buffer = n(m_file->mc_valid_chunks) + " / " + n(m_file->mc_invalid_chunks + m_file->mc_valid_chunks) + " x " + b2a(m_file->mcb_chunk, "b")
				+ " = " + b2a(m_file->mc_valid_chunks * m_file->mcb_chunk, "b") + " / " + b2a((m_file->mc_invalid_chunks + m_file->mc_valid_chunks) * m_file->mcb_chunk, "b");
			break;
		case dr_completed_at:
			if (m_file->m_completed_at)
				buffer = time2a(m_file->m_completed_at) + " (" + duration2a(time(NULL) - m_file->m_completed_at) + " ago)";
			else if (m_file->m_downloaded && m_file->m_left && time(NULL) - m_file->m_session_started_at > 300)
			{
				int duration = m_file->m_left * (time(NULL) - m_file->m_session_started_at) / m_file->m_downloaded;
				buffer = time2a(duration + time(NULL)) + " (" + duration2a(duration) + " to go, estimated)";
			}
			break;
		case dr_distributed_copies:
			if (m_file->mc_distributed_copies || m_file->mc_distributed_copies_remainder)
				buffer = n(m_file->mc_distributed_copies) + " + " + n(m_file->mc_distributed_copies_remainder) + " / " + n(m_file->mc_invalid_pieces + m_file->mc_valid_pieces);
			break;
		case dr_downloaded:
			buffer = b2a(m_file->m_downloaded, "b");
			if (m_file->m_total_downloaded != m_file->m_downloaded)
				buffer += " / " + b2a(m_file->m_total_downloaded, "b");
			if (m_file->m_size)
				buffer += " (" + n(m_file->m_total_downloaded * 100 / m_file->m_size) + " %)";
			break;
		case dr_downloaded_l5_overhead:
			buffer = b2a(m_file->m_downloaded_l5 - m_file->m_downloaded, "b");
			break;
		case dr_files:
			buffer = n(m_file->m_sub_files.size());
			break;
		case dr_hash:
			buffer = hex_encode(m_file->m_info_hash);
			break;
		case dr_leechers:
			buffer = n(m_file->mc_leechers);
			if (m_file->mc_leechers_total)
				buffer += " / " + n(m_file->mc_leechers_total);
			break;
		case dr_left:
			if (m_file->m_left)
				buffer = b2a(m_file->m_left, "b");
			break;
		case dr_name:
			buffer = m_file->m_name;
			break;
		case dr_peers:
			buffer = n(m_file->mc_leechers + m_file->mc_seeders);
			if (m_file->mc_leechers_total + m_file->mc_seeders_total)
				buffer += " / " + n(m_file->mc_leechers_total + m_file->mc_seeders_total);
			break;
		case dr_pieces:
			buffer = n(m_file->mc_valid_pieces) + " / " + n(m_file->mc_invalid_pieces + m_file->mc_valid_pieces) + " x " + b2a(m_file->mcb_piece, "b");
			break;
		case dr_rejected_chunks:
			if (m_file->mc_rejected_chunks)
				buffer = n(m_file->mc_rejected_chunks) + " x " + b2a(m_file->mcb_chunk, "b") + " = " + b2a(m_file->mc_rejected_chunks * m_file->mcb_chunk, "b");
			break;
		case dr_rejected_pieces:
			if (m_file->mc_rejected_pieces)
				buffer = n(m_file->mc_rejected_pieces) + " x " + b2a(m_file->mcb_piece, "b") + " = " + b2a(m_file->mc_rejected_pieces * m_file->mcb_piece, "b");
			break;
		case dr_seeders:
			buffer = n(m_file->mc_seeders);
			if (m_file->mc_seeders_total)
				buffer += " / " + n(m_file->mc_seeders_total);
			break;
		case dr_seeding_ratio:
			if (m_file->m_seeding_ratio)
				buffer = n(m_file->m_seeding_ratio) + " %";
			break;
		case dr_size:
			buffer = b2a(m_file->m_size, "b");
			break;
		case dr_started_at:
			if (m_file->m_started_at)
				buffer = time2a(m_file->m_started_at) + " (" + duration2a(time(NULL) - m_file->m_started_at) + " ago)";
			break;
		case dr_tracker:
			if (!m_file->m_trackers.empty())
				buffer = m_file->m_trackers.front().url;
			break;
		case dr_upload_slots_max:
			if (m_file->m_upload_slots_max)
				buffer = n(m_file->m_upload_slots_max);
			break;
		case dr_upload_slots_min:
			if (m_file->m_upload_slots_min)
				buffer = n(m_file->m_upload_slots_min);
			break;
		case dr_uploaded:
			buffer = b2a(m_file->m_uploaded, "b");
			if (m_file->m_total_uploaded != m_file->m_uploaded)
				buffer += " / " + b2a(m_file->m_total_uploaded, "b");
			if (m_file->m_size)
				buffer += " (" + n(m_file->m_total_uploaded * 100 / m_file->m_size) + " %)";
			break;
		case dr_uploaded_l5_overhead:
			buffer = b2a(m_file->m_uploaded_l5 - m_file->m_uploaded, "b");
			break;
		}
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoGlobalDetails(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const char* row_names[] =
	{
		"Downloaded",
		"Down Rate",
		"Files",
		"Leechers",
		"Left",
		"Peers",
		"Seeders",
		"Size",
		"Started at",
		"Torrents Complete",
		"Torrents Incomplete",
		"Uploaded",
		"Up Rate",
	};
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case gdc_name:
		buffer = row_names[pDispInfo->item.iItem];
		break;
	case gdc_value:
		switch (pDispInfo->item.iItem)
		{
		case gdr_downloaded:
			buffer = b2a(m_global_details.m_downloaded, "b");
			if (m_global_details.m_downloaded_total != m_global_details.m_downloaded)
				buffer += " / " + b2a(m_global_details.m_downloaded_total, "b");
			if (m_global_details.m_size)
				buffer += " (" + n(m_global_details.m_downloaded_total * 100 / m_global_details.m_size) + " %)";
			break;
		case gdr_down_rate:
			buffer = b2a(m_global_details.m_down_rate, "b/s");
			break;
		case gdr_files:
			buffer = n(m_global_details.mc_files);
			break;
		case gdr_leechers:
			buffer = n(m_global_details.mc_leechers);
			break;
		case gdr_left:
			buffer = b2a(m_global_details.m_left, "b");
			break;
		case gdr_peers:
			buffer = n(m_global_details.mc_leechers + m_global_details.mc_seeders);
			break;
		case gdr_seeders:
			buffer = n(m_global_details.mc_seeders);
			break;
		case gdr_size:
			buffer = b2a(m_global_details.m_size, "b");
			break;
		case gdr_started_at:
			buffer = time2a(m_global_details.m_start_time) + " (" + duration2a(time(NULL) - m_global_details.m_start_time) + " ago)";
			break;
		case gdr_torrents_complete:
			buffer = n(m_global_details.mc_torrents_complete);
			break;
		case gdr_torrents_incomplete:
			buffer = n(m_global_details.mc_torrents_incomplete);
			break;
		case gdr_uploaded:
			buffer = b2a(m_global_details.m_uploaded, "b");
			if (m_global_details.m_uploaded_total != m_global_details.m_uploaded)
				buffer += " / " + b2a(m_global_details.m_uploaded_total, "b");
			if (m_global_details.m_size)
				buffer += " (" + n(m_global_details.m_uploaded_total * 100 / m_global_details.m_size) + " %)";
			break;
		case gdr_up_rate:
			buffer = b2a(m_global_details.m_up_rate, "b/s");
			break;
		}
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoEvents(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_event& e = m_file->events[pDispInfo->item.lParam];
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case ec_time:
		{
			tm* time = localtime(&e.time);
			if (!time)
				break;
			char time_string[16];
			sprintf(time_string, "%02d:%02d:%02d", time->tm_hour, time->tm_min, time->tm_sec);
			buffer = time_string;
		}
		break;
	case ec_level:
		buffer = n(e.level);
		break;
	case ec_source:
		buffer = e.source;
		break;
	case ec_message:
		buffer = e.message;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoGlobalEvents(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_event& e = m_events[pDispInfo->item.lParam];
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case gec_time:
		{
			tm* time = localtime(&e.time);
			if (!time)
				break;
			char time_string[16];
			sprintf(time_string, "%02d:%02d:%02d", time->tm_hour, time->tm_min, time->tm_sec);
			buffer = time_string;
		}
		break;
	case gec_level:
		buffer = n(e.level);
		break;
	case gec_source:
		buffer = e.source;
		break;
	case gec_message:
		buffer = e.message;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
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
	case v_pieces:
		OnGetdispinfoPieces(pNMHDR, pResult);
		return;
	case v_trackers:
		OnGetdispinfoTrackers(pNMHDR, pResult);
		return;
	case v_global_details:
		OnGetdispinfoGlobalDetails(pNMHDR, pResult);
		return;
	case v_global_events:
		OnGetdispinfoGlobalEvents(pNMHDR, pResult);
		return;
	}
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_peer& e = m_file->peers.find(pDispInfo->item.lParam)->second;
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case pc_host:
		buffer = inet_ntoa(e.m_host);
		break;
	case pc_port:
		buffer = n(ntohs(e.m_port));
		break;
	case pc_done:
		if (m_file->m_size)
			buffer = n((m_file->m_size - e.m_left) * 100 / m_file->m_size);
		break;
	case pc_left:
		if (e.m_left)
			buffer = b2a(e.m_left);
		break;
	case pc_downloaded:
		if (e.m_downloaded)
			buffer = b2a(e.m_downloaded);
		break;
	case pc_uploaded:
		if (e.m_uploaded)
			buffer = b2a(e.m_uploaded);
		break;
	case pc_down_rate:
		if (e.m_down_rate)
			buffer = b2a(e.m_down_rate);
		break;
	case pc_up_rate:
		if (e.m_up_rate)
			buffer = b2a(e.m_up_rate);
		break;
	case pc_link_direction:
		buffer = e.m_local_link ? 'L' : 'R';
		break;
	case pc_local_choked:
		if (e.m_local_choked)
			buffer = 'C';
		break;
	case pc_local_interested:
		if (e.m_local_interested)
			buffer = 'I';
		break;
	case pc_local_requests:
		if (e.mc_local_requests)
			buffer = n(e.mc_local_requests);
		break;
	case pc_remote_choked:
		if (e.m_remote_choked)
			buffer = 'C';
		break;
	case pc_remote_interested:
		if (e.m_remote_interested)
			buffer = 'I';
		break;
	case pc_remote_requests:
		if (e.mc_remote_requests)
			buffer = n(e.mc_remote_requests);
		break;
	case pc_pieces:
		if (e.mc_pieces)
			buffer = n(e.mc_pieces);
		break;
	case pc_recv_time:
		buffer = n(time(NULL) - e.m_rtime);
		break;
	case pc_send_time:
		buffer = n(time(NULL) - e.m_stime);
		break;
	case pc_peer_id:
		buffer = hex_encode(e.m_remote_peer_id);
		break;
	case pc_client:
		buffer = peer_id2a(e.m_remote_peer_id);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoPieces(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_piece& e = m_file->pieces[pDispInfo->item.lParam];
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case pic_index:
		buffer = n(e.index);
		break;
	case pic_c_chunks:
		if (e.c_chunks_valid)
			buffer = n(e.c_chunks_valid) + " / " + n(e.c_chunks_invalid + e.c_chunks_valid);
		break;
	case pic_c_peers:
		if (e.c_peers)
			buffer = n(e.c_peers);
		break;
	case pic_priority:
		if (e.m_priority)
			buffer = priority2a(e.m_priority);
		break;
	case pic_valid:
		if (e.m_valid)
			buffer = 'V';
		break;
	case pic_rank:
		if (e.rank != INT_MAX)
			buffer = n(e.rank);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoSubFiles(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_sub_file& e = m_file->m_sub_files[pDispInfo->item.lParam];
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case sfc_name:
		if (e.m_name.empty())
		{
			int i = m_file->m_name.rfind('\\');
			buffer = i == string::npos ? m_file->m_name : m_file->m_name.substr(i + 1);
		}
		else
			buffer = e.m_name;
		break;
	case sfc_extension:
		buffer = get_extension(e.m_name);
		break;
	case sfc_done:
		if (e.m_size)
			buffer = n((e.m_size - e.m_left) * 100 / e.m_size);
		break;
	case sfc_left:
		if (e.m_left)
			buffer = b2a(e.m_left);
		break;
	case sfc_size:
		buffer = b2a(e.m_size);
		break;
	case sfc_priority:
		if (e.m_priority)
			buffer = priority2a(e.m_priority);
		break;
	case sfc_hash:
		buffer = hex_encode(e.m_merkle_hash);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::OnGetdispinfoTrackers(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (!m_file)
		return;
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	string& buffer = m_peers.get_buffer();
	const t_tracker& e = m_file->m_trackers[pDispInfo->item.lParam];
	switch (m_peers.GetColumnID(pDispInfo->item.iSubItem))
	{
	case tc_url:
		buffer = e.url;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void CXBTClientDlg::fill_peers()
{
	m_peers.DeleteAllItems();
	if (!m_file)
	{
		m_peers.auto_size();
		return;
	}
	m_peers.SetRedraw(false);
	switch (m_bottom_view)
	{
	case v_details:
		for (int i = 0; i < dr_count; i++)
			m_peers.InsertItemData(i);
		break;
	case v_events:
		for (int i = 0; i < m_file->events.size(); i++)
			m_peers.InsertItemData(0, i);
		break;
	case v_files:
		for (int i = 0; i < m_file->m_sub_files.size(); i++)
			m_peers.InsertItemData(i);
		break;
	case v_peers:
		for (t_peers::const_iterator i = m_file->peers.begin(); i != m_file->peers.end(); i++)
			m_peers.InsertItemData(i->first);
		break;
	case v_pieces:
		for (int i = 0; i < m_file->pieces.size(); i++)
			m_peers.InsertItemData(i);
		break;
	case v_trackers:
		for (t_trackers::const_iterator i = m_file->m_trackers.begin(); i != m_file->m_trackers.end(); i++)
			m_peers.InsertItemData(i - m_file->m_trackers.begin());
		break;
	case v_global_details:
		for (int i = 0; i < gdr_count; i++)
			m_peers.InsertItemData(i);
		break;
	case v_global_events:
		for (int i = 0; i < m_events.size(); i++)
			m_peers.InsertItemData(0, i);
		break;
	}
	sort_peers();
	m_peers.auto_size();
	m_peers.SetRedraw(true);
	m_peers.Invalidate();
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
			i->second.m_removed = true;
	}
	m_events.clear();
	for (int c_alerts = sr.read_int(4); c_alerts--; )
	{
		t_event e;
		e.time = sr.read_int(4);
		e.level = sr.read_int(4);
		e.message = sr.read_string();
		e.source = sr.read_string();
		m_events.push_back(e);
	}
	{
		int c_files = sr.read_int(4);
		for (int i = 0; i < c_files; i++)
			read_file_dump(sr);
	}
	m_global_details.m_start_time = sr.read_int(4);
	if (m_bottom_view == v_global_events)
	{
		while (m_peers.GetItemCount() < m_events.size())
			m_peers.InsertItemData(0, m_peers.GetItemCount());
	}
	{
		for (t_files::iterator i = m_files_map.begin(); i != m_files_map.end(); )
		{
			if (i->second.m_removed)
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
	update_global_details();
}

void CXBTClientDlg::read_file_dump(Cstream_reader& sr)
{
	bool inserted = false;
	string info_hash = sr.read_string();
	t_files::iterator i;
	for (i = m_files_map.begin(); i != m_files_map.end(); i++)
	{
		if (i->second.m_info_hash == info_hash)
			break;
	}
	int id;
	if (i == m_files_map.end())
	{
		m_files_map[id = m_files_map.empty() ? 0 : m_files_map.rbegin()->first + 1];
		m_files.InsertItemData(id);
		inserted = true;
	}
	else
		id = i->first;
	t_file& f = m_files_map.find(id)->second;
	f.m_display_name = f.m_name = backward_slashes(sr.read_string());
	f.m_info_hash = info_hash;
	f.m_trackers.clear();
	for (int c_trackers = sr.read_int(4); c_trackers--; )
	{
		t_tracker e;
		e.url = sr.read_string();
		f.m_trackers.push_back(e);
	}
	f.m_downloaded = sr.read_int(8);
	f.m_downloaded_l5 = sr.read_int(8);
	f.m_left = sr.read_int(8);
	f.m_size = sr.read_int(8);
	f.m_uploaded = sr.read_int(8);
	f.m_uploaded_l5 = sr.read_int(8);
	f.m_total_downloaded = sr.read_int(8);
	f.m_total_uploaded = sr.read_int(8);
	f.m_down_rate = sr.read_int(4);
	f.m_up_rate = sr.read_int(4);
	f.mc_leechers = sr.read_int(4);
	f.mc_seeders = sr.read_int(4);
	f.mc_leechers_total = sr.read_int(4);
	f.mc_seeders_total = sr.read_int(4);
	f.mc_invalid_chunks = sr.read_int(4);
	f.mc_invalid_pieces = sr.read_int(4);
	f.mc_rejected_chunks = sr.read_int(4);
	f.mc_rejected_pieces = sr.read_int(4);
	f.mc_valid_chunks = sr.read_int(4);
	f.mc_valid_pieces = sr.read_int(4);
	f.mcb_chunk = sr.read_int(4);
	f.mcb_piece = sr.read_int(4);
	f.m_state = static_cast<Cbt_file::t_state>(sr.read_int(4));
	f.m_started_at = sr.read_int(4);
	f.m_session_started_at = sr.read_int(4);
	f.m_completed_at = sr.read_int(4);
	f.mc_distributed_copies = sr.read_int(4);
	f.mc_distributed_copies_remainder = sr.read_int(4);
	f.m_priority = sr.read_int(4);
	f.m_allow_end_mode = sr.read_int(4);
	f.m_seeding_ratio = sr.read_int(4);
	f.m_seeding_ratio_override = sr.read_int(4);
	f.m_upload_slots_max = sr.read_int(4);
	f.m_upload_slots_max_override = sr.read_int(4);
	f.m_upload_slots_min = sr.read_int(4);
	f.m_upload_slots_min_override = sr.read_int(4);
	f.m_removed = false;
	{
		int i = f.m_display_name.rfind('\\');
		if (i != string::npos)
			f.m_display_name.erase(0, i + 1);
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); i++)
			i->second.m_removed = true;
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
	f.m_sub_files.clear();
	for (int c_files = sr.read_int(4); c_files--; )
	{
		t_sub_file e;
		e.m_left = sr.read_int(8);
		e.m_name = sr.read_string();
		e.m_priority = sr.read_int(4);
		e.m_size = sr.read_int(8);
		e.m_merkle_hash = sr.read_string();
		f.m_sub_files.push_back(e);
	}
	f.pieces.clear();
	int index = 0;
	for (int c_pieces = sr.read_int(4); c_pieces--; )
	{
		t_piece e;
		e.c_chunks_invalid = sr.read_int(1);
		e.c_chunks_valid = sr.read_int(1);
		e.c_peers = sr.read_int(4);
		e.index = index++;
		e.m_priority = static_cast<char>(sr.read_int(1));
		e.rank = sr.read_int(4);
		e.m_valid = sr.read_int(1);
		if (1 || !e.m_valid)
			f.pieces.push_back(e);
	}
	if (m_file == &f)
	{
		switch (m_bottom_view)
		{
		case v_events:
			while (m_peers.GetItemCount() < f.events.size())
			{
				m_peers.InsertItemData(0, m_peers.GetItemCount());
				inserted = true;
			}
			while (m_peers.GetItemCount() > f.events.size())
				m_peers.DeleteItem(0);
			break;
		case v_files:
			while (m_peers.GetItemCount() < f.m_sub_files.size())
			{
				m_peers.InsertItemData(m_peers.GetItemCount());
				inserted = true;
			}
			break;
		case v_trackers:
			while (m_peers.GetItemCount() < f.m_trackers.size())
			{
				m_peers.InsertItemData(m_peers.GetItemCount());
				inserted = true;
			}
			while (m_peers.GetItemCount() > f.m_trackers.size())
				m_peers.DeleteItem(m_peers.GetItemCount() - 1);
			break;
		}
	}
	{
		for (t_peers::iterator i = f.peers.begin(); i != f.peers.end(); )
		{
			if (i->second.m_removed)
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
		m_files.auto_size();
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
	p.m_host.s_addr = htonl(sr.read_int(4));
	p.m_port = htons(sr.read_int(4));
	p.m_remote_peer_id = sr.read_string();
	p.m_downloaded = sr.read_int(8);
	p.m_left = sr.read_int(8);
	p.m_uploaded = sr.read_int(8);
	p.m_down_rate = sr.read_int(4);
	p.m_up_rate = sr.read_int(4);
	p.m_local_link = sr.read_int(1);
	p.m_local_choked = sr.read_int(1);
	p.m_local_interested = sr.read_int(1);
	p.mc_local_requests = sr.read_int(4);
	p.m_remote_choked = sr.read_int(1);
	p.m_remote_interested = sr.read_int(1);
	p.mc_remote_requests = sr.read_int(4);
	p.mc_pieces = sr.read_int(4);
	p.m_rtime = sr.read_int(4);
	p.m_stime = sr.read_int(4);
	if (p.m_remote_peer_id.empty())
		return;
	t_peers::iterator i;
	for (i = f.peers.begin(); i != f.peers.end(); i++)
	{
		if (i->second.m_host.s_addr == p.m_host.s_addr)
			break;
	}
	int id;
	if (i == f.peers.end())
	{
		f.peers[id = f.peers.empty() ? 0 : f.peers.rbegin()->first + 1];
		if (m_bottom_view == v_peers && m_file == &f)
		{
			m_peers.InsertItemData(id);
			inserted = true;
		}
	}
	else
		id = i->first;
	p.m_removed = false;
	f.peers.find(id)->second = p;
	if (inserted)
		m_peers.auto_size();
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
		read_server_dump(Cstream_reader(m_server.get_status(Cserver::df_alerts | Cserver::df_files | Cserver::df_peers | Cserver::df_pieces | Cserver::df_trackers)));
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

void CXBTClientDlg::OnContextMenu(CWnd* pWnd, CPoint point)
{
	if (point.x == -1)
		GetCursorPos(&point);

	CMenu menu;
	if (pWnd == &m_peers)
	{
		switch (m_bottom_view)
		{
		case v_files:
			VERIFY(menu.LoadMenu(IDR_POPUP_BOTTOM_FILES));
			break;
		default:
			VERIFY(menu.LoadMenu(IDR_POPUP_BOTTOM));
		}
	}
	else
		VERIFY(menu.LoadMenu(CG_IDR_POPUP_XBTCLIENT_DLG));

	CMenu* pPopup = menu.GetSubMenu(0);
	ASSERT(pPopup);
	CWnd* pWndPopupOwner = this;

	while (pWndPopupOwner->GetStyle() & WS_CHILD)
		pWndPopupOwner = pWndPopupOwner->GetParent();

	pPopup->TrackPopupMenu(TPM_LEFTALIGN | TPM_RIGHTBUTTON, point.x, point.y, pWndPopupOwner);
}

void CXBTClientDlg::OnTrayMenu()
{
	CPoint point;
	GetCursorPos(&point);
	SetForegroundWindow();

	CMenu menu;
	VERIFY(menu.LoadMenu(IDR_TRAY));

	CMenu* pPopup = menu.GetSubMenu(0);
	ASSERT(pPopup);
	CWnd* pWndPopupOwner = this;

	while (pWndPopupOwner->GetStyle() & WS_CHILD)
		pWndPopupOwner = pWndPopupOwner->GetParent();

	pPopup->TrackPopupMenu(TPM_LEFTALIGN | TPM_RIGHTBUTTON, point.x, point.y, pWndPopupOwner);
}

void CXBTClientDlg::OnPopupExplore()
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id == -1)
	{
		ShellExecute(m_hWnd, "open", backward_slashes(m_server.completes_dir()).c_str(), NULL, NULL, SW_SHOW);
		return;
	}
	string name = m_files_map.find(id)->second.m_name;
	struct _stati64 b;
	if (_stati64(name.c_str(), &b) || ~b.st_mode & S_IFDIR)
	{
		int i = name.rfind('\\');
		if (i != string::npos)
			name.erase(i);
	}
	ShellExecute(m_hWnd, "open", name.c_str(), NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnUpdatePopupExplore(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnPopupExploreTracker()
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id == -1)
		return;
	const t_file& f = m_files_map.find(id)->second;
	if (f.m_trackers.empty())
		return;
	Cbt_tracker_url url = f.m_trackers.front().url;
	ShellExecute(m_hWnd, "open", ("http://" + url.m_host + "/?info_hash=" + uri_encode(f.m_info_hash)).c_str(), NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnUpdatePopupExploreTracker(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnPopupAnnounce()
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.announce(m_files_map.find(m_files.GetItemData(index))->second.m_info_hash);
}

void CXBTClientDlg::OnUpdatePopupAnnounce(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
}

void CXBTClientDlg::OnPopupTorrentOptions()
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id == -1)
		return;
	t_file& f = m_files_map.find(id)->second;
	Cdlg_torrent_options dlg;
	Cdlg_torrent_options::t_data data;
	for (t_trackers::const_iterator i = f.m_trackers.begin(); i != f.m_trackers.end(); i++)
		data.trackers += i->url + "\r\n";
	data.end_mode = f.m_allow_end_mode;
	data.seeding_ratio = f.m_seeding_ratio;
	data.seeding_ratio_override = f.m_seeding_ratio_override;
	data.upload_slots_max = f.m_upload_slots_max;
	data.upload_slots_max_override = f.m_upload_slots_max_override;
	data.upload_slots_min = f.m_upload_slots_min;
	data.upload_slots_min_override = f.m_upload_slots_min_override;
	dlg.set(data);
	if (IDOK != dlg.DoModal())
		return;
	data = dlg.get();
	m_server.torrent_end_mode(f.m_info_hash, data.end_mode);
	m_server.torrent_seeding_ratio(f.m_info_hash, data.seeding_ratio_override, data.seeding_ratio);
	m_server.torrent_trackers(f.m_info_hash, data.trackers);
	m_server.torrent_upload_slots_max(f.m_info_hash, data.upload_slots_max_override, data.upload_slots_max);
	m_server.torrent_upload_slots_min(f.m_info_hash, data.upload_slots_min_override, data.upload_slots_min);
}

void CXBTClientDlg::OnUpdatePopupTorrentOptions(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnDblclkFiles(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (GetKeyState(VK_MENU) < 0)
		OnPopupTorrentOptions();
	else
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
			return true;
		case VK_RETURN:
			TranslateAccelerator(m_hWnd, m_hAccel, pMsg);
			return true;
		}
	}
	return TranslateAccelerator(m_hWnd, m_hAccel, pMsg)
		|| ETSLayoutDialog::PreTranslateMessage(pMsg);
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
	nid.hIcon = static_cast<HICON>(LoadImage(AfxGetResourceHandle(), MAKEINTRESOURCE(IDR_MAINFRAME), IMAGE_ICON, 16, 16, LR_DEFAULTCOLOR | LR_SHARED));
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
	char b[256];
	if (m_global_details.m_size)
		sprintf(b, "%d %%, %s left, %d peers, %s down, %s up, %d leechers, %d seeders - ", static_cast<int>((m_global_details.m_size - m_global_details.m_left) * 100 / m_global_details.m_size), b2a(m_global_details.m_left).c_str(), m_global_details.mc_leechers + m_global_details.mc_seeders, b2a(m_global_details.m_down_rate).c_str(), b2a(m_global_details.m_up_rate).c_str(), m_global_details.mc_leechers, m_global_details.mc_seeders);
	else
		*b = 0;
	strcat(b, "XBT Client ");
	strcat(b, xbt_version2a(Cserver::version()).c_str());
	SetWindowText(b);
	if (!m_show_tray_icon)
		return;
	NOTIFYICONDATA nid;
	nid.cbSize = NOTIFYICONDATA_V1_SIZE;
	nid.hWnd = GetSafeHwnd();
	nid.uID = 0;
	nid.uFlags = NIF_TIP;
	if (m_global_details.m_size)
		sprintf(nid.szTip, "%d %%, %s left, %d leechers, %d seeders - XBT Client", static_cast<int>((m_global_details.m_size - m_global_details.m_left) * 100 / m_global_details.m_size), b2a(m_global_details.m_left).c_str(), m_global_details.mc_leechers, m_global_details.mc_seeders);
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
static int compare(const T& a, const T& b, int c = 0)
{
	return a < b ? -1 : a == b ? c : 1;
}

int CXBTClientDlg::files_compare(int id_a, int id_b) const
{
	if (m_torrents_sort_reverse)
		swap(id_a, id_b);
	const t_file& a = m_files_map.find(id_a)->second;
	const t_file& b = m_files_map.find(id_b)->second;
	switch (m_torrents_sort_column)
	{
	case fc_hash:
		return compare(a.m_info_hash, b.m_info_hash);
	case fc_done:
		return compare(b.m_size ? b.m_left * 1000 / b.m_size : 0, a.m_size ? a.m_left * 1000 / a.m_size : 0);
	case fc_left:
		return compare(a.m_left, b.m_left);
	case fc_size:
		return compare(a.m_size, b.m_size);
	case fc_total_downloaded:
		return compare(b.m_total_downloaded, a.m_total_downloaded);
	case fc_total_downloaded_rel:
		return compare(b.m_size ? b.m_total_downloaded * 1000 / b.m_size : 0, a.m_size ? a.m_total_downloaded * 1000 / a.m_size : 0);
	case fc_total_uploaded:
		return compare(b.m_total_uploaded, a.m_total_uploaded);
	case fc_total_uploaded_rel:
		return compare(b.m_size ? b.m_total_uploaded * 1000 / b.m_size : 0, a.m_size ? a.m_total_uploaded * 1000 / a.m_size : 0);
	case fc_down_rate:
		return compare(b.m_down_rate, a.m_down_rate);
	case fc_up_rate:
		return compare(b.m_up_rate, a.m_up_rate);
	case fc_leechers:
		return compare(a.mc_leechers, b.mc_leechers);
	case fc_seeders:
		return compare(a.mc_seeders, b.mc_seeders);
	case fc_peers:
		return compare(a.mc_leechers + a.mc_seeders, b.mc_leechers + b.mc_seeders);
	case fc_state:
		return compare(a.m_state, b.m_state);
	case fc_name:
		return compare(a.m_display_name, b.m_display_name);
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
	m_torrents_sort_reverse = m_files.GetColumnID(pNMListView->iSubItem) == m_torrents_sort_column && !m_torrents_sort_reverse;
	m_torrents_sort_column = m_files.GetColumnID(pNMListView->iSubItem);
	WriteProfileInt("torrents_sort_column", m_torrents_sort_column);
	WriteProfileInt("torrents_sort_reverse", m_torrents_sort_reverse);
	sort_files();
	*pResult = 0;
}

int CXBTClientDlg::events_compare(int id_a, int id_b) const
{
	if (!m_file)
		return 0;
	if (m_events_sort_reverse)
		swap(id_a, id_b);
	const t_event& a = m_file->events[id_a];
	const t_event& b = m_file->events[id_b];
	switch (m_events_sort_column)
	{
	case ec_time:
		return compare(id_b, id_a);
	case ec_level:
		return compare(a.level, b.level);
	case ec_source:
		return compare(a.source, b.source);
	case ec_message:
		return compare(a.message, b.message);
	}
	return 0;
}

int CXBTClientDlg::global_events_compare(int id_a, int id_b) const
{
	if (m_global_events_sort_reverse)
		swap(id_a, id_b);
	const t_event& a = m_events[id_a];
	const t_event& b = m_events[id_b];
	switch (m_global_events_sort_column)
	{
	case gec_time:
		return compare(id_b, id_a);
	case gec_level:
		return compare(a.level, b.level);
	case gec_source:
		return compare(a.source, b.source);
	case gec_message:
		return compare(a.message, b.message);
	}
	return 0;
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
		return compare(ntohl(a.m_host.s_addr), ntohl(b.m_host.s_addr));
	case pc_port:
		return compare(ntohs(a.m_port), ntohs(b.m_port));
	case pc_done:
		return compare(b.m_left, a.m_left);
	case pc_left:
		return compare(a.m_left, b.m_left);
	case pc_downloaded:
		return compare(b.m_downloaded, a.m_downloaded);
	case pc_uploaded:
		return compare(b.m_uploaded, a.m_uploaded);
	case pc_down_rate:
		return compare(b.m_down_rate, a.m_down_rate);
	case pc_up_rate:
		return compare(b.m_up_rate, a.m_up_rate);
	case pc_link_direction:
		return compare(a.m_local_link, b.m_local_link);
	case pc_local_choked:
		return compare(a.m_local_choked, b.m_local_choked);
	case pc_local_interested:
		return compare(a.m_local_interested, b.m_local_interested);
	case pc_local_requests:
		return compare(b.mc_local_requests, a.mc_local_requests);
	case pc_remote_choked:
		return compare(a.m_remote_choked, b.m_remote_choked);
	case pc_remote_interested:
		return compare(a.m_remote_interested, b.m_remote_interested);
	case pc_remote_requests:
		return compare(b.mc_remote_requests, a.mc_remote_requests);
	case pc_pieces:
		return compare(b.mc_pieces, a.mc_pieces);
	case pc_recv_time:
		return compare(b.m_rtime, a.m_rtime);
	case pc_send_time:
		return compare(b.m_stime, a.m_stime);
	case pc_peer_id:
		return compare(a.m_remote_peer_id, b.m_remote_peer_id);
	case pc_client:
		return compare(peer_id2a(a.m_remote_peer_id), peer_id2a(b.m_remote_peer_id));
	}
	return 0;
}

int CXBTClientDlg::pieces_compare(int id_a, int id_b) const
{
	if (!m_file)
		return 0;
	if (m_pieces_sort_reverse)
		swap(id_a, id_b);
	const t_piece& a = m_file->pieces[id_a];
	const t_piece& b = m_file->pieces[id_b];
	switch (m_pieces_sort_column)
	{
	case pic_index:
		return compare(a.index, b.index);
	case pic_c_chunks:
		return compare(b.c_chunks_valid, a.c_chunks_valid, compare(a.rank, b.rank));
	case pic_c_peers:
		return compare(a.c_peers, b.c_peers, compare(a.rank, b.rank));
	case pic_priority:
		return compare(a.m_priority, b.m_priority, compare(a.rank, b.rank));
	case pic_valid:
		return compare(a.m_valid, b.m_valid, compare(a.rank, b.rank));
	case pic_rank:
		return compare(a.rank, b.rank);
	}
	return 0;
}

int CXBTClientDlg::sub_files_compare(int id_a, int id_b) const
{
	if (!m_file)
		return 0;
	if (m_files_sort_reverse)
		swap(id_a, id_b);
	const t_sub_file& a = m_file->m_sub_files[id_a];
	const t_sub_file& b = m_file->m_sub_files[id_b];
	switch (m_files_sort_column)
	{
	case sfc_name:
		return compare(a.m_name, b.m_name);
	case sfc_extension:
		return compare(get_extension(a.m_name), get_extension(b.m_name), compare(a.m_name, b.m_name));
	case sfc_done:
		return compare(b.m_left * 1000 / b.m_size, a.m_left * 1000 / a.m_size);
	case sfc_left:
		return compare(a.m_left, b.m_left);
	case sfc_size:
		return compare(a.m_size, b.m_size);
	case sfc_priority:
		return compare(b.m_priority, a.m_priority);
	case sfc_hash:
		return compare(a.m_merkle_hash, b.m_merkle_hash);
	}
	return 0;
}

static int CALLBACK events_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->events_compare(lParam1, lParam2);
}

static int CALLBACK global_events_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->global_events_compare(lParam1, lParam2);
}

static int CALLBACK peers_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->peers_compare(lParam1, lParam2);
}

static int CALLBACK pieces_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->pieces_compare(lParam1, lParam2);
}

static int CALLBACK sub_files_compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<CXBTClientDlg*>(lParamSort)->sub_files_compare(lParam1, lParam2);
}

void CXBTClientDlg::OnColumnclickPeers(NMHDR* pNMHDR, LRESULT* pResult)
{
	NM_LISTVIEW* pNMListView = reinterpret_cast<NM_LISTVIEW*>(pNMHDR);
	switch (m_bottom_view)
	{
	case v_events:
		m_events_sort_reverse = m_peers.GetColumnID(pNMListView->iSubItem) == m_events_sort_column && !m_events_sort_reverse;
		m_events_sort_column = m_peers.GetColumnID(pNMListView->iSubItem);
		WriteProfileInt("events_sort_column", m_events_sort_column);
		WriteProfileInt("events_sort_reverse", m_events_sort_reverse);
		break;
	case v_files:
		m_files_sort_reverse = m_peers.GetColumnID(pNMListView->iSubItem) == m_files_sort_column && !m_files_sort_reverse;
		m_files_sort_column = m_peers.GetColumnID(pNMListView->iSubItem);
		WriteProfileInt("files_sort_column", m_files_sort_column);
		WriteProfileInt("files_sort_reverse", m_files_sort_reverse);
		break;
	case v_global_events:
		m_events_sort_reverse = m_peers.GetColumnID(pNMListView->iSubItem) == m_events_sort_column && !m_events_sort_reverse;
		m_events_sort_column = m_peers.GetColumnID(pNMListView->iSubItem);
		WriteProfileInt("global_events_sort_column", m_global_events_sort_column);
		WriteProfileInt("global_events_sort_reverse", m_global_events_sort_reverse);
		break;
	case v_peers:
		m_peers_sort_reverse = m_peers.GetColumnID(pNMListView->iSubItem) == m_peers_sort_column && !m_peers_sort_reverse;
		m_peers_sort_column = m_peers.GetColumnID(pNMListView->iSubItem);
		WriteProfileInt("peers_sort_column", m_peers_sort_column);
		WriteProfileInt("peers_sort_reverse", m_peers_sort_reverse);
		break;
	case v_pieces:
		m_pieces_sort_reverse = m_peers.GetColumnID(pNMListView->iSubItem) == m_pieces_sort_column && !m_pieces_sort_reverse;
		m_pieces_sort_column = m_peers.GetColumnID(pNMListView->iSubItem);
		WriteProfileInt("pieces_sort_column", m_pieces_sort_column);
		WriteProfileInt("pieces_sort_reverse", m_pieces_sort_reverse);
		break;
	}
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
	case v_events:
		m_peers.SortItems(::events_compare, reinterpret_cast<DWORD>(this));
		break;
	case v_files:
		m_peers.SortItems(::sub_files_compare, reinterpret_cast<DWORD>(this));
		break;
	case v_global_events:
		m_peers.SortItems(::global_events_compare, reinterpret_cast<DWORD>(this));
		break;
	case v_peers:
		m_peers.SortItems(::peers_compare, reinterpret_cast<DWORD>(this));
		break;
	case v_pieces:
		m_peers.SortItems(::pieces_compare, reinterpret_cast<DWORD>(this));
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
	m_torrents_columns.push_back(fc_total_downloaded_rel);
	m_torrents_columns.push_back(fc_total_uploaded);
	m_torrents_columns.push_back(fc_total_uploaded_rel);
	m_torrents_columns.push_back(fc_down_rate);
	m_torrents_columns.push_back(fc_up_rate);
	m_torrents_columns.push_back(fc_leechers);
	m_torrents_columns.push_back(fc_seeders);
	m_torrents_columns.push_back(fc_peers);
	m_torrents_columns.push_back(fc_priority);
	m_torrents_columns.push_back(fc_state);
	if (m_show_advanced_columns)
	{
		m_torrents_columns.push_back(fc_hash);
	}
	m_torrents_columns.push_back(fc_end);
	const char* torrents_columns_names[] =
	{
		"Name",
		"%",
		"Left",
		"Size",
		"Downloaded",
		"%",
		"Uploaded",
		"%",
		"Down Rate",
		"Up Rate",
		"Leechers",
		"Seeders",
		"Peers",
		"Priority",
		"State",
		"Hash",
		"",
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
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
	};
	m_files.DeleteAllColumns();
	for (t_columns::const_iterator i = m_torrents_columns.begin(); i != m_torrents_columns.end(); i++)
		m_files.InsertColumn(*i, torrents_columns_names[*i], torrents_columns_formats[*i]);
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
		m_peers_columns.push_back(ec_end);
		break;
	case v_files:
		m_peers_columns.push_back(sfc_name);
		m_peers_columns.push_back(sfc_extension);
		m_peers_columns.push_back(sfc_done);
		m_peers_columns.push_back(sfc_left);
		m_peers_columns.push_back(sfc_size);
		m_peers_columns.push_back(sfc_priority);
		m_peers_columns.push_back(sfc_hash);
		m_peers_columns.push_back(sfc_end);
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
			m_peers_columns.push_back(pc_local_requests);
			m_peers_columns.push_back(pc_remote_requests);
			m_peers_columns.push_back(pc_pieces);
			m_peers_columns.push_back(pc_recv_time);
			m_peers_columns.push_back(pc_send_time);
			m_peers_columns.push_back(pc_host);
			m_peers_columns.push_back(pc_port);
			m_peers_columns.push_back(pc_peer_id);
		}
		m_peers_columns.push_back(pc_end);
		break;
	case v_pieces:
		m_peers_columns.push_back(pic_index);
		m_peers_columns.push_back(pic_c_chunks);
		m_peers_columns.push_back(pic_c_peers);
		m_peers_columns.push_back(pic_priority);
		m_peers_columns.push_back(pic_valid);
		m_peers_columns.push_back(pic_rank);
		m_peers_columns.push_back(pic_end);
		break;
	case v_trackers:
		m_peers_columns.push_back(tc_url);
		break;
	case v_global_details:
		m_peers_columns.push_back(gdc_name);
		m_peers_columns.push_back(gdc_value);
		break;
	case v_global_events:
		m_peers_columns.push_back(gec_time);
		m_peers_columns.push_back(gec_level);
		m_peers_columns.push_back(gec_source);
		m_peers_columns.push_back(gec_message);
		m_peers_columns.push_back(gec_end);
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
		"",

		"Client",
		"%",
		"Left",
		"Downloaded",
		"Uploaded",
		"Down Rate",
		"Up Rate",
		"D",
		"L",
		"L",
		"R",
		"R",
		"LR",
		"RR",
		"P",
		"RT",
		"ST",
		"Host",
		"Port",
		"Peer ID",
		"",
		"Name",
		"Extension",
		"%",
		"Left",
		"Size",
		"Priority",
		"Hash",
		"",

		"Index",
		"Chunks",
		"Peers",
		"Priority",
		"Valid",
		"Rank",
		"",

		"URL",

		"Name",
		"Value",

		"Time",
		"Level",
		"Source",
		"Message",
		"",
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
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,

		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
		LVCFMT_RIGHT,
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
		LVCFMT_RIGHT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
		LVCFMT_LEFT,
	};
	m_peers.DeleteAllColumns();
	for (t_columns::const_iterator i = m_peers_columns.begin(); i != m_peers_columns.end(); i++)
		m_peers.InsertColumn(*i, peers_columns_names[*i], peers_columns_formats[*i]);
}

void CXBTClientDlg::insert_columns(bool auto_size)
{
	insert_top_columns();
	insert_bottom_columns();
	if (auto_size)
	{
		m_files.auto_size();
		m_peers.auto_size();
	}
}

void CXBTClientDlg::set_dir(const string& completes, const string& incompletes, const string local_app_data, const string& torrents)
{
	string local_app_data_default;
	string personal_default;
	{
		char path[MAX_PATH];
		if (FAILED(SHGetSpecialFolderPath(NULL, path, CSIDL_LOCAL_APPDATA, true)))
			strcpy(path, "C:");
		local_app_data_default = path;
	}
	{
		char path[MAX_PATH];
		if (FAILED(SHGetSpecialFolderPath(NULL, path, CSIDL_PERSONAL, true)))
			strcpy(path, "C:");
		personal_default = path;
	}
	m_server.completes_dir(completes.empty() ? personal_default + "/XBT/Completes" : completes);
	m_server.incompletes_dir(incompletes.empty() ? personal_default + "/XBT/Incompletes" : incompletes);
	m_server.local_app_data_dir(local_app_data.empty() ? local_app_data_default + "/XBT" : local_app_data);
	m_server.torrents_dir(torrents.empty() ? personal_default + "/XBT/Torrents" : torrents);
}

void CXBTClientDlg::lower_process_priority(bool v)
{
	SetPriorityClass(GetCurrentProcess(), v ? BELOW_NORMAL_PRIORITY_CLASS : NORMAL_PRIORITY_CLASS);
}

long CXBTClientDlg::OnHotKey(WPARAM, LPARAM)
{
	ShowWindow(IsWindowVisible() ? SW_HIDE : SW_SHOW);
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

void CXBTClientDlg::set_bottom_view(int v)
{
	if (v == m_bottom_view)
		return;
	WriteProfileInt("bottom_view", v);
	m_peers.DeleteAllItems();
	m_bottom_view = v;
	insert_bottom_columns();
	fill_peers();
	m_tab.SetCurSel(v);
}

void CXBTClientDlg::OnPopupViewDetails()
{
	set_bottom_view(v_details);
}

void CXBTClientDlg::OnPopupViewEvents()
{
	set_bottom_view(v_events);
}

void CXBTClientDlg::OnPopupViewFiles()
{
	set_bottom_view(v_files);
}

void CXBTClientDlg::OnPopupViewPeers()
{
	set_bottom_view(v_peers);
}

void CXBTClientDlg::OnPopupViewPieces()
{
	set_bottom_view(v_pieces);
}

void CXBTClientDlg::OnPopupViewTrackers()
{
	set_bottom_view(v_trackers);
}

void CXBTClientDlg::OnPopupViewGlobalDetails() 
{
	set_bottom_view(v_global_details);
}

void CXBTClientDlg::OnPopupViewGlobalEvents() 
{
	set_bottom_view(v_global_events);
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

void CXBTClientDlg::OnUpdatePopupPriorityHigh(CCmdUI* pCmdUI)
{
	if (m_bottom_view == v_files && m_file)
	{
		pCmdUI->Enable(m_peers.GetNextItem(-1, LVNI_SELECTED) != -1);
		pCmdUI->SetRadio(get_priority() == 1);
		return;
	}
	pCmdUI->Enable(false);
}

void CXBTClientDlg::OnUpdatePopupPriorityNormal(CCmdUI* pCmdUI)
{
	if (m_bottom_view == v_files && m_file)
	{
		pCmdUI->Enable(m_peers.GetNextItem(-1, LVNI_SELECTED) != -1);
		pCmdUI->SetRadio(get_priority() == 0);
		return;
	}
	pCmdUI->Enable(false);
}

void CXBTClientDlg::OnUpdatePopupPriorityLow(CCmdUI* pCmdUI)
{
	if (m_bottom_view == v_files && m_file)
	{
		pCmdUI->Enable(m_peers.GetNextItem(-1, LVNI_SELECTED) != -1);
		pCmdUI->SetRadio(get_priority() == -1);
		return;
	}
	pCmdUI->Enable(false);
}

void CXBTClientDlg::OnUpdatePopupPriorityExclude(CCmdUI* pCmdUI)
{
	if (m_bottom_view == v_files && m_file)
	{
		pCmdUI->Enable(m_peers.GetNextItem(-1, LVNI_SELECTED) != -1);
		pCmdUI->SetRadio(get_priority() == -10);
		return;
	}
	pCmdUI->Enable(false);
}

int CXBTClientDlg::get_priority()
{
	if (m_bottom_view != v_files || !m_file)
		return 2;
	int v = 2;
	for (int index = -1; (index = m_peers.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_sub_file& e = m_file->m_sub_files[m_peers.GetItemData(index)];
		if (v == 2)
			v = e.m_priority;
		else if (e.m_priority != v)
			return 2;
	}
	return v;
}

void CXBTClientDlg::set_priority(int v)
{
	if (m_bottom_view != v_files || !m_file)
		return;
	for (int index = -1; (index = m_peers.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_sub_file& e = m_file->m_sub_files[m_peers.GetItemData(index)];
		m_server.sub_file_priority(m_file->m_info_hash, e.m_name, v);
	}
}

void CXBTClientDlg::OnDblclkPeers(NMHDR* pNMHDR, LRESULT* pResult)
{
	if (m_bottom_view != v_files || !m_file)
		return;
	int id = m_peers.GetItemData(m_peers.GetNextItem(-1, LVNI_FOCUSED));
	if (id == -1)
		return;
	const t_sub_file& e = m_file->m_sub_files[id];
	ShellExecute(m_hWnd, "open", (m_file->m_name + e.m_name).c_str(), NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnPopupViewAdvancedColumns()
{
	m_show_advanced_columns = !GetProfileInt("show_advanced_columns", false);
	WriteProfileInt("show_advanced_columns", m_show_advanced_columns);
	insert_columns(true);
}

void CXBTClientDlg::OnPopupViewTrayIcon()
{
	m_show_tray_icon = !GetProfileInt("show_tray_icon", true);
	WriteProfileInt("show_tray_icon", m_show_tray_icon);
	if (m_show_tray_icon)
		register_tray();
	else
		unregister_tray();
}

void CXBTClientDlg::OnInitMenuPopup(CMenu* pMenu, UINT nIndex, BOOL bSysMenu)
{
	ETSLayoutDialog::OnInitMenuPopup(pMenu, nIndex, bSysMenu);
	if (!pMenu || bSysMenu)
		return;
	CCmdUI state;
	state.m_pMenu = pMenu;
	state.m_pSubMenu = NULL;
	for (state.m_nIndex = 0; state.m_nIndex < pMenu->GetMenuItemCount(); state.m_nIndex++)
	{
		state.m_nID = pMenu->GetMenuItemID(state.m_nIndex);
		state.m_nIndexMax = pMenu->GetMenuItemCount();
		state.DoUpdate(this, true);
	}
}

void CXBTClientDlg::OnUpdatePopupViewAdvancedColumns(CCmdUI* pCmdUI)
{
	pCmdUI->SetCheck(m_show_advanced_columns);
}

void CXBTClientDlg::OnUpdatePopupViewTrayIcon(CCmdUI* pCmdUI)
{
	pCmdUI->SetCheck(m_show_tray_icon);
}

void CXBTClientDlg::OnUpdatePopupViewDetails(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_details);
}

void CXBTClientDlg::OnUpdatePopupViewEvents(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_events);
}

void CXBTClientDlg::OnUpdatePopupViewFiles(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_files);
}

void CXBTClientDlg::OnUpdatePopupViewPeers(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_peers);
}

void CXBTClientDlg::OnUpdatePopupViewPieces(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_pieces);
}

void CXBTClientDlg::OnUpdatePopupViewTrackers(CCmdUI* pCmdUI)
{
	pCmdUI->SetRadio(m_bottom_view == v_trackers);
}

void CXBTClientDlg::OnUpdatePopupViewGlobalDetails(CCmdUI* pCmdUI) 
{
	pCmdUI->SetRadio(m_bottom_view == v_global_details);
}

void CXBTClientDlg::OnUpdatePopupViewGlobalEvents(CCmdUI* pCmdUI) 
{
	pCmdUI->SetRadio(m_bottom_view == v_global_events);
}

void CXBTClientDlg::OnPopupUploadRateLimit()
{
	m_server.upload_rate(m_server.upload_rate() ? 0 : GetProfileInt("upload_rate", 0));
}

void CXBTClientDlg::OnUpdatePopupUploadRateLimit(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(GetProfileInt("upload_rate", 0));
	pCmdUI->SetCheck(m_server.upload_rate());
}

int CXBTClientDlg::get_torrent_priority()
{
	int v = 2;
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_file& e = m_files_map[m_files.GetItemData(index)];
		if (v == 2)
			v = e.m_priority;
		else if (e.m_priority != v)
			return 2;
	}
	return v;
}

void CXBTClientDlg::set_torrent_priority(int v)
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_file& e = m_files_map[m_files.GetItemData(index)];
		m_server.file_priority(e.m_info_hash, v);
	}
}

void CXBTClientDlg::OnPopupTorrentPriorityHigh()
{
	set_torrent_priority(1);
}

void CXBTClientDlg::OnUpdatePopupTorrentPriorityHigh(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_priority() == 1);
}

void CXBTClientDlg::OnPopupTorrentPriorityNormal()
{
	set_torrent_priority(0);
}

void CXBTClientDlg::OnUpdatePopupTorrentPriorityNormal(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_priority() == 0);
}

void CXBTClientDlg::OnPopupTorrentPriorityLow()
{
	set_torrent_priority(-1);
}

void CXBTClientDlg::OnUpdatePopupTorrentPriorityLow(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_priority() == -1);
}

Cbt_file::t_state CXBTClientDlg::get_torrent_state()
{
	Cbt_file::t_state v = Cbt_file::s_unknown;
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_file& e = m_files_map[m_files.GetItemData(index)];
		if (v == Cbt_file::s_unknown)
			v = e.m_state;
		else if (e.m_state != v)
			return Cbt_file::s_unknown;
	}
	return v;
}

void CXBTClientDlg::set_torrent_state(Cbt_file::t_state v)
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
	{
		const t_file& e = m_files_map[m_files.GetItemData(index)];
		m_server.file_state(e.m_info_hash, v);
	}
}

void CXBTClientDlg::OnPopupStatePaused()
{
	set_torrent_state(Cbt_file::s_paused);
}

void CXBTClientDlg::OnUpdatePopupStatePaused(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_state() == Cbt_file::s_paused);
}

void CXBTClientDlg::OnPopupStateQueued()
{
	set_torrent_state(Cbt_file::s_queued);
}

void CXBTClientDlg::OnUpdatePopupStateQueued(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_state() == Cbt_file::s_queued);
}

void CXBTClientDlg::OnPopupStateStarted()
{
	set_torrent_state(Cbt_file::s_running);
}

void CXBTClientDlg::OnUpdatePopupStateStarted(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_state() == Cbt_file::s_hashing || get_torrent_state() == Cbt_file::s_running);
}

void CXBTClientDlg::OnPopupStateStopped()
{
	set_torrent_state(Cbt_file::s_stopped);
}

void CXBTClientDlg::OnUpdatePopupStateStopped(CCmdUI* pCmdUI)
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
	pCmdUI->SetRadio(get_torrent_state() == Cbt_file::s_stopped);
}

void CXBTClientDlg::OnCancel() 
{
	if (!GetProfileInt("show_confirm_exit_dialog", false)
		|| IDOK == MessageBox("Would you like to exit XBT Client?", NULL, MB_ICONWARNING | MB_OKCANCEL))
		ETSLayoutDialog::OnCancel();
}

void CXBTClientDlg::OnActivateApp(BOOL bActive, HTASK hTask) 
{
	ETSLayoutDialog::OnActivateApp(bActive, hTask);
	if (!bActive && m_hide_on_deactivate)
		ShowWindow(SW_HIDE);
}

void CXBTClientDlg::OnFileNew() 
{
	Cdlg_make_torrent dlg;
	if (IDOK == dlg.DoModal() && dlg.m_seed_after_making)
		open(dlg.torrent_fname(), true);
}

void CXBTClientDlg::OnFileOpen() 
{
	CFileDialog dlg(true, "torrent", NULL, OFN_HIDEREADONLY | OFN_FILEMUSTEXIST, "Torrents|*.torrent|", this);
	if (IDOK == dlg.DoModal())
		open(static_cast<string>(dlg.GetPathName()), m_ask_for_location);
}

void CXBTClientDlg::OnFileClose() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.close(m_files_map.find(m_files.GetItemData(index))->second.m_info_hash);
}

void CXBTClientDlg::OnUpdateFileClose(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
}

void CXBTClientDlg::OnFileDelete() 
{
	for (int index = -1; (index = m_files.GetNextItem(index, LVNI_SELECTED)) != -1; )
		m_server.close(m_files_map.find(m_files.GetItemData(index))->second.m_info_hash, true);
}

void CXBTClientDlg::OnUpdateFileDelete(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetNextItem(-1, LVNI_SELECTED) != -1);
}

void CXBTClientDlg::OnFileExit() 
{
	OnCancel();
}

void CXBTClientDlg::OnEditCopyAnnounceUrl() 
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id == -1)
		return;
	const t_file& file = m_files_map.find(id)->second;
	if (!file.m_trackers.empty())
		set_clipboard(file.m_trackers.front().url);
}

void CXBTClientDlg::OnUpdateEditCopyAnnounceUrl(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnEditCopyHash() 
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id != -1)
		set_clipboard(hex_encode(m_files_map.find(id)->second.m_info_hash));
}

void CXBTClientDlg::OnUpdateEditCopyHash(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnEditCopyUrl() 
{
	int id = m_files.GetItemData(m_files.GetNextItem(-1, LVNI_SELECTED));
	if (id != -1)
		set_clipboard(m_server.get_url(m_files_map.find(id)->second.m_info_hash));
}

void CXBTClientDlg::OnUpdateEditCopyUrl(CCmdUI* pCmdUI) 
{
	pCmdUI->Enable(m_files.GetSelectedCount() == 1);
}

void CXBTClientDlg::OnEditPasteUrl()
{
	if (!OpenClipboard())
		return;
	void* h = GetClipboardData(CF_TEXT);
	void* p = GlobalLock(h);
	if (p)
		open_url(reinterpret_cast<char*>(p));
	CloseClipboard();
}

void CXBTClientDlg::OnEditSelectAll() 
{
	m_files.select_all();
}

void CXBTClientDlg::OnToolsOptions() 
{
	Cdlg_options dlg(this);
	Cdlg_options::t_data data;
	data.admin_port = GetProfileInt("admin_port", m_server.admin_port());
	data.ask_for_location = GetProfileInt("ask_for_location", false);
	data.bind_before_connect = m_server.bind_before_connect();
	data.completes_dir = m_server.completes_dir();
	data.hide_on_deactivate = GetProfileInt("hide_on_deactivate", false);
	data.hot_key = GetProfileInt("hot_key", (HOTKEYF_CONTROL | HOTKEYF_SHIFT) << 8 |'Q');
	data.incompletes_dir = m_server.incompletes_dir();
	data.lower_process_priority = GetProfileInt("lower_process_priority", true);
	data.peer_limit = m_server.peer_limit();
	data.peer_port = GetProfileInt("peer_port", m_server.peer_port());
	data.public_ipa = GetProfileString("public_ipa");
	data.seeding_ratio = m_server.seeding_ratio();
	data.send_stop_event = m_server.send_stop_event();
	data.show_advanced_columns = GetProfileInt("show_advanced_columns", false);
	data.show_confirm_exit_dialog = GetProfileInt("show_confirm_exit_dialog", false);
	data.show_tray_icon = GetProfileInt("show_tray_icon", true);
	data.start_minimized = GetProfileInt("start_minimized", false);
	data.torrent_limit = m_server.torrent_limit();
	data.torrents_dir = m_server.torrents_dir();
	data.tracker_port = GetProfileInt("tracker_port", m_server.tracker_port());
	data.upload_rate = m_server.upload_rate();
	data.upload_slots = m_server.upload_slots();
	dlg.set(data);
	unregister_hot_key();
	if (IDOK != dlg.DoModal())
	{
		register_hot_key(data.hot_key);
		return;
	}
	data = dlg.get();
	m_server.admin_port(data.admin_port);
	m_ask_for_location = data.ask_for_location;
	m_server.bind_before_connect(data.bind_before_connect);
	m_hide_on_deactivate = data.hide_on_deactivate;
	lower_process_priority(data.lower_process_priority);
	set_dir(data.completes_dir, data.incompletes_dir, "", data.torrents_dir);
	m_server.peer_limit(data.peer_limit);
	m_server.peer_port(data.peer_port);
	if (!data.public_ipa.empty())
		m_server.public_ipa(Csocket::get_host(data.public_ipa));
	m_server.seeding_ratio(data.seeding_ratio);
	m_server.send_stop_event(data.send_stop_event);
	m_show_advanced_columns = data.show_advanced_columns;
	m_show_tray_icon = data.show_tray_icon;
	m_server.torrent_limit(data.torrent_limit);
	m_server.tracker_port(data.tracker_port);
	m_server.upload_rate(data.upload_rate);
	m_server.upload_slots(data.upload_slots);
	WriteProfileInt("admin_port", data.admin_port);
	WriteProfileInt("ask_for_location", data.ask_for_location);
	WriteProfileInt("bind_before_connect", data.bind_before_connect);
	WriteProfileString("completes_dir", data.completes_dir);
	WriteProfileInt("hide_on_deactivate", data.hide_on_deactivate);
	WriteProfileInt("hot_key", data.hot_key);
	WriteProfileString("incompletes_dir", data.incompletes_dir);
	WriteProfileInt("lower_process_priority", data.lower_process_priority);
	WriteProfileInt("peer_limit", data.peer_limit);
	WriteProfileInt("peer_port", data.peer_port);
	WriteProfileString("public_ipa", data.public_ipa);
	WriteProfileInt("seeding_ratio", data.seeding_ratio);
	WriteProfileInt("send_stop_event", data.send_stop_event);
	WriteProfileInt("show_advanced_columns", data.show_advanced_columns);
	WriteProfileInt("show_confirm_exit_dialog", data.show_confirm_exit_dialog);
	WriteProfileInt("show_tray_icon", data.show_tray_icon);
	WriteProfileInt("start_minimized", data.start_minimized);
	WriteProfileInt("torrent_limit", data.torrent_limit);
	WriteProfileString("torrents_dir", data.torrents_dir);
	WriteProfileInt("tracker_port", data.tracker_port);
	WriteProfileInt("upload_rate", data.upload_rate);
	WriteProfileInt("upload_slots", data.upload_slots);
	insert_columns(true);
	register_hot_key(data.hot_key);
	if (m_show_tray_icon)
		register_tray();
	else
		unregister_tray();
}

void CXBTClientDlg::OnToolsProfiles() 
{
	Cdlg_profiles dlg;
	dlg.entries(Cprofiles().load(m_server.get_profiles()));
	switch (dlg.DoModal())
	{
	case IDC_ACTIVATE:
		m_server.load_profile(dlg.selected_profile().save());
	case IDOK:
		m_server.set_profiles(dlg.entries().save());
		break;
	}
}

void CXBTClientDlg::OnToolsScheduler() 
{
	Cdlg_scheduler dlg;
	dlg.entries(Cscheduler().load(m_server.get_scheduler()));
	dlg.profiles(Cprofiles().load(m_server.get_profiles()));
	if (IDOK == dlg.DoModal())
		m_server.set_scheduler(dlg.entries().save());
}

void CXBTClientDlg::OnToolsTrackers() 
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

void CXBTClientDlg::OnHelpHomePage() 
{
	ShellExecute(m_hWnd, "open", "http://sourceforge.net/projects/xbtt/", NULL, NULL, SW_SHOW);
}

void CXBTClientDlg::OnHelpAbout() 
{
	Cdlg_about().DoModal();
}

void CXBTClientDlg::OnSelchangeTab(NMHDR* pNMHDR, LRESULT* pResult) 
{
	set_bottom_view(m_tab.GetCurSel());
	*pResult = 0;
}

long CXBTClientDlg::OnAreYouMe(WPARAM, LPARAM)
{
	return g_are_you_me_message_id;
}

long CXBTClientDlg::OnTaskbarCreated(WPARAM, LPARAM)
{
	register_tray();
	return 0;
}

long CXBTClientDlg::OnTray(WPARAM, LPARAM lParam)
{
	switch (lParam)
	{
	case WM_LBUTTONUP:
		ShowWindow(IsWindowVisible() ? SW_HIDE : SW_SHOW);
		if (IsWindowVisible())
			SetForegroundWindow();
		return 0;
	case WM_RBUTTONUP:
		OnTrayMenu();
		return 0;
	}
	return 0;
}

void CXBTClientDlg::OnSysCommand(UINT nID, LPARAM lParam) 
{
	switch (nID)
	{
	case SC_MINIMIZE:
		if (!m_show_tray_icon)
			break;
		ShowWindow(SW_HIDE);
		return;
	}
	ETSLayoutDialog::OnSysCommand(nID, lParam);
}

BOOL CXBTClientDlg::OnCopyData(CWnd* pWnd, COPYDATASTRUCT* pCopyDataStruct) 
{
	switch (pCopyDataStruct->dwData)
	{
	case 0:
		open(string(reinterpret_cast<const char*>(pCopyDataStruct->lpData), pCopyDataStruct->cbData), m_ask_for_location);
		return true;
	}
	return ETSLayoutDialog::OnCopyData(pWnd, pCopyDataStruct);
}

void CXBTClientDlg::update_global_details()
{
	m_global_details.m_down_rate = 0;
	m_global_details.m_downloaded = 0;
	m_global_details.m_downloaded_total = 0;
	m_global_details.m_left = 0;
	m_global_details.m_size = 0;
	m_global_details.m_up_rate = 0;
	m_global_details.m_uploaded = 0;
	m_global_details.m_uploaded_total = 0;
	m_global_details.mc_files = 0;
	m_global_details.mc_leechers = 0;
	m_global_details.mc_seeders = 0;
	m_global_details.mc_torrents_complete = 0;
	m_global_details.mc_torrents_incomplete = 0;
	for (t_files::const_iterator i = m_files_map.begin(); i != m_files_map.end(); i++)
	{
		m_global_details.m_down_rate += i->second.m_down_rate;
		m_global_details.m_downloaded += i->second.m_downloaded;
		m_global_details.m_downloaded_total += i->second.m_total_downloaded;
		m_global_details.m_left += i->second.m_left;
		m_global_details.m_size += i->second.m_size;
		m_global_details.m_up_rate += i->second.m_up_rate;
		m_global_details.m_uploaded += i->second.m_uploaded;
		m_global_details.m_uploaded_total += i->second.m_total_uploaded;
		m_global_details.mc_files += i->second.m_sub_files.size();
		m_global_details.mc_leechers += i->second.mc_leechers;
		m_global_details.mc_seeders += i->second.mc_seeders;
		(i->second.m_left ? m_global_details.mc_torrents_incomplete : m_global_details.mc_torrents_complete)++;
	}
}

void CXBTClientDlg::register_hot_key(DWORD v)
{
	int a = 0;
	if (v & HOTKEYF_ALT << 8)
		a |= MOD_ALT;
	if (v & HOTKEYF_SHIFT << 8)
		a |= MOD_SHIFT;
	if (v & HOTKEYF_CONTROL << 8)
		a |= MOD_CONTROL;
	RegisterHotKey(GetSafeHwnd(), 0, a, v & 0xff);
}

void CXBTClientDlg::unregister_hot_key()
{
	UnregisterHotKey(GetSafeHwnd(), 0);
}

int CXBTClientDlg::GetProfileInt(LPCTSTR Entry, int Default)
{
	return AfxGetApp()->GetProfileInt(m_reg_key, Entry, Default);
}

string CXBTClientDlg::GetProfileString(LPCTSTR Entry, LPCTSTR Default)
{
	return string(AfxGetApp()->GetProfileString(m_reg_key, Entry, Default));
}

BOOL CXBTClientDlg::WriteProfileInt(LPCTSTR Entry, int Value)
{
	return AfxGetApp()->WriteProfileInt(m_reg_key, Entry, Value);
}

BOOL CXBTClientDlg::WriteProfileString(LPCTSTR Entry, const string& Value)
{
	return AfxGetApp()->WriteProfileString(m_reg_key, Entry, Value.c_str());
}
