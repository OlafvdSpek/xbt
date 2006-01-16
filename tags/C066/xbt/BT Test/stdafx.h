#if !defined(AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_)
#define AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_

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
#include <natupnp.h>
#include <shlobj.h>
#include <windows.h>

#define atoll _atoi64
#define for if (0) {} else for

#pragma comment(lib, "ws2_32.lib")
#else
#include <sys/types.h>
#include <netinet/in.h>
#include <sys/ioctl.h>
#include <sys/select.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <cstdio>
#include <errno.h>
#include <signal.h>
#include <unistd.h>

#ifdef BSD
#define atoll xbt_atoll
#endif
#if defined(__APPLE__) && defined(__MACH__)
#define O_LARGEFILE 0
#define _lseeki64 lseek
#else
#define _lseeki64 lseek64
#endif
#define O_BINARY 0
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

#endif // !defined(AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_)
