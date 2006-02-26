#if !defined(AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_)
#define AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

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

	//{{AFX_DATA(Cdlg_scheduler)
	enum { IDD = IDD_SCHEDULER };
	CButton	m_delete;
	CButton	m_edit;
	CListCtrlEx	m_list;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_scheduler)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	void update_controls();

	//{{AFX_MSG(Cdlg_scheduler)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnEdit();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
	t_profiles m_profiles;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_SCHEDULER_H__E6C3F345_9345_47C0_9239_5954C1CADA62__INCLUDED_)
