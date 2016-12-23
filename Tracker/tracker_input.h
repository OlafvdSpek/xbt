#pragma once

class Ctracker_input
{
public:
	void set(const std::string& name, const std::string& value);
	bool valid() const;

	bool is_seeder() const
	{
		return !m_left;
	}
	
	bool is_leecher() const
	{
		return m_left;
	}

	enum t_event
	{
		e_none,
		e_completed,
		e_started,
		e_stopped,
	};

	t_event m_event = e_none;
	std::string m_info_hash;
	std::vector<std::string> m_info_hashes;
	std::array<char, 20> m_peer_id;
	long long m_downloaded = 0;
	long long m_left = 0;
	long long m_uploaded = 0;
	int m_ipa = 0;
	int m_port = 0;
};
