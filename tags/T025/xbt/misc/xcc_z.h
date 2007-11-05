#if !defined(AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_)
#define AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "virtual_binary.h"

namespace xcc_z
{
	Cvirtual_binary gunzip(const_memory_range);
	Cvirtual_binary gzip(const_memory_range);
	void gzip_out(const_memory_range);
}

#endif // !defined(AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_)
