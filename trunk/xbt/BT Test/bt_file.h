// bt_file.h: interface for the Cbt_file class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
#define AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "bvalue.h"
#include "bt_piece.h"
#include "bt_peer_link.h"
#include "bt_tracker_link.h"
#include "data_counter.h"
#include "stream_reader.h"

class Cbt_file  
{
public:
	void alert(const Calert&);
	void load_state(Cstream_reader&);
	int pre_save_state(bool intermediate) const;
	void save_state(Cstream_writer&, bool intermediate) const;
	__int64 size() const;
	int c_seeders() const;
	int c_leechers() const;
	int time_remaining() const;
	int pre_dump(bool full = false) const;
	void dump(Cstream_writer&, bool full = false) const;
	ostream& dump(ostream&) const;
	int next_invalid_piece(const Cbt_peer_link&);
	int read_piece(int a, byte* d);
	void write_data(int o, const char* s, int cb_s);
	void close();
	int open(const string& name, bool validate);
	int c_invalid_pieces() const;
	int c_pieces() const;
	int c_valid_pieces() const;
	void insert_peer(int h, int p);
	void insert_peer(const sockaddr_in& a);
	void insert_peer(const t_bt_handshake& handshake, const sockaddr_in& a, const Csocket& s);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int torrent(const Cbvalue&);
	int info(const Cbvalue&);
	Cbt_file();
	~Cbt_file();

	struct t_sub_file
	{
		string m_name;
		__int64 m_size;

		void close();
		bool open(const string& parent_name, int oflag);
		int read(__int64  offset, void* s, int cb_s);
		int write(__int64  offset, const void* s, int cb_s);
		
		operator bool() const
		{
			return m_f != -1;
		}

		t_sub_file()
		{
		}

		t_sub_file(const string& name, __int64 size)
		{
			m_f = -1;
			m_name = name;
			m_size = size;
		}
	private:
		int m_f;
	};

	typedef map<int, int> t_new_peers;
	typedef vector<t_sub_file> t_sub_files;
	typedef vector<Cbt_peer_link> t_peers;
	typedef vector<Cbt_piece> t_pieces;
	typedef vector<string> t_trackers;

	string m_info_hash;
	string m_info_hashes_hash;
	string m_name;
	string m_peer_id;
	t_sub_files m_sub_files;
	t_new_peers m_new_peers;
	t_peers m_peers;
	t_pieces m_pieces;
	Calerts m_alerts;
	Cbt_tracker_link m_tracker;
	t_trackers m_trackers;
	Cvirtual_binary m_info;
	Cvirtual_binary m_info_hashes;

	__int64 mcb_piece;
	__int64 mcb_f;

	__int64 m_downloaded;
	__int64 m_left;
	__int64 m_uploaded;
	__int64 m_total_downloaded;
	__int64 m_total_uploaded;
	Cdata_counter m_down_counter;
	Cdata_counter m_up_counter;

	int m_local_port;
	bool m_run;
};

ostream& operator<<(ostream&, const Cbt_file&);

#endif // !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
