#include "stdafx.h"
#include "block_list.h"

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
