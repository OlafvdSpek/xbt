// bt_file.h: interface for the Cbt_file class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
#define AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bvalue.h"
#include "bt_piece.h"
#include "bt_peer_link.h"
#include "bt_tracker_link.h"
#include "data_counter.h"

class Cbt_file  
{
public:
	__int64 left() const;
	int time_remaining();
	void dump(ostream& os);
	void write_have(int);
	int next_invalid_piece(const Cbt_peer_link::t_remote_pieces&) const;
	int read_piece(int a, byte* d);
	void write_data(int o, const char* s, int cb_s);
	void close();
	int open(const string& name);
	int c_invalid_pieces() const;
	int c_pieces() const;
	int c_valid_pieces() const;
	void insert_peer(const sockaddr_in& a);
	void insert_peer(const sockaddr_in& a, const Csocket& s);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int info(const Cvirtual_binary&);
	int info(const Cbvalue&);
	Cbt_file();
	~Cbt_file();

	struct t_sub_file
	{
		FILE* m_f;
		string m_name;
		int m_size;

		t_sub_file()
		{
		}

		t_sub_file(const string& name, int size)
		{
			m_f = NULL;
			m_name = name;
			m_size = size;
		}
	};

	typedef vector<t_sub_file> t_sub_files;
	typedef vector<Cbt_peer_link> t_peers;
	typedef vector<Cbt_piece> t_pieces;
	typedef vector<string> t_trackers;

	string m_info_hash;
	string m_name;
	string m_peer_id;
	t_sub_files m_sub_files;
	t_peers m_peers;
	t_pieces m_pieces;
	Cbt_tracker_link m_tracker;
	t_trackers m_trackers;

	int mcb_piece;
	int mcb_f;

	__int64 m_downloaded;
	__int64 m_uploaded;
	Cdata_counter m_down_counter;
	Cdata_counter m_up_counter;

	int m_local_port;
};

#endif // !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
