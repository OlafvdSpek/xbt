// alerts.cpp: implementation of the Calerts class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "alerts.h"

#include "bt_misc.h"

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Calert::Calert(t_level level, const sockaddr_in& source, const string& message)
{
	m_time = ::time(NULL);
	m_level = level;
	m_message = message;
	m_source = inet_ntoa(source.sin_addr);
	m_source += ':' + n(ntohs(source.sin_port));
}

int Calert::pre_dump() const
{
	return m_message.size() + m_source.size() + 16;
}

void Calert::dump(Cstream_writer& w) const
{
	w.write_int(4, m_time);
	w.write_int(4, m_level);
	w.write_string(m_message);
	w.write_string(m_source);
}
