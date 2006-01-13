#include "stdafx.h"
#include "udp_tracker.h"

#include "bt_misc.h"
#include "bt_strings.h"
#include "stream_int.h"

#define for if (0) {} else for

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

Cudp_tracker::Cudp_tracker()
{
	m_announce_interval = 1800;
	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
	clean_up();
}

void Cudp_tracker::recv(Csocket& s)
{
	if (time(NULL) - m_clean_up_time > 60)
		clean_up();
	const int cb_b = 2 << 10;
	char b[cb_b];
	sockaddr_in a;
	while (1)
	{
		socklen_t cb_a = sizeof(sockaddr_in);
		int r = s.recvfrom(b, cb_b, reinterpret_cast<sockaddr*>(&a), &cb_a);
		if (r == SOCKET_ERROR)
			return;
		if (r < uti_size)
			continue;
		switch (read_int(4, b + uti_action, b + r))
		{
		case uta_connect:
			if (r >= utic_size)
				send_connect(s, a, b, b + r);
			break;
		case uta_announce:
			if (r >= utia_size)
				send_announce(s, a, b, b + r);
			break;
		case uta_scrape:
			if (r >= utis_size)
				send_scrape(s, a, b, b + r);
			break;
		}
	}
}

__int64 Cudp_tracker::connection_id(sockaddr_in& a) const
{
	const int cb_s = 8 + sizeof(int);
	char s[cb_s];
	*reinterpret_cast<__int64*>(s) = m_secret;
	*reinterpret_cast<int*>(s + 8) = a.sin_addr.s_addr;
	char d[20];
	Csha1(&s, cb_s).read(d);
	return *reinterpret_cast<__int64*>(d);
}

void Cudp_tracker::send_connect(Csocket& s, sockaddr_in& a, const char* r, const char* r_end)
{
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_connect);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r_end));
	write_int(8, d + utoc_connection_id, connection_id(a));
	send(s, a, d, utoc_size);
}

void Cudp_tracker::send_announce(Csocket& s, sockaddr_in& a, const char* r, const char* r_end)
{
	if (read_int(8, r + uti_connection_id, r_end) != connection_id(a))
		return;
	t_file& file = m_files[string(r + utia_info_hash, 20)];
	int ipa = read_int(4, r + utia_ipa, r_end) && is_private_ipa(a.sin_addr.s_addr)
		? htonl(read_int(4, r + utia_ipa, r_end))
		: a.sin_addr.s_addr;
	t_peers::iterator i = file.peers.find(ipa);
	if (i != file.peers.end())
		(i->second.left ? file.leechers : file.seeders)--;
	if (read_int(4, r + utia_event, r_end) == bti_stopped)
		file.peers.erase(ipa);
	else
	{
		t_peer& peer = file.peers[ipa];
		peer.left = read_int(8, r + utia_left, r_end);
		peer.port = htons(read_int(2, r + utia_port, r_end));
		(peer.left ? file.leechers : file.seeders)++;
		peer.mtime = time(NULL);
	}
	switch (read_int(4, r + utia_event, r_end))
	{
	case bti_completed:
		file.completed++;
		break;
	case bti_started:
		file.started++;
		break;
	case bti_stopped:
		file.stopped++;
		break;
	}
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_announce);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r_end));
	write_int(4, d + utoa_interval, m_announce_interval);
	write_int(4, d + utoa_leechers, file.leechers);
	write_int(4, d + utoa_seeders, file.seeders);
	char* w = d + utoa_size;

	typedef vector<t_peers::const_iterator> t_candidates;

	t_candidates candidates;
	for (t_peers::const_iterator i = file.peers.begin(); i != file.peers.end(); i++)
	{
		if (read_int(8, r + utia_left, r_end) || i->second.left)
			candidates.push_back(i);
	}
	int c = read_int(4, r + utia_num_want, r_end) < 0 ? 100 : min(read_int(4, r + utia_num_want, r_end), 200);
	if (candidates.size() > c)	
	{
		while (c--)
		{
			int i = rand() % candidates.size();
			w = write_int(4, w, ntohl(candidates[i]->first));
			w = write_int(2, w, ntohs(candidates[i]->second.port));
			candidates[i] = candidates.back();
			candidates.pop_back();
		}
	}
	else
	{
		for (t_candidates::const_iterator i = candidates.begin(); i != candidates.end(); i++)
		{
			w = write_int(4, w, ntohl((*i)->first));
			w = write_int(2, w, ntohs((*i)->second.port));
		}
	}
	send(s, a, d, w - d);
}

void Cudp_tracker::send_scrape(Csocket& s, sockaddr_in& a, const char* r, const char* r_end)
{
	if (read_int(8, r + uti_connection_id, r_end) != connection_id(a))
		return;
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_scrape);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r_end));
	char* w = d + utos_size;
	for (; r + 20 <= r_end && w + 12 <= d + cb_d; r += 20)
	{
		const t_files::const_iterator file = m_files.find(string(r, 20));
		if (file == m_files.end())
		{
			w = write_int(4, w, file->second.seeders);
			w = write_int(4, w, file->second.completed);
			w = write_int(4, w, file->second.leechers);
		}
		else
		{
			w = write_int(4, w, 0);
			w = write_int(4, w, 0);
			w = write_int(4, w, 0);
		}
	}
	send(s, a, d, w - d);
}

void Cudp_tracker::send_error(Csocket& s, sockaddr_in& a, const char* r, const char* r_end, const string& msg)
{
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_error);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r_end));
	memcpy(d + utoe_size, msg.c_str(), msg.length());
	send(s, a, d, utoe_size + msg.length());
}

void Cudp_tracker::send(Csocket& s, sockaddr_in& a, const void* b, int cb_b)
{
	s.sendto(b, cb_b, reinterpret_cast<const sockaddr*>(&a), sizeof(sockaddr_in));
}

void Cudp_tracker::t_file::clean_up(int t)
{
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (i->second.mtime < t)
		{
			(i->second.left ? leechers : seeders)--;
			peers.erase(i++);
		}
		else
			i++;
	}
}

void Cudp_tracker::clean_up()
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->second.clean_up(time(NULL) - static_cast<int>(1.5 * m_announce_interval));
	m_clean_up_time = time(NULL);
}
