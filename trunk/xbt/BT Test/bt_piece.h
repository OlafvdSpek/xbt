// bt_piece.h: interface for the Cbt_piece class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_)
#define AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_peer_link.h"
#include "stream_reader.h"

class Cbt_piece  
{
public:
	int cb_sub_piece(int);
	int c_sub_pieces() const;
	void load_state(Cstream_reader&);
	int pre_save_state() const;
	void save_state(Cstream_writer&) const;
	int pre_dump() const;
	void dump(Cstream_writer&) const;
	int rank() const;
	void write(int offset, const char* s, int cb_s);
	Cbt_piece();

	typedef set<Cbt_peer_link*> t_peers;
	typedef vector<bool> t_sub_pieces;
	
	int mcb_d;
	char m_hash[20];
	t_peers m_peers;
	int mc_peers;
	t_sub_pieces m_sub_pieces;
	int mc_sub_pieces_left;
	int mcb_sub_piece;
	bool m_valid;
	char m_priority;
};

#endif // !defined(AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_)
