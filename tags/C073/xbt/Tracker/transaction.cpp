#include "stdafx.h"
#include "transaction.h"

#include <iostream>
#include "bt_misc.h"
#include "bt_strings.h"
#include "sha1.h"
#include "stream_int.h"

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

Cserver::t_user* Ctransaction::authenticate(const_memory_range s) const
{
	if (s.size() < 16)
		return NULL;
	std::string name(reinterpret_cast<const char*>(s.end - 16), 8);
	size_t i = name.find('\0');
	Cserver::t_user* user = m_server.find_user_by_name(i == std::string::npos ? name : name.substr(0, i));
	if (!user)
		return NULL;
	Csha1 sha1;
	sha1.write(const_memory_range(s, s.end - 8));
	sha1.write(user->pass);
	return memcmp(s.end - 8, sha1.read().data(), 8) ? NULL : user;
}

long long Ctransaction::connection_id() const
{
	const int cb_s = 12;
	char s[cb_s];
	write_int(8, s, m_server.secret());
	write_int(4, s + 8, m_a.sin_addr.s_addr);
	char d[20];
	(Csha1(const_memory_range(s, cb_s))).read(d);
	return read_int(8, d);
}

void Ctransaction::recv()
{
	const int cb_b = 2 << 10;
	char b[cb_b];
	while (1)
	{
		socklen_t cb_a = sizeof(sockaddr_in);
		int r = m_s.recvfrom(memory_range(b, cb_b), reinterpret_cast<sockaddr*>(&m_a), &cb_a);
		if (r == SOCKET_ERROR)
		{
			if (WSAGetLastError() != WSAEWOULDBLOCK)
				std::cerr << "recv failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
			return;
		}
		if (r < uti_size)
			return;
		switch (read_int(4, b + uti_action, b + r))
		{
		case uta_connect:
			if (r >= utic_size)
				send_connect(const_memory_range(b, r));
			break;
		case uta_announce:
			if (r >= utia_size)
				send_announce(const_memory_range(b, r));
			break;
		case uta_scrape:
			if (r >= utis_size)
				send_scrape(const_memory_range(b, r));
			break;
		}
	}
}

void Ctransaction::send_connect(const_memory_range r)
{
	if (!m_server.anonymous_connect() && !authenticate(r))
		return;
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_connect);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r.end));
	write_int(8, d + utoc_connection_id, connection_id());
	send(const_memory_range(d, utoc_size));
}

void Ctransaction::send_announce(const_memory_range r)
{
	if (read_int(8, r + uti_connection_id, r.end) != connection_id())
		return;
	Cserver::t_user* user = authenticate(r);
	if (!m_server.anonymous_announce() && !user)
	{
		send_error(r, "access denied");
		return;
	}
	Ctracker_input ti;
	ti.m_downloaded = read_int(8, r + utia_downloaded, r.end);
	ti.m_event = static_cast<Ctracker_input::t_event>(read_int(4, r + utia_event, r.end));
	ti.m_info_hash.assign(reinterpret_cast<const char*>(r + utia_info_hash), 20);
	ti.m_ipa = read_int(4, r + utia_ipa, r.end) && is_private_ipa(m_a.sin_addr.s_addr)
		? htonl(read_int(4, r + utia_ipa, r.end))
		: m_a.sin_addr.s_addr;
	ti.m_left = read_int(8, r + utia_left, r.end);
	ti.m_num_want = read_int(4, r + utia_num_want, r.end);
	ti.m_peer_id.assign(reinterpret_cast<const char*>(r + utia_peer_id), 20);
	ti.m_port = htons(read_int(2, r + utia_port, r.end));
	ti.m_uploaded = read_int(8, r + utia_uploaded, r.end);
	std::string error = m_server.insert_peer(ti, ti.m_ipa == m_a.sin_addr.s_addr, true, user);
	if (!error.empty())
	{
		send_error(r, error);
		return;
	}
	const Cserver::t_file* file = m_server.file(ti.m_info_hash);
	if (!file)
		return;
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_announce);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r.end));
	write_int(4, d + utoa_interval, m_server.announce_interval());
	write_int(4, d + utoa_leechers, file->leechers);
	write_int(4, d + utoa_seeders, file->seeders);
	Cannounce_output_udp o;
	o.w(d + utoa_size);
	file->select_peers(ti, o);
	send(const_memory_range(d, o.w()));
}

void Ctransaction::send_scrape(const_memory_range r)
{
	if (read_int(8, r + uti_connection_id, r.end) != connection_id())
		return;
	if (!m_server.anonymous_scrape() && !authenticate(r))
	{
		send_error(r, "access denied");
		return;
	}
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_scrape);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r.end));
	char* w = d + utos_size;
	for (r += utis_size; r + 20 <= r.end && w + 12 <= d + cb_d; r += 20)
	{
		const Cserver::t_file* file = m_server.file(std::string(reinterpret_cast<const char*>(r.begin), 20));
		if (file)
		{
			w = write_int(4, w, file->seeders);
			w = write_int(4, w, file->completed);
			w = write_int(4, w, file->leechers);
		}
		else
		{
			w = write_int(4, w, 0);
			w = write_int(4, w, 0);
			w = write_int(4, w, 0);
		}
	}
	m_server.stats().scraped_udp++;
	send(const_memory_range(d, w));
}

void Ctransaction::send_error(const_memory_range r, const std::string& msg)
{
	const int cb_d = 2 << 10;
	char d[cb_d];
	write_int(4, d + uto_action, uta_error);
	write_int(4, d + uto_transaction_id, read_int(4, r + uti_transaction_id, r.end));
	memcpy(d + utoe_size, msg.data(), msg.size());
	send(const_memory_range(d, utoe_size + msg.size()));
}

void Ctransaction::send(const_memory_range b)
{
	if (m_s.sendto(b, reinterpret_cast<const sockaddr*>(&m_a), sizeof(sockaddr_in)) != b.size())
		std::cerr << "send failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
}
