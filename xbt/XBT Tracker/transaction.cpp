// transaction.cpp: implementation of the Ctransaction class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "transaction.h"

#include "bt_misc.h"
#include "bt_strings.h"
#include "server.h"
#include "sha1.h"

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

const Cserver::t_user* Ctransaction::authenticate(const void* s, const char* a, const char* s_end) const
{
	if (s_end - a < 17 || ~*a & 1)
		return NULL;
	string name(a + 1, 8);
	int i = name.find('\0');
	const Cserver::t_user* user = m_server.find_user(i == string::npos ? name : name.substr(0, i));
	if (!user)
		return NULL;
	SHA1Context context;
	SHA1Reset(&context);
	SHA1Input(&context, s, sizeof(t_udp_tracker_input_announce) + s_end - reinterpret_cast<const char*>(s) - 8);
	SHA1Input(&context, user->pass.c_str(), user->pass.size());
	unsigned char hash[20];
	SHA1Result(&context, hash);
	return memcmp(a + 9, hash, 8) ? NULL : user;
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
	if (r < sizeof(t_udp_tracker_input))
		return;
	const t_udp_tracker_input& uti = *reinterpret_cast<t_udp_tracker_input*>(b);
	switch (uti.action())
	{
	case uta_connect:
		if (r >= sizeof(t_udp_tracker_input_connect))
			send_connect(*reinterpret_cast<t_udp_tracker_input_connect*>(b), b + sizeof(t_udp_tracker_input_connect), b + r);
		break;
	case uta_announce:
		if (r >= sizeof(t_udp_tracker_input_announce))
			send_announce(*reinterpret_cast<t_udp_tracker_input_announce*>(b), b + sizeof(t_udp_tracker_input_announce), b + r);
		break;
	case uta_scrape:
		if (r >= sizeof(t_udp_tracker_input_scrape))
			send_scrape(*reinterpret_cast<t_udp_tracker_input_scrape*>(b), b + sizeof(t_udp_tracker_input_scrape), b + r);
		break;
	}
}

void Ctransaction::send_connect(const t_udp_tracker_input_connect& uti, const char* r, const char* r_end)
{
	if (!m_server.anonymous_connect() && !authenticate(&uti, r, r_end))
		return;
	t_udp_tracker_output_connect uto;
	uto.action(uta_connect);
	uto.transaction_id(uti.transaction_id());
	uto.m_connection_id = connection_id();
	send(&uto, sizeof(t_udp_tracker_output_connect));
}

void Ctransaction::send_announce(const t_udp_tracker_input_announce& uti, const char* r, const char* r_end)
{
	if (uti.m_connection_id != connection_id())
		return;
	if (!m_server.anonymous_announce() && !authenticate(&uti, r, r_end))
	{
		send_error(uti, "access denied");
		return;
	}
	Ctracker_input ti;
	ti.m_downloaded = uti.downloaded();
	ti.m_event = static_cast<Ctracker_input::t_event>(uti.event());
	ti.m_info_hash = uti.info_hash();
	ti.m_ipa = uti.ipa() && is_private_ipa(m_a.sin_addr.s_addr)
		? uti.ipa()
		: m_a.sin_addr.s_addr;
	ti.m_left = uti.left();
	ti.m_num_want = uti.num_want();
	ti.m_peer_id = uti.peer_id();
	ti.m_port = uti.port();
	ti.m_uploaded = uti.uploaded();
	m_server.insert_peer(ti, ti.m_ipa == m_a.sin_addr.s_addr, true);
	const Cserver::t_file* file = m_server.file(ti.m_info_hash);
	if (!file)
	{
		send_error(uti, bts_unregistered_torrent);
		return;
	}
	const int cb_b = 2 << 10;
	char b[cb_b];
	t_udp_tracker_output_announce& uto = *reinterpret_cast<t_udp_tracker_output_announce*>(b);
	uto.action(uta_announce);
	uto.transaction_id(uti.transaction_id());
	uto.interval(m_server.announce_interval());
	uto.leechers(file->leechers);
	uto.seeders(file->seeders);
	Cannounce_output_udp o;
	o.w(b + sizeof(t_udp_tracker_output_announce));
	file->select_peers(ti, o);
	send(b, o.w() - b);
}

void Ctransaction::send_scrape(const t_udp_tracker_input_scrape& uti, const char* r, const char* r_end)
{
	if (uti.m_connection_id != connection_id())
		return;
	if (!m_server.anonymous_scrape() && !authenticate(&uti, r, r_end))
	{
		send_error(uti, "access denied");
		return;
	}
	const int cb_b = 2 << 10;
	char b[cb_b];
	t_udp_tracker_output_scrape& uto = *reinterpret_cast<t_udp_tracker_output_scrape*>(b);
	uto.transaction_id(uti.transaction_id());
	uto.action(uta_scrape);
	t_udp_tracker_output_file* file = reinterpret_cast<t_udp_tracker_output_file*>(b + sizeof(t_udp_tracker_output_scrape));
	for (; r + 20 <= r_end && reinterpret_cast<char*>(file + 1) <= b + cb_b; r += 20)
	{
		const Cserver::t_file* i = m_server.file(string(r, 20));
		if (!i)
		{
			file->complete(0);
			file->downloaded(0);
			file->incomplete(0);
		}
		else
		{
			file->complete(i->seeders);
			file->downloaded(i->completed);
			file->incomplete(i->leechers);
		}
		file++;
	}
	send(b, reinterpret_cast<char*>(file) - b);
}

void Ctransaction::send_error(const t_udp_tracker_input& uti, const string& msg)
{
	const int cb_b = 2 << 10;
	char b[cb_b];
	t_udp_tracker_output_error& uto = *reinterpret_cast<t_udp_tracker_output_error*>(b);
	uto.action(uta_error);
	uto.transaction_id(uti.transaction_id());
	memcpy(b + sizeof(t_udp_tracker_output_error), msg.c_str(), msg.length());
	send(b, sizeof(t_udp_tracker_output_error) + msg.length());
}

void Ctransaction::send(const void* b, int cb_b)
{
	if (m_s.sendto(b, cb_b, reinterpret_cast<const sockaddr*>(&m_a), sizeof(sockaddr_in)) != cb_b)
		cerr << "send failed: " << WSAGetLastError() << endl;
}
