#if !defined(AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_)
#define AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "virtual_binary.h"

namespace xcc_z
{
	Cvirtual_binary gunzip(const void* s, int cb_s);
	Cvirtual_binary gunzip(const std::string& v);
	Cvirtual_binary gunzip(const Cvirtual_binary&);
	Cvirtual_binary gzip(const void* s, int cb_s);
	Cvirtual_binary gzip(const std::string& v);
	Cvirtual_binary gzip(const Cvirtual_binary&);
	void gzip_out(const void* s, int cb_s);
	void gzip_out(const std::string& v);
	void gzip_out(const Cvirtual_binary&);
}

#endif // !defined(AFX_XCC_Z_H__63B3CD06_15B5_11D6_B606_C89000A7846E__INCLUDED_)
