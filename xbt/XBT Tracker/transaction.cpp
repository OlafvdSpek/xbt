// transaction.cpp: implementation of the Ctransaction class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "transaction.h"

#include "bt_misc.h"
#include "bt_strings.h"
#include "server.h"
#include "sha1.h"

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

class Cannounce_output_udp: public Cserver::Cannounce_output
{
public:
	void peer(int h, const Cserver::t_peer& peer)
	{
		memcpy(m_w, &h, 4);
		m_w += 4;
		memcpy(m_w, &peer.port, 2);
		m_w += 2;
	}

	char* w() const
	{
		return m_w;
	}

	void w(char* v)
	{
		m_w = v;
	}
private:
	char* m_w;
};

Ctransaction::Ctransaction(Cserver& server, const Csocket& s):
	m_server(server)
{
	m_s = s;
}

const Cserver::t_user* Ctransaction::authenticate(const void* s0, const char* s_end) const
{
	const char* s = reinterpret_cast<const char*>(s0);
	if (s_end - s < 16)
		return NULL;
	string name(s_end - 16, 8);
	int i = name.find('\0');
	const Cserver::t_user* user = m_server.find_user(i == string::npos ? name : name.substr(0, i));
	if (!user)
		return NULL;
	Csha1 sha1;
	sha1.write(s, s_end - s - 8);
	sha1.write(user->pass.c_str(), user->pass.size());
	unsigned char hash[20];
	sha1.read(hash);
	return memcmp(s_end - 8, hash, 8) ? NULL : user;
}

__int64 Ctransaction::connection_id() const
{
	const int cb_s = 8 + sizeof(int);
	char s[cb_s];
	*reinterpret_cast<__int64*>(s) = m_server.secret();
	*reinterpret_cast<int*>(s + 8) = m_a.sin_addr.s_addr;
	char d[20];
	Csha1(&s, cb_s).read(d);
	return *reinterpret_cast<__int64*>(d);
}

void Ctransaction::recv()
{
	const int cb_b = 2 << 10;
	char b[cb_b];
	socklen_t cb_a = sizeof(sockaddr_in);
	int r = m_s.recvfrom(b, cb_b, reinterpret_cast<sockaddr*>(&m_a), &cb_a);
	if (r == SOCKET_ERROR)
	{
		cerr << "recv failed: " << WSAGetLastError() << endl;
		return;
	}
	if (r < uti_size)
		return;
	switch (read<__int32>(b + uti_action, b + r))
	{
	case uta_connect:
		if (r >= utic_size)
			send_connect(b, b + r);
		break;
	case uta_announce:
		if (r >= utia_size)
			send_announce(b, b + r);
		break;
	case uta_scrape:
		if (r >= utis_size)
			send_scrape(b, b + r);
		break;
	}
}

void Ctransaction::send_connect(const char* r, const char* r_end)
{
	if (!m_server.anonymous_connect() && !authenticate(r, r_end))
		return;
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_connect);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	write<__int64>(d + utoc_connection_id, connection_id());
	send(d, utoc_size);
}

void Ctransaction::send_announce(const char* r, const char* r_end)
{
	if (read<__int64>(r + uti_connection_id, r_end) != connection_id())
		return;
	const Cserver::t_user* user = authenticate(r, r_end);
	if (!m_server.anonymous_announce() && !user)
	{
		send_error(r, r_end, "access denied");
		return;
	}
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
	m_server.insert_peer(ti, ti.m_ipa == m_a.sin_addr.s_addr, true, user ? user->uid : 0);
	const Cserver::t_file* file = m_server.file(ti.m_info_hash);
	if (!file)
	{
		send_error(r, r_end, bts_unregistered_torrent);
		return;
	}
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_announce);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	write<__int32>(d + utoa_interval, m_server.announce_interval());
	write<__int32>(d + utoa_leechers, file->leechers);
	write<__int32>(d + utoa_seeders, file->seeders);
	Cannounce_output_udp o;
	o.w(d + utoa_size);
	file->select_peers(ti, o);
	send(d, o.w() - d);
}

void Ctransaction::send_scrape(const char* r, const char* r_end)
{
	if (read<__int64>(r + uti_connection_id, r_end) != connection_id())
		return;
	if (!m_server.anonymous_scrape() && !authenticate(r, r_end))
	{
		send_error(r, r_end, "access denied");
		return;
	}
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_scrape);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	char* w = d + utos_size;
	for (; r + 20 <= r_end && w + 12 <= d + cb_d; r += 20)
	{
		const Cserver::t_file* file = m_server.file(string(r, 20));
		if (file)
		{
			w = write<__int32>(w, file->seeders);
			w = write<__int32>(w, file->completed);
			w = write<__int32>(w, file->leechers);
		}
		else
		{
			w = write<__int32>(w, 0);
			w = write<__int32>(w, 0);
			w = write<__int32>(w, 0);
		}
	}
	send(d, w - d);
}

void Ctransaction::send_error(const char* r, const char* r_end, const string& msg)
{
	const int cb_d = 2 << 10;
	char d[cb_d];
	write<__int32>(d + uto_action, uta_error);
	write<__int32>(d + uto_transaction_id, read<__int32>(r + uti_transaction_id, r_end));
	memcpy(d + utoe_size, msg.c_str(), msg.length());
	send(d, utoe_size + msg.length());
}

void Ctransaction::send(const void* b, int cb_b)
{
	if (m_s.sendto(b, cb_b, reinterpret_cast<const sockaddr*>(&m_a), sizeof(sockaddr_in)) != cb_b)
		cerr << "send failed: " << WSAGetLastError() << endl;
}
