<link rel=stylesheet href="xbt.css">
<title>XBT Files</title>
<?
	mysql_connect("localhost", "xbt", "pass");
	mysql_select_db("xbt");
	$results = mysql_query("select sum(leechers) as leechers, sum(seeders) as seeders, sum(leechers or seeders) as torrents from xbt_files");
	$result = mysql_fetch_assoc($results);
	$result[peers] = $result[leechers] + $result[seeders];
	if (!$result[peers])
		return;
	echo("<table>");
	printf("<tr><th>peers<td align=right>%d<td align=right>100 %%", $result[peers]);
	printf("<tr><th>leechers<td align=right>%d<td align=right>%d %%", $result[leechers], $result[leechers] * 100 / $result[peers]);
	printf("<tr><th>seeders<td align=right>%d<td align=right>%d %%", $result[seeders], $result[seeders] * 100 / $result[peers]);
	printf("<tr><th>torrents<td align=right>%d<td>", $result[torrents]);
	printf("<tr><th>time<td align=right colspan=2>%s", gmdate("Y-m-d H:i:s"));
	echo("</table>");
	echo("<hr>");
	$results = mysql_query("select *, unix_timestamp(mtime) as mtime, unix_timestamp(ctime) as ctime from xbt_files where leechers or seeders order by mtime desc");
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