#include "stdafx.h"
#include "data_counter.h"

Cdata_counter::Cdata_counter()
{
	m_got = 0;
	m_rate = 0;
}

void Cdata_counter::add(int s, time_t t)
{
	if (!m_got)
		m_start_time = t;
	m_got += s;
	update_rate(t);
}

int Cdata_counter::rate(time_t t) const
{
	const_cast<Cdata_counter*>(this)->update_rate(t);
	return m_rate;
}

void Cdata_counter::update_rate(time_t t)
{
	if (t - m_start_time)
		m_rate = m_got / (t - m_start_time);
	if (t - m_start_time > 15)
	{
		m_got = 0;
		m_start_time = t - 5;
	}
	else if (t - m_start_time > 10)
	{
		m_got = 5 * m_rate;
		m_start_time = t - 5;
	}
}
