cmake_minimum_required(VERSION 2.4)
set(CMAKE_BUILD_TYPE release)
include_directories(.)
add_library(
	xbt
	bt_misc.cpp
	sql/database.cpp
	sql/sql_query.cpp
	sql/sql_result.cpp
	virtual_binary.cpp
	xcc_z.cpp
)
install(TARGETS xbt DESTINATION lib)
