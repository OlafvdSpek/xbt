// server.cpp: implementation of the Cserver class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "server.h"

#include "bt_strings.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cserver::Cserver(Cdatabase& database):
	m_database(database)
{
	m_fid_end = 0;
}

void Cserver::run(Csocket& lt, Csocket& lu)
{
	clean_up();
	read_db();
	write_db();
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
	while (1)
	{
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		int n = max(static_cast<SOCKET>(lt), static_cast<SOCKET>(lu));
		{
			for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); i++)
			{
				int z = i->pre_select(&fd_read_set);
				n = max(n, z);
			}
		}
		{
			for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); i++)
			{
				int z = i->pre_select(&fd_write_set, &fd_except_set);
				n = max(n, z);
			}
		}
		FD_SET(lt, &fd_read_set);
		FD_SET(lu, &fd_read_set);
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, NULL) == SOCKET_ERROR)
			cerr << "select failed: " << WSAGetLastError() << endl;
		else 
		{
			if (FD_ISSET(lt, &fd_read_set))
			{
				sockaddr_in a;
				socklen_t cb_a = sizeof(sockaddr_in);
				SOCKET s = accept(lt, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
					cerr << "accept failed: " << WSAGetLastError() << endl;
				else
				{
					unsigned long p = true;
					if (ioctlsocket(s, FIONBIO, &p))
						cerr << "ioctlsocket failed: " << WSAGetLastError() << endl;
					m_connections.push_front(Cconnection(this, s, a));
				}
			}
			if (FD_ISSET(lu, &fd_read_set))
				udp_recv(lu);
			{
				for (t_connections::iterator i = m_connections.begin(); i != m_connections.end(); )
				{
					i->post_select(&fd_read_set);
					if (*i)
						i++;
					else
						i = m_connections.erase(i);
				}
			}
			{
				for (t_peer_links::iterator i = m_peer_links.begin(); i != m_peer_links.end(); )
				{
					i->post_select(&fd_write_set, &fd_except_set);
					if (*i)
						i++;
					else
						i = m_peer_links.erase(i);
				}
			}
		}
		if (time(NULL) - m_read_config_time > m_read_config_interval)
			read_config();
	}
}

void Cserver::insert_peer(const Ctracker_input& v)
{
	if (time(NULL) - m_read_db_time > m_read_db_interval)
		read_db();
	t_file& file = m_files[v.m_info_hash];
	t_peers::iterator i = file.peers.find(v.m_ipa);
	if (i != file.peers.end())
		(i->second.left ? file.leechers : file.seeders)--;
	if (v.m_event == Ctracker_input::e_stopped)
		file.peers.erase(v.m_ipa);
	else
	{
		t_peer& peer = file.peers[v.m_ipa];
		// peer.downloaded = v.m_downloaded;
		peer.left = v.m_left;
		peer.peer_id = v.m_peer_id;
		peer.port = v.m_port;
		// peer.uploaded = v.m_uploaded;
		(peer.left ? file.leechers : file.seeders)++;

		if (!peer.listening && time(NULL) - peer.mtime > 900)
		{
			Cpeer_link peer_link(ntohl(v.m_ipa), v.m_port, this, v.m_info_hash, v.m_ipa);
			if (peer_link)
				m_peer_links.push_front(peer_link);
		}
		peer.mtime = time(NULL);
	}
	switch (v.m_event)
	{
	case Ctracker_input::e_completed:
		file.completed++;
		break;
	case Ctracker_input::e_started:
		file.started++;
		break;
	case Ctracker_input::e_stopped:
		file.stopped++;
		break;
	}
	file.dirty = true;
}

void Cserver::update_peer(const string& file_id, int peer_id, bool listening)
{
	t_files::iterator i = m_files.find(file_id);
	if (i == m_files.end())
		return;
	t_peers::iterator j = i->second.peers.find(peer_id);
	if (j == i->second.peers.end())
		return;
	j->second.listening = listening;
}

Cbvalue Cserver::t_file::select_peers(const Ctracker_input& ti) const
{
	typedef vector<t_peers::const_iterator> t_candidates;

	t_candidates candidates;
	{
		for (t_peers::const_iterator i = peers.begin(); i != peers.end(); i++)
		{
			if ((ti.m_left || i->second.left) && i->second.listening)
				candidates.push_back(i);
		}
	}
	Cbvalue peers(Cbvalue::vt_list);
	int c = ti.m_num_want < 0 ? 50 : min(ti.m_num_want, 50);
	for (t_candidates::const_iterator i = candidates.begin(); c-- && i != candidates.end(); i++)
	{
		Cbvalue peer;
		if (!ti.m_no_peer_id)
			peer.d(bts_peer_id, (*i)->second.peer_id);
		in_addr a;
		a.s_addr = (*i)->first;
		peer.d(bts_ipa, static_cast<string>(inet_ntoa(a)));
		peer.d(bts_port, (*i)->second.port);
		peers.l(peer);
	}	
	return peers;
}

