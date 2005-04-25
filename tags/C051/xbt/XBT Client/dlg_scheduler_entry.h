#if !defined(AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_)
#define AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_scheduler_entry.h : header file
//

#include "../bt test/profiles.h"
#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_scheduler_entry dialog

class Cdlg_scheduler_entry: public ETSLayoutDialog
{
// Construction
public:
	void update_controls();
	typedef Cprofiles t_profiles;

	Cdlg_scheduler_entry(CWnd* pParent = NULL);   // standard constructor

	void profiles(const t_profiles& v)
	{
		m_profiles = v;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_scheduler_entry)
	enum { IDD = IDD_SCHEDULER_ENTRY };
	CButton	m_ok;
	CComboBox	m_profile;
	int		m_hours;
	int		m_minutes;
	int		m_seconds;
	//}}AFX_DATA
	int m_profile_id;


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_scheduler_entry)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
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
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_SCHEDULER_ENTRY_H__933E0EF4_E644_4BB1_A736_2D67123D91F5__INCLUDED_)
