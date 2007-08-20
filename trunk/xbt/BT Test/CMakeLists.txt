set(CMAKE_BUILD_TYPE release)
include_directories(. ../misc)
add_executable(
	xbt_client_backend
	../misc/alerts.cpp
	../misc/bt_misc.cpp
	../misc/bt_tracker_account.cpp
	../misc/bt_tracker_url.cpp
	../misc/bvalue.cpp
	../misc/sha1.cpp
	../misc/socket.cpp
	../misc/virtual_binary.cpp
	../misc/xcc_z.cpp
	../misc/xif_key.cpp
	../misc/xif_value.cpp
	block_list.cpp
	bt_hasher.cpp
	bt_piece.cpp
	"BT Test.cpp"
	http_link.cpp
	ring_buffer.cpp
	version_check_handler.cpp
	bt_admin_link.cpp
	bt_link.cpp
	bt_piece_data.cpp
	bt_tracker_link.cpp
	http_response_handler.cpp
	scheduler.cpp
	bt_file.cpp
	bt_peer_data.cpp
	bt_pl_write_data.cpp
	config.cpp
	merkle_tree.cpp
	server.cpp
	bt_file_data.cpp
	bt_peer_link.cpp
	bt_sub_file_data.cpp
	data_counter.cpp
	profiles.cpp
)
target_link_libraries (xbt_client_backend boost_filesystem-mt boost_program_options-mt z)
