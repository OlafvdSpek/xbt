#if !defined(AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_)
#define AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <fstream>

class Cbt_logger
{
public:
	void choke(const std::string& file, const std::string& peer, bool remote, bool v);
	void invalid(const std::string& file, bool remote, int piece);
	void piece(const std::string& file, const std::string& peer, bool remote, int piece, int offset, int size);
	void request(const std::string& file, const std::string& peer, bool remote, int piece, int offset, int size);
	void open(const std::string&);
	int string_id(const std::string&);
	void valid(const std::string& file, bool remote, int piece);
	Cbt_logger();
	~Cbt_logger();
private:
	typedef std::map<std::string, int> t_strings;

	std::ofstream m_os;
	time_t m_start_time;
	t_strings m_strings;
};

#endif // !defined(AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_)
