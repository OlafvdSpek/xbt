#pragma once

#include <string>

class Ctracker_input
{
public:
	void set(const std::string& name, const std::string& value);
	bool valid() const;
	Ctracker_input();

	enum t_event
	{
		e_none,
		e_completed,
		e_started,
		e_stopped,
	};

	typedef std::vector<std::string> t_info_hashes;

	t_event m_event;
	std::string m_info_hash;
	t_info_hashes m_info_hashes;
	int m_ipa;
	std::string m_peer_id;
	long long m_downloaded;
	long long m_left;
	int m_port;
	long long m_uploaded;
	int m_num_want;
	bool m_compact;
};
