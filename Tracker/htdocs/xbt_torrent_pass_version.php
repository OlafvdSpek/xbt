<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<title>XBT Torrent Pass Version</title>
<?php
	require_once('xbt_common.php');

	$info_hash = $_GET['info_hash'];
	$uid = $_GET['uid'];
	if (empty($uid))
		$uid = 1;
	$torrent_pass_private_key = db_query_first_field("select value from xbt_config where name = 'torrent_pass_private_key'");
	$torrent_pass_version = db_query_first_field(sprintf("select torrent_pass_version from xbt_users where uid = %d", $uid));;
	printf('<p>Torrent Pass: %08x%s', $uid, substr(sha1(sprintf('%s %d %d %s', $torrent_pass_private_key, $torrent_pass_version, $uid, pack('H*', $info_hash))), 0, 24));
?>
<form action="">
	<p>Info Hash: <input type=text name=info_hash value="<?php echo(htmlspecialchars($info_hash)); ?>">
	<p>UID: <input type=text name=uid value="<?php echo(htmlspecialchars($uid)); ?>">
	<p><input type=submit>
</form>
