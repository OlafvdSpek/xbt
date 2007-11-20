#pragma once

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
protected:
	void update_controls();
	virtual void DoDataExchange(CDataExchange* pDX);
	afx_msg void OnSeedingRatioOverride();
	virtual BOOL OnInitDialog();
	virtual void OnOK();
	afx_msg void OnUploadSlotsMinOverride();
	afx_msg void OnUploadSlotsMaxOverride();
	DECLARE_MESSAGE_MAP()
private:
	t_data m_data;
};
