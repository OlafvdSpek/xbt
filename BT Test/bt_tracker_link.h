#pragma once

#include "bt_tracker_url.h"
#include <socket.h>
#include <stream_writer.h>

class Cbt_file;

class Cbt_tracker_link
{
public:
	enum t_event
	{
		e_none,
		e_completed,
		e_started,
		e_stopped,
	};

	std::string http_request(const Cbt_file&);
	void event(int);
	int pre_dump() const;
	void dump(Cstream_writer&) const;
	void close(Cbt_file&);
	int read(Cbt_file& f, const_memory_range);
	int pre_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	Cbt_tracker_link();
	~Cbt_tracker_link();

	time_t m_announce_time;
private:
	long long m_connection_id;
	byte* m_w;
	Cbt_tracker_url m_url;
	Csocket m_s;
	Cvirtual_binary m_d;
	time_t m_announce_send;
	time_t m_connect_send;
	unsigned int m_current_tracker;
	int m_event;
	int m_state;
	int m_transaction_id;
	int mc_attempts;
};
