#if !defined(AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_)
#define AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

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

	//{{AFX_DATA(Cdlg_scheduler_entry)
	enum { IDD = IDD_SCHEDULER_ENTRY };
	CButton	m_ok;
	CComboBox	m_profile;
	int		m_hours;
	int		m_minutes;
	int		m_seconds;
	//}}AFX_DATA
	int m_profile_id;

	//{{AFX_VIRTUAL(Cdlg_scheduler_entry)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	//{{AFX_MSG(Cdlg_scheduler_entry)
	virtual BOOL OnInitDialog();
	virtual void OnOK();
	afx_msg void OnSelchangeProfile();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_profiles m_profiles;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_)
