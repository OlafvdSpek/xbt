// data_counter.cpp: implementation of the Cdata_counter class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "data_counter.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cdata_counter::Cdata_counter()
{
	m_got = 0;
	m_rate = 0;
}

void Cdata_counter::add(int s)
{
	if (!m_got)
		m_start_time = time(NULL);
	m_got += s;
	update_rate();
}

int Cdata_counter::rate() const
{
	const_cast<Cdata_counter*>(this)->update_rate();
	return m_rate;
}

void Cdata_counter::update_rate()
{
	if (time(NULL) - m_start_time)
		m_rate = m_got / (time(NULL) - m_start_time);
	if (time(NULL) - m_start_time > 15)
	{
		m_got = 0;
		m_start_time = time(NULL) - 5;
	}
	else if (time(NULL) - m_start_time > 10)
	{
		m_got = 5 * m_rate;
		m_start_time = time(NULL) - 5;
	}
}
