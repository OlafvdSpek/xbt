#include "stdafx.h"
#include "ListCtrlEx1.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

BEGIN_MESSAGE_MAP(CListCtrlEx1, CListCtrl)
	//{{AFX_MSG_MAP(CListCtrlEx1)
	ON_WM_CONTEXTMENU()
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

CListCtrlEx1::Ccolumn::Ccolumn()
{
	index = -1;
	order = -1;
}

BOOL CListCtrlEx1::OnCommand(WPARAM wParam, LPARAM lParam) 
{
	Ccolumns::iterator i = m_columns.find(wParam);
	if (i != m_columns.end())
		ShowColumn(i->first, !i->second.show());
	return true;
}

void CListCtrlEx1::OnContextMenu(CWnd* pWnd, CPoint point)
{
	if (pWnd != GetHeaderCtrl())
	{
		CWnd* parent = pWnd->GetParent();		
		if (parent)
			parent->SendMessage(WM_CONTEXTMENU, reinterpret_cast<WPARAM>(pWnd), MAKELPARAM(point.x, point.y));
		return;
	}
	CMenu menu;
	if (!menu.CreatePopupMenu())
		return;
	for (Ccolumns::const_iterator i = m_columns.begin(); i != m_columns.end(); i++)
	{
		if (!i->second.name.empty())
			menu.InsertMenu(-1, MF_BYPOSITION | (i->second.show() ? MF_CHECKED : 0) | MF_STRING, i->first, i->second.name.c_str());
	}
	CPoint pt;
	GetCursorPos(&pt);
	menu.TrackPopupMenu(TPM_LEFTALIGN, pt.x, pt.y, this, NULL);
}

void CListCtrlEx1::PreSubclassWindow()
{
	CListCtrlEx::PreSubclassWindow();
	SetExtendedStyle(GetExtendedStyle() | LVS_EX_HEADERDRAGDROP);
}

void CListCtrlEx1::InsertColumn(int id, const string& name, int format)
{
	if (m_columns.count(id))
		return;
	Ccolumn& e = m_columns[id];
	e.format = format;
	e.name = name;
	e.order = m_columns.size() - 1;
	ShowColumn(id, true);
}

void CListCtrlEx1::DeleteAllColumns()
{
	while (GetHeaderCtrl()->GetItemCount())
		CListCtrl::DeleteColumn(0);
	m_columns.clear();
}

void CListCtrlEx1::ShowColumn(int id, bool show)
{
	Ccolumns::iterator i = m_columns.find(id);
	if (i == m_columns.end() || i->second.show() == show)
		return;
	if (show)
	{
		int index = m_columns.size();
		for (Ccolumns::iterator j = m_columns.begin(); j != m_columns.end(); j++)
		{
			if (j->second.show() && j->second.order > i->second.order)
			{
				if (j->second.index < index)
					index = j->second.index;
				j->second.index++;
			}
		}
		i->second.index = CListCtrlEx::InsertColumn(index, i->second.name.c_str(), i->second.format);
		SetColumnWidth(i->second.index, LVSCW_AUTOSIZE_USEHEADER);
	}
	else
	{
		CListCtrlEx::DeleteColumn(i->second.index);
		for (Ccolumns::iterator j = m_columns.begin(); j != m_columns.end(); j++)
		{
			if (j->second.index > i->second.index)
				j->second.index--;
		}
		i->second.index = -1;
	}
}

int CListCtrlEx1::GetColumnID(int index) const
{
	for (Ccolumns::const_iterator i = m_columns.begin(); i != m_columns.end(); i++)
	{
		if (i->second.index == index)
			return i->first;
	}
	return -1;
}
