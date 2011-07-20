#pragma once
#pragma warning(disable: 4244 4554 4800)

#define _WIN32_IE 0x0500
#define FD_SETSIZE 1024
#define NOMINMAX
#define VC_EXTRALEAN
#define atoll _atoi64

#include <afxwin.h>
#include <afxext.h>
#include <afxdtctl.h>
#ifndef _AFX_NO_AFXCMN_SUPPORT
#include <afxcmn.h>
#endif

#include <afxsock.h>
#include <boost/algorithm/string.hpp>
#include <boost/foreach.hpp>
#include <cassert>
#include <fstream>
#include <io.h>
#include <list>
#include <map>
#include <natupnp.h>
#include <set>
#include <sha1.h>
#include <shlwapi.h>
#include <sstream>
#include <string>
#include <sys/stat.h>
#include <vector>
#include <windows/ETSLayout.h>
#include <xbt/bt_misc.h>
#include <xbt/virtual_binary.h>

inline long long max(long long a, long long b)
{
	return a > b ? a : b;
}

inline long long min(long long a, long long b)
{
	return a < b ? a : b;
}
