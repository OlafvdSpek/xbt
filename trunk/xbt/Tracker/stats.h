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

	int announced() const
	{
		return announced_http + announced_http_compact + announced_http_no_peer_id + announced_udp;
	}

	int scraped() const
	{
		return scraped_http + scraped_udp;
	}

	int announced_http;
	int announced_http_compact;
	int announced_http_no_peer_id;
	int announced_udp;
	int scraped_full;
	int scraped_http;
	int scraped_udp;
	time_t start_time;
};

#endif // !defined(AFX_STATS_H__6D5525CA_266C_4F62_B2DD_61A0CA34C290__INCLUDED_)
