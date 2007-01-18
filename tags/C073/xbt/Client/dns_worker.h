#if !defined(AFX_DNS_WORKER_H__D498D6AB_62D3_495E_9CFF_3E379D5D7B68__INCLUDED_)
#define AFX_DNS_WORKER_H__D498D6AB_62D3_495E_9CFF_3E379D5D7B68__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif

#include <boost/thread.hpp>
#include <queue>

class Cdns_worker  
{
public:
	std::string get_host_by_addr(int v);
	Cdns_worker();
	~Cdns_worker();
private:
	typedef std::map<int, std::string> t_reverse_map;
	typedef std::queue<int> t_reverse_map_queue;

	void run();

	boost::condition m_condition;
	boost::mutex m_mutex;
	t_reverse_map m_reverse_map;
	t_reverse_map_queue m_reverse_map_queue;
	bool m_run;
	boost::thread_group m_threads;
};

#endif
