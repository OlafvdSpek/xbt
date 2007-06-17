#if !defined(AFX_DATA_COUNTER_H__F3C125B1_D612_41A0_B2D2_AB9240FDC72E__INCLUDED_)
#define AFX_DATA_COUNTER_H__F3C125B1_D612_41A0_B2D2_AB9240FDC72E__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cdata_counter
{
public:
	int rate(time_t) const;
	void add(int, time_t);
	Cdata_counter();
private:
	void update_rate(time_t);

	int m_got;
	int m_rate;
	time_t m_start_time;
};

class Cdata_counter2
{
public:
	Cdata_counter2()
	{
		m_d.resize(11);
		m_w = 0;
		m_time = 0;
	}

	void add(int s, time_t t)
	{
		if (t < m_time || t - m_time > m_d.size() - 1)
		{
			for (int i = 0; i < m_d.size(); i++)
				m_d[i] = 0;
			m_w = 0;
			m_time = t;
		}
		while (t != m_time)
		{
			m_w++;
			if (m_w == m_d.size())
				m_w = 0;
			m_time++;
			m_d[m_w] = 0;
		}
		m_d[m_w] += s;
	}

	int rate(time_t t) const
	{
		const_cast<Cdata_counter2*>(this)->add(0, t);
		int z = 0;
		int y = m_w + 1;
		for (int i = 0; i < m_d.size() - 1; i++)
		{
			if (y == m_d.size())
				y = 0;
			z += m_d[y++];
		}
		return z / (m_d.size() - 1);
	}
private:
	std::vector<int> m_d;
	int m_w;
	time_t m_time;
};

#endif // !defined(AFX_DATA_COUNTER_H__F3C125B1_D612_41A0_B2D2_AB9240FDC72E__INCLUDED_)
