<?php
	$title = 'XBT Client';
	include('../top.php');
?>
<h2>Overview</h2>

XBT Client is a BitTorrent client written in C++.
It's designed to offer high performance while consuming little resources (CPU and RAM).

<p>
Features:
<ul>
	<li>Automatic, fast resume.
	<li>File queueing.
	<li>File priorities.
	<li>Global keyboard shortcut (Ctrl+Shift+Q) to show or hide the main window.
	<li>Integrated torrent maker.
	<li>Integrated UDP tracker.
	<li>Multi interface (command-line, graphical and web)
	<li>Multi platform (Linux and Windows).
	<li>Single window, multiple torrents.
	<li>Upload rate limit.
	<li>UPnP NAT port mapping.
	<li>Web frontend (PHP).
</ul>
<hr>
<h2>Installing under Windows</h2>

<ol>
<li>Download XBT Client from <a href="http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113736">http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113736</a>.
<li>Run the executable.
<li>Start the client via the start menu (Start - Programs - XBT Client) or by double-clicking on a torrent.
</ol>
<hr>
<h2>Screenshots</h2>
<p>
<img src="screenshots/client_torrents.png">
<dl>
	<dt>Hash
	<dd>The SHA1 hash of the info key of the torrent.
	<dt>%
	<dd>Percentage complete
	<dt>Left
	<dd>Bytes left
	<dt>Downloaded
	<dd>Bytes downloaded since torrent was opened
	<dt>Uploaded
	<dd>Bytes uploaded
	<dt>Down rate
	<dd>Download rate
	<dt>Up rate
	<dd>Uploaded rate
	<dt>Leechers
	<dd>The number of leechers you're connected too. If it's shown as # / #, the second number is the total number of leechers reported by the tracker.
	<dt>Seeders
	<dd>The number of seeders you're connected too.
	<dt>State
	<dd>The state of this torrent. It can be running or sleeping. If it's sleeping, the tracker won't be contacted and pieces won't be send or received.
	<dt>Name
	<dd>The filename of this torrent. It's stored in \Documents and Settings\*\My Documents\XBT\Incompletes\
</dl>
<p>
<img src="screenshots/client_peers.png">
<dl>
	<dt>Host
	<dd>The IP address of this peer.
	<dt>Port
	<dd>The port.
	<dt>%
	<dd>Percentage complete. This might not be accurate for newer clients.
	<dt>Left
	<dd>Bytes left
	<dt>Downloaded
	<dd>Bytes downloaded
	<dt>Uploaded
	<dd>Bytes uploaded
	<dt>Down rate
	<dd>Download rate
	<dt>Up rate
	<dd>Upload rate
	<dt>Direction
	<dd>The direction of this link. L for locally initiated connections and R for remotely initated connections. If after an hour no Rs appear, you might want to open a port if your firewall or router.
	<dt>Local Choked
	<dd>No pieces will be send to this peer.
	<dt>Local Interested
	<dd>You'd like to have some pieces of this peer.
	<dt>Remote Choked
	<dd>This peer will not send pieces to you.
	<dt>Remote Interested
	<dd>This peer would like to have some pieces from you.
	<dt>Peer ID
	<dd>The peer ID of this peer.
</dl>
<p>
<img src="screenshots/task_manager.png">
<dl>
	<dt>RAM usage
	<dd>Less than 4 mbyte is being used right after startup of the client.
	<dt>VM usage
	<dd>Less than 1 mbyte is being used.
</dl>
<p>
<img src="screenshots/tray_tip.png">
<p>
<img src="screenshots/popup_menu.png">
<dl>
	<dt>Explore
	<dd>This will open \Documents and Settings\*\My Documents\XBT\Incompletes\ in Windows Explorer.
	<dt>Files...
	<dd>This will show the files inside this torrent and allow you to change the priorities of individual files.
	<dt>Announce
	<dd>This will send an extra announce to the tracker. Use this will care, as tracker bandwiddh is expensive.
	<dt>Start
	<dd>This will change the state of the torrent to running.
	<dt>Stop
	<dd>This will change the state of the torrent to sleeping.
	<dt>Copy URL
	<dd>Experimental
	<dt>Paste URL
	<dd>Experimental
	<dt>Open...
	<dd>This will allow you to open a new torrent. Another way to open a torrent is to double-click on it in Windows Explorer or to drag- and drop it from Windows Explorer to XBT Client.
	<dt>Close
	<dd>This will close a torrent.
	<dt>Options...
	<dd>This will show the options of this client.
	<dt>Trackers...
	<dd>This will allow you to enter usernames and passwords for trackers that require UDP authentication.
</dl>
<p>
<img src="screenshots/options.png">
<dl>
	<dt>Admin port
	<dd>The TCP port for administrators. On localhost, you can connect to this port with a web browser to view some status information.
	<dt>Peer port
	<dd>The TCP port for peers. Be sure to open this port in your firewall or router, otherwise other peers will have trouble to connect to you and your download rates will be lower.
	<dt>Public IP address
	<dd>Your public IP address. This is only required if you're running a tracker.
	<dt>Upload rate
	<dd>The upload rate limit. At most this number of bytes is send every second. Set this to a few kbyte below your connection speed.
	<dt>Upload slots
	<dd>The number of upload slots. At most this number of peers will be unchoked at once.
	<dt>Seeding ratio
	<dd>The seeding ratio limit. After you've uploaded more than limit * torrent size, the state will be changed to sleeping.
	<dt>Files location
	<dd>The location where downloads will be saved.
	<dt>Tracker port
	<dd>The UDP port for the tracker.
	<dt>Peer limit
	<dd>The peer limit. Use this limit if your firewall, router or operating system can't handle many open TCP connections.
	<dt>Ask for location
	<dd>When enabled, you'll be asked for a location where the download will be saved.
	<dt>End mode
	<dd>Use this if your download is 'stuck' at 99 % and you're out of patience.
	<dt>Lower process priority
	<dd>If the client uses too much CPU time, enable this option.
	<dt>Show tray icon
	<dd>If this option is enabled, you can minimize the client to the tray by pressing the Escape key. If it's disabled, the client will be minimized to the task bar.
	<dt>Show advanced columns
	<dd>This will show the columns Hash, Host and Port in the main window.
	<dt>Start minimized
	<dd>This will start the client in the minimized state.
</dl>
<?php
	include('../bottom.php');
?>