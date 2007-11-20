#pragma once

#include "../bt test/profiles.h"
#include "resource.h"

class Cdlg_scheduler_entry: public ETSLayoutDialog
{
public:
	void update_controls();
	typedef Cprofiles t_profiles;

	Cdlg_scheduler_entry(CWnd* pParent = NULL);

	void profiles(const t_profiles& v)
	{
		m_profiles = v;
	}

	enum { IDD = IDD_SCHEDULER_ENTRY };
	CButton	m_ok;
	CComboBox	m_profile;
	int		m_hours;
	int		m_minutes;
	int		m_seconds;
	int m_profile_id;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	virtual void OnOK();
	afx_msg void OnSelchangeProfile();
	DECLARE_MESSAGE_MAP()
private:
	t_profiles m_profiles;
};
