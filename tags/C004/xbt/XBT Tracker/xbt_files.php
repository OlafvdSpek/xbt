<?php
	ob_start(ob_gzhandler);
?>
<link rel=stylesheet href="xbt.css">
<title>XBT Files</title>
<?php
	mysql_connect("localhost", "xbt", "pass");
	mysql_select_db("xbt");
	$results = mysql_query("select sum(announced_http) announced_http, sum(announced_http_compact) announced_http_compact, sum(announced_http_no_peer_id) announced_http_no_peer_id, sum(announced_udp) announced_udp, sum(scraped_http) scraped_http, sum(scraped_udp) scraped_udp, sum(completed) completed, sum(started) started, sum(stopped) stopped, sum(leechers) leechers, sum(seeders) seeders, sum(leechers or seeders) torrents from xbt_files");
	$result = mysql_fetch_assoc($results);
	$result[announced] = $result[announced_http] + $result[announced_http_compact] + $result[announced_http_no_peer_id] + $result[announced_udp];
	$result[scraped] = $result[scraped_http] + $result[scraped_udp];
	$result[peers] = $result[leechers] + $result[seeders];
	echo("<table>");
	printf("<tr><th align=right>announced<td align=right>%d<td align=right>100 %%", $result[announced]);
	if ($result[announced])
	{
		printf("<tr><th align=right>announced_http<td align=right>%d<td align=right>%d %%", $result[announced_http], $result[announced_http] * 100 / $result[announced]);
		printf("<tr><th align=right>announced_http_compact<td align=right>%d<td align=right>%d %%", $result[announced_http_compact], $result[announced_http_compact] * 100 / $result[announced]);
		printf("<tr><th align=right>announced_http_no_peer_id<td align=right>%d<td align=right>%d %%", $result[announced_http_no_peer_id], $result[announced_http_no_peer_id] * 100 / $result[announced]);
		printf("<tr><th align=right>announced_udp<td align=right>%d<td align=right>%d %%", $result[announced_udp], $result[announced_udp] * 100 / $result[announced]);
	}
	printf("<tr><th align=right>scraped<td align=right>%d<td align=right>100 %%", $result[scraped]);
	if ($result[scraped])
	{
		printf("<tr><th align=right>scraped_http<td align=right>%d<td align=right>%d %%", $result[scraped_http], $result[scraped_http] * 100 / $result[scraped]);
		printf("<tr><th align=right>scraped_udp<td align=right>%d<td align=right>%d %%", $result[scraped_udp], $result[scraped_udp] * 100 / $result[scraped]);
	}
	printf("<tr><th align=right>completed<td align=right>%d<td>", $result[completed]);
	printf("<tr><th align=right>started<td align=right>%d<td>", $result[started]);
	printf("<tr><th align=right>stopped<td align=right>%d<td>", $result[stopped]);
	printf("<tr><th align=right>peers<td align=right>%d<td align=right>100 %%", $result[peers]);
	if ($result[peers])
	{
		printf("<tr><th align=right>leechers<td align=right>%d<td align=right>%d %%", $result[leechers], $result[leechers] * 100 / $result[peers]);
		printf("<tr><th align=right>seeders<td align=right>%d<td align=right>%d %%", $result[seeders], $result[seeders] * 100 / $result[peers]);
	}
	printf("<tr><th align=right>torrents<td align=right>%d<td>", $result[torrents]);
	printf("<tr><th align=right>time<td align=right colspan=2>%s", gmdate("Y-m-d H:i:s"));
	echo("</table>");
	echo("<hr>");
	$results = mysql_query("select *, unix_timestamp(mtime) mtime, unix_timestamp(ctime) ctime from xbt_files where leechers or seeders order by ctime desc");
	echo("<table>");
	echo("<tr>");
	echo("<th>fid");
	echo("<th>info_hash");
	echo("<th>leechers");
	echo("<th>seeders");
	echo("<th colspan=4>announced");
	echo("<th colspan=2>scraped");
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
		echo("<td align=right>");
		if ($result[seeders])
			printf("%d", $result[seeders]);
		printf("<td align=right>%d", $result[announced_http]);
		printf("<td align=right>%d", $result[announced_http_compact]);
		printf("<td align=right>%d", $result[announced_http_no_peer_id]);
		echo("<td align=right>");
		if ($result[announced_udp])
			printf("%d", $result[announced_udp]);
		printf("<td align=right>%d", $result[scraped_http]);
		echo("<td align=right>");
		if ($result[scraped_udp])
			printf("%d", $result[scraped_udp]);
		echo("<td align=right>");
		if ($result[completed])
			printf("%d", $result[completed]);
		printf("<td align=right>%d", $result[started]);
		printf("<td align=right>%d", $result[stopped]);
		printf("<td>%s", gmdate("Y-m-d H:i:s", $result[mtime]));
		printf("<td>%s", gmdate("Y-m-d H:i:s", $result[ctime]));
	}
	echo("</table>");
?>