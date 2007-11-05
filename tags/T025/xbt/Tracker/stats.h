#if !defined(AFX_STATS_H__6D5525CA_266C_4F62_B2DD_61A0CA34C290__INCLUDED_)
#define AFX_STATS_H__6D5525CA_266C_4F62_B2DD_61A0CA34C290__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <ctime>

class Cstats
{
public:
	Cstats();

	long long announced() const
	{
		return announced_http + announced_http_compact + announced_http_no_peer_id + announced_udp;
	}

	long long scraped() const
	{
		return scraped_http + scraped_udp;
	}

	long long announced_http;
	long long announced_http_compact;
	long long announced_http_no_peer_id;
	long long announced_udp;
	long long scraped_full;
	long long scraped_http;
	long long scraped_udp;
	time_t start_time;
};

#endif // !defined(AFX_STATS_H__6D5525CA_266C_4F62_B2DD_61A0CA34C290__INCLUDED_)
