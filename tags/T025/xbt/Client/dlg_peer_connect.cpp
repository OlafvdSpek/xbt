#include "stdafx.h"
#include "dlg_peer_connect.h"

Cdlg_peer_connect::Cdlg_peer_connect(CWnd* pParent /*=NULL*/)
	: ETSLayoutDialog(Cdlg_peer_connect::IDD, pParent, "Cdlg_peer_connect")
{
	//{{AFX_DATA_INIT(Cdlg_peer_connect)
	m_host = _T("");
	m_port = 0;
	//}}AFX_DATA_INIT
}


void Cdlg_peer_connect::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_peer_connect)
	DDX_Text(pDX, IDC_HOST, m_host);
	DDX_Text(pDX, IDC_PORT, m_port);
	DDV_MinMaxInt(pDX, m_port, 0, 65535);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_peer_connect, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_peer_connect)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_peer_connect::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL)
			<< (pane(VERTICAL)
				<< item(IDC_HOST_STATIC, NORESIZE)
				<< item(IDC_PORT_STATIC, NORESIZE)
				)
			<< (pane(VERTICAL)
				<< item(IDC_HOST, ABSOLUTE_VERT)
				<< item(IDC_PORT, ABSOLUTE_VERT)
				)
			)
		<< (pane(HORIZONTAL)
			<< itemGrowing(HORIZONTAL)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();
	return true;
}
