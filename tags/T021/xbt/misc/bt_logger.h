#if !defined(AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_)
#define AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_logger
{
public:
	void choke(const string& file, const string& peer, bool remote, bool v);
	void invalid(const string& file, bool remote, int piece);
	void piece(const string& file, const string& peer, bool remote, int piece, int offset, int size);
	void request(const string& file, const string& peer, bool remote, int piece, int offset, int size);
	void open(const string&);
	int string_id(const string&);
	void valid(const string& file, bool remote, int piece);
	Cbt_logger();
	~Cbt_logger();
private:
	typedef map<string, int> t_strings;

	ofstream m_os;
	int m_start_time;
	t_strings m_strings;
};

#endif // !defined(AFX_BT_LOGGER_H__ED1D62AA_A8C0_4E89_81E0_3D9989BE6E37__INCLUDED_)
