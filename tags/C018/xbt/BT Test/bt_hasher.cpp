// bt_hasher.cpp: implementation of the Cbt_hasher class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_hasher.h"

#include <fcntl.h>

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_hasher::Cbt_hasher(bool validate):
	m_validate(validate)
{
	m_i = 0;
	m_offset = 0;
}

bool Cbt_hasher::run(Cbt_file& f)
{
	if (m_i >= f.m_pieces.size())
		return false;
	if (!m_i)
		m_sub_file = f.m_sub_files.begin();
	Cbt_piece& piece = f.m_pieces[m_i];
	Cvirtual_binary d;
	if (m_validate)
	{
		piece.m_valid = !f.read_data(f.mcb_piece * m_i, d.write_start(piece.mcb_d), piece.mcb_d)
			&& !memcmp(compute_sha1(d).c_str(), piece.m_hash, 20);
	}
	if (!piece.m_valid)
		f.m_left += piece.mcb_d;
	int cb0 = piece.mcb_d;
	while (cb0)
	{
		int cb1 = min(cb0, m_sub_file->size() - m_offset);
		if (!piece.m_valid)
			m_sub_file->left(m_sub_file->left() + cb1);
		cb0 -= cb1;
		m_offset += cb1;
		if (m_offset == m_sub_file->size())
		{
			if (!m_sub_file->left())
			{
				m_sub_file->close();
				m_sub_file->open(f.m_name, _O_RDONLY);
			}
			m_offset = 0;
			m_sub_file++;
		}
	}
	m_i++;
	return true;
}
