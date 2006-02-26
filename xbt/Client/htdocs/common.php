<?php
	require_once('benc.php');
	require_once('config.php');

	function recv_string($s)
	{
		$d = '';
		while (strlen($d) < 4)
		{
			$r = fread($s, 4 - strlen($d));
			if (!strlen($r))
				return '';
			$d .= $r;
		}
		$l = unpack('N', $d);
		$l = $l[1];
		$d = '';
		while (strlen($d) < $l)
		{
			$r = fread($s, $l - strlen($d));
			if (!strlen($r))
				return '';
			$d .= $r;
		}
		return substr($d, 1);
	}

	function send_string($s, $v)
	{
		$v = pack('N', strlen($v) + 1) . chr(0x40) . $v;
		if (fwrite($s, $v) != strlen($v))
			die('fwrite failed');
	}

?>