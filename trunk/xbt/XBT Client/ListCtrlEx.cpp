#include "stdafx.h"
#include "ListCtrlEx.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

BEGIN_MESSAGE_MAP(CListCtrlEx, CListCtrl)
	//{{AFX_MSG_MAP(CListCtrlEx)
	ON_WM_SIZE()
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

void CListCtrlEx::auto_size()
{
	if (!GetSafeHwnd())
		return;
	for (int i = 0; i < GetHeaderCtrl()->GetItemCount(); i++)
		SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

void CListCtrlEx::OnSize(UINT nType, int cx, int cy) 
{
	CListCtrl::OnSize(nType, cx, cy);
	auto_size();
}
