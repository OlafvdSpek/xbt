// bt_torrent.cpp: implementation of the Cbt_torrent class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_torrent.h"

#include "bt_strings.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_torrent::Cbt_torrent()
{
}

Cbt_torrent::Cbt_torrent(const Cvirtual_binary& v)
{
	write(v);
}

int Cbt_torrent::write(const Cvirtual_binary& v)
{
	Cbvalue a;
	a.write(v);
	return write(a);
}

int Cbt_torrent::write(const Cbvalue& v)
{
	m_announce = v.d(bts_announce).s();
	write_info(v.d(bts_info));
	return 0;
}

int Cbt_torrent::write_info(const Cbvalue& v)
{
	m_name = v.d(bts_name).s();
	return 0;
}