// bt_pl_write_data.h: interface for the Cbt_pl_write_data class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_)
#define AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_pl_write_data  
{
public:
	Cbt_pl_write_data();
	Cbt_pl_write_data(const Cvirtual_binary&);
	Cbt_pl_write_data(const char* s, int cb_s);

	const char* m_s;
	const char* m_s_end;
	const char* m_r;
	Cvirtual_binary m_vb;
};

#endif // !defined(AFX_BT_PL_WRITE_DATA_H__112E7038_6C60_48B6_8244_BF5D79172F3A__INCLUDED_)
