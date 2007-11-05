#include "stdafx.h"
#include "dns_worker.h"

#include <boost/bind.hpp>

Cdns_worker::Cdns_worker()
{
	m_run = true;
	m_threads.create_thread(boost::bind(&Cdns_worker::run, this));
}

Cdns_worker::~Cdns_worker()
{
	m_run = false;
	m_condition.notify_all();
	m_threads.join_all();
}

std::string Cdns_worker::get_host_by_addr(int v)
{
	boost::mutex::scoped_lock l(m_mutex);
	t_reverse_map::const_iterator i = m_reverse_map.find(v);
	if (i != m_reverse_map.end())
	{
		std::string d = i->second.c_str();
		return d;
	}
	m_reverse_map_queue.push(v);
	m_condition.notify_one();
	return "";
}

void Cdns_worker::run()
{
	boost::mutex::scoped_lock l(m_mutex);
	while (m_run)
	{
		int v;
		do
		{
			while (m_reverse_map_queue.empty() && m_run)
				m_condition.wait(l);
			if (!m_run)
				return;
			v = m_reverse_map_queue.front();
			m_reverse_map_queue.pop();
		}
		while (m_reverse_map.find(v) != m_reverse_map.end());
		l.unlock();
		in_addr a;
		a.s_addr = v;
		HOSTENT* he = gethostbyaddr(reinterpret_cast<char*>(&a), sizeof(a), AF_INET);
		l.lock();
		if (m_reverse_map.find(v) == m_reverse_map.end())
			m_reverse_map[v] = he ? he->h_name : "";
	}
}
