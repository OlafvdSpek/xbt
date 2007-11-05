#if !defined(AFX_BT_TRACKER_URL_H__0DBE3C91_1952_433F_A351_2EF87ABC1C7C__INCLUDED_)
#define AFX_BT_TRACKER_URL_H__0DBE3C91_1952_433F_A351_2EF87ABC1C7C__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_tracker_url
{
public:
	enum
	{
		tp_http,
		tp_udp,
		tp_unknown
	};
	
	void clear();
	bool valid() const;
	void write(const std::string&);
	Cbt_tracker_url(const std::string&);
	Cbt_tracker_url();

	int m_protocol;
	std::string m_host;
	int m_port;
	std::string m_path;
};

#endif // !defined(AFX_BT_TRACKER_URL_H__0DBE3C91_1952_433F_A351_2EF87ABC1C7C__INCLUDED_)
