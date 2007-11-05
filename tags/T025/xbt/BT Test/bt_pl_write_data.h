#if !defined(AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_)
#define AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "const_memory_range.h"

class Cbt_pl_write_data
{
public:
	Cbt_pl_write_data()
	{
	}

	Cbt_pl_write_data(const Cvirtual_binary&, bool user_data);

	const_memory_range m_s;
	Cvirtual_binary m_vb;
	bool m_user_data;
};

#endif // !defined(AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_)
