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

class Cserver;

class Cbt_file  
{
public:
	void update_piece_priorities();
	void sub_file_priority(const string& id, int priority);
	int local_ipa() const;
	int local_port() const;
	bool info_valid() const;
	Cbt_peer_link* find_peer(int h);
	string get_url() const;
	void alert(const Calert&);
	void load_state(Cstream_reader&);
	int pre_save_state(bool intermediate) const;
	void save_state(Cstream_writer&, bool intermediate) const;
	__int64 size() const;
	int c_seeders() const;
	int c_leechers() const;
	int time_remaining() const;
	int pre_dump(int flags) const;
	void dump(Cstream_writer&, int flags) const;
	ostream& dump(ostream&) const;
	int next_invalid_piece(const Cbt_peer_link&);
	int read_data(__int64 o, byte* d, int cb_d);
	void write_data(__int64 o, const char* s, int cb_s);
	void close();
	int open(const string& name);
	int c_invalid_pieces() const;
	int c_pieces() const;
	int c_valid_pieces() const;
	void insert_old_peer(int h, int p);
	void insert_peer(int h, int p);
	void insert_peer(const char* r, const sockaddr_in& a, const Csocket& s);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int torrent(const Cbvalue&);
	int info(const Cbvalue&);
	Cbt_file();
	~Cbt_file();

	struct t_sub_file
	{
		void close();
		void dump(Cstream_writer&) const;
		bool open(const string& parent_name, int oflag);
		int pre_dump() const;
		int read(__int64  offset, void* s, int cb_s);
		int write(__int64  offset, const void* s, int cb_s);
		
		const string& merkle_hash() const
		{
			return m_merkle_hash;
		}

		__int64 left() const
		{
			return m_left;
		}

		__int64 left(__int64 v)
		{
			return m_left = v;
		}

		const string& name() const
		{
			return m_name;
		}

		int priority() const
		{
			return m_priority;
		}

		void priority(int v)
		{
			m_priority = max(-128, min(v, 127));
		}

		__int64 size() const
		{
			return m_size;
		}

		operator bool() const
		{
			return m_f != -1;
		}

		t_sub_file()
		{
		}

		t_sub_file(const string& merkle_hash, const string& name, int priority, __int64 size)
		{
			m_f = -1;
			m_merkle_hash = merkle_hash;
			m_name = name;
			m_priority = priority;
			m_size = size;
		}
	private:
		int m_f;
		string m_merkle_hash;
		__int64 m_left;
		string m_name;
		int m_priority;
		__int64 m_size;
	};

	typedef vector<bool> t_info_blocks_valid;
	typedef map<int, int> t_old_peers;
	typedef map<int, int> t_new_peers;
	typedef vector<t_sub_file> t_sub_files;
	typedef list<Cbt_peer_link> t_peers;
	typedef vector<Cbt_piece> t_pieces;
	typedef vector<string> t_trackers;

	string m_info_hash;
	string m_info_hashes_hash;
	string m_name;
	string m_peer_id;
	t_info_blocks_valid m_info_blocks_valid;
	t_sub_files m_sub_files;
	t_old_peers m_old_peers;
	t_new_peers m_new_peers;
	t_peers m_peers;
	t_pieces m_pieces;
	Calerts m_alerts;
	Cbt_tracker_link m_tracker;
	t_trackers m_trackers;
	Cvirtual_binary m_info;
	Cvirtual_binary m_info_hashes;
	int mc_leechers_total;
	int mc_seeders_total;

	__int64 mcb_piece;
	__int64 mcb_f;

	__int64 m_downloaded;
	__int64 m_left;
	__int64 m_uploaded;
	__int64 m_total_downloaded;
	__int64 m_total_uploaded;
	Cdata_counter m_down_counter;
	Cdata_counter m_up_counter;

	bool m_run;
	Cserver* m_server;
};

ostream& operator<<(ostream&, const Cbt_file&);

#endif // !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
