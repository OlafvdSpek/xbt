// dlg_trackers.cpp : implementation file
//

#include "stdafx.h"
#include "dlg_trackers.h"

#include "dlg_tracker.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_trackers dialog


Cdlg_trackers::Cdlg_trackers(CWnd* pParent):
	ETSLayoutDialog(Cdlg_trackers::IDD, pParent, "Cdlg_trackers")
{
	//{{AFX_DATA_INIT(Cdlg_trackers)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_trackers::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_trackers)
	DDX_Control(pDX, IDC_LIST, m_list);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_trackers, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_trackers)
	ON_BN_CLICKED(IDC_INSERT, OnInsert)
	ON_BN_CLICKED(IDC_DELETE, OnDelete)
	ON_BN_CLICKED(IDC_EDIT, OnEdit)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_WM_SIZE()
	ON_NOTIFY(NM_DBLCLK, IDC_LIST, OnDblclkList)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_trackers message handlers

BOOL Cdlg_trackers::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< item(IDC_LIST, GREEDY)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item(IDC_INSERT, NORESIZE)
			<< item(IDC_EDIT, NORESIZE)
			<< item(IDC_DELETE, NORESIZE)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();

	m_list.InsertColumn(0, "Tracker");
	m_list.InsertColumn(1, "User");
	for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
		m_list.SetItemData(m_list.InsertItem(m_list.GetItemCount(), LPSTR_TEXTCALLBACK), i->first);
	m_list.auto_size();
	return true;
}

void Cdlg_trackers::OnInsert()
{
	Cdlg_tracker dlg(this);
	if (IDOK != dlg.DoModal())
		return;
	t_tracker e;
	e.m_tracker = dlg.m_tracker;
	e.m_user = dlg.m_user;
	e.m_pass = dlg.m_pass;
	insert(e);
	m_list.auto_size();
}

void Cdlg_trackers::OnEdit()
{
	int index = m_list.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	int id = m_list.GetItemData(index);
	t_tracker& e = m_trackers.find(id)->second;
	Cdlg_tracker dlg(this);
	dlg.m_tracker = e.m_tracker.c_str();
	dlg.m_user = e.m_user.c_str();
	dlg.m_pass = e.m_pass.c_str();
	if (IDOK != dlg.DoModal())
		return;
	e.m_tracker = dlg.m_tracker;
	e.m_user = dlg.m_user;
	e.m_pass = dlg.m_pass;
	m_list.Update(index);
}

void Cdlg_trackers::OnDelete()
{
	int index = m_list.GetNextItem(-1, LVNI_FOCUSED);
	if (index == -1)
		return;
	m_list.DeleteItem(index);
	m_trackers.erase(m_list.GetItemData(index));
}

void Cdlg_trackers::insert(const t_tracker& e)
{
	int id = m_trackers.empty() ? 0 : m_trackers.rbegin()->first + 1;
	m_trackers[id] = e;
	if (m_list.GetSafeHwnd())
		m_list.SetItemData(m_list.InsertItem(m_list.GetItemCount(), LPSTR_TEXTCALLBACK), id);
}

void Cdlg_trackers::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = (LV_DISPINFO*)pNMHDR;
	m_buffer[++m_buffer_w &= 3].erase();
	const t_tracker& e = m_trackers.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		m_buffer[m_buffer_w] = e.m_tracker;
		break;
	case 1:
		m_buffer[m_buffer_w] = e.m_user;
		break;
	case 2:
		m_buffer[m_buffer_w] = e.m_pass;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void Cdlg_trackers::OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult)
{
	OnEdit();
	*pResult = 0;
}
