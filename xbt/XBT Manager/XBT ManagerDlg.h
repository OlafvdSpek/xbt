// XBT ManagerDlg.h : header file
//

#if !defined(AFX_XBTMANAGERDLG_H__7EF696C3_D9AD_467D_8F2E_9DC97F402FCB__INCLUDED_)
#define AFX_XBTMANAGERDLG_H__7EF696C3_D9AD_467D_8F2E_9DC97F402FCB__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerDlg dialog

class CXBTManagerDlg : public ETSLayoutDialog
{
// Construction
public:
	void sort(int column);
	int compare(LPARAM lParam1, LPARAM lParam2);
	void load(const char* name);
	void save(const char* name);
	void insert(const string& name);
	void auto_resize();
	CXBTManagerDlg(CWnd* pParent = NULL);	// standard constructor

// Dialog Data
	//{{AFX_DATA(CXBTManagerDlg)
	enum { IDD = IDD_XBTMANAGER_DIALOG };
	CListCtrl	m_list;
	//}}AFX_DATA

	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(CXBTManagerDlg)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);	// DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:
	HICON m_hIcon;

	// Generated message map functions
	//{{AFX_MSG(CXBTManagerDlg)
	virtual BOOL OnInitDialog();
	afx_msg void OnPaint();
	afx_msg HCURSOR OnQueryDragIcon();
	afx_msg void OnDropFiles(HDROP hDropInfo);
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	virtual void OnOK();
	afx_msg void OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	struct t_map_entry
	{
		string fname;
		string info_hash;
		string name;
		string tracker;
		int leechers;
		int seeders;
	};

	typedef map<int, t_map_entry> t_map;

	string m_buffer[4];
	int m_buffer_w;
	t_map m_map;
	int m_sort_column;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTMANAGERDLG_H__7EF696C3_D9AD_467D_8F2E_9DC97F402FCB__INCLUDED_)
