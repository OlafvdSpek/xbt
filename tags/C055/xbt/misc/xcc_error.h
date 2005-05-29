// xcc_error.h: interface for the Cxcc_error class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_XCC_ERROR_H__EA1254C7_2222_11D5_B606_0000B4936994__INCLUDED_)
#define AFX_XCC_ERROR_H__EA1254C7_2222_11D5_B606_0000B4936994__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>

using namespace std;

class Cxcc_error  
{
public:
	Cxcc_error();
	Cxcc_error(const string& message);
	
	operator bool() const
	{
		return !m_message.empty();
	}

	const string& message() const
	{
		return m_message;
	}
private:
	string m_message;
};

#endif // !defined(AFX_XCC_ERROR_H__EA1254C7_2222_11D5_B606_0000B4936994__INCLUDED_)
