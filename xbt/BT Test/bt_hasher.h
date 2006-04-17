#if !defined(AFX_BT_HASHER_H__27B10CD6_0586_4367_91D8_1C9BAEA2CAC5__INCLUDED_)
#define AFX_BT_HASHER_H__27B10CD6_0586_4367_91D8_1C9BAEA2CAC5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "bt_file.h"

class Cbt_hasher
{
public:
	bool run(Cbt_file&);
	Cbt_hasher(bool validate);
private:
	unsigned int m_i;
	unsigned int m_j;
	long long m_offset;
	Cbt_file::t_sub_files::iterator m_sub_file;
	const bool m_validate;
};

#endif // !defined(AFX_BT_HASHER_H__27B10CD6_0586_4367_91D8_1C9BAEA2CAC5__INCLUDED_)
