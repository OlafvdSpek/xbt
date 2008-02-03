#pragma once

#include "ListCtrlEx.h"
#include "../bt test/profiles.h"
#include "resource.h"

class Cdlg_profiles: public ETSLayoutDialog
{
public:
	typedef Cprofile t_entry;
	typedef Cprofiles t_entries;

	void insert(const t_entry&);
	Cdlg_profiles(CWnd* pParent = NULL);

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

	enum { IDD = IDD_PROFILES };
	CButton	m_edit;
	CButton	m_activate;
	CButton	m_delete;
	CListCtrlEx	m_list;
protected:
	void update_controls();
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnEdit();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnActivate();
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
	int m_selected_profile;
};
