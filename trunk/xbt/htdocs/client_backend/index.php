<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<link rel=stylesheet href="/xbt.css">
<title>XBT Client Backend</title>
<table width="100%">
	<tr>
		<td align=left valign=bottom><h1>XBT Client Backend</h1>
		<td align=right valign=bottom><a href="/">Home</a>
</table>
<hr>
<h2>Installing under Windows</h2>

<ol>
	<li>Download XBT Client Backend from <a href="http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113736">http://sourceforge.net/project/showfiles.php?group_id=94951&amp;package_id=113736</a>.
	<li>Run the executable.
</ol>

<p>
There are two ways to run the client backend under Windows (NT, 2000, XP and 2003).
The first way is to run the client backend manually, like every other application.
The second way is to run the client backend as service.
The advantage of this way is that it also runs when no user is logged in.

<ol>
	<li>Open a command window (Start - Run - cmd).
	<li>Run net start "XBT Client"
</ol>

<hr>
<h2>Starting under Windows</h2>

Just start the executable. An empty DOS window should appear.
<hr>
<h2>Installing under Linux</h2>

Enter the following commands in a terminal.
Be patient while g++ is running, it'll take a few minutes.
<pre>
svn co https://svn.sourceforge.net/svnroot/xbtt/trunk/xbt
cd xbt/BT\ Test
./make.sh
</pre>
<hr>
<h2>Starting under Linux</h2>

Enter the following commands in a terminal.
<pre>
./xbt_client_backend
</pre>
<hr>
<h2>Stopping under Linux</h2>

Enter the following commands in a terminal.
<pre>
kill `cat xbt_client_backend.pid`
</pre>
<?php
	include('../bottom.php');
?>