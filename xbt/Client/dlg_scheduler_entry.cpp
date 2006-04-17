#include "stdafx.h"
#include "dlg_scheduler_entry.h"

Cdlg_scheduler_entry::Cdlg_scheduler_entry(CWnd* pParent /*=NULL*/):
	ETSLayoutDialog(Cdlg_scheduler_entry::IDD, pParent, "Cdlg_scheduler_entry")
{
	//{{AFX_DATA_INIT(Cdlg_scheduler_entry)
	m_hours = 0;
	m_minutes = 0;
	m_seconds = 0;
	//}}AFX_DATA_INIT
	m_profile_id = -1;
}


void Cdlg_scheduler_entry::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_scheduler_entry)
	DDX_Control(pDX, IDOK, m_ok);
	DDX_Control(pDX, IDC_PROFILE, m_profile);
	DDX_Text(pDX, IDC_HOURS, m_hours);
	DDV_MinMaxInt(pDX, m_hours, 0, 23);
	DDX_Text(pDX, IDC_MINUTES, m_minutes);
	DDV_MinMaxInt(pDX, m_minutes, 0, 59);
	DDX_Text(pDX, IDC_SECONDS, m_seconds);
	DDV_MinMaxInt(pDX, m_seconds, 0, 59);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_scheduler_entry, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_scheduler_entry)
	ON_CBN_SELCHANGE(IDC_PROFILE, OnSelchangeProfile)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_scheduler_entry::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< (pane(VERTICAL, NORESIZE)
				<< item(IDC_TIME_STATIC)
				<< item(IDC_PROFILE_STATIC)
				)
			<< (pane(VERTICAL, ABSOLUTE_VERT)
				<< (pane(HORIZONTAL, ABSOLUTE_VERT)
					<< item(IDC_HOURS)
					<< item(IDC_MINUTES)
					<< item(IDC_SECONDS)
				)
				<< item(IDC_PROFILE)
				)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();

	for (t_profiles::const_iterator i = m_profiles.begin(); i != m_profiles.end(); i++)
	{
		int index = m_profile.AddString(i->second.name.c_str());
		if (i->first == m_profile_id)
			m_profile.SetCurSel(index);
		m_profile.SetItemData(index, i->first);
	}
	update_controls();
	return true;
}

void Cdlg_scheduler_entry::OnOK()
{
	ETSLayoutDialog::OnOK();
	m_profile_id = m_profile.GetItemData(m_profile.GetCurSel());
}

void Cdlg_scheduler_entry::update_controls()
{
	m_ok.EnableWindow(m_profile.GetCurSel() != -1);
}

void Cdlg_scheduler_entry::OnSelchangeProfile()
{
	update_controls();

}