Cbvalue Cserver::select_peers(const Ctracker_input& ti)
{
	if (time(NULL) - m_clean_up_time > m_clean_up_interval)
		clean_up();
	if (time(NULL) - m_write_db_time > m_write_db_interval)
		write_db();
	t_files::const_iterator i = m_files.find(ti.m_info_hash);
	if (i == m_files.end())
		return Cbvalue();
	Cbvalue v;
	v.d(bts_interval, m_announce_interval);
	v.d(bts_peers, i->second.select_peers(ti));	
	return v;
}

void Cserver::t_file::clean_up(int announce_interval)
{
	int t = time(NULL);
	for (t_peers::iterator i = peers.begin(); i != peers.end(); )
	{
		if (t - i->second.mtime > 1.5 * announce_interval)
		{
			(i->second.left ? leechers : seeders)--;
			peers.erase(i++);
			dirty = true;
		}
		else
			i++;
	}
}

void Cserver::clean_up()
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->second.clean_up(m_announce_interval);
	m_clean_up_time = time(NULL);
}

Cbvalue Cserver::t_file::scrape() const
{
	Cbvalue v;
	v.d(bts_complete, seeders);
	v.d(bts_downloaded, completed);
	v.d(bts_incomplete, leechers);
	return v;
}

Cbvalue Cserver::scrape(const Ctracker_input& ti)
{
	Cbvalue v;
	Cbvalue files(Cbvalue::vt_dictionary);
	if (ti.m_info_hash.empty())
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (i->second.leechers || i->second.seeders)
				files.d(i->first, i->second.scrape());
		}
	}
	else
	{
		t_files::const_iterator i = m_files.find(ti.m_info_hash);
		if (i != m_files.end())
			files.d(i->first, i->second.scrape());
	}
	v.d(bts_files, files);
	return v;
}

void Cserver::udp_recv(Csocket& s)
{
	const int cb_b = 2 << 10;
	char b[cb_b];
	sockaddr_in a;
	socklen_t cb_a = sizeof(sockaddr_in);
	int r = s.recvfrom(b, cb_b, reinterpret_cast<sockaddr*>(&a), &cb_a);
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
		{
			t_udp_tracker_output_connect& uto = *reinterpret_cast<t_udp_tracker_output_connect*>(b);
			uto.return_value(0);
			uto.transaction_id(uti.transaction_id());
			memset(uto.m_connection_id, 0, 8);
			if (s.sendto(b, sizeof(t_udp_tracker_output_connect), reinterpret_cast<sockaddr*>(&a), cb_a) == SOCKET_ERROR)
				cerr << "send failed: " << WSAGetLastError() << endl;
		}
		break;
	case uta_announce:
		if (r >= sizeof(t_udp_tracker_input_announce))
		{
			const t_udp_tracker_input_announce& uti = *reinterpret_cast<t_udp_tracker_input_announce*>(b);
			Ctracker_input ti;
			ti.m_downloaded = uti.downloaded();
			ti.m_event = static_cast<Ctracker_input::t_event>(uti.event());
			ti.m_info_hash = uti.info_hash();
			ti.m_ipa = a.sin_addr.s_addr;
			ti.m_left = uti.left();
			ti.m_num_want = uti.num_want();
			ti.m_peer_id = uti.peer_id();
			ti.m_port = uti.port();
			ti.m_uploaded = uti.uploaded();
			insert_peer(ti);
			t_udp_tracker_output_announce& uto = *reinterpret_cast<t_udp_tracker_output_announce*>(b);
			uto.transaction_id(uti.transaction_id());
			t_files::const_iterator i = m_files.find(ti.m_info_hash);
			if (i == m_files.end())
			{

				uto.return_value(-1);
				if (s.sendto(b, sizeof(t_udp_tracker_output_announce), reinterpret_cast<sockaddr*>(&a), cb_a) == SOCKET_ERROR)
					cerr << "send failed: " << WSAGetLastError() << endl;
				return;
			}
			uto.return_value(0);
			uto.interval(m_announce_interval);
			t_udp_tracker_output_peer* peer = reinterpret_cast<t_udp_tracker_output_peer*>(b + sizeof(t_udp_tracker_output_announce));
			int c = min(ti.m_num_want, 100);
			for (t_peers::const_iterator j = i->second.peers.begin(); j != i->second.peers.end(); j++)
			{
				if (!ti.m_left && !j->second.left || !j->second.listening)
					continue;
				if (!c--)
					break;
				peer->host(ntohl(j->first));
				peer->port(j->second.port);
				peer++;
			}
			if (s.sendto(b, reinterpret_cast<char*>(peer) - b, reinterpret_cast<sockaddr*>(&a), cb_a) == SOCKET_ERROR)
				cerr << "send failed: " << WSAGetLastError() << endl;
		}
		break;
	case uta_scrape:
		if (r >= sizeof(t_udp_tracker_input_scrape))
		{
			const t_udp_tracker_input_scrape& uti = *reinterpret_cast<t_udp_tracker_input_scrape*>(b);
			t_udp_tracker_output_scrape& uto = *reinterpret_cast<t_udp_tracker_output_scrape*>(b);
			uto.transaction_id(uti.transaction_id());
			t_files::const_iterator i = m_files.find(uti.info_hash());
			if (i == m_files.end())
			{
				uto.return_value(-1);
				if (s.sendto(b, sizeof(t_udp_tracker_output_scrape), reinterpret_cast<sockaddr*>(&a), cb_a) == SOCKET_ERROR)
					cerr << "send failed: " << WSAGetLastError() << endl;
				return;
			}
			uto.return_value(0);
			t_udp_tracker_output_file* file = reinterpret_cast<t_udp_tracker_output_file*>(b + sizeof(t_udp_tracker_output_scrape));
			file->info_hash(i->first);
			file->complete(i->second.seeders);
			file->downloaded(i->second.completed);
			file->incomplete(i->second.leechers);
			file++;
			if (s.sendto(b, reinterpret_cast<char*>(file) - b, reinterpret_cast<sockaddr*>(&a), cb_a) == SOCKET_ERROR)
				cerr << "send failed: " << WSAGetLastError() << endl;
		}
		break;
	}
}

