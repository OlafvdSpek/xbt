// bt_logger.cpp: implementation of the Cbt_logger class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_logger.h"

#include "bt_misc.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_logger::Cbt_logger()
{
	m_start_time = time(NULL);
}

Cbt_logger::~Cbt_logger()
{
}

void Cbt_logger::open(const string& v)
{
	m_os.open(v.c_str());
}

int Cbt_logger::string_id(const string& v)
{
	t_strings::const_iterator i = m_strings.find(v);
	if (i != m_strings.end())
		return i->second;
	m_strings[v] = m_strings.size() + 1;
	return m_strings.find(v)->second;
}

void Cbt_logger::choke(const string& file, const string& peer, bool remote, bool v)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << (v ? "\tc\t" : "\tuc\t") << endl;
}

void Cbt_logger::invalid(const string& file, bool remote, int piece)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << "\t-\t" << (remote ? 'r' : 'l') << "\tiv\t" << piece << endl;
}

void Cbt_logger::piece(const string& file, const string& peer, bool remote, int piece, int offset, int size)
{
	if (offset & 0x7fff)
		m_os << "unaligned offset: " << offset << endl;
	offset >>= 15;
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << "\tp\t" << piece << '\t' << offset << '\t' << size << endl;
}

void Cbt_logger::request(const string& file, const string& peer, bool remote, int piece, int offset, int size)
{
	if (offset & 0x7fff)
		m_os << "unaligned offset: " << offset << endl;
	offset >>= 15;
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << "\tr\t" << piece << '\t' << offset << '\t' << size << endl;
}

void Cbt_logger::valid(const string& file, bool remote, int piece)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << "\t-\t" << (remote ? 'r' : 'l') << "\tv\t" << piece << endl;
}