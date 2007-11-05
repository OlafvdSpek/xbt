#if !defined(AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_)
#define AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <boost/utility.hpp>

#ifdef EPOLL
#include <sys/epoll.h>
#else
enum
{
	EPOLLIN = 1,
	EPOLLOUT = 2,
	EPOLLPRI = 4,
	EPOLLERR = 8,
	EPOLLHUP = 0x10,
	EPOLLET = 0x20,
	EPOLLONESHOT = 0x40,
};

enum
{
	EPOLL_CTL_ADD = 1,
	EPOLL_CTL_MOD = 2,
	EPOLL_CTL_DEL = 4,
};

typedef void epoll_event;
#endif

class Cepoll: boost::noncopyable
{
public:
	int create(int size);
	int ctl(int op, int fd, int events, void* p);
	int wait(epoll_event* events, int maxevents, int timeout);
	Cepoll();
	~Cepoll();
private:
	int m_fd;
};

#endif // !defined(AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_)
