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
		case '0':
			return '';
		case 1:
			return 'R';
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
		$d .= $v['torrents'];
		$d .= '<hr>';
		$d .= $v['options'];
		$d .= '<hr>';
		$d .= '<center>';
		$d .= '<a href="http://sourceforge.net/projects/xbtt/"><img src="http://sourceforge.net/sflogo.php?group_id=94951;type=1" alt="XBT project at SF"></a>';
		$d .= '</center>';
		return $d;
	};

	function template_torrent($v)
	{
		$d = "\n";
		$d .= '<tr>';
		$d .= sprintf('<td><input type=checkbox name="%s">', urlencode($v['info_hash']['value']));
		$d .= sprintf('<td align=left>%s', htmlspecialchars(strip_name($v['name']['value'])));
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
		$d .= '</table>';
		$d .= '<br>';
		$d .= '<select name="a" onchange="this.form.submit();">';
		$d .= '<option>Do...</option>';
		$d .= '<option value="start">Start</option>';
		$d .= '<option value="pause">Pause</option>';
		$d .= '<option value="unpause">Unpause</option>';
		$d .= '<option value="stop">Stop</option>';
		$d .= '<option value="close">Close</option>';
		$d .= '<option value="set_priority_high">Priority - High</option>';
		$d .= '<option value="set_priority_normal">Priority - Normal</option>';
		$d .= '<option value="set_priority_low">Priority - Low</option>';
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

	function template_options($v)
	{
		$d = '';
		$d .= '<center>';
		$d .= '<form action="?" method=post>';
		$d .= '<table>';
		$d .= '<caption>Options</caption>';
		$d .= '<th align=left>Name';
		$d .= '<th align=left>Value';
		$d .= sprintf('<tr><td align=left>Admin Port<td align=right>%d', $v['admin port']['value']);
		$d .= sprintf('<tr><td align=left>Peer Port<td align=right>%d', $v['peer port']['value']);
		$d .= sprintf('<tr><td align=left>Tracker Port<td align=right>%d', $v['tracker port']['value']);
		$d .= sprintf('<tr><td align=left>Upload Rate<td align=right>%s', b2a($v['upload rate']['value']));
		$d .= sprintf('<tr><td align=left>Upload Slots<td align=right>%d', $v['upload slots']['value']);
		$d .= sprintf('<tr><td align=left>Seeding Ratio<td align=right>%d', $v['seeding ratio']['value']);
		$d .= '</table>';
		$d .= '</form>';
		$d .= '</center>';
		return $d;
	}
?>