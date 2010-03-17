<?php
	require_once('xbt_config.php');

	function db_connect()
	{
		global $mysql;
		mysql_pconnect($mysql['host'], $mysql['user'], $mysql['password']) || die(htmlspecialchars(mysql_error()));
		mysql_select_db($mysql['db']) || die(htmlspecialchars(mysql_error()));
	}

	function db_query($query)
	{
		// printf('%s<br>', htmlspecialchars($query));
		$result = mysql_query($query);
		if (!$result)
				die(sprintf('%s<br>%s', htmlspecialchars(mysql_error()), htmlspecialchars($query)));
		return $result;
	}

	function db_query_first($query)
	{
			return mysql_fetch_assoc(db_query($query));
	}

	function db_query_first_field($query)
	{
		$row = mysql_fetch_array(db_query($query));
		return $row[0];
	}

	db_connect();
