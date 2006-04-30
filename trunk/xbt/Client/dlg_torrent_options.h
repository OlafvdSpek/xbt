#if !defined(AFX_DLG_TORRENT_OPTIONS_H__6925B405_EACA_4518_B562_CE2E525AF1E7__INCLUDED_)
#define AFX_DLG_TORRENT_OPTIONS_H__6925B405_EACA_4518_B562_CE2E525AF1E7__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "resource.h"

class Cdlg_torrent_options: public ETSLayoutDialog
{
public:
	struct t_data
	{
		bool end_mode;
		int priority;
		int seeding_ratio;
		bool seeding_ratio_override;
		std::string trackers;
		int upload_slots_min;
		bool upload_slots_min_override;
		int upload_slots_max;
		bool upload_slots_max_override;
	};

	const t_data& get() const;
	void set(const t_data&);
	Cdlg_torrent_options(CWnd* pParent = NULL);

	//{{AFX_DATA(Cdlg_torrent_options)
	enum { IDD = IDD_TORRENT_OPTIONS };
	CEdit	m_upload_slots_max;
	CEdit	m_upload_slots_min;
	CButton	m_upload_slots_min_override;
	CButton	m_upload_slots_max_override;
	CButton	m_seeding_ratio_override;
	CEdit	m_seeding_ratio;
	int		m_priority;
	int		m_seeding_ratio_value;
	int		m_upload_slots_max_value;
	int		m_upload_slots_min_value;
	BOOL	m_end_mode;
	CString	m_trackers;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_torrent_options)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	void update_controls();

	//{{AFX_MSG(Cdlg_torrent_options)
	afx_msg void OnSeedingRatioOverride();
	virtual BOOL OnInitDialog();
	virtual void OnOK();
	afx_msg void OnUploadSlotsMinOverride();
	afx_msg void OnUploadSlotsMaxOverride();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_data m_data;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_TORRENT_OPTIONS_H__6925B405_EACA_4518_B562_CE2E525AF1E7__INCLUDED_)
