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
	mcb_sub_piece = 32 << 10;
	m_priority = 0;
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
	m_sub_pieces[offset / mcb_sub_piece] = true;
	if (!--mc_sub_pieces_left)
	{
		for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
			(*i)->m_pieces.erase(this);
		m_peers.clear();
		m_sub_pieces.clear();
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

int Cbt_piece::pre_dump() const
{
	return 12;
}

int Cbt_piece::rank() const
{
	return 400000 * min(m_peers.size(), 9)
		+ 20000 * max(0, min(9 - m_priority, 19)) 
		+ 2000 * min(mc_peers, 9)
		+ 1000 * m_sub_pieces.empty()
		+ min(mc_peers, 999);
}

void Cbt_piece::load_state(Cstream_reader& r)
{
	m_valid = r.read_int(1);
}

int Cbt_piece::pre_save_state() const
{
	return 1;
}

void Cbt_piece::save_state(Cstream_writer& w) const
{
	w.write_int(1, m_valid);
}

void Cbt_piece::dump(Cstream_writer& w) const
{
	if (m_sub_pieces.empty())
	{
		w.write_int(1, 0);
		w.write_int(1, 0);
	}
	else
	{
		w.write_int(1, mc_sub_pieces_left);
		w.write_int(1, c_sub_pieces() - mc_sub_pieces_left);
	}
	w.write_int(4, mc_peers);
	w.write_int(1, m_priority);
	w.write_int(4, rank());
	w.write_int(1, m_valid);
}

