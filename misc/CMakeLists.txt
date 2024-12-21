cmake_minimum_required(VERSION 3.10)
set(CMAKE_BUILD_TYPE release)
set(CMAKE_CXX_STANDARD 17)
include_directories(.)
add_library(
	xbt
	bt_misc.cpp
	database.cpp
	sql_query.cpp
	xcc_z.cpp
)
target_link_libraries(xbt mysqlclient z)
install(TARGETS xbt DESTINATION lib)
