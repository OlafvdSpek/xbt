// bt_tracker_link.cpp: implementation of the Cbt_tracker_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_tracker_link.h"

#include "bt_file.h"
#include "bt_misc.h"
#include "bt_strings.h"
#include "xcc_z.h"

enum
{
	tp_http,
	tp_tcp,
	tp_udp,
	tp_unknown
};

enum
{
};

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_tracker_link::Cbt_tracker_link()
{
	m_announce_time = 0;
	m_event = e_none;
	m_state = 0;
}

Cbt_tracker_link::~Cbt_tracker_link()
{
}

static int split_url(const string& url, int& protocol, string& address, int& port, string& path)
{
	int a = url.find("://");
	if (a == string::npos)
		return 1;
	int b = url.find(':', a + 3);
	int c = url.find('/', a + 3);
	if (c == string::npos)
		return 1;
	if (url.substr(0, a) == "http")
		protocol = tp_http;
	else if (url.substr(0, a) == "tcp")
		protocol = tp_tcp;
	else if (url.substr(0, a) == "udp")
		protocol = tp_udp;
	else 
		protocol = tp_unknown;
	if (b == string::npos || b > c)
	{
		address = url.substr(a + 3, c - a - 3);
		port = 80;
	}
	else
	{
		address = url.substr(a + 3, b - a - 3);
		port = atoi(url.substr(b + 1, c - b - 1).c_str());
		if (port == 2710)
			protocol = tp_udp;
	}
	path = url.substr(c);
	return 0;
}

int Cbt_tracker_link::pre_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 0:
		if (m_announce_time > time(NULL))
			return 0;
		m_announce_time = time(NULL) + 300;
		if (split_url(f.m_trackers.front(), m_protocol, m_host, m_port, m_path))
			return 0;
		switch (m_protocol)
		{
		case tp_http:
			if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
				return 0;
			cout << "http://";
			break;
		case tp_tcp:
			if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
				return 0;
			cout << "tcp://";
			break;
		case tp_udp:
			if (m_s.open(SOCK_DGRAM) == INVALID_SOCKET)
				return 0;
			cout << "udp://";
			break;
		default:
			return 0;
		}
		cout << m_host << ':' << m_port << m_path << "?info_hash=" << uri_encode(f.m_info_hash) << endl;
		if (m_s.connect(Csocket::get_host(m_host), htons(m_port)) && WSAGetLastError() != WSAEWOULDBLOCK)
			return 0;
		if (m_protocol == tp_udp)
		{
			t_udp_tracker_input_connect uti;
			uti.action(uta_connect);
			uti.transaction_id(m_transaction_id = rand());
			if (m_s.send(&uti, sizeof(t_udp_tracker_input_connect)) != sizeof(t_udp_tracker_input_connect))
				return 0;
			m_connect_send = time(NULL);
			m_state = 3;
		}
		else
		{
			m_w = m_d.write_start(16 << 10);
			m_state = 1;
		}
	case 1:
		FD_SET(m_s, fd_write_set);
		FD_SET(m_s, fd_except_set);
	case 2:
	case 3:
	case 4:
		FD_SET(m_s, fd_read_set);
		return m_s;
	}
	return 0;
}

