#if !defined(AFX_DLG_PROFILE_H__D6763518_01E4_4119_A02F_3701B5BFE69C__INCLUDED_)
#define AFX_DLG_PROFILE_H__D6763518_01E4_4119_A02F_3701B5BFE69C__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "resource.h"

class Cdlg_profile: public ETSLayoutDialog
{
public:
	void update_controls();
	Cdlg_profile(CWnd* pParent = NULL);

	//{{AFX_DATA(Cdlg_profile)
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
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_profile)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	//{{AFX_MSG(Cdlg_profile)
	afx_msg void OnSeedingRatioEnable();
	afx_msg void OnUploadRateEnable();
	afx_msg void OnUploadSlotsEnable();
	afx_msg void OnPeerLimitEnable();
	afx_msg void OnTorrentLimitEnable();
	virtual BOOL OnInitDialog();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_PROFILE_H__D6763518_01E4_4119_A02F_3701B5BFE69C__INCLUDED_)
