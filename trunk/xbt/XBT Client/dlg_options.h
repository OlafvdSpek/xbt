#if !defined(AFX_DLG_OPTIONS_H__F88A336D_3B46_4580_8ACF_F796B1E0ED0F__INCLUDED_)
#define AFX_DLG_OPTIONS_H__F88A336D_3B46_4580_8ACF_F796B1E0ED0F__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_options.h : header file
//

#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_options dialog

class Cdlg_options: public ETSLayoutDialog
{
// Construction
public:
	struct t_data
	{
		int admin_port;
		bool ask_for_location;
		bool bind_before_connect;
		bool hide_on_deactivate;
		DWORD hot_key;
		string completes_dir;
		string incompletes_dir;
		string torrents_dir;
		bool lower_process_priority;
		int peer_limit;
		int peer_port;
		string public_ipa;
		int seeding_ratio;
		bool send_stop_event;
		bool show_advanced_columns;
		bool show_confirm_exit_dialog;
		bool show_tray_icon;
		bool start_minimized;
		int torrent_limit;
		int tracker_port;
		int upload_rate;
		int upload_slots;
		bool upnp;
		string user_agent;
	};

	t_data get() const;
	void set(const t_data&);
	Cdlg_options(CWnd* pParent = NULL);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_options)
	enum { IDD = IDD_OPTIONS };
	CHotKeyCtrl	m_hot_key;
	int		m_peer_port;
	int		m_admin_port;
	int		m_upload_rate;
	CString	m_public_ipa;
	int		m_upload_slots;
	int		m_seeding_ratio;
	BOOL	m_show_tray_icon;
	BOOL	m_show_advanced_columns;
	BOOL	m_start_minimized;
	BOOL	m_ask_for_location;
	int		m_tracker_port;
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
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_options)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_options)
	afx_msg void OnCompletesDirectoryBrowse();
	afx_msg void OnIncompletesDirectoryBrowse();
	afx_msg void OnTorrentsDirectoryBrowse();
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	DWORD m_hot_key_value;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_OPTIONS_H__F88A336D_3B46_4580_8ACF_F796B1E0ED0F__INCLUDED_)
