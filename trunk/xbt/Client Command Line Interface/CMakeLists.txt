cmake_minimum_required(VERSION 2.4)
set(CMAKE_BUILD_TYPE release)
include_directories(. ../misc)
add_executable(
	xbt_client_cli
	../misc/bt_misc.cpp
	../misc/bvalue.cpp
	../misc/socket.cpp
	../misc/virtual_binary.cpp
	../misc/xcc_z.cpp
	"XBT Client Command Line Interface.cpp"
)
target_link_libraries(xbt_client_cli boost_program_options-mt boost_system-mt z)
