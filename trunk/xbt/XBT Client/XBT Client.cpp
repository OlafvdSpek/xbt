// XBT Client.cpp : Defines the class behaviors for the application.
//

#include "stdafx.h"
#include "XBT Client.h"
#include "XBT ClientDlg.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// CXBTClientApp

BEGIN_MESSAGE_MAP(CXBTClientApp, CWinApp)
	//{{AFX_MSG_MAP(CXBTClientApp)
	//}}AFX_MSG
	ON_COMMAND(ID_HELP, CWinApp::OnHelp)
END_MESSAGE_MAP()

/////////////////////////////////////////////////////////////////////////////
// CXBTClientApp construction

CXBTClientApp::CXBTClientApp()
{
}

/////////////////////////////////////////////////////////////////////////////
// The one and only CXBTClientApp object

CXBTClientApp theApp;

/////////////////////////////////////////////////////////////////////////////
// CXBTClientApp initialization

BOOL CXBTClientApp::InitInstance()
{
	if (!AfxSocketInit())
	{
		AfxMessageBox(IDP_SOCKETS_INIT_FAILED);
		return FALSE;
	}

	// Standard initialization

#ifdef _AFXDLL
	Enable3dControls();			// Call this when using MFC in a shared DLL
#else
	Enable3dControlsStatic();	// Call this when linking to MFC statically
#endif

	CXBTClientDlg dlg;
	m_pMainWnd = &dlg;
	dlg.DoModal();

	// Since the dialog has been closed, return FALSE so that we exit the
	//  application, rather than start the application's message pump.
	return FALSE;
}
