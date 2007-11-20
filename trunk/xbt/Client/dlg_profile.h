#pragma once

#include "resource.h"

class Cdlg_profile: public ETSLayoutDialog
{
public:
	void update_controls();
	Cdlg_profile(CWnd* pParent = NULL);

	enum { IDD = IDD_PROFILE };
	CEdit	m_upload_slots;
	CEdit	m_upload_rate;
	CEdit	m_torrent_limit;
	CEdit	m_seeding_ratio;
	CEdit	m_peer_limit;
	int		m_peer_limit_value;
	int		m_seeding_ratio_value;
	int		m_torrent_limit_value;
	int		m_upload_rate_value;
	int		m_upload_slots_value;
	BOOL	m_peer_limit_enable;
	BOOL	m_seeding_ratio_enable;
	BOOL	m_torrent_limit_enable;
	BOOL	m_upload_rate_enable;
	BOOL	m_upload_slots_enable;
	CString	m_name;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	afx_msg void OnSeedingRatioEnable();
	afx_msg void OnUploadRateEnable();
	afx_msg void OnUploadSlotsEnable();
	afx_msg void OnPeerLimitEnable();
	afx_msg void OnTorrentLimitEnable();
	virtual BOOL OnInitDialog();
	DECLARE_MESSAGE_MAP()
};
