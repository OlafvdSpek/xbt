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
	static string compute_root(const void* s, const void* s_end);
	static string compute_root(const Cvirtual_binary&);
	void invalidate();
	string root() const;
	void root(const string&);
	int load(const Cvirtual_binary&);
	Cvirtual_binary save() const;
	ostream& operator<<(ostream& os) const;
	string get(int i) const;
	string get(int i, int c) const;
	bool has(int i) const;
	void resize(int);
	void set(int i, const string&);
	void set(int i, const string&, const string&);
	bool test(int i, const string&, const string&);
	bool test_and_set(int i, const string&, const string&);
	Cmerkle_tree();

	int size() const
	{
		return m_size;
	}
private:
	char* d(int);
	const char* d(int) const;
	string get0(int) const;
	void set0(int, const string&);

	Cvirtual_binary m_d;
	int m_size;
};

ostream& operator<<(ostream&, const Cmerkle_tree&);

#endif // !defined(AFX_MERKLE_TREE_H__3C33F874_B781_4D36_B51F_3251B71B96A5__INCLUDED_)
