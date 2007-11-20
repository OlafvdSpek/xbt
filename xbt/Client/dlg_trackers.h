#pragma once

#include "ListCtrlEx.h"
#include "resource.h"

class Cdlg_trackers: public ETSLayoutDialog
{
public:
	struct t_tracker
	{
		std::string m_tracker;
		std::string m_user;
		std::string m_pass;
	};

	typedef std::map<int, t_tracker> t_trackers;

	void insert(const t_tracker&);
	Cdlg_trackers(CWnd* pParent);

	const t_trackers& trackers() const
	{
		return m_trackers;
	}

	enum { IDD = IDD_TRACKERS };
	CButton	m_delete;
	CButton	m_edit;
	CListCtrlEx	m_list;
protected:
	void update_controls();
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnDelete();
	afx_msg void OnEdit();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	DECLARE_MESSAGE_MAP()
private:
	t_trackers m_trackers;
};
