// bt_tracker_link.h: interface for the Cbt_tracker_link class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_TRACKER_LINK_H__19566F35_0475_4CE0_BF87_19345BBD0E42__INCLUDED_)
#define AFX_BT_TRACKER_LINK_H__19566F35_0475_4CE0_BF87_19345BBD0E42__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_tracker_url.h"
#include "socket.h"
#include "stream_writer.h"

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

	void event(int);
	int pre_dump() const;
	void dump(Cstream_writer&) const;
	void close(Cbt_file&);
	int read(Cbt_file& f, const Cvirtual_binary&);
	int pre_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	void post_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set);
	Cbt_tracker_link();
	~Cbt_tracker_link();

	Cvirtual_binary m_d;
	byte* m_w;
	Cbt_tracker_url m_url;
	Csocket m_s;
	int m_state;
	int m_announce_time;
	__int64 m_connection_id;
	int m_transaction_id;
private:
	int m_announce_send;
	int mc_attempts;
	int m_connect_send;
	int m_current_tracker;
	int m_event;
};

#endif // !defined(AFX_BT_TRACKER_LINK_H__19566F35_0475_4CE0_BF87_19345BBD0E42__INCLUDED_)

