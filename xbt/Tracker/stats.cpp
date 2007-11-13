#include "stdafx.h"
#include "stats.h"

#include <ctime>

Cstats::Cstats()
{
	announced_http = 0;
	announced_udp = 0;
	scraped_full = 0;
	scraped_http = 0;
	scraped_udp = 0;
	start_time = time(NULL);
}
