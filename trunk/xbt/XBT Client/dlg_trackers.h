#if !defined(AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_)
#define AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "ListCtrlEx.h"
#include "resource.h"

class Cdlg_trackers: public ETSLayoutDialog
{
public:
	struct t_tracker
	{
		string m_tracker;
		string m_user;
		string m_pass;
	};

	typedef map<int, t_tracker> t_trackers;

	void insert(const t_tracker&);
	Cdlg_trackers(CWnd* pParent);

	const t_trackers& trackers() const
	{
		return m_trackers;
	}

	//{{AFX_DATA(Cdlg_trackers)
	enum { IDD = IDD_TRACKERS };
	CButton	m_delete;
	CButton	m_edit;
	CListCtrlEx	m_list;
	//}}AFX_DATA

	//{{AFX_VIRTUAL(Cdlg_trackers)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	//}}AFX_VIRTUAL
protected:
	void update_controls();

	//{{AFX_MSG(Cdlg_trackers)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnDelete();
	afx_msg void OnEdit();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_trackers m_trackers;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_)
