#if !defined(AFX_DLG_MAKE_TORRENT_H__513CC546_788E_41DE_93DD_6D85B16FB6A9__INCLUDED_)
#define AFX_DLG_MAKE_TORRENT_H__513CC546_788E_41DE_93DD_6D85B16FB6A9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_make_torrent.h : header file
//

#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_make_torrent dialog

class Cdlg_make_torrent : public ETSLayoutDialog
{
// Construction
public:
	void post_insert();
	void auto_size();
	int compare(int id_a, int id_b) const;
	void insert(const string& name);
	void sort();
	Cdlg_make_torrent(CWnd* pParent = NULL);   // standard constructor

	const string& torrent_fname() const
	{
		return m_torrent_fname;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_make_torrent)
	enum { IDD = IDD_MAKE_TORRENT };
	CButton	m_save;
	CListCtrl	m_list;
	CString	m_tracker;
	CString	m_name;
	BOOL	m_use_merkle;
	CString	m_trackers;
	BOOL	m_seed_after_making;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_make_torrent)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_make_torrent)
	virtual BOOL OnInitDialog();
	afx_msg void OnDropFiles(HDROP hDropInfo);
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	afx_msg void OnSave();
	afx_msg void OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	struct t_map_entry
	{
		string name;
		__int64 size;
	};

	typedef map<int, t_map_entry> t_map;

	string m_buffer[4];
	int m_buffer_w;
	t_map m_map;
	int m_sort_column;
	bool m_sort_reverse;
	string m_torrent_fname;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_MAKE_TORRENT_H__513CC546_788E_41DE_93DD_6D85B16FB6A9__INCLUDED_)
