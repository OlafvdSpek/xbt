#include "stdafx.h"
#include "XBT Client.h"
#include "XBT ClientDlg.h"

const extern UINT g_are_you_me_message_id;

BEGIN_MESSAGE_MAP(CXBTClientApp, CWinApp)
	//{{AFX_MSG_MAP(CXBTClientApp)
	//}}AFX_MSG
END_MESSAGE_MAP()

CXBTClientApp theApp;

BOOL CXBTClientApp::InitInstance()
{
	srand(time(NULL));
	CreateMutex(NULL, true, "9a6bfda6-7733-4b7d-92b0-3046c9191830");
	if (GetLastError() == ERROR_ALREADY_EXISTS)
	{
		HWND hWnd = NULL;
		EnumWindows(enumerator, reinterpret_cast<LPARAM>(&hWnd));
		CCommandLineInfo cmdInfo;
		ParseCommandLine(cmdInfo);
		if (cmdInfo.m_nShellCommand == CCommandLineInfo::FileOpen)
		{
			COPYDATASTRUCT cds;
			cds.dwData = 0;
			cds.lpData = const_cast<char*>(static_cast<const char*>(cmdInfo.m_strFileName));
			cds.cbData = cmdInfo.m_strFileName.GetLength();
			SendMessage(hWnd, WM_COPYDATA, NULL, reinterpret_cast<LPARAM>(&cds));
		}
		else
		{
			ShowWindow(hWnd, SW_SHOWMAXIMIZED);
			SetForegroundWindow(hWnd);
		}
		return false;
	}
	if (!AfxSocketInit())
	{
		AfxMessageBox(IDP_SOCKETS_INIT_FAILED);
		return false;
	}
	SetRegistryKey("XBT");

	CXBTClientDlg dlg;
	m_pMainWnd = &dlg;
	dlg.DoModal();

	return false;
}

BOOL CALLBACK CXBTClientApp::enumerator(HWND hWnd, LPARAM lParam)
{
	DWORD result;
	if (!SendMessageTimeout(hWnd, g_are_you_me_message_id, 0, 0, SMTO_BLOCK, 200, &result)
		|| result != g_are_you_me_message_id)
		return true;
	*reinterpret_cast<HWND*>(lParam) = hWnd;
	return false;
}