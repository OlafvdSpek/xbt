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
	m_d.write_start(merkle_tree_size(21 * v));
	memset(m_d.data_edit(), 0, m_d.size());
}

string Cmerkle_tree::get(int i) const
{
	assert(i >= 0 && i < m_size);
	return string(reinterpret_cast<const char*>(m_d + 21 * i + 1), 20);
}

void Cmerkle_tree::set(int i, const string& v)
{
	assert(i >= 0 && i < m_size);
	m_d.data_edit()[21 * i] = true;
	memcpy(m_d.data_edit() + 21 * i + 1, v.c_str(), 20);
}