// stdafx.h : include file for standard system include files,
//  or project specific include files that are used frequently, but
//      are changed infrequently
//

#if !defined(AFX_STDAFX_H__0F969B55_78EF_41CC_9853_DD6DFC1EB356__INCLUDED_)
#define AFX_STDAFX_H__0F969B55_78EF_41CC_9853_DD6DFC1EB356__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#define VC_EXTRALEAN		// Exclude rarely-used stuff from Windows headers
#define atoll _atoi64

#include <afxwin.h>         // MFC core and standard components
#include <afxext.h>         // MFC extensions
#include <afxdtctl.h>		// MFC support for Internet Explorer 4 Common Controls
#ifndef _AFX_NO_AFXCMN_SUPPORT
#include <afxcmn.h>			// MFC support for Windows Common Controls
#endif // _AFX_NO_AFXCMN_SUPPORT

#include <afxsock.h>		// MFC socket extensions

#pragma warning(disable: 4786)

#include <cassert>
#include <fstream>
#include <map>
#include <string>
#include <vector>
#include "ETSLayout.h"

using namespace ETSLayout;
using namespace std;

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_STDAFX_H__0F969B55_78EF_41CC_9853_DD6DFC1EB356__INCLUDED_)
