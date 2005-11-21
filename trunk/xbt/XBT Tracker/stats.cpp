#include "stdafx.h"
#include "stats.h"

Cstats::Cstats()
{
	announced_http = 0;
	announced_http_compact = 0;
	announced_http_no_peer_id = 0;
	announced_udp = 0;
	scraped_http = 0;
	scraped_udp = 0;
	start_time = time(NULL);
}
