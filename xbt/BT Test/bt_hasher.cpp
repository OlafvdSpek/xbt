#include "stdafx.h"
#include "bt_hasher.h"

#include <fcntl.h>

Cbt_hasher::Cbt_hasher(bool validate):
	m_validate(validate)
{
	m_i = 0;
	m_j = 0;
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
		if (f.m_merkle)
		{
			bool root_valid = m_sub_file->merkle_tree().root() == m_sub_file->merkle_hash();
			piece.valid(!f.read_data(f.mcb_piece * m_i, d.write_start(piece.size()), piece.size()));
			for (const byte* r = d; r < d.data_end(); r += 0x8000)
			{
				string h = Cmerkle_tree::compute_root(r, min(r + 0x8000, d.data_end()));
				if (root_valid)
				{
					if (piece.valid() && (!m_sub_file->merkle_tree().has(m_j) || h != m_sub_file->merkle_tree().get(m_j)))
						piece.valid(false);
				}
				else
					m_sub_file->merkle_tree().set(m_j, h);
				m_j++;
			}
		}
		else
		{
			piece.valid(!f.read_data(f.mcb_piece * m_i, d.write_start(piece.size()), piece.size())
				&& !memcmp(compute_sha1(d).c_str(), piece.m_hash, 20));
		}
	}
	if (!piece.valid())
		f.m_left += piece.size();
	int cb0 = piece.size();
	while (cb0)
	{
		int cb1 = min(cb0, m_sub_file->size() - m_offset);
		if (!piece.valid())
			m_sub_file->left(m_sub_file->left() + cb1);
		cb0 -= cb1;
		m_offset += cb1;
		if (m_offset == m_sub_file->size())
		{
			if (f.m_merkle && m_sub_file->merkle_tree().root() != m_sub_file->merkle_hash())
			{
				Cbt_piece* piece = &f.m_pieces.front() + m_sub_file->offset() / f.mcb_piece;
				for (int i = 0; i < m_sub_file->c_pieces(f.mcb_piece); i++)
				{
					if (piece->valid())
						f.m_left += piece->size();
					piece->valid(false);
					piece++;
				}
				m_sub_file->left(m_sub_file->size());
				m_sub_file->merkle_tree().invalidate();
				m_sub_file->merkle_tree().root(m_sub_file->merkle_hash());
			}
			if (!m_sub_file->left())
			{
				m_sub_file->close();
				m_sub_file->open(f.m_name, O_RDONLY);
			}
			m_j = 0;
			m_offset = 0;
			m_sub_file++;
		}
	}
	m_i++;
	return true;
}
