#if !defined(AFX_DLG_BLOCK_LIST_H__A3BEF737_261C_4965_9098_F6F7080E09BD__INCLUDED_)
#define AFX_DLG_BLOCK_LIST_H__A3BEF737_261C_4965_9098_F6F7080E09BD__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_block_list.h : header file
//

#include "ListCtrlEx.h"
#include "../bt test/block_list.h"
#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_block_list dialog

class Cdlg_block_list: public ETSLayoutDialog
{
// Construction
public:
	typedef Cblock_list t_entries;

	Cdlg_block_list(CWnd* pParent = NULL);   // standard constructor

	const t_entries& entries() const
	{
		return m_entries;
	}

	void entries(const t_entries& v)
	{
		m_entries = v;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_block_list)
	enum { IDD = IDD_BLOCK_LIST };
	CListCtrlEx	m_list;
	CButton	m_delete;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_block_list)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:
	void update_controls();

	// Generated message map functions
	//{{AFX_MSG(Cdlg_block_list)
	virtual BOOL OnInitDialog();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	t_entries m_entries;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_BLOCK_LIST_H__A3BEF737_261C_4965_9098_F6F7080E09BD__INCLUDED_)
