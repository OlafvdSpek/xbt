#if !defined(AFX_DLG_FILES_H__1776133D_5BC8_49B8_97C2_899E651875C9__INCLUDED_)
#define AFX_DLG_FILES_H__1776133D_5BC8_49B8_97C2_899E651875C9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_files.h : header file
//

#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_files dialog

class Cserver;

class Cdlg_files : public ETSLayoutDialog
{
// Construction
public:
	int compare(int id_a, int id_b) const;
	void sort();
	void auto_size();
	void load_data();
	Cdlg_files(CWnd* pParent, Cserver& server, const string& info_hash);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_files)
	enum { IDD = IDD_FILES };
	CListCtrl	m_files;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_files)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_files)
	virtual BOOL OnInitDialog();
	afx_msg void OnSize(UINT nType, int cx, int cy);
	afx_msg void OnTimer(UINT nIDEvent);
	afx_msg void OnDecreasePriority();
	afx_msg void OnIncreasePriority();
	afx_msg void OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnColumnclickFiles(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnOpen();
	afx_msg void OnDblclkFiles(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnExplore();
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	struct t_map_entry
	{
		string hash;
		__int64 left;
		string name;
		int priority;
		__int64 size;
	};

	typedef map<int, t_map_entry> t_map;

	string m_buffer[4];
	int m_buffer_w;
	string m_info_hash;
	t_map m_map;
	string m_name;
	Cserver& m_server;
	int m_sort_column;
	int m_sort_reverse;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_FILES_H__1776133D_5BC8_49B8_97C2_899E651875C9__INCLUDED_)
