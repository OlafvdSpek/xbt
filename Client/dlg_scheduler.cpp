#include "stdafx.h"
#include "dlg_scheduler.h"

#include "dlg_scheduler_entry.h"

Cdlg_scheduler::Cdlg_scheduler(CWnd* pParent):
	ETSLayoutDialog(Cdlg_scheduler::IDD, pParent, "Cdlg_scheduler")
{
	//{{AFX_DATA_INIT(Cdlg_scheduler)
	//}}AFX_DATA_INIT
}


void Cdlg_scheduler::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_scheduler)
	DDX_Control(pDX, IDC_DELETE, m_delete);
	DDX_Control(pDX, IDC_EDIT, m_edit);
	DDX_Control(pDX, IDC_LIST, m_list);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_scheduler, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_scheduler)
	ON_BN_CLICKED(IDC_INSERT, OnInsert)
	ON_BN_CLICKED(IDC_EDIT, OnEdit)
	ON_BN_CLICKED(IDC_DELETE, OnDelete)
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_NOTIFY(NM_DBLCLK, IDC_LIST, OnDblclkList)
	ON_NOTIFY(LVN_ITEMCHANGED, IDC_LIST, OnItemchangedList)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_scheduler::OnInitDialog()
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

	m_list.InsertColumn(0, "Time");
	m_list.InsertColumn(1, "Profile");
	for (t_entries::const_iterator i = m_entries.begin(); i != m_entries.end(); i++)
		m_list.InsertItemData(i->first);
	m_list.auto_size();
	return true;
}

void Cdlg_scheduler::OnInsert()
{
	Cdlg_scheduler_entry dlg(this);
	dlg.profiles(m_profiles);
	if (IDOK != dlg.DoModal())
		return;
	t_entry e;
	e.time = 60 * (60 * dlg.m_hours + dlg.m_minutes) + dlg.m_seconds;
	e.profile = dlg.m_profile_id;
	insert(e);
	m_list.auto_size();
}

void Cdlg_scheduler::OnEdit()
{
	int index = m_list.GetNextItem(-1, LVNI_SELECTED);
	if (index == -1)
		return;
	int id = m_list.GetItemData(index);
	t_entry& e = m_entries.find(id)->second;
	Cdlg_scheduler_entry dlg(this);
	dlg.profiles(m_profiles);
	dlg.m_hours = e.time / 3600;
	dlg.m_minutes = e.time % 3600 / 60;
	dlg.m_seconds = e.time % 60;
	dlg.m_profile_id = e.profile;
	if (IDOK != dlg.DoModal())
		return;
	e.time = 60 * (60 * dlg.m_hours + dlg.m_minutes) + dlg.m_seconds;
	e.profile = dlg.m_profile_id;
	m_list.Update(index);
}

void Cdlg_scheduler::OnDelete()
{
	int index;
	while ((index = m_list.GetNextItem(-1, LVNI_SELECTED)) != -1)
	{
		m_entries.erase(m_list.GetItemData(index));
		m_list.DeleteItem(index);
	}
}

void Cdlg_scheduler::insert(const t_entry& e)
{
	int id = m_entries.empty() ? 0 : m_entries.rbegin()->first + 1;
	m_entries[id] = e;
	if (m_list.GetSafeHwnd())
		m_list.InsertItemData(id);
}

void Cdlg_scheduler::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	std::string& buffer = m_list.get_buffer();
	const t_entry& e = m_entries.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		{
			char b[9];
			sprintf(b, "%02d:%02d:%02d", e.time / 3600, e.time / 60 % 60, e.time % 60);
			buffer = b;
		}
		break;
	case 1:
		buffer = m_profiles.find(e.profile)->second.name;
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

void Cdlg_scheduler::OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult)
{
	OnEdit();
	*pResult = 0;
}

void Cdlg_scheduler::update_controls()
{
	m_edit.EnableWindow(m_list.GetSelectedCount() == 1);
	m_delete.EnableWindow(m_list.GetSelectedCount());
}

void Cdlg_scheduler::OnItemchangedList(NMHDR* pNMHDR, LRESULT* pResult)
{
	update_controls();
	*pResult = 0;
}
