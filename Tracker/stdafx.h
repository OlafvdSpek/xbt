#pragma once

#define FD_SETSIZE 1024
#define NOMINMAX

#include <array>
#include <boost/format.hpp>
#include <boost/ptr_container/ptr_container.hpp>
#include <boost/utility.hpp>
#include <cassert>
#include <cerrno>
#include <csignal>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <set>
#include <sha1.h>
#include <socket.h>
#include <sstream>
#include <stream_int.h>
#include <string>
#include <sys/stat.h>
#include <unordered_map>
#include <vector>
#include <xbt/bt_misc.h>
#include <xbt/database.h>
#include <xbt/find_ptr.h>
#include <xbt/make_query.h>
#include <xbt/sql_query.h>
#include <xbt/to_array.h>
#include <xbt/xcc_z.h>
