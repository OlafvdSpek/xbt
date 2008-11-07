#pragma once

#include <boost/algorithm/string.hpp>
#include <boost/foreach.hpp>
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

#ifdef WIN32
#define FD_SETSIZE 1024
#define NOMINMAX

#include <io.h>
#include <natupnp.h>
#include <shlobj.h>

#define atoll _atoi64
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

#if defined(__APPLE__) && defined(__MACH__)
#define O_LARGEFILE 0
#define _lseeki64 lseek
#else
#define _lseeki64 lseek64
#endif
#define O_BINARY 0
#endif

inline long long max(long long a, long long b)
{
	return a > b ? a : b;
}

inline long long min(long long a, long long b)
{
	return a < b ? a : b;
}

#include <bt_misc.h>
#include <bvalue.h>
#include <socket.h>
#include <virtual_binary.h>

typedef unsigned char byte;
