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
	class Cfile
	{
	public:
		const string& name() const
		{
			return m_name;
		}

		__int64 size() const
		{
			return m_size;
		}

		Cfile()
		{
		}

		Cfile(const string& name, __int64 size)
		{
			m_name = name;
			m_size = size;
		}
	private:
		string m_name;
		__int64 m_size;
	};

	typedef vector<string> t_announces;
	typedef vector<Cfile> t_files;

	__int64 size() const;
	bool valid() const;
	int write(const Cbvalue&);
	int write_info(const Cbvalue&);
	Cbt_torrent();
	Cbt_torrent(const Cbvalue&);

	const string& announce() const
	{
		return m_announce;
	}

	const t_announces& announces() const
	{
		return m_announces;
	}

	const t_files& files() const
	{
		return m_files;
	}

	const string& name() const
	{
		return m_name;
	}

	int piece_size() const
	{
		return m_piece_size;
	}
private:
	string m_announce;
	t_announces m_announces;
	t_files m_files;
	string m_name;
	int m_piece_size;
};

#endif // !defined(AFX_BT_TORRENT_H__AF87B246_788C_42B3_BE1C_08679DFEFBA4__INCLUDED_)
