project('xbt', 'cpp',
	default_options : ['cpp_std=c++17'],
	version : '1.0')

exe = executable('xbt_tracker', [
  'misc/bt_misc.cpp',
  'misc/database.cpp',
  'misc/sha1.cpp',
  'misc/socket.cpp',
  'misc/sql_query.cpp',
  'misc/xcc_z.cpp',
  'Tracker/config.cpp',
  'Tracker/connection.cpp',
  'Tracker/epoll.cpp',
  'Tracker/tracker.cpp',
  'Tracker/tracker_input.cpp',
  'Tracker/transaction.cpp',
],
dependencies : [
  dependency('mysqlclient'),
  dependency('zlib'),
],
include_directories : include_directories('misc'))

test('basic', exe)
