#pragma once

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

	boost::condition_variable m_condition;
	boost::mutex m_mutex;
	t_reverse_map m_reverse_map;
	t_reverse_map_queue m_reverse_map_queue;
	bool m_run;
	boost::thread_group m_threads;
};
