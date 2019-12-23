#pragma once

class tracker_input_t
{
public:
	void set(std::string_view name, std::string_view value);
	bool valid() const;

	bool is_seeder() const
	{
		return !left_;
	}
	
	bool is_leecher() const
	{
		return left_;
	}

	enum event_t
	{
		e_none,
		e_completed,
		e_started,
		e_stopped,
	};

	event_t event_ = e_none;
	std::string info_hash_;
	std::vector<std::string> info_hashes_;
	std::array<char, 20> peer_id_;
	long long downloaded_ = 0;
	long long left_ = 0;
	long long uploaded_ = 0;
	int ipa_ = 0;
	int port_ = 0;
};
