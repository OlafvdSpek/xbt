<?php
	ob_start('ob_gzhandler');

	require_once('common.php');
	require_once('templates.php');

	if (!isset($config['users'][$_SERVER['PHP_AUTH_USER']])
		|| $config['users'][$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW'])
	{
		header('www-authenticate: basic realm="XBT Client"');
		return;
	}
	set_time_limit(15);
	$s = fsockopen($config['client_host'], $config['client_port']);
	if ($s === false)
		die('fsockopen failed');
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
	{
		$d = file_get_contents($_FILES['file']['tmp_name']);
		send_string($s, sprintf('d6:action12:open torrent7:torrent%d:%se', strlen($d), $d));
		recv_string($s);
	}
	$actions = array
	(
		'close' => 'close torrent',
		'pause' => 'pause torrent',
		'set_priority_high' => 'set priority',
		'set_priority_normal' => 'set priority',
		'set_priority_low' => 'set priority',
		'set_state_queued' => 'set state',
		'set_state_started' => 'set state',
		'set_state_paused' => 'set state',
		'set_state_stopped' => 'set state',
		'set_options' => 'set options',
		'start' => 'start torrent',
		'stop' => 'stop torrent',
		'unpause' => 'unpause torrent',
	);
	$arguments = array
	(
		'set_priority_high' => 1,
		'set_priority_normal' => 0,
		'set_priority_low' => -1,
		'set_state_queued' => 0,
		'set_state_started' => 2,
		'set_state_paused' => 3,
		'set_state_stopped' => 4,
	);
	if (array_key_exists($_REQUEST['a'], $actions))
	{
		$action = $actions[$_REQUEST['a']];
		switch ($_REQUEST['a'])
		{
		case 'set_options':
			$completes_dir = stripslashes($_REQUEST['completes_dir']);
			$incompletes_dir = stripslashes($_REQUEST['incompletes_dir']);
			$torrents_dir = stripslashes($_REQUEST['torrents_dir']);
			send_string
			(
				$s,
				sprintf
				(
					'd6:action%d:%s13:completes dir%d:%s15:incompletes dir%d:%s10:peer limiti%de9:peer porti%de13:seeding ratioi%de13:torrent limiti%de12:torrents dir%d:%s12:tracker porti%de11:upload ratei%de12:upload slotsi%dee',
					strlen($action),
					$action,
					strlen($completes_dir),
					$completes_dir,
					strlen($incompletes_dir),
					$incompletes_dir,
					$_REQUEST['peer_limit'],
					$_REQUEST['peer_port'],
					$_REQUEST['seeding_ratio'],
					$_REQUEST['torrent_limit'],
					strlen($torrents_dir),
					$torrents_dir,
					$_REQUEST['tracker_port'],
					$_REQUEST['upload_rate'] << 10,
					$_REQUEST['upload_slots']
				)
			);
			break;
		default:
			foreach ($_REQUEST as $name => $value)
			{
				$name = urldecode($name);
				if (strlen($name) != 20 || $value != 'on')
					continue;
				switch ($action)
				{
				case 'set priority':
					send_string($s, sprintf('d6:action%d:%s4:hash20:%s8:priorityi%dee', strlen($action), $action, $name, $arguments[$_REQUEST['a']]));
					break;
				case 'set state':
					send_string($s, sprintf('d6:action%d:%s4:hash20:%s5:statei%dee', strlen($action), $action, $name, $arguments[$_REQUEST['a']]));
					break;
				default:
					send_string($s, sprintf('d6:action%d:%s4:hash20:%se', strlen($action), $action, $name));
				}
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
		if ($info_hash == $_REQUEST['torrent'])
			$torrent_events .= template_torrent_events($file['value']['events']['value']);
	}
	$torrents = template_torrents(array('rows' => $rows));
	send_string($s, 'd6:action11:get optionse');
	$v = recv_string($s);
	$v = bdec($v);
	$options = template_options($v['value']);
	echo(template_page(array('options' => $options, 'torrent_events' => $torrent_events, 'torrents' => $torrents)));
?>