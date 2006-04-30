#if !defined(AFX_TRACKER_INPUT_H__E4F9E6ED_97B7_4526_B310_82F149E42EA8__INCLUDED_)
#define AFX_TRACKER_INPUT_H__E4F9E6ED_97B7_4526_B310_82F149E42EA8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>

class Ctracker_input
{
public:
	void set(const std::string& name, const std::string& value);
	bool valid() const;
	Ctracker_input();

	enum t_event
	{
		e_none,
		e_completed,
		e_started,
		e_stopped,
	};

	t_event m_event;
	std::string m_info_hash;
	int m_ipa;
	std::string m_key;
	std::string m_peer_id;
	long long m_downloaded;
	long long m_left;
	int m_port;
	long long m_uploaded;
	int m_num_want;
	bool m_compact;
	bool m_no_peer_id;
};

#endif // !defined(AFX_TRACKER_INPUT_H__E4F9E6ED_97B7_4526_B310_82F149E42EA8__INCLUDED_)
