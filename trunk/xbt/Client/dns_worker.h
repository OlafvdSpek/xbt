#if !defined(AFX_DNS_WORKER_H__D498D6AB_62D3_495E_9CFF_3E379D5D7B68__INCLUDED_)
#define AFX_DNS_WORKER_H__D498D6AB_62D3_495E_9CFF_3E379D5D7B68__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif

#include <boost/thread.hpp>

using namespace boost;
using namespace std;

class Cdns_worker  
{
public:
	string get_host_by_addr(int v);
	Cdns_worker();
	~Cdns_worker();
private:
	typedef map<int, string> t_reverse_map;
	typedef list<int> t_reverse_map_queue;

	void run();

	condition m_condition;
	mutex m_mutex;
	t_reverse_map m_reverse_map;
	t_reverse_map_queue m_reverse_map_queue;
	bool m_run;
	thread_group m_threads;
};

#endif
