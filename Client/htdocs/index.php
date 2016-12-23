<?php
	error_reporting(E_ALL & ~E_NOTICE);

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
		send_string($s, sprintf('d6:action12:open torrent10:admin pass%d:%s10:admin user%d:%s7:torrent%d:%se', strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user'], strlen($d), $d));
		recv_bvalue($s);
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
			$completes_dir = $_REQUEST['completes_dir'];
			$incompletes_dir = $_REQUEST['incompletes_dir'];
			$torrents_dir = $_REQUEST['torrents_dir'];
			$user_agent = $_REQUEST['user_agent'];
			if (get_magic_quotes_gpc())
			{
				$completes_dir = stripslashes($completes_dir);
				$incompletes_dir = stripslashes($incompletes_dir);
				$torrents_dir = stripslashes($torrents_dir);
				$user_agent = stripslashes($user_agent);
			}
			send_string
			(
				$s,
				sprintf
				(
					'd6:action%d:%s10:admin pass%d:%s10:admin user%d:%s13:completes dir%d:%s15:incompletes dir%d:%s10:peer limiti%de9:peer porti%de13:seeding ratioi%de13:torrent limiti%de12:torrents dir%d:%s12:tracker porti%de11:upload ratei%de12:upload slotsi%de10:user agent%d:%se',
					strlen($action),
					$action,
					strlen($config['client_pass']),
					$config['client_pass'],
					strlen($config['client_user']),
					$config['client_user'],
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
					$_REQUEST['upload_slots'],
					strlen($user_agent),
					$user_agent
				)
			);
			break;
		default:
			foreach ($_REQUEST as $name => $value)
			{
				if (strlen($name) != 40 || $value != 'on')
					continue;
				$name = pack('H*', $name);
				if (strlen($name) != 20)
					continue;
				switch ($action)
				{
				case 'set priority':
					send_string($s, sprintf('d6:action%d:%s10:admin pass%d:%s10:admin user%d:%s4:hash20:%s8:priorityi%dee',
						strlen($action), $action, strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user'], $name, $arguments[$_REQUEST['a']]));
					break;
				case 'set state':
					send_string($s, sprintf('d6:action%d:%s10:admin pass%d:%s10:admin user%d:%s4:hash20:%s5:statei%dee',
						strlen($action), $action, strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user'], $name, $arguments[$_REQUEST['a']]));
					break;
				default:
					send_string($s, sprintf('d6:action%d:%s10:admin pass%d:%s10:admin user%d:%s4:hash20:%se',
						strlen($action), $action, strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user'], $name));
				}
				$v = recv_bvalue($s);
			}
		}
	}
	if ($_SERVER['REQUEST_METHOD'] != 'GET')
	{
		header('location: ' . $_SERVER['SCRIPT_NAME']);
		exit();
	}
	send_string($s, sprintf('d6:action10:get status10:admin pass%d:%s10:admin user%d:%se',
		strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user']));
	$v = recv_bvalue($s);
	$aggregate = array();
	$rows = '';
	foreach ($v['value']['files']['value'] as $info_hash => $file)
	{
		$aggregate['left'] += $file['value']['left']['value'];
		$aggregate['size'] += $file['value']['size']['value'];
		$aggregate['total downloaded'] += $file['value']['total downloaded']['value'];
		$aggregate['total uploaded'] += $file['value']['total uploaded']['value'];
		$aggregate['down rate'] += $file['value']['down rate']['value'];
		$aggregate['up rate'] += $file['value']['up rate']['value'];
		$aggregate['incomplete'] += $file['value']['incomplete']['value'];
		$aggregate['incomplete total'] += $file['value']['incomplete total']['value'];
		$aggregate['complete'] += $file['value']['complete']['value'];
		$aggregate['complete total'] += $file['value']['complete total']['value'];
		$rows .= template_torrent(array_merge($file['value'], array('info_hash' => array('value' => $info_hash))));
		if ($info_hash == pack('H*', $_REQUEST['torrent']))
			$torrent_events .= template_torrent_events($file['value']['events']['value']);
	}
	$torrents = template_torrents(array('aggregate' => $aggregate, 'rows' => $rows));
	$version = $v['value']['version']['value'];
	send_string($s, sprintf('d6:action11:get options10:admin pass%d:%s10:admin user%d:%se',
		strlen($config['client_pass']), $config['client_pass'], strlen($config['client_user']), $config['client_user']));
	$v = recv_bvalue($s);
	$options = template_options($v['value']);
	echo(template_page(array('options' => $options, 'torrent_events' => $torrent_events, 'torrents' => $torrents, 'version' => $version)));
?>