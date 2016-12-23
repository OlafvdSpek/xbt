#pragma once

#include "bt_file.h"

class Cbt_hasher
{
public:
	bool run(Cbt_file&);
	Cbt_hasher(bool validate);
private:
	unsigned int m_i;
	unsigned int m_j;
	long long m_offset;
	Cbt_file::t_sub_files::iterator m_sub_file;
	const bool m_validate;
};
