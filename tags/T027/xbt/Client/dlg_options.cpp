#include "stdafx.h"
#include "dlg_options.h"

#include "windows/browse_for_directory.h"

Cdlg_options::Cdlg_options(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(Cdlg_options::IDD, pParent, "Cdlg_options")
{
	//{{AFX_DATA_INIT(Cdlg_options)
	m_peer_port = 0;
	m_admin_port = 0;
	m_upload_rate = 0;
	m_public_ipa = _T("");
	m_upload_slots = 0;
	m_seeding_ratio = 0;
	m_show_tray_icon = FALSE;
	m_start_minimized = FALSE;
	m_ask_for_location = FALSE;
	m_lower_process_priority = FALSE;
	m_peer_limit = 0;
	m_bind_before_connect = FALSE;
	m_completes_dir = _T("");
	m_incompletes_dir = _T("");
	m_torrents_dir = _T("");
	m_torrent_limit = 0;
	m_show_confirm_exit_dialog = FALSE;
	m_hide_on_deactivate = FALSE;
	m_send_stop_event = FALSE;
	m_upnp = FALSE;
	m_user_agent = _T("");
	m_peer_id = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_options::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_options)
	DDX_Control(pDX, IDC_HOT_KEY, m_hot_key);
	DDX_Text(pDX, IDC_PEER_PORT, m_peer_port);
	DDV_MinMaxInt(pDX, m_peer_port, 0, 65535);
	DDX_Text(pDX, IDC_ADMIN_PORT, m_admin_port);
	DDV_MinMaxInt(pDX, m_admin_port, 0, 65535);
	DDX_Text(pDX, IDC_UPLOAD_RATE, m_upload_rate);
	DDX_Text(pDX, IDC_PUBLIC_IPA, m_public_ipa);
	DDX_Text(pDX, IDC_UPLOAD_SLOTS, m_upload_slots);
	DDX_Text(pDX, IDC_SEEDING_RATIO, m_seeding_ratio);
	DDX_Check(pDX, IDC_SHOW_TRAY_ICON, m_show_tray_icon);
	DDX_Check(pDX, IDC_START_MINIMIZED, m_start_minimized);
	DDX_Check(pDX, IDC_ASK_FOR_LOCATION, m_ask_for_location);
	DDX_Check(pDX, IDC_LOWER_PROCESS_PRIORITY, m_lower_process_priority);
	DDX_Text(pDX, IDC_PEER_LIMIT, m_peer_limit);
	DDX_Check(pDX, IDC_BIND_BEFORE_CONNECT, m_bind_before_connect);
	DDX_Text(pDX, IDC_COMPLETES_DIRECTORY, m_completes_dir);
	DDX_Text(pDX, IDC_INCOMPLETES_DIRECTORY, m_incompletes_dir);
	DDX_Text(pDX, IDC_TORRENTS_DIRECTORY, m_torrents_dir);
	DDX_Text(pDX, IDC_TORRENT_LIMIT, m_torrent_limit);
	DDX_Check(pDX, IDC_SHOW_CONFIRM_EXIT_DIALOG, m_show_confirm_exit_dialog);
	DDX_Check(pDX, IDC_HIDE_ON_DEACTIVATE, m_hide_on_deactivate);
	DDX_Check(pDX, IDC_SEND_STOP_EVENT, m_send_stop_event);
	DDX_Check(pDX, IDC_UPNP, m_upnp);
	DDX_CBString(pDX, IDC_USER_AGENT, m_user_agent);
	DDX_CBString(pDX, IDC_PEER_ID, m_peer_id);
	//}}AFX_DATA_MAP
	if (pDX->m_bSaveAndValidate)
		m_hot_key_value = m_hot_key.GetHotKey();
	else
		m_hot_key.SetHotKey(m_hot_key_value & 0xff, m_hot_key_value >> 8);
}


