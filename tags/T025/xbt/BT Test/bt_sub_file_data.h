#if !defined(AFX_BT_SUB_FILE_DATA_H__25D502E6_5E98_4F24_93F5_A0208D7639C9__INCLUDED_)
#define AFX_BT_SUB_FILE_DATA_H__25D502E6_5E98_4F24_93F5_A0208D7639C9__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_sub_file_data
{
public:
	Cbt_sub_file_data();

	long long m_left;
	long long m_offset;
	long long m_size;
	int m_priority;
	std::string m_merkle_hash;
	std::string m_name;
};

#endif // !defined(AFX_BT_SUB_FILE_DATA_H__25D502E6_5E98_4F24_93F5_A0208D7639C9__INCLUDED_)
