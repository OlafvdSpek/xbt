#pragma once
#pragma warning(disable: 4786)

#include <bvalue.h>
#include <ctime>
#include <fcntl.h>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <sha1.h>
#include <socket.h>
#include <vector>
#include <virtual_binary.h>
#include <xcc_z.h>

#ifdef WIN32
#include <io.h>

#define atoll _atoi64
#else
#include <stdint.h>
#endif
