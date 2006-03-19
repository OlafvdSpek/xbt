<?php
	$title = 'XBT Client Command Line Interface';
	include('../top.php');
?>
<h2>Overview</h2>

<p>
XBT Client Command Line Interface is a frontend for XBT Client Backend.<br>

<h2>Usage</h2>

<pre>
./xbt_client_cli close <info_hash>
./xbt_client_cli erase <info_hash>
./xbt_client_cli pause <info_hash>
./xbt_client_cli peer_port <port>
./xbt_client_cli queue <info_hash>
./xbt_client_cli start <info_hash>
./xbt_client_cli stop <info_hash>
./xbt_client_cli tracker_port <port>
./xbt_client_cli upload_rate <rate>
./xbt_client_cli upload_slots <slots>
./xbt_client_cli user_agent <user_agent>
./xbt_client_cli options
./xbt_client_cli status
</pre>

<?php
	include('../bottom.php');
?>