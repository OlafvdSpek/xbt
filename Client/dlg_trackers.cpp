#include "stdafx.h"
#include "dlg_trackers.h"

#include "dlg_tracker.h"

Cdlg_trackers::Cdlg_trackers(CWnd* pParent):
	ETSLayoutDialog(Cdlg_trackers::IDD, pParent, "Cdlg_trackers")
{
	//{{AFX_DATA_INIT(Cdlg_trackers)
	//}}AFX_DATA_INIT
}


void Cdlg_trackers::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_trackers)
	DDX_Control(pDX, IDC_DELETE, m_delete);
	DDX_Control(pDX, IDC_EDIT, m_edit);
	DDX_Control(pDX, IDC_LIST, m_list);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_trackers, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_trackers)
	ON_BN_CLICKED(IDC_INSERT, OnInsert)
	ON_BN_CLICKED(IDC_DELETE, OnDelete)
	ON_BN_CLICKED(IDC_EDIT, OnEdit)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_NOTIFY(NM_DBLCLK, IDC_LIST, OnDblclkList)
	ON_WM_SIZE()
	ON_NOTIFY(LVN_ITEMCHANGED, IDC_LIST, OnItemchangedList)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_trackers::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	update_controls();
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
		m_list.InsertItemData(i->first);
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
	int index = m_list.GetNextItem(-1, LVNI_SELECTED);
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
	int index;
	while ((index = m_list.GetNextItem(-1, LVNI_SELECTED)) != -1)
	{
		m_trackers.erase(m_list.GetItemData(index));
		m_list.DeleteItem(index);
	}
}

void Cdlg_trackers::insert(const t_tracker& e)
{
	int id = m_trackers.empty() ? 0 : m_trackers.rbegin()->first + 1;
	m_trackers[id] = e;
	if (m_list.GetSafeHwnd())
		m_list.InsertItemData(id);
}

void Cdlg_trackers::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	std::string& buffer = m_list.get_buffer();
	const t_tracker& e = m_trackers.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		buffer = e.m_tracker;
		break;
	case 1:
		buffer = e.m_user;
		break;
	case 2:
		buffer = e.m_pass;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void Cdlg_trackers::OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult)
{
	OnEdit();
	*pResult = 0;
}

void Cdlg_trackers::update_controls()
{
	m_edit.EnableWindow(m_list.GetSelectedCount() == 1);
	m_delete.EnableWindow(m_list.GetSelectedCount());
}

void Cdlg_trackers::OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult)
{
	update_controls();
	*pResult = 0;
}
