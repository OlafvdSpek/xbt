#if !defined(AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_)
#define AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#pragma warning(disable: 4244 4554 4800)
#endif // _MSC_VER > 1000

#define NOMINMAX

#include <asio.hpp>
#include <cassert>
#include <ctime>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <sstream>
#include <string>
#include <vector>

#ifdef WIN32
#include <io.h>
#include <windows.h>

#define atoll _atoi64
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
#endif
#include "bt_misc.h"
#include "bvalue.h"
#include "vartypes.h"
#include "virtual_binary.h"

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_STDAFX_H__65F76B17_AF08_4DB2_B7D8_399776542FE6__INCLUDED_)
