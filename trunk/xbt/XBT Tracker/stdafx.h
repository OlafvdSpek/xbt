// stdafx.h : include file for standard system include files,
//  or project specific include files that are used frequently, but
//      are changed infrequently
//

#if !defined(AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_)
#define AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#pragma warning(disable: 4503 4786 4800)

#include <cassert>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <string>
#include <vector>

using namespace std;

#ifdef WIN32
#define FD_SETSIZE 1024

#include <windows.h>

#define atoll _atoi64

#pragma comment(lib, "libmysql.lib")
#pragma comment(lib, "ws2_32.lib")
#else
#include <netinet/in.h>
#include <sys/ioctl.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <cstdio>
#include <errno.h>
#include <signal.h>
#include <unistd.h>

#define stricmp strcasecmp
#define strnicmp strncasecmp

typedef char __int8;
typedef int __int32;
typedef long long __int64;
#endif
#include "bvalue.h"
#include "bt_misc.h"
#include "socket.h"
#include "virtual_binary.h"


//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_)
