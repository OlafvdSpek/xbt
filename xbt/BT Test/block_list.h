#if !defined(AFX_BLOCK_LIST_H__2DB02634_8686_4627_BC2C_113884ADD499__INCLUDED_)
#define AFX_BLOCK_LIST_H__2DB02634_8686_4627_BC2C_113884ADD499__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <set>
#include "xif_key.h"

using namespace std;

class Cblock_list: public set<int>
{
public:
	Cblock_list& load(const Cxif_key&);
	Cxif_key save() const;
};

#endif // !defined(AFX_BLOCK_LIST_H__2DB02634_8686_4627_BC2C_113884ADD499__INCLUDED_)
