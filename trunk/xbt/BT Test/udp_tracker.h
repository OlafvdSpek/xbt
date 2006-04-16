#if !defined(AFX_UDP_TRACKER_H__12DAD37E_1E30_4E4C_832E_44105A312226__INCLUDED_)
#define AFX_UDP_TRACKER_H__12DAD37E_1E30_4E4C_832E_44105A312226__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "socket.h"

class Cudp_tracker
{
public:
	void recv(Csocket& s);
	Cudp_tracker();
private:
	struct t_peer
	{
		t_peer()
		{
			mtime = 0;
		}

		bool left;
		time_t mtime;
		int port;
	};

	typedef map<int, t_peer> t_peers;

	struct t_file
	{
		void clean_up(time_t t);
		string debug() const;
		// void select_peers(const Ctracker_input&, Cannounce_output&) const;
		// Cbvalue scrape() const;

		t_file()
		{
			announced_udp = 0;
			completed = 0;
			leechers = 0;
			scraped_udp = 0;
			seeders = 0;
			started = 0;
			stopped = 0;
		}

		t_peers peers;
		int announced_udp;
		int completed;
		int leechers;
		int scraped_udp;
		int seeders;
		int started;
		int stopped;
	};

	typedef map<string, t_file> t_files;

	void clean_up();
	long long connection_id(sockaddr_in&) const;
	void send(Csocket&, sockaddr_in&, const void* d, int cb_d);
	void send_announce(Csocket&, sockaddr_in&, const char* r, const char* r_end);
	void send_connect(Csocket&, sockaddr_in&, const char* r, const char* r_end);
	void send_scrape(Csocket&, sockaddr_in&, const char* r, const char* r_end);
	void send_error(Csocket&, sockaddr_in&, const char* r, const char* r_end, const string& msg);

	int m_announce_interval;
	time_t m_clean_up_time;
	t_files m_files;
	long long m_secret;
};

#endif // !defined(AFX_UDP_TRACKER_H__12DAD37E_1E30_4E4C_832E_44105A312226__INCLUDED_)
