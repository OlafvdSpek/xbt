#include "stdafx.h"
#include "ListCtrlEx.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

BEGIN_MESSAGE_MAP(CListCtrlEx, CListCtrl)
	//{{AFX_MSG_MAP(CListCtrlEx)
	ON_NOTIFY_REFLECT(NM_CUSTOMDRAW, OnCustomDraw)
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

void CListCtrlEx::OnCustomDraw(NMHDR* pNMHDR, LRESULT* pResult)
{
	NMLVCUSTOMDRAW* pCustomDraw = reinterpret_cast<NMLVCUSTOMDRAW*>(pNMHDR);
	switch (pCustomDraw->nmcd.dwDrawStage)
	{
	case CDDS_PREPAINT:
		*pResult = CDRF_NOTIFYITEMDRAW;
		break;
	case CDDS_ITEMPREPAINT:
		pCustomDraw->clrTextBk = pCustomDraw->nmcd.dwItemSpec & 1 ? RGB(0xf8, 0xf8, 0xf8) : RGB(0xff, 0xff, 0xff);
		break;
	}
}

void CListCtrlEx::OnSize(UINT nType, int cx, int cy)
{
	CListCtrl::OnSize(nType, cx, cy);
	auto_size();
}

void CListCtrlEx::PreSubclassWindow()
{
	CListCtrl::PreSubclassWindow();
	SetExtendedStyle(GetExtendedStyle() | LVS_EX_FULLROWSELECT);
}

BOOL CListCtrlEx::PreTranslateMessage(MSG* pMsg)
{
	if (pMsg->message == WM_KEYDOWN)
	{
		switch (pMsg->wParam)
		{
		case VK_ADD:
			auto_size();
			return true;
		}
	}
	return CListCtrl::PreTranslateMessage(pMsg);
}
