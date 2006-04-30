#include "stdafx.h"
#include "bt_logger.h"

#include <ctime>
#include "bt_misc.h"

Cbt_logger::Cbt_logger()
{
	m_start_time = time(NULL);
}

Cbt_logger::~Cbt_logger()
{
}

void Cbt_logger::open(const std::string& v)
{
	m_os.open(v.c_str());
}

int Cbt_logger::string_id(const std::string& v)
{
	t_strings::const_iterator i = m_strings.find(v);
	if (i != m_strings.end())
		return i->second;
	m_strings[v] = m_strings.size() + 1;
	return m_strings.find(v)->second;
}

void Cbt_logger::choke(const std::string& file, const std::string& peer, bool remote, bool v)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << (v ? "\tc\t" : "\tuc\t") << std::endl;
}

void Cbt_logger::invalid(const std::string& file, bool remote, int piece)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << "\t-\t" << (remote ? 'r' : 'l') << "\tiv\t" << piece << std::endl;
}

void Cbt_logger::piece(const std::string& file, const std::string& peer, bool remote, int piece, int offset, int size)
{
	if (offset & 0x7fff)
		m_os << "unaligned offset: " << offset << std::endl;
	offset >>= 15;
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << "\tp\t" << piece << '\t' << offset << '\t' << size << std::endl;
}

void Cbt_logger::request(const std::string& file, const std::string& peer, bool remote, int piece, int offset, int size)
{
	if (offset & 0x7fff)
		m_os << "unaligned offset: " << offset << std::endl;
	offset >>= 15;
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << '\t' << string_id(peer) << '\t' << (remote ? 'r' : 'l') << "\tr\t" << piece << '\t' << offset << '\t' << size << std::endl;
}

void Cbt_logger::valid(const std::string& file, bool remote, int piece)
{
	m_os << time(NULL) - m_start_time << '\t' << string_id(file) << "\t-\t" << (remote ? 'r' : 'l') << "\tv\t" << piece << std::endl;
}
