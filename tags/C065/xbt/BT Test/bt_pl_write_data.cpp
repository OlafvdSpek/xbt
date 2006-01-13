#include "stdafx.h"
#include "bt_pl_write_data.h"

Cbt_pl_write_data::Cbt_pl_write_data()
{
}

Cbt_pl_write_data::Cbt_pl_write_data(const Cvirtual_binary& s)
{
	m_vb = s;
	m_s = m_r = reinterpret_cast<const char*>(m_vb.data());
	m_s_end = reinterpret_cast<const char*>(m_vb.data_end());
}

Cbt_pl_write_data::Cbt_pl_write_data(const char* s, int cb_s)
{
	m_s = m_r = s;
	m_s_end = s + cb_s;
}

