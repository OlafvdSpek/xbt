// epoll.h: interface for the Cepoll class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_)
#define AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#ifdef EPOLL
#include <sys/epoll.h>
#else
enum
{
	EPOLLIN,
	EPOLLOUT,
	EPOLLPRI,
	EPOLLERR,
	EPOLLHUP,
	EPOLLET,
	EPOLLONESHOT,

	EPOLL_CTL_ADD,
	EPOLL_CTL_MOD,
	EPOLL_CTL_DEL,
};

typedef void epoll_event;
#endif

class Cepoll  
{
public:
	int create(int size);
	int ctl(int op, int fd, int events, void* p);
	int wait(epoll_event* events, int maxevents, int timeout);
	Cepoll();
	~Cepoll();
private:
	Cepoll(const Cepoll&);
	const Cepoll& operator=(const Cepoll&);

	int m_fd;
};

#endif // !defined(AFX_EPOLL_H__54CD3A68_E8A0_421F_991B_2A387A8893DC__INCLUDED_)
