#if !defined(AFX_LISTCTRLEX_H__1A7B4E56_9CB8_4BA4_B5AC_AF5DA38C4285__INCLUDED_)
#define AFX_LISTCTRLEX_H__1A7B4E56_9CB8_4BA4_B5AC_AF5DA38C4285__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class CListCtrlEx: public CListCtrl
{
public:
	//{{AFX_VIRTUAL(CListCtrlEx)
	//}}AFX_VIRTUAL
public:
	void auto_size();
protected:
	//{{AFX_MSG(CListCtrlEx)
	afx_msg void OnCustomDraw(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
};

/////////////////////////////////////////////////////////////////////////////

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_LISTCTRLEX_H__1A7B4E56_9CB8_4BA4_B5AC_AF5DA38C4285__INCLUDED_)
