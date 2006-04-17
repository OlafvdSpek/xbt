#include "stdafx.h"
#include "merkle_tree.h"

string internal_hash(const string& a, const string& b)
{
	assert(a.size() == 20);
	assert(b.size() == 20);
	char d[41];
	*d = 1;
	memcpy(d + 1, a.c_str(), 20);
	memcpy(d + 21, b.c_str(), 20);
	return Csha1(d, 41).read();
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

string Cmerkle_tree::get0(int i) const
{
	assert(d(i));
	return string(d(i) + 1, 20);
}

string Cmerkle_tree::get(int i) const
{
	assert(i >= 0);
	assert(i < m_size);
	return get0(i);
}

string Cmerkle_tree::get(int i, int c) const
{
	assert(i >= 0);
	assert(i < m_size);
	string v;
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

bool Cmerkle_tree::test(int i, const string& v, const string& w)
{
	assert(i >= 0);
	assert(i < m_size);
	int a = 0;
	int b = m_size;
	unsigned int z = 0;
	string h = v;
	while (1)
	{
		if (*d(a + i))
			return h == get0(a + i);
		if (b - a < 2 || z + 20 > w.size())
			return false;
		int j = i ^ 1;
		if (a + j < b)
		{
			h = i < j ? internal_hash(h, w.substr(z, 20)) : internal_hash(w.substr(z, 20), h);
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

void Cmerkle_tree::set0(int i, const string& v)
{
	assert(v.size() == 20);
	*d(i) = true;
	memcpy(d(i) + 1, v.c_str(), 20);
}

void Cmerkle_tree::set(int i, const string& v)
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
			swap(i, j);
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

void Cmerkle_tree::set(int i, const string& v, const string& w)
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
			set0(a + j, w.substr(z, 20));
			z += 20;
		}
		int c = a;
		a = b;
		b += b - c + 1 >> 1;
		i >>= 1;
	}
}

bool Cmerkle_tree::test_and_set(int i, const string& v, const string& w)
{
	if (!test(i, v, w))
		return false;
	set(i, v, w);
	return true;
}

string Cmerkle_tree::root() const
{
	return get0(m_d.size() / 21 - 1);
}

void Cmerkle_tree::root(const string& v)
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

string Cmerkle_tree::compute_root(const void* s0, const void* s_end0)
{
	typedef map<int, string> t_map;

	t_map map;
	const byte* s = reinterpret_cast<const byte*>(s0);
	const byte* s_end = reinterpret_cast<const byte*>(s_end0);
	char d[1025];
	for (const byte* r = s; r < s_end; r += 1024)
	{
		*d = 0;
		memcpy(d + 1, r, min(s_end - r, 1024));
		string h = Csha1(d, min(s_end - r, 1024) + 1).read();
		*d = 1;
		int i;
		for (i = 0; map.find(i) != map.end(); i++)
		{
			memcpy(d + 1, map.find(i)->second.c_str(), 20);
			memcpy(d + 21, h.c_str(), 20);
			h = Csha1(d, 41).read();
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
		map[0] = Csha1(d, 41).read();
	}
	return map.empty() ? "" : map.begin()->second;
}

string Cmerkle_tree::compute_root(const Cvirtual_binary& s)
{
	return compute_root(s, s.data_end());
}

ostream& Cmerkle_tree::operator<<(ostream& os) const
{
	const char* r_end = reinterpret_cast<const char*>(m_d.data_end());
	for (const char* r = d(0); r < r_end; r += 21)
		os << (*r ? 1 : 0) << ' ' << hex_encode(string(r + 1, 20)) << endl;
	return os;
}

ostream& operator<<(ostream& os, const Cmerkle_tree& v)
{
	return v.operator<<(os);
}