void Cbt_tracker_link::post_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 1:
		if (FD_ISSET(m_s, fd_write_set))
		{
			strstream os;
			os << "GET " << m_path 
				<< "?info_hash=" << uri_encode(f.m_info_hash) 
				<< "&peer_id=" << uri_encode(f.m_peer_id) 
				// << "&ip=" << uri_encode("62.163.33.227")
				<< "&port=" << f.m_local_port
				<< "&uploaded=" << static_cast<unsigned>(f.m_uploaded)
				<< "&downloaded=" << static_cast<unsigned>(f.m_downloaded)
				<< "&left=" << static_cast<unsigned>(f.m_left);
			switch (m_event)
			{
			case e_completed:
				os << "&event=completed";
				break;
			case e_started:
				os << "&event=started";
				break;
			case e_stopped:
				os << "&event=stopped";
				break;
			}
			m_event = e_none;			
			os << " HTTP/1.0" << endl
				<< "accept-encoding: gzip" << endl
				<< "host: " << m_host << ':' << m_port << endl
				<< endl;
			if (m_s.send(os.str(), os.pcount()) != os.pcount())
				close();
			else
				m_state = 2;
		}
		else if (FD_ISSET(m_s, fd_except_set))
			close();
		break;
	case 2:
		if (FD_ISSET(m_s, fd_read_set))
		{
			for (int r; r = m_s.recv(m_w, m_d.data_end() - m_w); )
			{
				if (r == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						close();
					return;
				}
				m_w += r;
			}
			m_d.size(m_w - m_d);
			m_d.save("d:/temp/bt/tracker out.txt");
			read(f, m_d);
			close();
		}
		break;
	case 3:
		if (FD_ISSET(m_s, fd_read_set))
		{
			const int cb_d = 2 << 10;
			char d[cb_d];
			int r = m_s.recv(d, cb_d);
			if (r != SOCKET_ERROR && r >= sizeof(t_udp_tracker_output_connect))
			{
				const t_udp_tracker_output_connect& uto = *reinterpret_cast<const t_udp_tracker_output_connect*>(d);
				if (uto.action() == uta_connect && uto.transaction_id() == m_transaction_id)
				{
					m_connection_id = uto.m_connection_id;
					t_udp_tracker_input_announce uti;
					uti.m_connection_id = m_connection_id;
					uti.action(uta_announce);
					uti.transaction_id(m_transaction_id = rand());
					memcpy(uti.m_info_hash, f.m_info_hash.c_str(), 20);
					memcpy(uti.m_peer_id, f.m_peer_id.c_str(), 20);
					uti.downloaded(f.m_downloaded);
					uti.left(f.m_left);
					uti.uploaded(f.m_uploaded);
					uti.event(m_event);
					m_event = e_none;
					uti.ipa(0);
					uti.num_want(-1);
					uti.port(f.m_local_port);
					if (m_s.send(&uti, sizeof(t_udp_tracker_input_announce)) != sizeof(t_udp_tracker_input_announce))
					{
						close();
						return;
					}
					m_announce_send = time(NULL);
					m_state = 4;
				}
			}				
		}
		else if (time(NULL) - m_connect_send > 15)
			close();
		break;
	case 4:
		if (FD_ISSET(m_s, fd_read_set))
		{
			const int cb_d = 2 << 10;
			char d[cb_d];
			int r = m_s.recv(d, cb_d);
			if (r != SOCKET_ERROR && r >= sizeof(t_udp_tracker_output_announce))
			{
				const t_udp_tracker_output_announce& uto = *reinterpret_cast<const t_udp_tracker_output_announce*>(d);
				if (uto.action() == uta_announce && uto.transaction_id() == m_transaction_id)
				{
					m_announce_time = time(NULL) + max(300, uto.interval());
					for (int o = sizeof(t_udp_tracker_output_announce); o + sizeof(t_udp_tracker_output_peer) <= r; o += sizeof(t_udp_tracker_output_announce))
					{
						const t_udp_tracker_output_peer& peer = *reinterpret_cast<const t_udp_tracker_output_peer*>(d + o);
						sockaddr_in a;
						a.sin_family = AF_INET;
						a.sin_port = peer.port();
						a.sin_addr.s_addr = peer.host();
						f.insert_peer(a);
					}

				}
				close();
			}
		}
		else if (time(NULL) - m_announce_send > 15)
			close();
		break;
	}
}

int Cbt_tracker_link::read(Cbt_file& f, const Cvirtual_binary& d)
{
	for (const byte* r = d; r < d.data_end(); r++)
	{
		if (*r == ' ')
		{
			int http_result = atoi(string(reinterpret_cast<const char*>(r), d.data_end() - r).c_str());
			if (http_result != 200)
				return 1;
			for (const byte* r = d; r + 4 <= d.data_end(); r++)
			{
				if (!memcmp(r, "\r\n\r\n", 4))
				{
					r += 4;
					Cbvalue v;
					if (r[0] == 0x1f && r[1] == 0x8b && r[2] == 8)
					{
						if (v.write(xcc_z::gunzip(r, d.data_end() - r)))
							return 1;						;
					}
					else if (v.write(r, d.data_end() - r))
						return 1;
					if (v.d(bts_failure_reason).s().empty())
					{
						m_announce_time = time(NULL) + max(300, v.d(bts_interval).i());
						const Cbvalue::t_list& peers = v.d(bts_peers).l();
						for (Cbvalue::t_list::const_iterator i = peers.begin(); i != peers.end(); i++)
						{
							int ipa = htonl(inet_addr(i->d(bts_ipa).s().c_str()));
							if (!ipa)
							{
								cout << i->d(bts_ipa).s() << endl;
								ipa = Csocket::get_host(i->d(bts_ipa).s());
							}
							sockaddr_in a;
							a.sin_family = AF_INET;
							a.sin_port = htons(i->d(bts_port).i());
							a.sin_addr.s_addr = htonl(ipa);
							f.insert_peer(a);
						}
						return 0;
					}
					else
						cout << v.d(bts_failure_reason).s();
				}
			}
		}
	}
	return 1;
}

void Cbt_tracker_link::close()
{
	m_s.close();
	m_state = 0;
}

ostream& Cbt_tracker_link::dump(ostream& os) const
{
	return os;
}

ostream& operator<<(ostream& os, const Cbt_tracker_link& v)
{
	return v.dump(os);
}

int Cbt_tracker_link::pre_dump() const
{
	int size = 0;
	return size;
}

void Cbt_tracker_link::dump(Cstream_writer& w) const
{
}

