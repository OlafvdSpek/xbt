// stdafx.h : include file for standard system include files,
//  or project specific include files that are used frequently, but
//      are changed infrequently
//

#if !defined(AFX_STDAFX_H__934FECC7_0914_4243_9D96_05910F2591B5__INCLUDED_)
#define AFX_STDAFX_H__934FECC7_0914_4243_9D96_05910F2591B5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#pragma warning(disable: 4786)

#include <iostream>
#include <map>
#include <vector>

using namespace std;

#ifdef WIN32
#include <fcntl.h>
#include <io.h>
#include <windows.h>

#define atoll _atoi64
#define for if (0) {} else for

#pragma comment(lib, "ws2_32.lib")
#else
#define O_BINARY 0

typedef char __int8;
typedef short __int16;
typedef int __int32;
typedef long long __int64;
#endif
#include "bvalue.h"
#include "sha1.h"
#include "xcc_z.h"

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_STDAFX_H__934FECC7_0914_4243_9D96_05910F2591B5__INCLUDED_)
