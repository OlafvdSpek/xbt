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
	if (!GetSafeHwnd() || !GetHeaderCtrl())
		return;
	for (int i = 0; i < GetHeaderCtrl()->GetItemCount(); i++)
		SetColumnWidth(i, LVSCW_AUTOSIZE_USEHEADER);
}

DWORD CListCtrlEx::GetItemData(int nItem) const
{
	return nItem == -1 ? -1 : CListCtrl::GetItemData(nItem);
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
		if (GetKeyState(VK_CONTROL) < 0)
		{
			switch (pMsg->wParam)
			{
			case 'A':
				{
					for (int i = 0; i < GetItemCount(); i++)
						SetItemState(i, LVIS_SELECTED, LVIS_SELECTED);
				}
				return true;
			case VK_ADD:
				auto_size();
				return true;
			}
		}
	}
	return CListCtrl::PreTranslateMessage(pMsg);
}
