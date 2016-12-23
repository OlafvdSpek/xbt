#pragma once

#ifndef __AFXWIN_H__
	#error include 'stdafx.h' before including this file for PCH
#endif

#include "resource.h"
#include "../bt test/server.h"

class CXBTClientApp: public CWinApp
{
public:
	static BOOL CALLBACK enumerator(HWND, LPARAM);
	virtual BOOL InitInstance();
	DECLARE_MESSAGE_MAP()
};
