<?php
	$title = 'XBT Tracker';
	include('../top.php');
?>
<h2>Overview</h2>

XBT Tracker is a BitTorrent tracker written in C++.
It's designed to offer high performance while consuming little resources (CPU and RAM).
It's a pure tracker, so it doesn't offer a frontend. You can use any (PHP) frontend you want.

<p>
XBT Tracker listens on port 2710. The announce URL is http://...:2710/announce. The debug URL is http://...:2710/debug. The scrape URL is http://...:2710/scrape.
The statistics URL is http://...:2710/statistics.
An experimental UDP tracker extension is also supported via announce URL udp://...:2710.

<hr>
<h2>MySQL</h2>

The tracker stores stats in a MySQL database/table.
Version >= 5 is required.
Create a database (xbt) and a user (xbt) with password for the tracker and use these in the next step.
Create the tables defined in xbt_tracker.sql.

<hr>
<h2>Installing under Windows</h2>

<ol>
	<li>Download XBT Tracker from <a href="http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113737">http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113737</a>.
	<li>Run the executable.
	<li>Update xbt_tracker.conf with the database, host, user and pass of your MySQL setup.
</ol>

<p>
There are two ways to run the tracker under Windows (NT, 2000, XP and 2003).
The first way is to run the tracker manually, like every other application.
The second way is to run the tracker as service.
The advantage of this way is that it also runs when no user is logged in.

<ol>
	<li>Open a command window (Start - Run - cmd).
	<li>Run net start "XBT Tracker"
</ol>

<hr>
<h2>Starting under Windows</h2>

Just start the executable. An empty DOS window should appear.
<hr>
<h2>Installing under Linux</h2>

The following commands can be used to install the dependencies on Debian.
The g++ version should be at least 3.4.
<pre>
apt-get install cmake g++ libboost-date-time-dev libboost-dev libboost-filesystem-dev libboost-program-options-dev libboost-regex-dev libboost-serialization-dev libmysqlclient15-dev make subversion zlib1g-dev
</pre>

The following commands can be used to install some of the dependencies on CentOS, Fedora Core and Red Hat.
The g++ version should be at least 3.4.
<pre>
yum install boost-devel gcc-c++ mysql-devel subversion
</pre>

Enter the following commands in a terminal.
Be patient while g++ is running, it'll take a few minutes.
<pre>
svn co http://xbt.googlecode.com/svn/trunk/xbt/misc xbt/misc
svn co http://xbt.googlecode.com/svn/trunk/xbt/Tracker xbt/Tracker
cd xbt/Tracker
./make.sh
cp xbt_tracker.conf.default xbt_tracker.conf
</pre>
<hr>
<h2>Starting under Linux</h2>

Enter the following commands in a terminal.
<pre>
./xbt_tracker
</pre>
<hr>
<h2>Stopping under Linux</h2>

Enter the following commands in a terminal.
<pre>
killall xbt_tracker
</pre>
<hr>
<h2>Configuration</h2>

<p>
The tracker reads it's configuration from the file xbt_tracker.conf and the SQL table xbt_config.
There is no need to insert default values into this table.

<table>
	<tr>
		<th align=left>name
		<th align=left>
		<th align=right>default value
	<tr>
		<td>announce_interval
		<td>
		<td align=right>1800
	<tr>
		<td>anonymous_connect
		<td>
		<td align=right>1
	<tr>
		<td>anonymous_announce
		<td>
		<td align=right>1
	<tr>
		<td>anonymous_scrape
		<td>
		<td align=right>1
	<tr>
		<td>auto_register
		<td>
		<td align=right>1
	<tr>
		<td>clean_up_interval
		<td>
		<td align=right>60
	<tr>
		<td>daemon
		<td>
		<td align=right>1
	<tr>
		<td>debug
		<td>
		<td align=right>0
	<tr>
		<td>full_scrape
		<td>
		<td align=right>0
	<tr>
		<td>gzip_debug
		<td>
		<td align=right>1
	<tr>
		<td>gzip_scrape
		<td>
		<td align=right>1
	<tr>
		<td>listen_ipa
		<td>
		<td align=right>*
	<tr>
		<td>listen_port
		<td>
		<td align=right>2710
	<tr>
		<td>log_access
		<td>
		<td align=right>0
	<tr>
		<td>log_announce
		<td>
		<td align=right>0
	<tr>
		<td>log_scrape
		<td>
		<td align=right>0
	<tr>
		<td>offline_message
		<td>
		<td>
	<tr>
		<td>pid_file
		<td>
		<td>xbt_tracker.pid
	<tr>
		<td>read_config_interval
		<td>
		<td align=right>60
	<tr>
		<td>read_db_interval
		<td>
		<td align=right>60
	<tr>
		<td>redirect_url
		<td>
		<td>
	<tr>
		<td>scrape_interval
		<td>
		<td align=right>0
	<tr>
		<td>table_announce_log
		<td>
		<td>xbt_announce_log
	<tr>
		<td>table_files
		<td>
		<td>xbt_files
	<tr>
		<td>table_files_users
		<td>
		<td>xbt_files_users
	<tr>
		<td>table_scrape_log
		<td>
		<td>xbt_scrape_log
	<tr>
		<td>table_users
		<td>
		<td>xbt_users
	<tr>
		<td>write_db_interval
		<td>
		<td align=right>15
</table>
<hr>
<h2>Auto Register</h2>

<p>
If auto_register is on, the tracker will track any torrent.
If it's off, the tracker will only track torrents (identified by info_hash) that are in the xbt_files table.

<pre>
insert into xbt_files (info_hash, mtime, ctime) values ('&lt;info_hash>', unix_timestamp(), unix_timestamp()); // insert
update xbt_files set flags = 1 where info_hash = '&lt;info_hash>'; // delete
</pre>
<hr>
<h2>Anonymous Announce</h2>

<p>
If anonymous_announce is on, the tracker will serve any user.
If it's off, the tracker will only serve users (identified by torrent_pass) that are in the xbt_users table.

<p>
The torrent_pass field in xbt_users contains the 32 char torrent_pass. The announce URL contains the same torrent_pass: /&lt;torrent_pass>/announce

<h2>Tables</h2>

<a href="http://visigod.com/xbt-tracker/table-documentation">Table Documentation</a>

<?php
	include('../bottom.php');
