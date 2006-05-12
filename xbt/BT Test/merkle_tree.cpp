#include "stdafx.h"
#include "merkle_tree.h"

std::string internal_hash(const_memory_range a, const_memory_range b)
{
	assert(a.size() == 20);
	assert(b.size() == 20);
	char d[41];
	*d = 1;
	memcpy(d + 1, a, 20);
	memcpy(d + 21, b, 20);
	return Csha1(const_memory_range(d, 41)).read();
}

Cmerkle_tree::Cmerkle_tree()
{
	m_size = 0;
}

void Cmerkle_tree::resize(int v)
{
	m_size = v;
	m_d.write_start(21 * merkle_tree_size(v));
	memset(m_d.data_edit(), 0, m_d.size());
}

void Cmerkle_tree::invalidate()
{
	memset(m_d.data_edit(), 0, m_d.size());
}

std::string Cmerkle_tree::get0(int i) const
{
	assert(d(i));
	return std::string(d(i) + 1, 20);
}

std::string Cmerkle_tree::get(int i) const
{
	assert(i >= 0);
	assert(i < m_size);
	return get0(i);
}

std::string Cmerkle_tree::get(int i, int c) const
{
	assert(i >= 0);
	assert(i < m_size);
	std::string v;
	int a = 0;
	int b = m_size;
	while (b - a >= 2 && c--)
	{
		int j = i ^ 1;
		if (a + j < b)
			v += get0(a + j);
		int c = a;
		a = b;
		b += b - c + 1 >> 1;
		i >>= 1;
	}
	return v;
}

bool Cmerkle_tree::test(int i, const_memory_range v, const_memory_range w)
{
	assert(i >= 0);
	assert(i < m_size);
	int a = 0;
	int b = m_size;
	unsigned int z = 0;
	const_memory_range h = v;
	while (1)
	{
		if (*d(a + i))
			return h.string() == get0(a + i);
		if (b - a < 2 || z + 20 > w.size())
			return false;
		int j = i ^ 1;
		if (a + j < b)
		{
			h = i < j ? internal_hash(h, w.sub_range(z, 20)) : internal_hash(w.sub_range(z, 20), h);
			z += 20;
		}
		int c = a;
		a = b;
		b += b - c + 1 >> 1;
		i >>= 1;
	}
}

bool Cmerkle_tree::has(int i) const
{
	assert(i >= 0);
	assert(i < m_size);
	return m_d[21 * i];
}

void Cmerkle_tree::set0(int i, const_memory_range v)
{
	assert(v.size() == 20);
	*d(i) = true;
	memcpy(d(i) + 1, v, 20);
}

void Cmerkle_tree::set(int i, const_memory_range v)
{
	assert(i >= 0);
	assert(i < m_size);
	set0(i, v);
	int a = 0;
	int b = m_size;
	while (b - a >= 2)
	{
		int j = i ^ 1;
		if (a + j < b && !*d(a + j))
			break;
		if (i > j)
			std::swap(i, j);
		int c = a;
		a = b;
		i >>= 1;
		if (*d(a + i))
			break;
		*d(a + i) = true;
		if (c + j < b)
			set0(a + i, internal_hash(get0(c + j - 1), get0(c + j)));
		else
			set0(a + i, get0(c + j - 1));
		b += b - c + 1 >> 1;
	}
}

void Cmerkle_tree::set(int i, const_memory_range v, const_memory_range w)
{
	assert(i >= 0);
	assert(i < m_size);
	int a = 0;
	int b = m_size;
	unsigned int z = 0;
	set0(a + i, v);
	while (b - a >= 2 && z + 20 <= w.size())
	{
		int j = i ^ 1;
		if (a + j < b)
		{
			if (*d(a + j))
				return;
			set0(a + j, w.sub_range(z, 20));
			z += 20;
		}
		int c = a;
		a = b;
		b += b - c + 1 >> 1;
		i >>= 1;
	}
}

bool Cmerkle_tree::test_and_set(int i, const_memory_range v, const_memory_range w)
{
	if (!test(i, v, w))
		return false;
	set(i, v, w);
	return true;
}

std::string Cmerkle_tree::root() const
{
	return get0(m_d.size() / 21 - 1);
}

void Cmerkle_tree::root(const_memory_range v)
{
	set0(m_d.size() / 21 - 1, v);
}

char* Cmerkle_tree::d(int i)
{
	assert(21 * i < m_d.size());
	return reinterpret_cast<char*>(m_d.data_edit()) + 21 * i;
}

const char* Cmerkle_tree::d(int i) const
{
	assert(21 * i < m_d.size());
	return reinterpret_cast<const char*>(m_d.data()) + 21 * i;
}

int Cmerkle_tree::load(const Cvirtual_binary& v)
{
	if (v.size() % 21)
		return 1;
	m_size = v.size() / 21;
	for (int i = 1; i < m_size; i <<= 1)
		m_size -= i;
	if (m_size)
		m_d = v;
	return 0;
}

Cvirtual_binary Cmerkle_tree::save() const
{
	return m_d;
}

std::string Cmerkle_tree::compute_root(const_memory_range r)
{
	typedef std::map<int, std::string> t_map;

	t_map map;
	char d[1025];
	for (; r.size(); r += 1024)
	{
		*d = 0;
		memcpy(d + 1, r, min(r.size(), 1024));
		std::string h = Csha1(const_memory_range(d, min(r.size(), 1024) + 1)).read();
		*d = 1;
		int i;
		for (i = 0; map.find(i) != map.end(); i++)
		{
			memcpy(d + 1, map.find(i)->second.c_str(), 20);
			memcpy(d + 21, h.c_str(), 20);
			h = Csha1(const_memory_range(d, 41)).read();
			map.erase(i);
		}
		map[i] = h;
	}
	*d = 1;
	while (map.size() > 1)
	{
		memcpy(d + 21, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		memcpy(d + 1, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		map[0] = Csha1(const_memory_range(d, 41)).read();
	}
	return map.empty() ? "" : map.begin()->second;
}

std::ostream& Cmerkle_tree::operator<<(std::ostream& os) const
{
	const char* r_end = reinterpret_cast<const char*>(m_d.end());
	for (const char* r = d(0); r < r_end; r += 21)
		os << (*r ? 1 : 0) << ' ' << hex_encode(std::string(r + 1, 20)) << std::endl;
	return os;
}

std::ostream& operator<<(std::ostream& os, const Cmerkle_tree& v)
{
	return v.operator<<(os);
}
