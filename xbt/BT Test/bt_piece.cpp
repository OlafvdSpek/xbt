// bt_piece.cpp: implementation of the Cbt_piece class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_piece.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_piece::Cbt_piece()
{
	mc_peers = 0;
	mcb_sub_piece = 16 << 10;
	m_valid = false;
}

void Cbt_piece::write(int offset, const char* s, int cb_s)
{
	int b = offset / mcb_sub_piece;
	if (m_valid || offset < 0 || offset >= mcb_d || offset % mcb_sub_piece || cb_s != cb_sub_piece(b))
		return;
	if (m_sub_pieces.empty())
		m_sub_pieces.resize(mc_sub_pieces_left = c_sub_pieces());
	if (m_sub_pieces[b])
		return;
	memcpy(m_d.write_start(mcb_d) + offset, s, cb_s);
	m_sub_pieces[offset / mcb_sub_piece] = true;
	if (!--mc_sub_pieces_left)
	{
		for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
			(*i)->m_pieces.erase(this);
		m_peers.clear();
		m_sub_pieces.clear();
		if (memcmp(compute_sha1(m_d).c_str(), m_hash, 20))
		{
			m_d.clear();
			cout << "invalid" << endl;
		}
		else
			m_valid = true;
	}
}

int Cbt_piece::c_sub_pieces() const
{
	return (mcb_d + mcb_sub_piece - 1) / mcb_sub_piece;
}

int Cbt_piece::cb_sub_piece(int b)
{
	return min(mcb_sub_piece * (b + 1), mcb_d) - mcb_sub_piece * b;
}