void Cserver::read_db()
{
	try
	{
		Csql_query q(m_database);
		q.write("select info_hash, completed, fid, started, stopped from xbt_files where fid >= %s");
		q.p(m_fid_end);
		Csql_result result = q.execute();
		Csql_row row;
		while (row = result.fetch_row())
		{
			if (row.size(0) != 20)
				continue;
			t_file& file = m_files[string(row.f(0), 20)];
			file.completed = row.f_int(1, 0);
			file.dirty = true;
			file.fid = row.f_int(2, 0);
			file.started = row.f_int(3, 0);
			file.stopped = row.f_int(4, 0);
			m_fid_end = max(m_fid_end, file.fid + 1);
		}
	}
	catch (Cxcc_error error)
	{
	}
	m_read_db_time = time(NULL);
}

void Cserver::write_db()
{
	try
	{
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			t_file& file = i->second;
			if (!file.dirty)
				continue;
			Csql_query q(m_database);
			if (!file.fid)
			{
				q.write("insert into xbt_files (info_hash, ctime) values (%s, NULL)");
				q.pe(i->first);
				q.execute();
				file.fid = m_database.insert_id();
			}
			q.write("update xbt_files set leechers = %s, seeders = %s, completed = %s, started = %s, stopped = %s where fid = %s");
			q.p(file.leechers);
			q.p(file.seeders);
			q.p(file.completed);
			q.p(file.started);
			q.p(file.stopped);
			q.p(file.fid);
			q.execute();
			file.dirty = false;
		}
	}
	catch (Cxcc_error error)
	{
	}
	m_write_db_time = time(NULL);
}

void Cserver::read_config()
{
	m_announce_interval = 1800;
	m_clean_up_interval = 60;
	m_read_config_interval = 300;
	m_read_db_interval = 60;
	m_write_db_interval = 60;
	try
	{
		Csql_result result = m_database.query("select name, value from xbt_config");
		Csql_row row;
		while (row = result.fetch_row())
		{
			if (!row.f(1))
				continue;
			if (row.f(0) == "announce_interval")
				m_announce_interval = row.f_int(1);
			else if (row.f(0) == "read_config_interval")
				m_read_config_interval = row.f_int(1);
			else if (row.f(0) == "read_db_interval")
				m_read_db_interval = row.f_int(1);
			else if (row.f(0) == "write_db_interval")
				m_write_db_interval = row.f_int(1);
		}
	}
	catch (Cxcc_error error)
	{
	}
	m_read_config_time = time(NULL);
}
