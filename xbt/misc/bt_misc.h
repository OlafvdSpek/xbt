// bt_misc.h: interface for the Cbt_misc class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
#define AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

string escape_string(const string& v);
string n(int v);
string hex_encode(int l, int v);
string uri_decode(const string& v);

struct t_udp_tracker_input
{
	int m_zero;
	char m_info_hash[20];
	char m_peer_id[20];
	int m_downloaded;
	int m_event;
	int m_left;
	int m_port;
	int m_uploaded;
};

struct t_udp_tracker_output
{
	int m_zero;
	int m_interval;
};

struct t_udp_tracker_output_peer
{
	int m_host;
	short m_port;
};

#endif // !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
