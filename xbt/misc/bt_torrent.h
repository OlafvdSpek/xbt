// bt_torrent.h: interface for the Cbt_torrent class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_TORRENT_H__AF87B246_788C_42B3_BE1C_08679DFEFBA4__INCLUDED_)
#define AFX_BT_TORRENT_H__AF87B246_788C_42B3_BE1C_08679DFEFBA4__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bvalue.h"

class Cbt_torrent  
{
public:
	int write(const Cvirtual_binary&);
	int write(const Cbvalue&);
	int write_info(const Cbvalue& v);
	Cbt_torrent();
	Cbt_torrent(const Cvirtual_binary&);

	const string& announce() const
	{
		return m_announce;
	}

	const string& name() const
	{
		return m_name;
	}
private:
	string m_announce;
	string m_name;
};

#endif // !defined(AFX_BT_TORRENT_H__AF87B246_788C_42B3_BE1C_08679DFEFBA4__INCLUDED_)
