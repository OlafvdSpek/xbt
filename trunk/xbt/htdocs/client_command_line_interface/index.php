<?php
	$title = 'XBT Client Command Line Interface';
	include('../top.php');
?>
<h2>Overview</h2>

<p>
XBT Client Command Line Interface is a frontend for XBT Client Backend.<br>

<hr>
<h2>Installing under Linux</h2>

Enter the following commands in a terminal.
Be patient while g++ is running, it'll take a few minutes.
<pre>
svn co https://svn.sourceforge.net/svnroot/xbtt/trunk/xbt
cd Client\ Command\ Line\ Interface
./make.sh
</pre>

<hr>
<h2>Usage</h2>

<pre>
./xbt_client_cli close &lt;info_hash>
./xbt_client_cli erase &lt;info_hash>
./xbt_client_cli pause &lt;info_hash>
./xbt_client_cli peer_port &lt;port>
./xbt_client_cli queue &lt;info_hash>
./xbt_client_cli start &lt;info_hash>
./xbt_client_cli stop &lt;info_hash>
./xbt_client_cli tracker_port &lt;port>
./xbt_client_cli upload_rate &lt;rate>
./xbt_client_cli upload_slots &lt;slots>
./xbt_client_cli user_agent &lt;user_agent>
./xbt_client_cli options
./xbt_client_cli status
</pre>
<?php
	include('../bottom.php');
?>