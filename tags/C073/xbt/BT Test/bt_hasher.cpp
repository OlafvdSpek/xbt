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
			bool root_valid = m_sub_file->merkle_tree().root().string() == m_sub_file->merkle_hash();
			d.write_start(piece.size());
			piece.valid(!f.read_data(f.mcb_piece * m_i, d));
			for (const byte* r = d; r < d.end(); r += 0x8000)
			{
				std::string h = Cmerkle_tree::compute_root(const_memory_range(r, std::min(r + 0x8000, d.end())));
				if (root_valid)
				{
					if (piece.valid() && (!m_sub_file->merkle_tree().has(m_j) || h != m_sub_file->merkle_tree().get(m_j).string()))
						piece.valid(false);
				}
				else
					m_sub_file->merkle_tree().set(m_j, h);
				m_j++;
			}
		}
		else
		{
			d.write_start(piece.size());
			piece.valid(!f.read_data(f.mcb_piece * m_i, d)
				&& !memcmp(Csha1(d).read().data(), piece.m_hash, 20));
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
			if (f.m_merkle && m_sub_file->merkle_tree().root().string() != m_sub_file->merkle_hash())
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
