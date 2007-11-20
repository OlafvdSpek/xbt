#pragma once

class Cbt_sub_file_data
{
public:
	Cbt_sub_file_data();

	long long m_left;
	long long m_offset;
	long long m_size;
	int m_priority;
	std::string m_merkle_hash;
	std::string m_name;
};
