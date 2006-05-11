#if !defined(AFX_RING_BUFFER_H__67856C4E_F9FE_4D73_8553_27AF76B4C5F2__INCLUDED_)
#define AFX_RING_BUFFER_H__67856C4E_F9FE_4D73_8553_27AF76B4C5F2__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "virtual_binary.h"

class Cring_buffer
{
public:
	void combine();
	void size(size_t cb_d);
	void write(const void* d, size_t cb_d);

	size_t size() const
	{
		return m_d.size();
	}

	const char* r() const
	{
		return m_r;
	}

	const char* r_end() const
	{
		return m_r <= m_w ? m_w : m_e;
	}

	void r(const char* r)
	{
		m_r = r;
	}

	size_t cb_r() const
	{
		return m_r <= m_w ? m_w - m_r : m_e - m_r;
	}

	size_t cb_read() const
	{
		return m_r <= m_w ? m_w - m_r : m_e - m_r + m_w - m_b;
	}

	void cb_r(size_t v)
	{
		m_r += v;
		if (m_r == m_e)
			m_r = m_b;
	}

	void w(char* w)
	{
		m_w = w;
	}

	char* w()
	{
		return m_w;
	}

	size_t cb_w() const
	{
		return m_r <= m_w ? (m_b == m_r ? m_e - m_w - 1 : m_e - m_w) : m_r - m_w - 1;
	}

	size_t cb_write() const
	{
		return m_r <= m_w ? m_e - m_w + m_r - m_b - 1 : m_r - m_w - 1;
	}

	void cb_w(size_t v)
	{
		m_w += v;
		if (m_w == m_e)
			m_w = m_b;
	}
private:
	Cvirtual_binary m_d;
	char* m_b;
	const char* m_r;
	char* m_w;
	const char* m_e;
};

#endif // !defined(AFX_RING_BUFFER_H__67856C4E_F9FE_4D73_8553_27AF76B4C5F2__INCLUDED_)
