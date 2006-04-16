#if !defined(AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_)
#define AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#pragma warning(disable: 4244 4554 4800)
#endif // _MSC_VER > 1000

#include <boost/algorithm/string.hpp>
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

using namespace boost;
using namespace std;

#ifdef WIN32
#define FD_SETSIZE 1024
#define NOMINMAX

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

typedef int __int32;
#endif

inline long long max(long long a, long long b)
{
	return a > b ? a : b;
}

inline long long min(long long a, long long b)
{
	return a < b ? a : b;
}

#include "bt_misc.h"
#include "bvalue.h"
#include "socket.h"
#include "virtual_binary.h"

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_STDAFX_H__6FD5A396_75BD_4D9F_BD48_029F34D66F2E__INCLUDED_)
