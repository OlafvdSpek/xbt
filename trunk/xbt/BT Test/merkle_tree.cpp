// merkle_tree.cpp: implementation of the Cmerkle_tree class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "merkle_tree.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

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

string Cmerkle_tree::get(int i) const
{
	assert(i >= 0);
	assert(i < m_size);
	return string(d(i) + 1, 20);
}

bool Cmerkle_tree::has(int i) const
{
	assert(i >= 0);
	assert(i < m_size);
	return m_d[21 * i];
}

void Cmerkle_tree::set(int i, const string& v)
{
	assert(i >= 0);
	assert(i < m_size);
	assert(v.size() == 20);
	*d(i) = true;
	memcpy(d(i) + 1, v.c_str(), 20);
	int a = 0;
	int b = m_size;
	while (b - a >= 2)
	{
		int j = i ^ 1;
		if (j < b && !*d(a + j))
			break;
		if (i > j)
			swap(i, j);
		int c = a;
		a = b;
		i >>= 1;
		if (*d(a + i))
			break;
		*d(a + i) = true;
		if (j < b)
		{
			char s[41];
			*s = 1;
			memcpy(s + 1, d(c + j - 1) + 1, 20);
			memcpy(s + 21, d(c + j) + 1, 20);
			Csha1(s, 41).read(d(a + i) + 1);
		}
		else
			memcpy(d(a + i) + 1, d(c + j - 1) + 1, 20);
		b += b - c + 1 >> 1;
	}
}

string Cmerkle_tree::root() const
{
	return string(reinterpret_cast<const char*>(m_d.data_end()) - 20, 20);
}

void Cmerkle_tree::root(const string& v)
{
	assert(v.size() == 20);
	char* w = d(m_d.size() / 21 - 1);
	*w = true;
	memcpy(w + 1, v.c_str(), 20);
}

char* Cmerkle_tree::d(int i)
{
	return reinterpret_cast<char*>(m_d.data_edit()) + 21 * i;
}

const char* Cmerkle_tree::d(int i) const
{
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

string Cmerkle_tree::compute_root(const byte* s, const byte* s_end)
{
	typedef map<int, string> t_map;
	
	t_map map;
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
