#pragma once

#include "resource.h"

class Cdlg_options: public ETSLayoutDialog
{
public:
	struct t_data
	{
		int admin_port;
		bool ask_for_location;
		bool bind_before_connect;
		bool hide_on_deactivate;
		DWORD hot_key;
		std::string completes_dir;
		std::string incompletes_dir;
		std::string torrents_dir;
		bool lower_process_priority;
		std::string peer_id;
		int peer_limit;
		int peer_port;
		std::string public_ipa;
		int seeding_ratio;
		bool send_stop_event;
		bool show_confirm_exit_dialog;
		bool show_tray_icon;
		bool start_minimized;
		int torrent_limit;
		int upload_rate;
		int upload_slots;
		bool upnp;
		std::string user_agent;
	};

	t_data get() const;
	void set(const t_data&);
	Cdlg_options(CWnd* pParent = NULL);

	enum { IDD = IDD_OPTIONS };
	CHotKeyCtrl	m_hot_key;
	int		m_peer_port;
	int		m_admin_port;
	int		m_upload_rate;
	CString	m_public_ipa;
	int		m_upload_slots;
	int		m_seeding_ratio;
	BOOL	m_show_tray_icon;
	BOOL	m_start_minimized;
	BOOL	m_ask_for_location;
	BOOL	m_lower_process_priority;
	int		m_peer_limit;
	BOOL	m_bind_before_connect;
	CString	m_completes_dir;
	CString	m_incompletes_dir;
	CString	m_torrents_dir;
	int		m_torrent_limit;
	BOOL	m_show_confirm_exit_dialog;
	BOOL	m_hide_on_deactivate;
	BOOL	m_send_stop_event;
	BOOL	m_upnp;
	CString	m_user_agent;
	CString	m_peer_id;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	afx_msg void OnCompletesDirectoryBrowse();
	afx_msg void OnIncompletesDirectoryBrowse();
	afx_msg void OnTorrentsDirectoryBrowse();
	virtual BOOL OnInitDialog();
	DECLARE_MESSAGE_MAP()
private:
	DWORD m_hot_key_value;
};