BEGIN_MESSAGE_MAP(Cdlg_options, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_options)
	ON_BN_CLICKED(IDC_COMPLETES_DIRECTORY_BROWSE, OnCompletesDirectoryBrowse)
	ON_BN_CLICKED(IDC_INCOMPLETES_DIRECTORY_BROWSE, OnIncompletesDirectoryBrowse)
	ON_BN_CLICKED(IDC_TORRENTS_DIRECTORY_BROWSE, OnTorrentsDirectoryBrowse)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

Cdlg_options::t_data Cdlg_options::get() const
{
	t_data v;
	v.admin_port = m_admin_port;
	v.ask_for_location = m_ask_for_location;
	v.bind_before_connect = m_bind_before_connect;
	v.completes_dir = m_completes_dir;
	v.hide_on_deactivate = m_hide_on_deactivate;
	v.hot_key = m_hot_key_value;
	v.incompletes_dir = m_incompletes_dir;
	v.lower_process_priority = m_lower_process_priority;
	v.peer_id = m_peer_id;
	v.peer_limit = m_peer_limit;
	v.peer_port = m_peer_port;
	v.public_ipa = m_public_ipa;
	v.seeding_ratio = m_seeding_ratio;
	v.send_stop_event = m_send_stop_event;
	v.show_confirm_exit_dialog = m_show_confirm_exit_dialog;
	v.show_tray_icon = m_show_tray_icon;
	v.start_minimized = m_start_minimized;
	v.torrent_limit = m_torrent_limit;
	v.torrents_dir = m_torrents_dir;
	v.upload_rate = m_upload_rate << 10;
	v.upload_slots = m_upload_slots;
	v.upnp = m_upnp;
	v.user_agent = m_user_agent;
	return v;
}

void Cdlg_options::set(const t_data& v)
{
	m_admin_port = v.admin_port;
	m_ask_for_location = v.ask_for_location;
	m_bind_before_connect = v.bind_before_connect;
	m_completes_dir = native_slashes(v.completes_dir).c_str();
	m_hide_on_deactivate = v.hide_on_deactivate;
	m_hot_key_value = v.hot_key;
	m_incompletes_dir = native_slashes(v.incompletes_dir).c_str();
	m_lower_process_priority = v.lower_process_priority;
	m_peer_id = v.peer_id.c_str();
	m_peer_limit = v.peer_limit;
	m_peer_port = v.peer_port;
	m_public_ipa = v.public_ipa.c_str();
	m_seeding_ratio = v.seeding_ratio;
	m_send_stop_event = v.send_stop_event;
	m_show_confirm_exit_dialog = v.show_confirm_exit_dialog;
	m_show_tray_icon = v.show_tray_icon;
	m_start_minimized = v.start_minimized;
	m_torrent_limit = v.torrent_limit;
	m_torrents_dir = native_slashes(v.torrents_dir).c_str();
	m_upload_rate = v.upload_rate >> 10;
	m_upload_slots = v.upload_slots;
	m_upnp = v.upnp;
	m_user_agent = v.user_agent.c_str();
}

void Cdlg_options::OnCompletesDirectoryBrowse()
{
	std::string dir = std::string(m_completes_dir);
	if (!UpdateData(true) || browse_for_directory(GetSafeHwnd(), "Completes Directory", dir))
		return;
	m_completes_dir = dir.c_str();
	UpdateData(false);
}

void Cdlg_options::OnIncompletesDirectoryBrowse()
{
	std::string dir = std::string(m_incompletes_dir);
	if (!UpdateData(true) || browse_for_directory(GetSafeHwnd(), "Incompletes Directory", dir))
		return;
	m_incompletes_dir = dir.c_str();
	UpdateData(false);
}

void Cdlg_options::OnTorrentsDirectoryBrowse()
{
	std::string dir = std::string(m_torrents_dir);
	if (!UpdateData(true) || browse_for_directory(GetSafeHwnd(), "Torrents Directory", dir))
		return;
	m_torrents_dir = dir.c_str();
	UpdateData(false);
}

