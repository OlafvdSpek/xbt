// bt_strings.h: interface for the Cbt_strings class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_STRINGS_H__2BC4A814_E7F6_45BF_AEA3_59B270DA3A9A__INCLUDED_)
#define AFX_BT_STRINGS_H__2BC4A814_E7F6_45BF_AEA3_59B270DA3A9A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

enum
{
	bti_choke ,
	bti_unchoke,
	bti_interested,
	bti_uninterested,
	bti_have,
	bti_bitfield,
	bti_request,
	bti_piece,
	bti_cancel,

	bti_get_info,
	bti_info,
	bti_get_peers,
	bti_peers,

	bti_bvalue = 0x40,
};

enum
{
	bti_none,
	bti_completed,
	bti_started,
	bti_stopped,
};

const string bts_action = "action";
const string bts_admin_port = "admin port";
const string bts_announce = "announce";
const string bts_announce_list = "announce-list";
const string bts_close_torrent = "close torrent";
const string bts_complete = "complete";
const string bts_down_rate = "down rate";
const string bts_downloaded = "downloaded";
const string bts_failure_reason = "failure reason";
const string bts_files = "files";
const string bts_get_options = "get options";
const string bts_get_status = "get status";
const string bts_hash = "hash";
const string bts_incomplete = "incomplete";
const string bts_info = "info";
const string bts_interval = "interval";
const string bts_ipa = "ip";
const string bts_left = "left";
const string bts_length = "length";
const string bts_login = "login";
const string bts_merkle_hash = "merkle hash";
const string bts_name = "name";
const string bts_open_torrent = "open torrent";
const string bts_pass = "pass";
const string bts_path = "path";
const string bts_pause_torrent = "pause torrent";
const string bts_peer_id = "peer id";
const string bts_peer_port = "peer port";
const string bts_peers = "peers";
const string bts_piece_length = "piece length";
const string bts_pieces = "pieces";
const string bts_port = "port";
const string bts_priority = "priority";
const string bts_seeding_ratio = "seeding ratio";
const string bts_set_options = "set options";
const string bts_set_priority = "set priority";
const string bts_size = "size";
const string bts_start_torrent = "start torrent";
const string bts_state = "state";
const string bts_stop_torrent = "stop torrent";
const string bts_torrent = "torrent";
const string bts_total_downloaded = "total downloaded";
const string bts_total_uploaded = "total uploaded";
const string bts_tracker_port = "tracker port";
const string bts_unpause_torrent = "unpause torrent";
const string bts_unregistered_ipa = "unregistered IP address";
const string bts_unregistered_torrent = "unregistered torrent";
const string bts_up_rate = "up rate";
const string bts_upload_rate = "upload rate";
const string bts_upload_slots = "upload slots";

#endif // !defined(AFX_BT_STRINGS_H__2BC4A814_E7F6_45BF_AEA3_59B270DA3A9A__INCLUDED_)
