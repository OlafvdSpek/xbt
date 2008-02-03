#include "stdafx.h"
#include "bt_pl_write_data.h"

Cbt_pl_write_data::Cbt_pl_write_data(const Cvirtual_binary& s, bool user_data)
{
	m_vb = s;
	m_s = m_vb;
	m_user_data = user_data;
}
