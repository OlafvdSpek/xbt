<?php
	function b2a($v)
	{
		if (!$v)
			return '';
		for ($l = 0; $v < -9999 || $v > 9999; $l++)
			$v /= 1024;
		$a = array('', ' k', ' m', ' g', ' t', ' p');
		return sprintf('%d%s', $v, $a[$l]);
	}

	function nz($v)
	{
		return $v ? $v : '';
	}

	function priority2a($v)
	{
		switch ($v)
		{
		case 1:
			return 'H';
		case 0:
			return '';
		case -1:
			return 'L';
		case -10:
			return 'E';
		}
		return $v;
	}

	function state2a($v)
	{
		switch ($v)
		{
		case 0:
			return 'Q';
		case 1:
			return 'H';
		case 2:
			return 'R';
		case 3:
			return 'P';
		case 4:
			return 'S';
		}
		return $v;
	}

	function strip_name($v)
	{
		$i = strrpos($v, '/');
		if ($i !== false)
		$v = substr($v, $i + 1);
		$i = strrpos($v, '\\');
		return $i === false ? $v : substr($v, $i + 1);

	}

	function template_page($v)
	{
		$d = '';
		$d .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">';
		$d .= '<link rel=stylesheet href="xbt.css">';
		$d .= '<meta http-equiv=refresh content=60>';
		$d .= '<title>XBT Client</title>';
		$d .= '<center>';
		$d .= '<table>';
		$d .= '<caption>Links</caption>';
		$d .= '<tr>';
		$d .= '<td><a href="?">Home</a>';
		$d .= '<td><a href="http://sourceforge.net/projects/xbtt/">XBT Home Page</a>';
		$d .= '</table>';
		$d .= '</center>';
		$d .= '<hr>';
		$d .= $v['torrents'];
		$d .= '<hr>';
		if (strlen($v['torrent_events']))
		{
			$d .= $v['torrent_events'];
			$d .= '<hr>';
		}
		$d .= $v['options'];
		$d .= '<hr>';
		$d .= '<center>';
		$d .= '<a href="http://sourceforge.net/projects/xbtt/"><img src="http://sourceforge.net/sflogo.php?group_id=94951;type=1" alt="XBT project at SF"></a>';
		$d .= '</center>';
		return $d;
	};

	function template_torrent($v)
	{
		$d = '';
		$d .= '<tr>';
		$d .= sprintf('<td><input type=checkbox name="%s"%s>', urlencode($v['info_hash']['value']), $_REQUEST['torrent'] == $v['info_hash']['value'] ? ' checked' : '');
		$d .= sprintf('<td align=left><a href="?torrent=%s">%s</a>', implode('', unpack('H40', $v['info_hash']['value'])), htmlspecialchars(strip_name($v['name']['value'])));
		$d .= $v['size']['value']
			? sprintf('<td align=right>%d', ($v['size']['value'] - $v['left']['value']) * 100 / $v['size']['value'])
			: '<td>';
		$d .= sprintf('<td align=right>%s', b2a($v['left']['value']));
		$d .= sprintf('<td align=right>%s', b2a($v['size']['value']));
		$d .= sprintf('<td align=right>%s', b2a($v['total downloaded']['value']));
		$d .= sprintf('<td align=right>%s', b2a($v['total uploaded']['value']));
		$d .= sprintf('<td align=right>%s', b2a($v['down rate']['value']));
		$d .= sprintf('<td align=right>%s', b2a($v['up rate']['value']));
		$d .= sprintf('<td align=right>%s', nz($v['incomplete']['value']));
		$d .= sprintf('<td align=right>%s', nz($v['complete']['value']));
		$d .= sprintf('<td align=right>%s', priority2a($v['priority']['value']));
		$d .= sprintf('<td align=left>%s', state2a($v['state']['value']));
		return $d;
	}

	function template_torrents($v)
	{
		$d = '';
		$d .= '<center>';
		$d .= '<form action="?" method=post>';
		$d .= '<table>';
		$d .= '<caption>Torrents</caption>';
		$d .= '<tr>';
		$d .= '<th>';
		$d .= '<th align=left>Name';
		$d .= '<th align=right>%';
		$d .= '<th align=right>Left';
		$d .= '<th align=right>Size';
		$d .= '<th align=right>Downloaded';
		$d .= '<th align=right>Uploaded';
		$d .= '<th align=right>Down rate';
		$d .= '<th align=right>Up rate';
		$d .= '<th align=right>Leechers';
		$d .= '<th align=right>Seeders';
		$d .= '<th align=right>Priority';
		$d .= '<th align=right>State';
		$d .= $v['rows'];
		if ($v['aggregate']['size'])
		{
			$d .= '<tr>';
			$d .= '<th>';
			$d .= '<th>';
			$d .= sprintf('<th align=right>%d', ($v['aggregate']['size'] - $v['aggregate']['left']) * 100 / $v['aggregate']['size']);
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['left']));
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['size']));
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['total downloaded']));
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['total uploaded']));
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['down rate']));
			$d .= sprintf('<th align=right>%s', b2a($v['aggregate']['up rate']));
			$d .= sprintf('<th align=right>%s', nz($v['aggregate']['incomplete']));
			$d .= sprintf('<th align=right>%s', nz($v['aggregate']['complete']));
			$d .= '<th>';
			$d .= '<th>';
		}
		$d .= '</table>';
		$d .= '<br>';
		$d .= '<select name="a" onchange="this.form.submit();">';
		$d .= '<option>Do...</option>';
		$d .= '<option value="set_priority_high">Priority - High</option>';
		$d .= '<option value="set_priority_normal">Priority - Normal</option>';
		$d .= '<option value="set_priority_low">Priority - Low</option>';
		$d .= '<option value="set_state_queued">State - Queued</option>';
		$d .= '<option value="set_state_started">State - Started</option>';
		$d .= '<option value="set_state_paused">State - Paused</option>';
		$d .= '<option value="set_state_stopped">State - Stopped</option>';
		$d .= '<option value="close">Close</option>';
		$d .= '</select>';
		$d .= '</form>';
		$d .= '</center>';
		$d .= '<hr>';
		$d .= '<center>';
		$d .= '<form action="?" enctype="multipart/form-data" method=post>';
		$d .= '<table>';
		$d .= '<tr>';
		$d .= '<td><input type=file name=file>';
		$d .= '<td><input type=submit value="Open">';
		$d .= '</table>';
		$d .= '</form>';
		$d .= '</center>';
		return $d;
	}

	function template_torrent_events($v)
	{
		$d = '';
		$d .= '<center>';
		$d .= '<table>';
		$d .= '<tr>';
		$d .= '<th align=left>Time';
		$d .= '<th align=left>Message';
		foreach ($v as $event)
		{
			$d .= '<tr>';
			$d .= sprintf('<td align=left>%s', date('H:i:s', $event['value']['time']['value']));
			$d .= sprintf('<td align=left>%s', htmlspecialchars($event['value']['message']['value']));
		}
		$d .= '</table>';
		$d .= '</center>';
		$d .= '';
		return $d;
	}

	function template_options($v)
	{
		$d = '';
		$d .= '<center>';
		$d .= '<form action="?a=set_options" method=post>';
		$d .= '<table>';
		$d .= '<caption>Options</caption>';
		$d .= '<th align=left>Name';
		$d .= '<th align=right>Value';
		$d .= '<th>';
		$d .= sprintf('<tr><td align=left>Admin Port (TCP)<td><input type=text name=admin_port size=80 value=%d style="text-align: right"><td>', $v['admin port']['value']);
		$d .= sprintf('<tr><td align=left>Peer Port (TCP)<td><input type=text name=peer_port size=80 value=%d style="text-align: right"><td>', $v['peer port']['value']);
		$d .= sprintf('<tr><td align=left>Tracker Port (UDP)<td><input type=text name=tracker_port size=80 value=%d style="text-align: right"><td>', $v['tracker port']['value']);
		$d .= sprintf('<tr><td align=left>Upload Rate<td><input type=text name=upload_rate size=80 value=%d style="text-align: right"><td align=left>kb/s', $v['upload rate']['value'] >> 10);
		$d .= sprintf('<tr><td align=left>Upload Slots<td><input type=text name=upload_slots size=80 value=%d style="text-align: right"><td>', $v['upload slots']['value']);
		$d .= sprintf('<tr><td align=left>Seeding Ratio<td><input type=text name=seeding_ratio size=80 value=%d style="text-align: right"><td align=left>%%', $v['seeding ratio']['value']);
		$d .= sprintf('<tr><td align=left>Peer Limit<td><input type=text name=peer_limit size=80 value=%d style="text-align: right"><td align=left>peers', $v['peer limit']['value']);
		$d .= sprintf('<tr><td align=left>Torrent Limit<td><input type=text name=torrent_limit size=80 value=%d style="text-align: right"><td align=left>torrents', $v['torrent limit']['value']);
		$d .= sprintf('<tr><td align=left>Completes Directory<td><input type=text name=completes_dir size=80 value="%s"><td>', htmlspecialchars($v['completes dir']['value']));
		$d .= sprintf('<tr><td align=left>Incompletes Directory<td><input type=text name=incompletes_dir size=80 value="%s"><td>', htmlspecialchars($v['incompletes dir']['value']));
		$d .= sprintf('<tr><td align=left>Torrents Directory<td><input type=text name=torrents_dir size=80 value="%s"><td>', htmlspecialchars($v['torrents dir']['value']));
		$d .= '<tr><td><td align=left><input type=submit value="Set"><td>';
		$d .= '</table>';
		$d .= '</form>';
		$d .= '</center>';
		return $d;
	}
?>