BOOL Cdlg_options::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL)
			<< (pane(VERTICAL)
				<< item(IDC_SEEDING_RATIO_STATIC, NORESIZE)
				<< item(IDC_UPLOAD_RATE_STATIC, NORESIZE)
				<< item(IDC_UPLOAD_SLOTS_STATIC, NORESIZE)
				<< item(IDC_PEER_LIMIT_STATIC, NORESIZE)
				<< item(IDC_TORRENT_LIMIT_STATIC, NORESIZE)
				<< item(IDC_COMPLETES_DIRECTORY_STATIC, NORESIZE)
				<< item(IDC_INCOMPLETES_DIRECTORY_STATIC, NORESIZE)
				<< item(IDC_TORRENTS_DIRECTORY_STATIC, NORESIZE)
				<< item(IDC_PUBLIC_IPA_STATIC, NORESIZE)
				<< item(IDC_ADMIN_PORT_STATIC, NORESIZE)
				<< item(IDC_PEER_PORT_STATIC, NORESIZE)
				<< item(IDC_HOT_KEY_STATIC, NORESIZE)
				<< item(IDC_PEER_ID_STATIC, NORESIZE)
				<< item(IDC_USER_AGENT_STATIC, NORESIZE)
				)
			<< (pane(VERTICAL)
				<< item(IDC_SEEDING_RATIO, ABSOLUTE_VERT)
				<< item(IDC_UPLOAD_RATE, ABSOLUTE_VERT)
				<< item(IDC_UPLOAD_SLOTS, ABSOLUTE_VERT)
				<< item(IDC_PEER_LIMIT, ABSOLUTE_VERT)
				<< item(IDC_TORRENT_LIMIT, ABSOLUTE_VERT)
				<< (pane(HORIZONTAL)
					<< item(IDC_COMPLETES_DIRECTORY, ABSOLUTE_VERT)
					<< item(IDC_COMPLETES_DIRECTORY_BROWSE, NORESIZE)
					)
				<< (pane(HORIZONTAL)
					<< item(IDC_INCOMPLETES_DIRECTORY, ABSOLUTE_VERT)
					<< item(IDC_INCOMPLETES_DIRECTORY_BROWSE, NORESIZE)
					)
				<< (pane(HORIZONTAL)
					<< item(IDC_TORRENTS_DIRECTORY, ABSOLUTE_VERT)
					<< item(IDC_TORRENTS_DIRECTORY_BROWSE, NORESIZE)
					)
				<< item(IDC_PUBLIC_IPA, ABSOLUTE_VERT)
				<< item(IDC_ADMIN_PORT, ABSOLUTE_VERT)
				<< item(IDC_PEER_PORT, ABSOLUTE_VERT)
				<< item(IDC_HOT_KEY, ABSOLUTE_VERT)
				<< item(IDC_PEER_ID, ABSOLUTE_VERT)
				<< item(IDC_USER_AGENT, ABSOLUTE_VERT)
				)
			)
		<< (pane(HORIZONTAL)
			<< (pane(VERTICAL)
				<< item(IDC_ASK_FOR_LOCATION, NORESIZE)
				<< item(IDC_BIND_BEFORE_CONNECT, NORESIZE)
				<< item(IDC_HIDE_ON_DEACTIVATE, NORESIZE)
				<< item(IDC_LOWER_PROCESS_PRIORITY, NORESIZE)
				<< item(IDC_SEND_STOP_EVENT, NORESIZE)
				)
			<< (pane(VERTICAL)
				<< item(IDC_SHOW_CONFIRM_EXIT_DIALOG, NORESIZE)
				<< item(IDC_SHOW_TRAY_ICON, NORESIZE)
				<< item(IDC_START_MINIMIZED, NORESIZE)
				<< item(IDC_UPNP, NORESIZE)
				)
			)
		<< (pane(HORIZONTAL)
			<< itemGrowing(HORIZONTAL)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();
	return true;
}
