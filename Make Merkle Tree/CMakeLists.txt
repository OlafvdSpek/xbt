cmake_minimum_required(VERSION 2.4)
set(CMAKE_BUILD_TYPE release)
include_directories(. ../misc)
add_executable(
	xbt_make_merkle_tree
	../misc/bt_misc.cpp
	../misc/sha1.cpp
	"XBT Make Merkle Tree.cpp"
)
