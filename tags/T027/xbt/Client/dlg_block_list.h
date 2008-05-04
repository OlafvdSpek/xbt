#pragma once

#include "ListCtrlEx.h"
#include "../bt test/block_list.h"
#include "resource.h"

class Cdlg_block_list: public ETSLayoutDialog
{
public:
	typedef Cblock_list t_entries;

	Cdlg_block_list(CWnd* pParent = NULL);

	const t_entries& entries() const
	{
		return m_entries;
	}

	void entries(const t_entries& v)
	{
		m_entries = v;
	}

	enum { IDD = IDD_BLOCK_LIST };
	CListCtrlEx	m_list;
	CButton	m_delete;
protected:
	void update_controls();
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
};
