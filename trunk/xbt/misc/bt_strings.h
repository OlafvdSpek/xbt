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

	bti_get_status = 0x80,
	bti_status,
};

const string bts_announce = "announce";
const string bts_complete = "complete";
const string bts_downloaded = "downloaded";
const string bts_failure_reason = "failure reason";
const string bts_files = "files";
const string bts_incomplete = "incomplete";
const string bts_info = "info";
const string bts_interval = "interval";
const string bts_ipa = "ip";
const string bts_length = "length";
const string bts_name = "name";
const string bts_path = "path";
const string bts_peer_id = "peer id";
const string bts_peers = "peers";
const string bts_piece_length = "piece length";
const string bts_pieces = "pieces";
const string bts_port = "port";

#endif // !defined(AFX_BT_STRINGS_H__2BC4A814_E7F6_45BF_AEA3_59B270DA3A9A__INCLUDED_)
