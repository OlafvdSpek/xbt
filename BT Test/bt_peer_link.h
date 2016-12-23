#pragma once

#include "alerts.h"
#include "bt_peer_data.h"
#include "bt_pl_write_data.h"
#include "data_counter.h"
#include "ring_buffer.h"
#include <socket.h>
#include <stream_writer.h>

class Cbt_file;
class Cbt_piece;
class Cserver;

class Cbt_peer_link: public Cbt_peer_data
{
public:
	Cserver* server();
	const Cserver* server() const;
	time_t time() const;
	std::string debug_string() const;
	int write_data(long long o, const_memory_range, int latency);
	int c_max_requests_pending() const;
	void check_pieces();
	void clear_local_requests();
	int cb_write_buffer() const;
	void alert(Calert::t_level, const std::string&);
	int pre_dump() const;
	void dump(Cstream_writer&) const;
	void write_piece(int, int, const Cvirtual_binary&);
	void write_merkle_piece(long long offset, const_memory_range, const std::string& hashes);
	void queue_have(int);
	void write_have(int);
	void write_haves();
	int read_handshake(const_memory_range);
	int read_message(const_memory_range);
	void read_info(const_memory_range);
	void write_keepalive();
	int read_piece(int, int, const_memory_range);
	void read_merkle_piece(long long offset, const_memory_range, const_memory_range hashes);
	void write_extended_handshake();
	void write_handshake();
	void write_request(int, int, int);
	void write_merkle_cancel(long long offset);
	void write_merkle_request(long long offset, int c_hashes);
	void write_cancel(int, int, int);
	void write_get_info();
	void write_info();
	void write_get_peers();
	void write_peers();
	void choked(bool);
	void interested(bool);
	void write_bitfield();
	void remote_requests(int, int, int);
	void remote_has(unsigned int);
	void remote_cancels(int, int, int);
	void remote_merkle_cancels(long long offset);
	void remote_merkle_requests(long long offset, int c_hashes);
	int send(int& send_quota);
	int recv();
	void write(const Cvirtual_binary&, bool user_data = false);
	void close();
	int pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	int post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	Cbt_peer_link();
	~Cbt_peer_link();

	operator bool() const
	{
		return m_s != INVALID_SOCKET;
	}

	struct t_local_request
	{
		long long offset;
		int size;
		time_t stime;

		t_local_request()
		{
		}

		t_local_request(long long _offset, int _size, time_t _time)
		{
			offset = _offset;
			size = _size;
			stime = _time;
		}
	};

	struct t_remote_request
	{
		int c_hashes;
		long long offset;
		int size;

		t_remote_request()
		{
		}

		t_remote_request(long long _offset, int _size, int _c_hashes)
		{
			c_hashes = _c_hashes;
			offset = _offset;
			size = _size;
		}
	};

	typedef std::list<t_local_request> t_local_requests;
	typedef std::set<int> t_have_queue;
	typedef std::vector<bool> t_remote_pieces;
	typedef std::list<t_remote_request> t_remote_requests;
	typedef std::list<Cbt_pl_write_data> t_write_buffer;

	sockaddr_in m_a;
	Cbt_file* m_f;
	Csocket m_s;
	int m_ut_pex_extension;
	int m_state;
	Cring_buffer m_read_b;
	t_write_buffer m_write_b;
	t_have_queue m_have_queue;
	time_t m_check_pieces_time;
	int mc_max_requests_pending;
	t_local_requests m_local_requests;
	int mc_local_requests_pending;
	t_remote_pieces m_remote_pieces;
	t_remote_requests m_remote_requests;
	Cdata_counter m_down_counter;
	Cdata_counter m_up_counter;
	time_t m_get_peers_stime;
	time_t m_peers_stime;
	bool m_extended_extension;
	bool m_get_info_extension;
	bool m_get_peers_extension;
	bool m_local_choked_goal;
	bool m_local_interested_goal;
	bool m_can_recv;
	bool m_can_send;
private:
	void write_choke(bool);
	void write_interested(bool);
};
