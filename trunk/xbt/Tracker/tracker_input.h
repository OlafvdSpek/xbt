#pragma once

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
	std::array<char, 20> m_peer_id;
	long long m_downloaded;
	long long m_left;
	long long m_uploaded;
	int m_ipa;
	int m_port;
	int m_num_want;
};
