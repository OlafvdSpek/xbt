#pragma once

class Cbt_peer_data
{
public:
	Cbt_peer_data();

	long long m_downloaded;
	long long m_left;
	long long m_uploaded;
	time_t m_ctime;
	time_t m_rtime;
	time_t m_stime;
	bool m_local_link;
	bool m_local_choked;
	bool m_local_interested;
	bool m_remote_choked;
	bool m_remote_interested;
	std::string m_remote_peer_id;
};
