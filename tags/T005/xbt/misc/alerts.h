// alerts.h: interface for the Calerts class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_ALERTS_H__FE59568B_F1B9_45F4_9148_369A1454BC33__INCLUDED_)
#define AFX_ALERTS_H__FE59568B_F1B9_45F4_9148_369A1454BC33__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <list>
#include "stream_writer.h"

class Calert
{
public:
	enum t_level
	{
		emerg,
		alert,
		crit,
		error,
		warn,
		notice,
		info,
		debug,  
	};

	int time() const
	{
		return m_time;
	}

	t_level level() const
	{
		return m_level;
	}

	const string& message() const
	{
		return m_message;
	}

	Calert(t_level level, const string& message)
	{
		m_time = ::time(NULL);
		m_level = level;
		m_message = message;
	}

	Calert(t_level level, const string& source, const string& message)
	{
		m_time = ::time(NULL);
		m_level = level;
		m_message = message;
		m_source = source;
	}

	Calert(t_level level, const sockaddr_in& source, const string& message)
	{
		m_time = ::time(NULL);
		m_level = level;
		m_message = message;
		m_source = inet_ntoa(source.sin_addr);
	}

	int pre_dump() const;
	void dump(Cstream_writer&) const;
private:
	int m_time;
	t_level m_level;
	string m_message;
	string m_source;
};

class Calerts: public list<Calert>
{
};

#endif // !defined(AFX_ALERTS_H__FE59568B_F1B9_45F4_9148_369A1454BC33__INCLUDED_)
