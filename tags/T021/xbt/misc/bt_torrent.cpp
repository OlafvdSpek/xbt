#include "stdafx.h"
#include "bt_torrent.h"

#include "bt_strings.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

Cbt_torrent::Cbt_torrent()
{
}

Cbt_torrent::Cbt_torrent(const Cbvalue& v)
{
	write(v);
}

int Cbt_torrent::write(const Cbvalue& v)
{
	m_announce = v.d(bts_announce).s();
	m_announces.clear();
	const Cbvalue::t_list& announces = v.d(bts_announce_list).l();
	for (Cbvalue::t_list::const_iterator i = announces.begin(); i != announces.end(); i++)
	{
		for (Cbvalue::t_list::const_iterator j = i->l().begin(); j != i->l().end(); j++)
			m_announces.push_back(j->s());		
	}
	return write_info(v.d(bts_info));
}

int Cbt_torrent::write_info(const Cbvalue& v)
{
	m_files.clear();
	const Cbvalue::t_list& files = v.d(bts_files).l();
	for (Cbvalue::t_list::const_iterator i = files.begin(); i != files.end(); i++)
	{
		string name;
		__int64 size = i->d(bts_length).i();
		{
			const Cbvalue::t_list& path = i->d(bts_path).l();
			for (Cbvalue::t_list::const_iterator i = path.begin(); i != path.end(); i++)
			{
				if (i->s().empty() || i->s()[0] == '.' || i->s().find_first_of("\"*/:<>?\\|") != string::npos)
					return 1;
				name += '/' + i->s();
			}
		}
		if (name.empty())
			return 1;
		m_files.push_back(Cfile(name, size));
	}
	if (m_files.empty())
		m_files.push_back(Cfile("", v.d(bts_length).i()));
	m_name = v.d(bts_name).s();
	m_piece_size = v.d(bts_piece_length).i();
	return 0;
}

__int64 Cbt_torrent::size() const
{
	__int64 r = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		r += i->size();
	return r;
}

bool Cbt_torrent::valid() const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->size() < 0)
			return false;
	}
	return !announce().empty()
		&& !files().empty()
		&& !name().empty()
		&& name()[0] != '.'
		&& name().find_first_of("\"*/:<>?\\|") == string::npos
		&& piece_size() >= 16 << 10
		&& piece_size() <= 4 << 20;
}
