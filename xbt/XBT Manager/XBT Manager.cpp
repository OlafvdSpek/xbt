// XBT Manager.cpp : Defines the class behaviors for the application.
//

#include "stdafx.h"
#include "XBT Manager.h"
#include "XBT ManagerDlg.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerApp

BEGIN_MESSAGE_MAP(CXBTManagerApp, CWinApp)
	//{{AFX_MSG_MAP(CXBTManagerApp)
	//}}AFX_MSG
	ON_COMMAND(ID_HELP, CWinApp::OnHelp)
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerApp construction

CXBTManagerApp::CXBTManagerApp()
{
}

/////////////////////////////////////////////////////////////////////////////
// The one and only CXBTManagerApp object

CXBTManagerApp theApp;

/////////////////////////////////////////////////////////////////////////////
// CXBTManagerApp initialization

BOOL CXBTManagerApp::InitInstance()
{
	if (!AfxSocketInit())
	{
		AfxMessageBox(IDP_SOCKETS_INIT_FAILED);
		return false;
	}

#ifdef _AFXDLL
	Enable3dControls();
#else
	Enable3dControlsStatic();
#endif
	SetRegistryKey("XBT");

	CXBTManagerDlg dlg;
	m_pMainWnd = &dlg;
	dlg.DoModal();

	return false;
}
