#if !defined(AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_)
#define AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_profiles.h : header file
//

#include "ListCtrlEx.h"
#include "../bt test/profiles.h"
#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_profiles dialog

class Cdlg_profiles: public ETSLayoutDialog
{
// Construction
public:
	typedef Cprofile t_entry;
	typedef Cprofiles t_entries;

	void insert(const t_entry&);
	Cdlg_profiles(CWnd* pParent = NULL);   // standard constructor

	const t_entries& entries() const
	{
		return m_entries;
	}

	void entries(const t_entries& v)
	{
		m_entries = v;
	}

	const Cprofile& selected_profile() const
	{
		return m_entries.find(m_selected_profile)->second;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_profiles)
	enum { IDD = IDD_PROFILES };
	CButton	m_edit;
	CButton	m_activate;
	CButton	m_delete;
	CListCtrlEx	m_list;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_profiles)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:
	void update_controls();

	// Generated message map functions
	//{{AFX_MSG(Cdlg_profiles)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnEdit();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnActivate();
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
	int m_selected_profile;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_)
