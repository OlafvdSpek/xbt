#include "stdafx.h"
#include "ring_buffer.h"

void Cring_buffer::write(const void* d, size_t cb_d)
{
	size_t cb = min(cb_d, cb_w());
	memcpy(w(), d, cb);
	cb_w(cb);
	if (cb_d -= cb)
	{
		memcpy(w(), reinterpret_cast<const char*>(d) + cb, cb_d);
		cb_w(cb_d);
	}
}

void Cring_buffer::size(size_t cb_d)
{
	if (cb_d)
	{
		m_r = m_b = m_w = reinterpret_cast<char*>(m_d.write_start(cb_d));
		m_e = reinterpret_cast<const char*>(m_d.end());
	}
	else
	{
		m_d.clear();
		m_r = m_e = m_b = m_w = NULL;
	}
}

void Cring_buffer::combine()
{
	char* d = new char[cb_read()];
	size_t c0 = cb_r();
	memcpy(d, r(), c0);
	cb_r(c0);
	size_t c1 = cb_r();
	memcpy(d + c0, r(), c1);
	cb_r(c1);
	memcpy(m_b, d, c0 + c1);
	m_r = m_b;
	m_w = m_b + c0 + c1;
	delete[] d;
}
