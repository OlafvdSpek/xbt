#if !defined(AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_)
#define AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000
// dlg_trackers.h : header file
//

/////////////////////////////////////////////////////////////////////////////
// Cdlg_trackers dialog

class Cdlg_trackers : public ETSLayoutDialog
{
// Construction
public:
	struct t_tracker
	{
		string m_tracker;
		string m_user;
		string m_pass;
	};

	typedef map<int, t_tracker> t_trackers;

	void auto_size();
	void insert(const t_tracker&);
	Cdlg_trackers(CWnd* pParent);   // standard constructor

	const t_trackers& trackers()
	{
		return m_trackers;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_trackers)
	enum { IDD = IDD_TRACKERS };
	CListCtrl	m_list;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_trackers)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_trackers)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnDelete();
	afx_msg void OnEdit();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	string m_buffer[4];
	int m_buffer_w;
	t_trackers m_trackers;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_TRACKERS_H__4F7D710B_0573_4CEE_8776_CFE2D4399201__INCLUDED_)
