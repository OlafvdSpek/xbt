// dlg_scheduler.cpp : implementation file
//

#include "stdafx.h"
#include "dlg_scheduler.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Cdlg_scheduler dialog


Cdlg_scheduler::Cdlg_scheduler(CWnd* pParent):
	ETSLayoutDialog(Cdlg_scheduler::IDD, pParent, "Cdlg_scheduler")
{
	//{{AFX_DATA_INIT(Cdlg_scheduler)
		// NOTE: the ClassWizard will add member initialization here
	//}}AFX_DATA_INIT
}


void Cdlg_scheduler::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_scheduler)
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
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// Cdlg_scheduler message handlers

BOOL Cdlg_scheduler::OnInitDialog()
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

	m_list.InsertColumn(0, "Time");
	m_list.InsertColumn(1, "Name");
	m_list.InsertColumn(2, "Value");
	m_list.auto_size();
	return true;
}

void Cdlg_scheduler::OnInsert()
{
}

void Cdlg_scheduler::OnEdit()
{
}

void Cdlg_scheduler::OnDelete()
{
}

void Cdlg_scheduler::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	m_buffer[++m_buffer_w &= 3].erase();
	switch (pDispInfo->item.iSubItem)
	{
	}
	pDispInfo->item.pszText = const_cast<char*>(m_buffer[m_buffer_w].c_str());
	*pResult = 0;
}

void Cdlg_scheduler::OnDblclkList(NMHDR* pNMHDR, LRESULT* pResult)
{
	OnEdit();
	*pResult = 0;
}
