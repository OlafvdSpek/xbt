// XBT ClientDlg.h : header file
//

#if !defined(AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_)
#define AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "stream_reader.h"

/////////////////////////////////////////////////////////////////////////////
// CXBTClientDlg dialog

class CXBTClientDlg : public ETSLayoutDialog
{
// Construction
public:
	void server(Cserver& server);
	void auto_size_files();
	void auto_size_peers();
	void auto_size();
	void fill_peers();
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
	public:
	virtual BOOL PreTranslateMessage(MSG* pMsg);
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);	// DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:
	afx_msg void OnContextMenu(CWnd*, CPoint point);
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
	afx_msg void OnTimer(UINT nIDEvent);
	afx_msg void OnPopupOpen();
	afx_msg void OnPopupClose();
	afx_msg void OnUpdatePopupClose(CCmdUI* pCmdUI);
	afx_msg void OnPopupOptions();
	afx_msg void OnDropFiles(HDROP hDropInfo);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	struct t_peer
	{
		in_addr host;
		int port;
		string peer_id;
		__int64 downloaded;
		__int64 left;
		__int64 uploaded;
		int down_rate;
		int up_rate;
		bool local_link;
		bool local_choked;
		bool local_interested;
		bool remote_choked;
		bool remote_interested;
		bool removed;
	};

	typedef map<int, t_peer> t_peers;

	struct t_file
	{
		string info_hash;
		string name;
		t_peers peers;
		__int64 downloaded;
		__int64 left;
		__int64 size;
		__int64 uploaded;
		int down_rate;
		int up_rate;
		int c_leechers;
		int c_seeders;
		bool removed;
	};

	typedef map<int, t_file> t_files;

	void read_peer_dump(t_file& f, Cstream_reader& sr);
	void read_file_dump(Cstream_reader& sr);
	void read_server_dump(Cstream_reader& sr);

	string m_buffer[4];
	int m_buffer_w;
	t_file* m_file;
	t_files m_files_map;
	Cserver* m_server;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_XBTCLIENTDLG_H__24B01140_CC8B_4862_B4FD_31A9CF22FAF8__INCLUDED_)
