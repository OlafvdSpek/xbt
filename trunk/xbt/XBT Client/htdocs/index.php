<?php
	require_once('common.php');
	require_once('templates.php');

	set_time_limit(5);
	$s = fsockopen($config['client_host'], $config['client_port']);
	if ($s === false)
		die('fsockopen failed');
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
	{
		$d = file_get_contents($_FILES['file']['tmp_name']);
		send_string($s, sprintf('d6:action12:open torrent7:torrent%se', $d));
		recv_string($s);
	}
	$actions = array
	(
		'close' => 'close torrent',
		'pause' => 'pause torrent',
		'priority_high' => 'set priority',
		'priority_normal' => 'set priority',
		'priority_low' => 'set priority',
		'unpause' => 'unpause torrent',
	);
	switch ($_REQUEST['a'])
	{
	case 'open':
		break;
	default:
		if (array_key_exists($_REQUEST['a'], $actions))
		{
			$action = $actions[$_REQUEST['a']];
			foreach ($_REQUEST as $name => $value)
			{
				$name = urldecode($name);
				if (strlen($name) != 20 || $value != 'on')
					continue;
				send_string($s, sprintf('d6:action%d:%s4:hash20:%se', strlen($action), $action, $name));
				recv_string($s);
			}
		}
	}
	if ($_SERVER['REQUEST_METHOD'] != 'GET')
	{
		header('location: ' . $_SERVER['SCRIPT_NAME']);
		exit();
	}
	send_string($s, 'd6:action10:get statuse');
	$v = recv_string($s);
	$v = bdec($v);
	$rows = '';
	foreach ($v['value']['files']['value'] as $info_hash => $file)
	{
		$rows .= template_torrent(array_merge($file['value'], array('info_hash' => array('value' => $info_hash))));
	}
	send_string($s, 'd6:action11:get optionse');
	$v = recv_string($s);
	$v = bdec($v);
	echo(template_top());
	echo(template_torrents(array('rows' => $rows)));
	echo(template_options($v['value']));
	echo(template_bottom());
?>