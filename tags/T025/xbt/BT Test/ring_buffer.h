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
	void size(size_t);
	void write(const_memory_range);

	size_t size() const
	{
		return m_d ? m_d.size() - 1 : 0;
	}

	const_memory_range r() const
	{
		return const_memory_range(m_r, m_r <= m_w ? m_w : m_e);
	}

	const byte* r_end() const
	{
		return m_r <= m_w ? m_w : m_e;
	}

	void r(const byte* r)
	{
		m_r = const_cast<byte*>(r);
	}

	size_t cb_r() const
	{
		// return m_r <= m_w ? m_w - m_r : m_e - m_r;
		return r().size();
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
		if (m_r == m_w)
			m_r = m_w = m_b;
	}

	void w(byte* w)
	{
		m_w = w;
	}

	memory_range w()
	{
		return memory_range(m_w, m_r <= m_w ? (m_b == m_r ? m_e - 1 : m_e) : m_r - 1);
	}

	size_t cb_w()
	{
		// return m_r <= m_w ? (m_b == m_r ? m_e - m_w - 1 : m_e - m_w) : m_r - m_w - 1;
		return m_d ? w().size() : 0;
	}

	size_t cb_write() const
	{
		return m_d ? m_r <= m_w ? m_e - m_w + m_r - m_b - 1 : m_r - m_w - 1 : 0;
	}

	void cb_w(size_t v)
	{
		m_w += v;
		if (m_w == m_e)
			m_w = m_b;
	}
private:
	Cvirtual_binary m_d;
	byte* m_b;
	byte* m_r;
	byte* m_w;
	byte* m_e;
};

#endif // !defined(AFX_RING_BUFFER_H__67856C4E_F9FE_4D73_8553_27AF76B4C5F2__INCLUDED_)
