#pragma once

class Cbt_pl_write_data
{
public:
	Cbt_pl_write_data()
	{
	}

	Cbt_pl_write_data(const Cvirtual_binary&, bool user_data);

	const_memory_range m_s;
	Cvirtual_binary m_vb;
	bool m_user_data;
};
