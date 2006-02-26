#include "stdafx.h"
#include "dns_worker.h"

Cdns_worker::Cdns_worker()
{
	pthread_cond_init(&m_condition, NULL); 
	pthread_mutex_init(&m_mutex, NULL);
	m_run = true;
	pthread_create(&m_thread, NULL, Cdns_worker::run, this);
}

Cdns_worker::~Cdns_worker()
{
	m_run = false;
	pthread_cond_signal(&m_condition);
	pthread_join(m_thread, NULL);
	pthread_cond_destroy(&m_condition); 
	pthread_mutex_destroy(&m_mutex);
}

string Cdns_worker::get_host_by_addr(int v)
{
	pthread_mutex_lock(&m_mutex);
	t_reverse_map::const_iterator i = m_reverse_map.find(v);
	if (i != m_reverse_map.end())
	{
		string d = i->second.c_str();
		pthread_mutex_unlock(&m_mutex);
		return d;
	}
	m_reverse_map_queue.push_back(v);
	pthread_cond_signal(&m_condition);
	pthread_mutex_unlock(&m_mutex);
	return "";
}

void Cdns_worker::run()
{
	pthread_mutex_lock(&m_mutex);
	while (m_run)
	{
		int v;
		do
		{
			while (m_reverse_map_queue.empty() && m_run)
				pthread_cond_wait(&m_condition, &m_mutex);
			if (!m_run)
			{
				pthread_mutex_unlock(&m_mutex);
				return;
			}
			v = m_reverse_map_queue.front();
			m_reverse_map_queue.pop_front();
		}
		while (m_reverse_map.find(v) != m_reverse_map.end());
		pthread_mutex_unlock(&m_mutex);
		in_addr a;
		a.s_addr = v;
		HOSTENT* he = gethostbyaddr(reinterpret_cast<char*>(&a), sizeof(a), AF_INET);
		pthread_mutex_lock(&m_mutex);
		if (m_reverse_map.find(v) == m_reverse_map.end())
			m_reverse_map[v] = he ? he->h_name : "";
	}
	pthread_mutex_unlock(&m_mutex);
}

void* Cdns_worker::run(void* v)
{
	reinterpret_cast<Cdns_worker*>(v)->run();
	return NULL;
}
