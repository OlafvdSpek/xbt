#if !defined(AFX_DLG_TORRENT_H__87B855BB_F43A_4541_BA9F_CF1675E3E999__INCLUDED_)
#define AFX_DLG_TORRENT_H__87B855BB_F43A_4541_BA9F_CF1675E3E999__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000
// dlg_torrent.h : header file
//

/////////////////////////////////////////////////////////////////////////////
// Cdlg_torrent dialog

class Cdlg_torrent : public ETSLayoutDialog
{
// Construction
public:
	void auto_size();
	void load_data();
	Cdlg_torrent(CWnd* pParent, Cserver& server, const string& info_hash);   // standard constructor

// Dialog Data
	//{{AFX_DATA(Cdlg_torrent)
	enum { IDD = IDD_TORRENT };
	CListCtrl	m_alerts;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_torrent)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_torrent)
	virtual BOOL OnInitDialog();
	afx_msg void OnSize(UINT nType, int cx, int cy);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	Cserver& m_server;
	string m_info_hash;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_TORRENT_H__87B855BB_F43A_4541_BA9F_CF1675E3E999__INCLUDED_)
