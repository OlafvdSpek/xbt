// bt_tracker_link.cpp: implementation of the Cbt_tracker_link class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_tracker_link.h"

#include "bt_file.h"
#include "bt_misc.h"
#include "bt_strings.h"
#include "xcc_z.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_tracker_link::Cbt_tracker_link()
{
	m_announce_time = 0;
	mc_attempts = 0;
	m_current_tracker = 0;
	m_event = e_started;
	m_state = 0;
}

Cbt_tracker_link::~Cbt_tracker_link()
{
}

int Cbt_tracker_link::pre_select(Cbt_file& f, fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	switch (m_state)
	{
	case 0:
		assert(m_current_tracker >= 0 && m_current_tracker < f.m_trackers.size());
		if (!f.m_run || m_announce_time > time(NULL) || m_current_tracker < 0 || m_current_tracker >= f.m_trackers.size())
			return 0;
		m_url = f.m_trackers[m_current_tracker];
		if (!m_url.valid())
			return 0;
		switch (m_url.m_protocol)
		{
		case Cbt_tracker_url::tp_http:
			f.alert(Calert(Calert::info, "Tracker: URL: http://" + m_url.m_host + ':' + n(m_url.m_port) + m_url.m_path + "?info_hash=" + uri_encode(f.m_info_hash)));		
			m_announce_time = time(NULL) + (300 << mc_attempts++);
			if (m_s.open(SOCK_STREAM) == INVALID_SOCKET)
				return 0;
			break;
		case Cbt_tracker_url::tp_udp:
			f.alert(Calert(Calert::info, "Tracker: URL: udp://" + m_url.m_host + ':' + n(m_url.m_port) + "?info_hash=" + uri_encode(f.m_info_hash)));
			m_announce_time = time(NULL) + (60 << mc_attempts++);
			if (m_s.open(SOCK_DGRAM) == INVALID_SOCKET)
				return 0;
			break;
		default:
			return 0;
		}
		{
			int h = Csocket::get_host(m_url.m_host);
			if (h == INADDR_NONE)
			{
				f.alert(Calert(Calert::error, "Tracker: gethostbyname failed"));
				close(f);
				return 0;
			}
			if (m_s.connect(h, htons(m_url.m_port)) && WSAGetLastError() != WSAEWOULDBLOCK)
				return 0;
			in_addr a;
			a.s_addr = h;
			f.alert(Calert(Calert::info, "Tracker: IPA: " + static_cast<string>(inet_ntoa(a))));
		}		
		if (m_url.m_protocol == Cbt_tracker_url::tp_udp)
		{
			t_udp_tracker_input_connect uti;
			uti.action(uta_connect);
			uti.transaction_id(m_transaction_id = rand());
			if (m_s.send(&uti, sizeof(t_udp_tracker_input_connect)) != sizeof(t_udp_tracker_input_connect))
			{
				close(f);
				return 0;
			}
			f.alert(Calert(Calert::debug, "Tracker: UDP: connect send"));
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
			os << "GET " << m_url.m_path 
				<< "?info_hash=" << uri_encode(f.m_info_hash) 
				<< "&peer_id=" << uri_encode(f.m_peer_id) 
				<< "&port=" << f.m_local_port
				<< "&downloaded=" << n(f.m_downloaded)
				<< "&left=" << n(f.m_left)
				<< "&uploaded=" << n(f.m_uploaded)
				<< "&compact=1"
				<< "&no_peer_id=1";
			if (f.m_local_ipa)
			{
				in_addr a;
				a.s_addr = f.m_local_ipa;
				os << "&ip=" << inet_ntoa(a);
			}
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
				<< "host: " << m_url.m_host << ':' << m_url.m_port << endl
				<< endl;
			if (m_s.send(os.str(), os.pcount()) != os.pcount())
				close(f);
			else
				m_state = 2;
		}
		else if (FD_ISSET(m_s, fd_except_set))
		{
			f.alert(Calert(Calert::error, "Tracker: HTTP: connect failed"));
			close(f);
		}
		break;
	case 2:
		if (FD_ISSET(m_s, fd_read_set))
		{
			for (int r; r = m_s.recv(m_w, m_d.data_end() - m_w); )
			{
				if (r == SOCKET_ERROR)
				{
					int e = WSAGetLastError();
					if (e != WSAEWOULDBLOCK)
					{
						f.alert(Calert(Calert::error, "Tracker: HTTP: recv failed:" + n(e)));
						close(f);
					}
					return;
				}
				m_w += r;
			}
			m_d.size(m_w - m_d);
			read(f, m_d);
			close(f);
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
					uti.ipa(f.m_local_ipa);
					uti.num_want(-1);
					uti.port(htons(f.m_local_port));
					if (m_s.send(&uti, sizeof(t_udp_tracker_input_announce)) != sizeof(t_udp_tracker_input_announce))
					{
						close(f);
						return;
					}
					f.alert(Calert(Calert::debug, "Tracker: UDP: announce send"));
					m_announce_send = time(NULL);
					m_state = 4;
				}
			}				
		}
		else if (time(NULL) - m_connect_send > 15)
			close(f);
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
					mc_attempts = 0;
					f.alert(Calert(Calert::info, "Tracker: " + n((r - sizeof(t_udp_tracker_output_announce)) / 6) + " peers (" + n(r) + " bytes)"));
					for (int o = sizeof(t_udp_tracker_output_announce); o + sizeof(t_udp_tracker_output_peer) <= r; o += sizeof(t_udp_tracker_output_peer))
					{
						const t_udp_tracker_output_peer& peer = *reinterpret_cast<const t_udp_tracker_output_peer*>(d + o);
						sockaddr_in a;
						a.sin_family = AF_INET;
						a.sin_port = peer.port();
						a.sin_addr.s_addr = peer.host();
						f.insert_peer(a);
					}
				}
				close(f);
			}
		}
		else if (time(NULL) - m_announce_send > 15)
			close(f);
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
			{
				f.alert(Calert(Calert::error, "Tracker: HTTP error: " + n(http_result)));
				return 1;
			}
			for (const byte* r = d; r + 4 <= d.data_end(); r++)
			{
				if (!memcmp(r, "\r\n\r\n", 4))
				{
					r += 4;
					Cbvalue v;
					if (r[0] == 0x1f && r[1] == 0x8b && r[2] == 8)
					{
						if (v.write(xcc_z::gunzip(r, d.data_end() - r)))
						{
							f.alert(Calert(Calert::error, "Tracker: gzip decode failed"));
							return 1;						;
						}
					}
					else if (v.write(r, d.data_end() - r))
					{
						f.alert(Calert(Calert::error, "Tracker: bdecode failed"));
						return 1;
					}
					if (v.d(bts_failure_reason).s().empty())
					{
						m_announce_time = time(NULL) + max(300, v.d(bts_interval).i());
						mc_attempts = 0;
						if (v.d(bts_peers).s().empty())
						{
							const Cbvalue::t_list& peers = v.d(bts_peers).l();
							f.alert(Calert(Calert::info, "Tracker: " + n(peers.size()) + " peers (" + n(d.size()) + " bytes)"));
							for (Cbvalue::t_list::const_iterator i = peers.begin(); i != peers.end(); i++)
								f.insert_peer(inet_addr(i->d(bts_ipa).s().c_str()), htons(i->d(bts_port).i()));
						}
						else
						{
							string peers = v.d(bts_peers).s();
							f.alert(Calert(Calert::info, "Tracker: " + n(peers.size() / 6) + " peers (" + n(d.size()) + " bytes)"));
							for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
								f.insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
						}
						return 0;
					}
					f.alert(Calert(Calert::error, "Tracker: failure reason: " + v.d(bts_failure_reason).s()));
					return 1;
				}
			}
		}
	}
	f.alert(Calert(Calert::error, "Tracker: Invalid HTTP output"));
	return 1;
}

void Cbt_tracker_link::close(Cbt_file& f)
{
	m_s.close();
	m_state = 0;
	if (!mc_attempts)
	{
		swap(f.m_trackers[0], f.m_trackers[m_current_tracker]);
		m_current_tracker = 0;
	}
	else if (++m_current_tracker < f.m_trackers.size())
	{
		m_announce_time = 0;
		mc_attempts--;
	}
	else
		m_current_tracker = 0;
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

void Cbt_tracker_link::event(int v)
{
	m_event = v;
	m_announce_time = 0;
}