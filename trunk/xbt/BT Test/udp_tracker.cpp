// udp_tracker.cpp: implementation of the Cudp_tracker class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "udp_tracker.h"

#include "bt_misc.h"
#include "bt_strings.h"

#define for if (0) {} else for

#ifdef _DEBUG
#undef THIS_FILE
static char THIS_FILE[]=__FILE__;
#define new DEBUG_NEW
#endif

template <class T>
static T read(const char* r, const char* r_end)
{
	T v = 0;
	for (int i = 0; i < sizeof(T); i++)
		v = v << 8 | *reinterpret_cast<const unsigned char*>(r++);
	return v;
}

template <class T>
static char* write(char* w, T v)
{
	w += sizeof(T);
	for (int i = 0; i < sizeof(T); i++)
	{
		*--w = v & 0xff;
		v >>= 8;
	}
	return w + sizeof(T);
}

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cudp_tracker::Cudp_tracker()
{
	m_announce_interval = 1800;
	for (int i = 0; i < 8; i++)
		m_secret = m_secret << 8 ^ rand();
}

void Cudp_tracker::recv(Csocket& s)
{
	const int cb_b = 2 << 10;
	char b[cb_b];
	sockaddr_in a;
	socklen_t cb_a = sizeof(sockaddr_in);
	int r = s.recvfrom(b, cb_b, reinterpret_cast<sockaddr*>(&a), &cb_a);
	if (r == SOCKET_ERROR)
		return;
	if (r < uti_size)
		return;
	switch (read<__int32>(b + uti_action, b + r))
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
	write<__int32>(d + uto_action, uta_connect);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	write<__int64>(d + utoc_connection_id, connection_id(a));
	send(s, a, d, utoc_size);
}

void Cudp_tracker::send_announce(Csocket& s, sockaddr_in& a, const char* r, const char* r_end)
{
	if (read<__int64>(r + uti_connection_id, r_end) != connection_id(a))
		return;
	/*
	Ctracker_input ti;
	ti.m_downloaded = read<__int64>(r + utia_downloaded, r_end);
	ti.m_event = static_cast<Ctracker_input::t_event>(read<__int32>(r + utia_event, r_end));
	ti.m_info_hash.assign(r + utia_info_hash, 20);
	ti.m_ipa = read<__int32>(r + utia_ipa, r_end) && is_private_ipa(m_a.sin_addr.s_addr)
		? htonl(read<__int32>(r + utia_ipa, r_end))
		: m_a.sin_addr.s_addr;
	ti.m_left = read<__int64>(r + utia_left, r_end);
	ti.m_num_want = read<__int32>(r + utia_num_want, r_end);
	ti.m_peer_id.assign(r + utia_peer_id, 20);
	ti.m_port = htons(read<__int16>(r + utia_port, r_end));
	ti.m_uploaded = read<__int64>(r + utia_uploaded, r_end);
	*/
	t_file& file = m_files[string(r + utia_info_hash, 20)];
	int ipa = read<__int32>(r + utia_ipa, r_end) && is_private_ipa(a.sin_addr.s_addr)
		? htonl(read<__int32>(r + utia_ipa, r_end))
		: a.sin_addr.s_addr;
	t_peers::iterator i = file.peers.find(ipa);
	if (i != file.peers.end())
		(i->second.left ? file.leechers : file.seeders)--;
	if (read<__int32>(r + utia_event, r_end) == bti_stopped)
		file.peers.erase(ipa);
	else
	{
		t_peer& peer = file.peers[ipa];
		peer.left = read<__int64>(r + utia_left, r_end);
		peer.port = htons(read<__int16>(r + utia_port, r_end));
		(peer.left ? file.leechers : file.seeders)++;
		peer.mtime = time(NULL);
	}
	switch (read<__int32>(r + utia_event, r_end))
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
	write<__int32>(d + uto_action, uta_announce);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	write<__int32>(d + utoa_interval, m_announce_interval);
	write<__int32>(d + utoa_leechers, file.leechers);
	write<__int32>(d + utoa_seeders, file.seeders);
	char* w = d + utoa_size;

	typedef vector<t_peers::const_iterator> t_candidates;

	t_candidates candidates;
	for (t_peers::const_iterator i = file.peers.begin(); i != file.peers.end(); i++)
	{
		if (read<__int64>(r + utia_left, r_end) || i->second.left)
			candidates.push_back(i);
	}
	int c = read<__int32>(r + utia_num_want, r_end) < 0 ? 50 : min(read<__int32>(r + utia_num_want, r_end), 50);
	while (c--)
	{
		int i = rand() % candidates.size();
		write<__int32>(d + 0, ntohl(candidates[i]->first));
		write<__int16>(d + 4, ntohs(candidates[i]->second.port));
		w += 6;
		candidates[i] = candidates.back();
		candidates.pop_back();
	}
	send(s, a, d, w - d);
}

void Cudp_tracker::send_scrape(Csocket& s, sockaddr_in& a, const char* r, const char* r_end)
{
	if (read<__int64>(r + uti_connection_id, r_end) != connection_id(a))
		return;
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_scrape);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	char* w = d + utos_size;
	for (; r + 20 <= r_end && w + 12 <= d + cb_d; r += 20)
	{
		const t_files::const_iterator file = m_files.find(string(r, 20));
		if (file == m_files.end())
		{
			w = write<__int32>(w, file->second.seeders);
			w = write<__int32>(w, file->second.completed);
			w = write<__int32>(w, file->second.leechers);
		}
		else
		{
			w = write<__int32>(w, 0);
			w = write<__int32>(w, 0);
			w = write<__int32>(w, 0);
		}
	}
	send(s, a, d, w - d);
}

void Cudp_tracker::send_error(Csocket& s, sockaddr_in& a, const char* r, const char* r_end, const string& msg)
{
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_error);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	memcpy(d + utoe_size, msg.c_str(), msg.length());
	send(s, a, d, utoe_size + msg.length());
}

void Cudp_tracker::send(Csocket& s, sockaddr_in& a, const void* b, int cb_b)
{
	s.sendto(b, cb_b, reinterpret_cast<const sockaddr*>(&a), sizeof(sockaddr_in));
}
