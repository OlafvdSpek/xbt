<link rel=stylesheet href="xbt.css">
<title>XBT Files</title>
<?
	mysql_connect("localhost", "xbt", "pass");
	mysql_select_db("xbt");
	$results = mysql_query("select *, unix_timestamp(mtime) as mtime, unix_timestamp(ctime) as ctime from xbt_files");
	echo("<table>");
	echo("<tr>");
	echo("<th>fid");
	echo("<th>info_hash");
	echo("<th>leechers");
	echo("<th>seeders");
	echo("<th>announced");
	echo("<th>scraped");
	echo("<th>completed");
	echo("<th>started");
	echo("<th>stopped");
	echo("<th>modified");
	echo("<th>created");
	while ($result = mysql_fetch_assoc($results))
	{
		echo("<tr>");
		printf("<td align=right>%d", $result[fid]);
		printf("<td>%s", bin2hex($result[info_hash]));
		printf("<td align=right>%d", $result[leechers]);
		printf("<td align=right>%d", $result[seeders]);
		printf("<td align=right>%d", $result[announced]);
		printf("<td align=right>%d", $result[scraped]);
		printf("<td align=right>%d", $result[completed]);
		printf("<td align=right>%d", $result[started]);
		printf("<td align=right>%d", $result[stopped]);
		printf("<td>%s", date("Y-m-d G:i:s", $result[mtime]));
		printf("<td>%s", date("Y-m-d G:i:s", $result[ctime]));
	}
	echo("</table>");
?>