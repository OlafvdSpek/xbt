#if !defined(AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_)
#define AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// dlg_profiles.h : header file
//

#include "ListCtrlEx.h"
#include "resource.h"

/////////////////////////////////////////////////////////////////////////////
// Cdlg_profiles dialog

class Cdlg_profiles: public ETSLayoutDialog
{
// Construction
public:
	struct t_entry
	{
		string name;
		int upload_rate;
		bool upload_rate_enable;
		int upload_slots;
		bool upload_slots_enable;
		int seeding_ratio;
		bool seeding_ratio_enable;
		int peer_limit;
		bool peer_limit_enable;
		int torrent_limit;
		bool torrent_limit_enable;
	};

	typedef map<int, t_entry> t_entries;

	void insert(const t_entry&);
	Cdlg_profiles(CWnd* pParent = NULL);   // standard constructor

	const t_entries& entries() const
	{
		return m_entries;
	}

// Dialog Data
	//{{AFX_DATA(Cdlg_profiles)
	enum { IDD = IDD_PROFILES };
	CListCtrlEx	m_list;
	//}}AFX_DATA


// Overrides
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Cdlg_profiles)
	protected:
	virtual void DoDataExchange(CDataExchange* pDX);    // DDX/DDV support
	//}}AFX_VIRTUAL

// Implementation
protected:

	// Generated message map functions
	//{{AFX_MSG(Cdlg_profiles)
	virtual BOOL OnInitDialog();
	afx_msg void OnInsert();
	afx_msg void OnEdit();
	afx_msg void OnDelete();
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	string m_buffer[4];
	int m_buffer_w;
	t_entries m_entries;
};

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_DLG_PROFILES_H__59531AC8_BA48_4E7D_8D35_49CB3F3B9D0F__INCLUDED_)
