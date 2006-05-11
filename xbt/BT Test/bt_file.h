#if !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
#define AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "alerts.h"
#include "bvalue.h"
#include "bt_file_data.h"
#include "bt_logger.h"
#include "bt_piece.h"
#include "bt_peer_link.h"
#include "bt_sub_file_data.h"
#include "bt_tracker_link.h"
#include "data_counter.h"
#include "merkle_tree.h"
#include "stream_reader.h"

class Cbt_hasher;
class Cserver;

class Cbt_file: public Cbt_file_data
{
public:
	const std::string& peer_id() const;
	const std::string& peer_key() const;
	Cserver* server();
	const Cserver* server() const;
	time_t time() const;
	void peer_connect(int ipa, int port);
	void peer_disconnect(int ipa);
	void trackers(const std::string&);
	int c_local_links() const;
	int c_remote_links() const;
	int seeding_ratio() const;
	int upload_slots_max() const;
	int upload_slots_min() const;
	void state(t_state);
	void announce();
	int c_max_requests_pending() const;
	bool end_mode() const;
	bool begin_mode() const;
	Cbt_logger& logger();
	std::string get_hashes(long long offset, int c) const;
	bool test_and_set_hashes(long long offset, const std::string& v, const std::string& w);
	bool hash();
	void update_piece_priorities();
	void sub_file_priority(const std::string& id, int priority);
	int local_ipa() const;
	int local_port() const;
	Cbt_peer_link* find_peer(int h);
	std::string get_url() const;
	void alert(const Calert&);
	void load_state(Cstream_reader&);
	int pre_save_state(bool intermediate) const;
	void save_state(Cstream_writer&, bool intermediate) const;
	long long size() const;
	int c_seeders() const;
	int c_leechers() const;
	int pre_dump(int flags) const;
	void dump(Cstream_writer&, int flags) const;
	int next_invalid_piece(const Cbt_peer_link&);
	int read_data(long long o, memory_range);
	int write_data(long long o, const_memory_range, Cbt_peer_link*);
	void close();
	void erase();
	void open();
	int c_invalid_pieces() const;
	int c_pieces() const;
	int c_valid_pieces() const;
	void insert_old_peer(int h, int p);
	void insert_peer(int h, int p);
	void insert_peers(const_memory_range);
	void insert_peer(const char* r, const sockaddr_in& a, const Csocket& s);
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int torrent(const Cbvalue&);
	int info(const Cbvalue&);
	Cbt_file();
	~Cbt_file();

	bool is_open() const
	{
		switch (state())
		{
		case s_hashing:
		case s_paused:
		case s_running:
			return true;
		}
		return false;
	}

	int priority() const
	{
		return m_priority;
	}

	void priority(int v)
	{
		m_priority = v;
	}

	t_state state() const
	{
		return m_state;
	}

	struct t_sub_file: public Cbt_sub_file_data
	{
		int c_pieces(int cb_piece) const;
		void close();
		void dump(Cstream_writer&) const;
		void erase(const std::string& parent_name);
		bool open(const std::string& parent_name, int oflag);
		int pre_dump() const;
		int read(long long offset, memory_range);
		int write(long long offset, const_memory_range);

		const std::string& merkle_hash() const
		{
			return m_merkle_hash;
		}

		Cmerkle_tree& merkle_tree()
		{
			return m_merkle_tree;
		}

		const Cmerkle_tree& merkle_tree() const
		{
			return m_merkle_tree;
		}

		long long left() const
		{
			return m_left;
		}

		long long left(long long v)
		{
			return m_left = v;
		}

		const std::string& name() const
		{
			return m_name;
		}

		long long offset() const
		{
			return m_offset;
		}

		int priority() const
		{
			return m_priority;
		}

		void priority(int v)
		{
			m_priority = std::max(-128, std::min(v, 127));
		}

		long long size() const
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

		t_sub_file(const std::string& merkle_hash, const std::string& name, long long offset, int priority, long long size)
		{
			m_f = -1;
			m_merkle_hash = merkle_hash;
			m_merkle_tree.resize(size + 0x7fff >> 15);
			m_name = name;
			m_offset = offset;
			m_priority = priority;
			m_size = size;
		}
	private:
		int m_f;
		Cmerkle_tree m_merkle_tree;
	};

	typedef std::map<int, int> t_old_peers;
	typedef std::map<int, int> t_new_peers;
	typedef std::vector<t_sub_file> t_sub_files;
	typedef std::list<Cbt_peer_link> t_peers;
	typedef std::vector<Cbt_piece> t_pieces;
	typedef std::vector<std::string> t_trackers;

	t_sub_files m_sub_files;
	t_old_peers m_old_peers;
	t_new_peers m_new_peers;
	t_peers m_peers;
	t_pieces m_pieces;
	Calerts m_alerts;
	Cbt_tracker_link m_tracker;
	t_trackers m_trackers;
	Cvirtual_binary m_info;

	Cdata_counter m_down_counter;
	Cdata_counter m_up_counter;

	Cbt_hasher* m_hasher;
	bool m_merkle;
	bool m_validate;
	Cbt_logger* m_logger;
	Cserver* m_server;
};

#endif // !defined(AFX_BT_FILE_H__E64A5C96_20E5_4C90_8267_F9BC96F99888__INCLUDED_)
