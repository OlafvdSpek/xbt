// block_list.cpp: implementation of the Cblock_list class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "block_list.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cblock_list& Cblock_list::load(const Cxif_key& v)
{
	clear();
	for (int i = 0; i < v.c_values(); i++)
		insert(v.get_value_int(i));
	return *this;
}

Cxif_key Cblock_list::save() const
{
	Cxif_key v;
	int j = 0;
	for (const_iterator i = begin(); i != end(); i++)
		v.set_value_int(j++, *i);
	return v;
}
