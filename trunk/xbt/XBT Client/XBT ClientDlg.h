// XBT ClientDlg.h : header file
//

#if !defined(AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_)
#define AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg dialog

class CXBTClientDlg : public ETSLayoutDialog
{
// Construction
public:
	void fill_peers();
	void auto_size();
	void open(const string& name);
	CXBTClientDlg(CWnd* pParent = NULL);	// standard constructor

// Dialog Data
	//{{AFX_DATA(CXBTClientDlg)
	enum { IDD = IDD_XBTCLIENT_DIALOG };
	CListCtrl	m_peers;
	CListCtrl	m_files;
	//}}AFX_DATA

	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(CXBTClientDlg)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);	// DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:
	HICON m_hIcon;

	// Generated message map functions
	//{{AFX_MSG(CXBTClientDlg)
	virtual BOOL OnInitDialog();
	afx_msg void OnPaint();
	afx_msg HCURSOR OnQueryDragIcon();
	afx_msg void OnGetdispinfoFiles(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnGetdispinfoPeers(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	afx_msg void OnItemchangedFiles(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	struct t_peer
	{
		string peer_id;
		__int64 downloaded;
		__int64 left;
		__int64 uploaded;
		int down_rate;
		int up_rate;
	};

	typedef map<int, t_peer> t_peers;

	struct t_file
	{
		string info_hash;
		string name;
		t_peers peers;
		__int64 downloaded;
		__int64 left;
		__int64 uploaded;
		int down_rate;
		int up_rate;
	};

	typedef map<int, t_file> t_files;

	string m_buffer[4];
	int m_buffer_w;
	t_file* m_file;
	t_files m_files_map;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_)
