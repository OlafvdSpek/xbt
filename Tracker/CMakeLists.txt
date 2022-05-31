cmake_minimum_required(VERSION 3.7)
project(xbt-tracker)
set(CMAKE_BUILD_TYPE release)
# set(CMAKE_CXX_COMPILER clang++)
set(CMAKE_CXX_STANDARD 17)
set(CPACK_GENERATOR DEB)
set(CPACK_PACKAGE_CONTACT "Olaf van der Spek <olafvdspek@gmail.com>")
set(CPACK_STRIP_FILES true)
include_directories(. ../misc)
include(CheckIncludeFileCXX)
check_include_file_cxx(sys/epoll.h HAVE_SYS_EPOLL)
if(HAVE_SYS_EPOLL)
	add_definitions(-DEPOLL)
endif()
check_include_file_cxx(systemd/sd-daemon.h HAVE_SYSTEMD)
if(HAVE_SYSTEMD)
	add_definitions(-DXBT_SYSTEMD)
endif()
# set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -pedantic -Wall -Wextra")
# set(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -Werror -Wno-implicit-fallthrough -Wno-parentheses -Wno-sign-compare")

set(tracker_SOURCES
	../misc/bt_misc.cpp
	../misc/database.cpp
	../misc/sha1.cpp
	../misc/socket.cpp
	../misc/sql_query.cpp
	../misc/xcc_z.cpp
	config.cpp
	connection.cpp
	epoll.cpp
	tracker.cpp
	tracker_input.cpp
	transaction.cpp
)

if(WIN32)
set(tracker_SOURCES
	${tracker_SOURCES}
	../misc/windows/nt_service.cpp
	"XBT Tracker.rc")
endif()

add_executable(
	xbt_tracker
	${tracker_SOURCES}
)

if(WIN32)
set(tracker_LIBS ws2_32)
else()
set(tracker_LIBS systemd)
endif()

target_link_libraries(
	xbt_tracker
	mysqlclient
	z
	${tracker_LIBS}
)
install(TARGETS xbt_tracker DESTINATION bin)
include(CPack)
