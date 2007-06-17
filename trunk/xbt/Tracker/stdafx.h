#if !defined(AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_)
#define AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#pragma warning(disable: 4800)
#endif // _MSC_VER > 1000

#include <cassert>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <string>
#include <vector>

#ifdef WIN32
#define FD_SETSIZE 1024
#define NOMINMAX

#define atoll _atoi64
#else
#include <sys/types.h>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <sys/ioctl.h>
#include <sys/socket.h>
#include <cstdio>
#include <errno.h>
#include <signal.h>
#include <unistd.h>

#ifdef BSD
#include "bt_misc.h"

#define atoll xbt_atoll
#endif
#endif

typedef unsigned char byte;

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_STDAFX_H__442CF508_B879_4863_8154_1014EBBD78CA__INCLUDED_)
