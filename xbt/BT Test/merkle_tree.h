// merkle_tree.h: interface for the Cmerkle_tree class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_MERKLE_TREE_H__3C33F874_B781_4D36_B51F_3251B71B96A5__INCLUDED_)
#define AFX_MERKLE_TREE_H__3C33F874_B781_4D36_B51F_3251B71B96A5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "virtual_binary.h"

class Cmerkle_tree
{
public:
	string get(int i) const;
	void resize(int);
	void set(int i, const string&);
	Cmerkle_tree();
private:
	Cvirtual_binary m_d;
	int m_size;
};

#endif // !defined(AFX_MERKLE_TREE_H__3C33F874_B781_4D36_B51F_3251B71B96A5__INCLUDED_)
