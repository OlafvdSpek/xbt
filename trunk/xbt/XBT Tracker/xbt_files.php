<link rel=stylesheet href="xbt.css">
<title>XBT Files</title>
<?
	mysql_connect("localhost", "xbt", "pass");
	mysql_select_db("xbt");
	$results = mysql_query("select sum(announced) announced, sum(scraped) scraped, sum(completed) completed, sum(started) started, sum(stopped) stopped, sum(leechers) leechers, sum(seeders) seeders, sum(leechers or seeders) torrents from xbt_files");
	$result = mysql_fetch_assoc($results);
	$result[peers] = $result[leechers] + $result[seeders];
	echo("<table>");
	printf("<tr><th>announced<td align=right>%d<td>", $result[announced]);
	printf("<tr><th>scraped<td align=right>%d<td>", $result[scraped]);
	printf("<tr><th>completed<td align=right>%d<td>", $result[completed]);
	printf("<tr><th>started<td align=right>%d<td>", $result[started]);
	printf("<tr><th>stopped<td align=right>%d<td>", $result[stopped]);
	printf("<tr><th>peers<td align=right>%d<td align=right>100 %%", $result[peers]);
	printf("<tr><th>leechers<td align=right>%d<td align=right>", $result[leechers]);
	if ($result[peers])
		printf("%d %%", $result[leechers] * 100 / $result[peers]);
	printf("<tr><th>seeders<td align=right>%d<td align=right>", $result[seeders]);
	if ($result[peers])
		printf("%d %%", $result[seeders] * 100 / $result[peers]);
	printf("<tr><th>torrents<td align=right>%d<td>", $result[torrents]);
	printf("<tr><th>time<td align=right colspan=2>%s", gmdate("Y-m-d H:i:s"));
	echo("</table>");
	echo("<hr>");
	$results = mysql_query("select *, unix_timestamp(mtime) mtime, unix_timestamp(ctime) ctime from xbt_files where leechers or seeders order by ctime desc");
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
		printf("<td>%s", gmdate("Y-m-d H:i:s", $result[mtime]));
		printf("<td>%s", gmdate("Y-m-d H:i:s", $result[ctime]));
	}
	echo("</table>");
?>