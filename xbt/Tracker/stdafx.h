#pragma once

#define FD_SETSIZE 1024
#define NOMINMAX

#include <array>
#include <boost/foreach.hpp>
#include <boost/format.hpp>
#include <boost/ptr_container/ptr_container.hpp>
#include <boost/smart_ptr.hpp>
#include <boost/utility.hpp>
#include <cassert>
#include <cstdio>
#include <cstring>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <sha1.h>
#include <signal.h>
#include <socket.h>
#include <sstream>
#include <stream_int.h>
#include <string>
#include <sys/stat.h>
#include <vector>
#include <xbt/bt_misc.h>
#include <xbt/database.h>
#include <xbt/find_ptr.h>
#include <xbt/sql_query.h>
#include <xbt/xcc_z.h>

#ifdef WIN32
#define atoll _atoi64
#else
#include <sys/types.h>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <sys/ioctl.h>
#include <sys/socket.h>
#include <cerrno>
#include <cstdio>
#include <signal.h>
#include <syslog.h>
#include <unistd.h>
#endif

typedef unsigned char byte;
