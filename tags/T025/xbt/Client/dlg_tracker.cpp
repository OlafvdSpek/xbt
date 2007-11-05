#include "stdafx.h"
#include "dlg_tracker.h"

Cdlg_tracker::Cdlg_tracker(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(Cdlg_tracker::IDD, pParent, "Cdlg_tracker")
{
	//{{AFX_DATA_INIT(Cdlg_tracker)
	m_pass = _T("");
	m_tracker = _T("");
	m_user = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_tracker::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_tracker)
	DDX_Text(pDX, IDC_PASS, m_pass);
	DDX_Text(pDX, IDC_TRACKER, m_tracker);
	DDX_Text(pDX, IDC_USER, m_user);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_tracker, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_tracker)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_tracker::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_TRACKER_STATIC, NORESIZE)
			<< item(IDC_TRACKER, ABSOLUTE_VERT)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_USER_STATIC, NORESIZE)
			<< item(IDC_USER, ABSOLUTE_VERT)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_PASS_STATIC, NORESIZE)
			<< item(IDC_PASS, ABSOLUTE_VERT)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();
	return true;
}
