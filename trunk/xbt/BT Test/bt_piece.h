// bt_piece.h: interface for the Cbt_piece class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_)
#define AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_peer_link.h"

class Cbt_piece  
{
public:
	int cb_sub_piece(int);
	int c_sub_pieces() const;
	void clear();
	void write(int offset, const char* s, int cb_s);
	Cbt_piece();
	~Cbt_piece();

	typedef vector<bool> t_sub_pieces;
	
	Cvirtual_binary m_d;
	int mcb_d;
	char m_hash[20];
	Cbt_peer_link* m_peer;
	t_sub_pieces m_sub_pieces;
	int mc_sub_pieces_left;
	int mcb_sub_piece;
	bool m_valid;
};

#endif // !defined(AFX_BT_PIECE_H__E6E03656_9830_4FFE_8F22_B3BF46E9D3C4__INCLUDED_)
