// stdafx.h : include file for standard system include files,
//  or project specific include files that are used frequently, but
//      are changed infrequently
//

#if !defined(AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_)
#define AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#pragma warning(disable: 4503 4554 4786 4800)

#include <cassert>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <sstream>
#include <string>
#include <vector>

using namespace std;

#ifdef WIN32
#define FD_SETSIZE 1024

#include <io.h>
#include <windows.h>

#define atoll _atoi64
#define for if (0) {} else for

#pragma comment(lib, "ws2_32.lib")
#else
#include <netinet/in.h>
#include <sys/ioctl.h>
#include <sys/select.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <sys/types.h>
#include <cstdio>
#include <errno.h>
#include <signal.h>
#include <unistd.h>

#define O_BINARY 0
#define _lseeki64 lseek
#define stricmp strcasecmp
#define strnicmp strncasecmp

typedef char __int8;
typedef short __int16;
typedef int __int32;
typedef long long __int64;

inline __int64 max(__int64 a, __int64 b)
{
	return a > b ? a : b;
}

inline __int64 min(__int64 a, __int64 b)
{
	return a < b ? a : b;
}
#endif
#include "bt_misc.h"
#include "bvalue.h"
#include "socket.h"
#include "virtual_binary.h"

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_)
