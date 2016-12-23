#pragma once

#include "ListCtrlEx.h"
#include "../bt test/profiles.h"
#include "../bt test/scheduler.h"
#include "resource.h"

class Cdlg_scheduler: public ETSLayoutDialog
{
public:
	typedef Cprofiles t_profiles;
	typedef Cscheduler_entry t_entry;
	typedef Cscheduler t_entries;

	void insert(const t_entry&);
	Cdlg_scheduler(CWnd* pParent = NULL);

	const t_entries& entries() const
	{
		return m_entries;
	}

	void entries(const t_entries& v)
	{
		m_entries = v;
	}

	void profiles(const t_profiles& v)
	{
		m_profiles = v;
	}

	enum { IDD = IDD_SCHEDULER };
	CButton	m_delete;
	CButton	m_edit;
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
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
	t_profiles m_profiles;
};
