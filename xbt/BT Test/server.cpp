#include "stdafx.h"
#include "server.h"

#include <sys/stat.h>
#include <algorithm>
#include <signal.h>
#include "bt_strings.h"
#include "stream_reader.h"

#define for if (0) {} else for

const char* g_pid_fname = "xbt_client_backend.pid";
static volatile bool g_sig_term = false;
const static int g_state_version = 4;

class Clock
{
#ifdef WIN32
public:
	Clock(CRITICAL_SECTION& cs)
	{
		EnterCriticalSection(m_cs = &cs);
	}

	~Clock()
	{
		LeaveCriticalSection(m_cs);
	}
private:
	Clock(const Clock&);
	const Clock& operator=(const Clock&);

	CRITICAL_SECTION* m_cs;
#else
public:
	Clock(int)
	{
	}
#endif
};

static string new_peer_id(const string& prefix)
{
	string v;
	if (prefix.empty())
	{
		v = "XBT-----";
		v[3] = '0' + Cserver::version() / 100 % 10;
		v[4] = '0' + Cserver::version() / 10 % 10;
		v[5] = '0' + Cserver::version() % 10;
#ifndef NDEBUG
		v[6] = 'd';
#endif
	}
	else
		v = prefix;
	size_t i = v.size();
	v.resize(20);
	for (; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

static string new_peer_key()
{
	string v;
	v.resize(8);
	for (size_t i = 0; i < v.size(); i++)
		v[i] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZabcdefghijklmnopqrstuvwyxz"[rand() % 62];
	return v;
}

Cserver::Cserver():
	m_version_check_handler(*this)
{
	m_admin_port = m_config.m_admin_port;
	m_check_remote_links_time = 0;
	m_peer_key = new_peer_key();
	m_peer_port = m_config.m_peer_port;
	m_run = false;
	m_run_scheduler_time = 0;
	m_send_quota = 0;
	m_start_time = ::time(NULL);
	m_time = ::time(NULL);
	m_tracker_port = m_config.m_tracker_port;
	m_update_chokes_time = 0;
	m_update_send_quotas_time = time();
	m_update_states_time = 0;

#ifdef WIN32
	InitializeCriticalSection(&m_cs);
#endif
	srand(time());
#ifndef NDEBUG
	m_logger.open("/temp/bt_logger.txt");
#endif
}

Cserver::~Cserver()
{
#ifdef WIN32
	DeleteCriticalSection(&m_cs);
#endif
}

void Cserver::admin_port(int v)
{
	m_config.m_admin_port = max(0, v);
}

void Cserver::peer_port(int v)
{
	m_config.m_peer_port = max(0, v);
}

void Cserver::public_ipa(int v)
{
	m_config.m_public_ipa = v == INADDR_NONE ? 0 : v;
}

void Cserver::seeding_ratio(int v)
{
	m_config.m_seeding_ratio = v ? max(100, v) : 0;
}

void Cserver::send_stop_event(bool v)
{
	m_config.m_send_stop_event = v;
}

void Cserver::tracker_port(int v)
{
	m_config.m_tracker_port = max(0, v);
}

void Cserver::upload_rate(int v)
{
	m_config.m_upload_rate = max(0, v);
}

void Cserver::upload_slots(int v)
{
	m_config.m_upload_slots = max(0, v);
}

void Cserver::upnp(bool v)
{
	m_config.m_upnp = v;
}

#ifdef WIN32
string get_host_name()
{
	vector<char> t(256);
	if (gethostname(&t.front(), t.size()))
		throw std::exception("gethostname failed");
	return &t.front();
}

wstring mbyte_to_wchar(const string& s)
{
	vector<wchar_t> t(MultiByteToWideChar(CP_ACP, 0, s.c_str(), -1, NULL, 0));
	if (!MultiByteToWideChar(CP_ACP, 0, s.c_str(), -1, &t.front(), t.size()))
		throw std::exception("MultiByteToWideChar failed");
	return &t.front();
}

string wchar_to_mbyte(const wstring& s)
{
	vector<char> t(WideCharToMultiByte(CP_ACP, 0, s.c_str(), -1, NULL, 0, NULL, NULL));
	if (!WideCharToMultiByte(CP_ACP, 0, s.c_str(), -1, &t.front(), t.size(), NULL, NULL))
		throw std::exception("WideCharToMultiByte failed");
	return &t.front();
}
#endif

int Cserver::run()
{
#ifdef WIN32
	HRESULT hr;
	hr = CoInitialize(NULL);
	if (FAILED(hr))
		alert(Calert(Calert::warn, "Server", "CoInitialize failed: " + hex_encode(8, hr)));
	IStaticPortMappingCollection* static_port_mapping_collection = NULL;
	if (m_config.m_upnp)
	{
		IUPnPNAT* upnp_nat;
		hr = CoCreateInstance(CLSID_UPnPNAT, NULL, CLSCTX_INPROC_SERVER, IID_IUPnPNAT, reinterpret_cast<void**>(&upnp_nat));
		if (FAILED(hr) || !upnp_nat)
			alert(Calert(Calert::warn, "UPnP NAT", "CoCreateInstance failed: " + hex_encode(8, hr)));
		else
		{
			hr = upnp_nat->get_StaticPortMappingCollection(&static_port_mapping_collection);
			upnp_nat->Release();
			if (FAILED(hr) || !static_port_mapping_collection)
				alert(Calert(Calert::warn, "UPnP NAT", "get_StaticPortMappingCollection failed: " + hex_encode(8, hr)));
		}
	}
#endif
	m_admin_port = m_config.m_admin_port;
	m_peer_id = new_peer_id(peer_id_prefix());
	m_peer_port = m_config.m_peer_port;
	m_tracker_port = m_config.m_tracker_port;
	Csocket l, la, lt;
	if (admin_port())
	{
		if (la.open(SOCK_STREAM) == INVALID_SOCKET)
			alert(Calert(Calert::error, "Server", "socket failed: " + Csocket::error2a(WSAGetLastError())));
		else
		{
			while (admin_port() < 0x10000 && la.setsockopt(SOL_SOCKET, SO_REUSEADDR, true), la.bind(htonl(INADDR_LOOPBACK), htons(admin_port())) && WSAGetLastError() == WSAEADDRINUSE)
				m_admin_port++;
			if (la.listen())
			{
				alert(Calert(Calert::error, "Server", "listen failed: " + Csocket::error2a(WSAGetLastError())));
				la.close();
			}
		}
	}
	if (peer_port())
	{
		if (l.open(SOCK_STREAM) == INVALID_SOCKET)
			alert(Calert(Calert::error, "Server", "socket failed: " + Csocket::error2a(WSAGetLastError())));
		else
		{
			l.setsockopt(SOL_SOCKET, SO_REUSEADDR, true);
			for (;  peer_port() < 0x10000; m_peer_port++)
			{
				if (l.bind(htonl(INADDR_ANY), htons(peer_port())) && WSAGetLastError() == WSAEADDRINUSE)
					continue;
#ifdef WIN32
				try
				{
					if (!static_port_mapping_collection)
						break;
					IStaticPortMapping* static_port_mapping = NULL;
					BSTR bstrProtocol = SysAllocString(L"TCP");
					BSTR bstrInternalClient = SysAllocString(mbyte_to_wchar(get_host_name()).c_str());
					BSTR bstrDescription = SysAllocString(L"XBT Client");
					hr = static_port_mapping_collection->Add(peer_port(), bstrProtocol, peer_port(), bstrInternalClient, true, bstrDescription, &static_port_mapping);
					SysFreeString(bstrProtocol);
					SysFreeString(bstrInternalClient);
					SysFreeString(bstrDescription);
					if (FAILED(hr) || !static_port_mapping)
					{
						alert(Calert(Calert::warn, "UPnP NAT", "static_port_mapping_collection->Add failed failed: " + hex_encode(8, hr)));
						break;
					}
					BSTR bstrExternalIPA;
					hr = static_port_mapping->get_ExternalIPAddress(&bstrExternalIPA);
					static_port_mapping->Release();
					if (FAILED(hr))
					{
						alert(Calert(Calert::warn, "UPnP NAT", "static_port_mapping->get_ExternalIPAddress failed: " + hex_encode(8, hr)));
						break;
					}
					alert(Calert(Calert::info, "UPnP NAT", "External IPA: " + wchar_to_mbyte(bstrExternalIPA)));
					SysFreeString(bstrExternalIPA);
				}
				catch (std::exception& e)
				{
					alert(Calert(Calert::warn, "UPnP NAT", e.what()));
				}
#endif
				break;
			}
			if (l.listen())
			{
				alert(Calert(Calert::error, "Server", "listen failed: " + Csocket::error2a(WSAGetLastError())));
				l.close();
			}
		}
	}
	if (tracker_port())
	{
		if (lt.open(SOCK_DGRAM) == INVALID_SOCKET)
			alert(Calert(Calert::error, "Server", "socket failed: " + Csocket::error2a(WSAGetLastError())));
		else
		{
			while (tracker_port() < 0x10000 && lt.bind(htonl(INADDR_ANY), htons(tracker_port())) && WSAGetLastError() == WSAEADDRINUSE)
				m_tracker_port++;
		}
	}
#ifdef WIN32
	if (static_port_mapping_collection)
	{
		static_port_mapping_collection->Release();
		static_port_mapping_collection = NULL;
	}
#endif
	mkpath(local_app_data_dir());
	load_state(Cvirtual_binary(state_fname()));
	m_profiles.load(Cxif_key(Cvirtual_binary(profiles_fname())));
	m_scheduler.load(Cxif_key(Cvirtual_binary(scheduler_fname())));
	m_tracker_accounts.load(Cvirtual_binary(trackers_fname()));
	clean_scheduler();
	run_scheduler();
#ifndef WIN32
	if (daemon(true, false))
		alert(Calert(Calert::error, "Server", "daemon failed: " + n(errno)));
	ofstream(g_pid_fname) << getpid() << endl;
	struct sigaction act;
	act.sa_handler = sig_handler;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	if (sigaction(SIGTERM, &act, NULL))
		cerr << "sigaction failed" << endl;
	act.sa_handler = SIG_IGN;
	if (sigaction(SIGPIPE, &act, NULL))
		cerr << "sigaction failed" << endl;
#endif
	http_request(Csocket::get_host("xbtt.sourceforge.net"), htons(80), "GET /version_check.php?xbtc HTTP/1.0\r\nhost: xbtt.sourceforge.net\r\n\r\n", &m_version_check_handler);
	m_save_state_time = time();
	fd_set fd_read_set;
	fd_set fd_write_set;
	fd_set fd_except_set;
	bool stopping = false;
	for (m_run = true; !stopping || !m_http_links.empty(); )
	{
		lock();
		if (m_config.m_admin_port != m_admin_port)
		{
			Csocket s;
			if (!m_config.m_admin_port)
			{
				la.close();
				m_admin_port = m_config.m_admin_port;
			}
			else if (s.open(SOCK_STREAM) != INVALID_SOCKET
				&& !s.bind(htonl(INADDR_LOOPBACK), htons(m_config.m_admin_port))
				&& !s.listen())
			{
				la = s;
				m_admin_port = m_config.m_admin_port;
			}
		}
		if (m_config.m_peer_port != m_peer_port)
		{
			Csocket s;
			if (!m_config.m_peer_port)
			{
				l.close();
				m_peer_port = m_config.m_peer_port;
			}
			else if (s.open(SOCK_STREAM) != INVALID_SOCKET
				&& !s.bind(htonl(INADDR_ANY), htons(m_config.m_peer_port))
				&& !s.listen())
			{
				l = s;
				m_peer_port = m_config.m_peer_port;
			}
		}
		if (m_config.m_tracker_port != m_tracker_port)
		{
			Csocket s;
			if (!m_config.m_tracker_port)
			{
				lt.close();
				m_tracker_port = m_config.m_tracker_port;
			}
			else if (s.open(SOCK_DGRAM) != INVALID_SOCKET
				&& !s.bind(htonl(INADDR_ANY), htons(m_config.m_tracker_port)))
			{
				lt = s;
				m_tracker_port = m_config.m_tracker_port;
			}
		}
		FD_ZERO(&fd_read_set);
		FD_ZERO(&fd_write_set);
		FD_ZERO(&fd_except_set);
		bool hash = true;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (hash && i->hash())
				hash = false;
		}
		int n = pre_select(&fd_read_set, &fd_write_set, &fd_except_set);
		if (l != INVALID_SOCKET && below_peer_limit())
		{
			FD_SET(l, &fd_read_set);
			n = max(n, static_cast<SOCKET>(l));
		}
		if (la != INVALID_SOCKET)
		{
			FD_SET(la, &fd_read_set);
			n = max(n, static_cast<SOCKET>(la));
		}
		if (lt != INVALID_SOCKET)
		{
			FD_SET(lt, &fd_read_set);
			n = max(n, static_cast<SOCKET>(lt));
		}
		unlock();
		timeval tv;
		tv.tv_sec = hash ? 1 : 0;
		tv.tv_usec = 0;
		if (select(n + 1, &fd_read_set, &fd_write_set, &fd_except_set, &tv) == SOCKET_ERROR)
		{
			alert(Calert(Calert::error, "Server", "select failed: " + Csocket::error2a(WSAGetLastError())));
			continue;
		}
		m_time = ::time(NULL);
		if (0)
		{
#ifdef WIN32
			static ofstream f("/temp/select log.txt");
			f << time();
			f << "\tR:";
			for (size_t i = 0; i < fd_read_set.fd_count; i++)
				f << ' ' << fd_read_set.fd_array[i];
			f << "\tW:";
			for (size_t i = 0; i < fd_write_set.fd_count; i++)
				f << ' ' << fd_write_set.fd_array[i];
			f << "\tE:";
			for (size_t i = 0; i < fd_except_set.fd_count; i++)
				f << ' ' << fd_except_set.fd_array[i];
			f << endl;
#endif
		}
		lock();
		if (l != INVALID_SOCKET && FD_ISSET(l, &fd_read_set))
		{
			sockaddr_in a;
			while (1)
			{
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(l, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						alert(Calert(Calert::error, "Server", "accept failed: " + Csocket::error2a(WSAGetLastError())));
					break;
				}
				else if (!block_list_has(a.sin_addr.s_addr))
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed: " + Csocket::error2a(WSAGetLastError())));
					m_links.push_back(Cbt_link(this, a, s));
				}
			}
		}
		if (la != INVALID_SOCKET && FD_ISSET(la, &fd_read_set))
		{
			sockaddr_in a;
			while (1)
			{
				socklen_t cb_a = sizeof(sockaddr_in);
				Csocket s = accept(la, reinterpret_cast<sockaddr*>(&a), &cb_a);
				if (s == SOCKET_ERROR)
				{
					if (WSAGetLastError() != WSAEWOULDBLOCK)
						alert(Calert(Calert::error, "Server", "accept failed: " + Csocket::error2a(WSAGetLastError())));
					break;
				}
				else
				{
					if (s.blocking(false))
						alert(Calert(Calert::error, "Server", "ioctlsocket failed: " + Csocket::error2a(WSAGetLastError())));
					m_admins.push_back(Cbt_admin_link(this, a, s));
				}
			}
		}
		if (lt != INVALID_SOCKET && FD_ISSET(lt, &fd_read_set))
			m_udp_tracker.recv(lt);
		post_select(&fd_read_set, &fd_write_set, &fd_except_set);
		if (stopping)
		{
			unlock();
			continue;
		}
		if (g_sig_term || !m_run)
		{
			stopping = true;
			save_state(false).save(state_fname());
			for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
				i->close();
		}
		if (time() - m_update_chokes_time > 10)
			update_chokes();
		else if (time() - m_update_states_time > 15)
			update_states();
		else if (time() - m_save_state_time > 60)
			save_state(true).save(state_fname());
		else if (time() - m_run_scheduler_time > 60)
			run_scheduler();
		else if (time() - m_check_remote_links_time > 900)
			check_remote_links();
		unlock();
	}
	config().read().read().save(options_fname());
	unlink(g_pid_fname);
	return 0;
}

int Cserver::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	int n = 0;
	{
		for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); i++)
		{
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	{
		bool hash = true;
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (hash && i->hash())
				hash = false;
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	for (t_http_links::iterator i = m_http_links.begin(); i != m_http_links.end(); i++)
	{
		int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
		n = max(n, z);
	}
	{
		for (t_links::iterator i = m_links.begin(); i != m_links.end(); i++)
		{
			int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
			n = max(n, z);
		}
	}
	return n;
}

void Cserver::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	for (t_admins::iterator i = m_admins.begin(); i != m_admins.end(); )
	{
		if (!i->post_select(fd_read_set, fd_write_set, fd_except_set) && *i)
			i++;
		else
			i = m_admins.erase(i);
	}
	for (t_http_links::iterator i = m_http_links.begin(); i != m_http_links.end(); )
	{
		if (!i->post_select(fd_read_set, fd_write_set, fd_except_set) && *i)
			i++;
		else
			i = m_http_links.erase(i);
	}
	for (t_links::iterator i = m_links.begin(); i != m_links.end(); )
	{
		if (!i->post_select(fd_read_set, fd_write_set, fd_except_set) && *i)
			i++;
		else
			i = m_links.erase(i);
	}
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->post_select(fd_read_set, fd_write_set, fd_except_set);
	if (m_update_send_quotas_time != time())
		m_send_quota = m_config.m_upload_rate;
	m_update_send_quotas_time = time();
	if (m_config.m_upload_rate && !m_send_quota)
		return;
	typedef multimap<int, Cbt_peer_link*> t_links;
	t_links links;
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_hasher)
			continue;
		for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); )
		{
			int q = INT_MAX;
			if (m_config.m_upload_rate)
			{
				if (j->m_can_send && j->cb_write_buffer())
					links.insert(t_links::value_type(j->cb_write_buffer(), &*j));
				j++;
			}
			else if (j->send(q))
			{
				j->close();
				j = i->m_peers.erase(j);
			}
			else
				j++;
		}
	}
	if (!m_config.m_upload_rate)
		return;
	while (m_send_quota && !links.empty())
	{
		int j = links.size();
		int send_quota_left = 0;
		for (t_links::iterator i = links.begin(); i != links.end(); j--)
		{
			int q = min(i->first, m_send_quota / j);
			m_send_quota -= q;
			if (i->second->send(q))
				i->second->close();
			if (q)
				links.erase(i++);
			else
				i++;
			send_quota_left += q;
		}
		m_send_quota += send_quota_left;
	}
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); )
		{
			if (j->m_s == INVALID_SOCKET)
				j = i->m_peers.erase(j);
			else
				j++;
		}
	}
}

void Cserver::stop()
{
	m_run = false;
}

int Cserver::pre_file_dump(const string& id, int flags) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
			return i->pre_dump(flags);
	};
	return 0;
}

void Cserver::file_dump(Cstream_writer& w, const string& id, int flags) const
{
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
		{
			i->dump(w, flags);
			return;
		}
	}
}

int Cserver::pre_dump(int flags) const
{
	int size = 12;
	for (Calerts::const_iterator i = m_alerts.begin(); i != m_alerts.end(); i++)
		size += i->pre_dump();
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		size += i->pre_dump(flags);
	return size;
}

void Cserver::dump(Cstream_writer& w, int flags) const
{
	w.write_int(4, m_alerts.size());
	for (Calerts::const_iterator i = m_alerts.begin(); i != m_alerts.end(); i++)
		i->dump(w);
	w.write_int(4, m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->dump(w, flags);
	w.write_int(4, m_start_time);
}

void Cserver::insert_peer(const char* r, const sockaddr_in& a, const Csocket& s)
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == string(r + hs_info_hash, 20))
			i->insert_peer(r, a, s);
	}
}

void Cserver::lock()
{
#ifdef WIN32
	EnterCriticalSection(&m_cs);
#endif
}

void Cserver::unlock()
{
#ifdef WIN32
	LeaveCriticalSection(&m_cs);
#endif
}

Cvirtual_binary Cserver::get_file_status(const string& id, int flags)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_file_dump(id, flags)));
	file_dump(w, id, flags);
	assert(w.w() == d.data_end());
	return d;
}

Cvirtual_binary Cserver::get_status(int flags)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	Cstream_writer w(d.write_start(pre_dump(flags)));
	dump(w, flags);
	assert(w.w() == d.data_end());
	return d;
}

Cxif_key Cserver::get_block_list()
{
	return m_block_list.save();
}

void Cserver::set_block_list(const Cxif_key& v)
{
	m_block_list.load(v);
}

void Cserver::load_profile(const Cxif_key& v)
{
	load_profile(Cprofile().load(v));
}

void Cserver::load_profile(const Cprofile& v)
{
	seeding_ratio(v.seeding_ratio_enable ? v.seeding_ratio : 0);
	upload_rate(v.upload_rate_enable ? v.upload_rate : 0);
	upload_slots(v.upload_slots_enable ? v.upload_slots : 0);
	peer_limit(v.peer_limit_enable ? v.peer_limit : 0);
	torrent_limit(v.torrent_limit_enable ? v.torrent_limit : 0);
}

Cxif_key Cserver::get_profiles()
{
	return m_profiles.save();
}

void Cserver::set_profiles(const Cxif_key& v)
{
	m_profiles.load(v);
	clean_scheduler();
	m_profiles.save().vdata().save(profiles_fname());
	m_scheduler.save().vdata().save(scheduler_fname());
}

Cxif_key Cserver::get_scheduler()
{
	return m_scheduler.save();
}

void Cserver::set_scheduler(const Cxif_key& v)
{
	m_scheduler.load(v);
	clean_scheduler();
	m_scheduler.save().vdata().save(scheduler_fname());
}

void Cserver::clean_scheduler()
{
	for (Cscheduler::iterator i = m_scheduler.begin(); i != m_scheduler.end(); )
	{
		if (m_profiles.count(i->second.profile))
			i++;
		else
			m_scheduler.erase(i++);
	}
}

void Cserver::run_scheduler()
{
	time_t t0 = m_run_scheduler_time;
	tm t1 = *localtime(&t0);
	t0 = time();
	tm t2 = *localtime(&t0);
	int old_profile = m_run_scheduler_time ? m_scheduler.find_active_profile(hms2i(t1.tm_hour, t1.tm_min, t1.tm_sec)) : -1;
	int new_profile = m_scheduler.find_active_profile(hms2i(t2.tm_hour, t2.tm_min, t2.tm_sec));
	if (new_profile != old_profile)
		load_profile(m_profiles.find(new_profile)->second);
	m_run_scheduler_time = time();
}

Cvirtual_binary Cserver::get_trackers()
{
	Clock l(m_cs);
	return m_tracker_accounts.dump();
}

void Cserver::set_trackers(const Cvirtual_binary& d)
{
	Clock l(m_cs);
	m_tracker_accounts.load(d);
	d.save(trackers_fname());
}

int Cserver::announce(const string& id)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	if (!f)
		return 1;
	f->announce();
	return 0;
}

int Cserver::file_priority(const string& id, int priority)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	if (!f)
		return 1;
	f->priority(priority);
	return 0;
}

int Cserver::file_state(const string& id, Cbt_file::t_state state)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	if (!f)
		return 1;
	f->state(state);
	m_update_states_time = 0;
	return 0;
}

void Cserver::sub_file_priority(const string& file_id, const string& sub_file_id, int priority)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (f)
		f->sub_file_priority(sub_file_id, priority);
}

void Cserver::torrent_seeding_ratio(const string& file_id, bool override, int v)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (!f)
		return;
	f->m_seeding_ratio = max(100, v);
	f->m_seeding_ratio_override = override;
}

void Cserver::torrent_trackers(const string& file_id, const string& v)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (f)
		f->trackers(v);
}

void Cserver::torrent_upload_slots_max(const string& file_id, bool override, int v)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (!f)
		return;
	f->m_upload_slots_max = max(0, v);
	f->m_upload_slots_max_override = override;
}

void Cserver::torrent_upload_slots_min(const string& file_id, bool override, int v)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (!f)
		return;
	f->m_upload_slots_min = max(0, v);
	f->m_upload_slots_min_override = override;
}

void Cserver::torrent_end_mode(const string& file_id, bool v)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(file_id);
	if (f)
		f->m_allow_end_mode = v;
}

string Cserver::get_url(const string& id)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	return f ? f->get_url() : "";
}

int Cserver::open(const Cvirtual_binary& info, const string& name)
{
#ifdef WIN32
	while (!m_run)
		Sleep(100);
#endif
	Clock l(m_cs);
	Cbt_file f;
	f.m_server = this;
	if (f.torrent(info))
		return 1;
	mkpath(torrents_dir());
	info.save(torrents_dir() + '/' + f.m_name + ' ' + hex_encode(f.m_info_hash) + ".torrent");
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == f.m_info_hash)
			return 2;
	}
	if (!name.empty())
		f.m_name = name;
	if (below_torrent_limit())
		f.open();
	m_files.push_back(f);
	save_state(true).save(state_fname());
	return 0;
}

int Cserver::open_url(const string& v)
{
	int a = v.find("://");
	if (a == string::npos || v.substr(0, a) != "xbtp")
		return 1;
	a += 3;
	int b = v.find('/', a);
	if (b == string::npos)
		return 2;
	string trackers = v.substr(a, b++ - a);
	a = v.find('/', b);
	if (a == string::npos)
		return 3;
	string info_hash = hex_decode(v.substr(b, a++ - b));
	if (info_hash.empty())
		return 5;
	string peers = hex_decode(v.substr(a));
#ifdef WIN32
	while (!m_run)
		Sleep(100);
#endif
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != info_hash)
			continue;
		for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
			i->insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
		return 0;
	}
	Cbt_file f;
	f.m_server = this;
	for (a = 0; (b = trackers.find(',', a)) != string::npos; a = b + 1)
	{
		f.m_trackers.push_back(uri_decode(trackers.substr(a, b - a)));
	}
	for (const char* r = peers.c_str(); r + 6 <= peers.c_str() + peers.length(); r += 6)
		f.insert_peer(*reinterpret_cast<const __int32*>(r), *reinterpret_cast<const __int16*>(r + 4));
	f.m_info_hash = info_hash;
	if (below_torrent_limit())
		f.m_state = Cbt_file::s_running;
	m_files.push_back(f);
	save_state(true).save(state_fname());
	return 0;
}

int Cserver::close(const string& id, bool erase)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash != id)
			continue;
		i->close();
		if (erase)
			i->erase();
		m_files.erase(i);
		save_state(true).save(state_fname());
		return 0;
	}
	return 1;
}

void Cserver::load_state(const Cvirtual_binary& d)
{
	Clock l(m_cs);
	Cstream_reader r(d);
	if (d.size() < 8 || r.read_int(4) != g_state_version)
		return;
	for (int c_files = r.read_int(4); c_files--; )
	{
		Cbt_file f;
		f.m_server = this;
		f.load_state(r);
		m_files.push_back(f);
	}
	assert(r.r() == d.data_end());
}

Cvirtual_binary Cserver::save_state(bool intermediate)
{
	Clock l(m_cs);
	Cvirtual_binary d;
	int cb_d = 8;
	{
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
			cb_d += i->pre_save_state(intermediate);
	}
	Cstream_writer w(d.write_start(cb_d));
	w.write_int(4, g_state_version);
	w.write_int(4, m_files.size());
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		i->save_state(w, intermediate);
	assert(w.w() == d.data_end());
	m_save_state_time = time();
	return d;
}

string Cserver::options_fname() const
{
	return local_app_data_dir() + "/options.bin";
}

string Cserver::profiles_fname() const
{
	return local_app_data_dir() + "/profiles.xif";
}

string Cserver::scheduler_fname() const
{
	return local_app_data_dir() + "/scheduler.xif";
}

string Cserver::state_fname() const
{
	return local_app_data_dir() + "/state.bin";
}

string Cserver::trackers_fname() const
{
	return local_app_data_dir() + "/trackers.bin";
}

void Cserver::alert(const Calert& v)
{
	m_alerts.push_back(v);
}

void Cserver::update_chokes()
{
	typedef map<Cbt_file*, pair<int, int> > t_files_limits;
	typedef multimap<int, Cbt_peer_link*, less<int> > t_links0;
	typedef vector<Cbt_peer_link*> t_links1;
	t_files_limits files_limits;
	t_links0 links0;
	t_links1 links1;
	t_links1 links2;
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		for (Cbt_file::t_peers::iterator j = i->m_peers.begin(); j != i->m_peers.end(); j++)
		{
			if (j->m_state != 3)
				continue;
			j->choked(true);
			if (i->state() != Cbt_file::s_running || !j->m_left)
				;
			else if (!m_config.m_upload_slots)
				j->choked(false);
			else if (j->m_down_counter.rate(time()) > 256)
				links0.insert(t_links0::value_type(j->m_down_counter.rate(time()), &*j));
			else if (j->m_remote_interested)
				(j->m_local_interested ? links1 : links2).push_back(&*j);
		}
		files_limits[&*i] = make_pair(i->upload_slots_min(), i->upload_slots_max() ? i->upload_slots_max() : INT_MAX);
	}
	random_shuffle(links1.begin(), links1.end());
	random_shuffle(links2.begin(), links2.end());
	int slots_left = max(4, m_config.m_upload_slots);
	for (int a = 0; a < 3; a++)
	{
		for (t_links0::iterator i = links0.begin(); slots_left && i != links0.end(); i++)
		{
			pair<int, int>& file_limits = files_limits.find(i->second->m_f)->second;
			if (!i->second->m_local_choked_goal
				|| !a && file_limits.first < 1
				|| a == 1 && file_limits.second < 1)
				continue;
			if (i->second->m_remote_interested)
			{
				file_limits.first--;
				file_limits.second--;
				slots_left--;
			}
			i->second->choked(false);
		}
		for (t_links1::const_iterator i = links1.begin(); slots_left && i != links1.end(); i++)
		{
			pair<int, int>& file_limits = files_limits.find((*i)->m_f)->second;
			if (!(*i)->m_local_choked_goal
				|| !a && file_limits.first < 1
				|| a == 1 && file_limits.second < 1)
				continue;
			file_limits.first--;
			file_limits.second--;
			slots_left--;
			(*i)->choked(false);
		}
		for (t_links1::const_iterator i = links2.begin(); slots_left && i != links2.end(); i++)
		{
			pair<int, int>& file_limits = files_limits.find((*i)->m_f)->second;
			if (!(*i)->m_local_choked_goal
				|| !a && file_limits.first < 1
				|| a == 1 && file_limits.second < 1)
				continue;
			file_limits.first--;
			file_limits.second--;
			slots_left--;
			(*i)->choked(false);
		}
	}
	m_update_chokes_time = time();
}

void Cserver::update_states()
{
	while (below_torrent_limit())
	{
		t_files::iterator best = m_files.end();
		for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			if (i->state() == Cbt_file::s_queued && (best == m_files.end() || i->priority() > best->priority() || i->priority() == best->priority() && i->size() < best->size()))
				best = i;
		}
		if (best == m_files.end())
			break;
		best->open();
	}
	m_update_states_time = time();
}

string Cserver::completes_dir() const
{
	return m_config.m_completes_dir;
}

void Cserver::completes_dir(const string& v)
{
	m_config.m_completes_dir = v;
}

string Cserver::incompletes_dir() const
{
	return m_config.m_incompletes_dir;
}

void Cserver::incompletes_dir(const string& v)
{
	m_config.m_incompletes_dir = v;
}

string Cserver::local_app_data_dir() const
{
	return m_config.m_local_app_data_dir;
}

void Cserver::local_app_data_dir(const string& v)
{
	m_config.m_local_app_data_dir = v;
}

string Cserver::torrents_dir() const
{
	return m_config.m_torrents_dir;
}

void Cserver::torrents_dir(const string& v)
{
	m_config.m_torrents_dir = v;
}

bool Cserver::below_peer_limit() const
{
	if (!m_config.m_peer_limit)
		return true;
	int c = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		c += i->m_peers.size();
	return c < m_config.m_peer_limit;
}

bool Cserver::below_torrent_limit() const
{
	if (!m_config.m_torrent_limit)
		return true;
	int c = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		c += i->is_open();
	return c < m_config.m_torrent_limit;
}

void Cserver::sig_handler(int v)
{
	switch (v)
	{
	case SIGTERM:
		g_sig_term = true;
		break;
	}
}

Cbvalue Cserver::admin_request(const Cbvalue& s)
{
	Cbvalue d;
	string action = s.d(bts_action).s();
	if (action == bts_close_torrent)
		close(s.d(bts_hash).s(), false);
	else if (action == bts_erase_torrent)
		close(s.d(bts_hash).s(), true);
	else if (action == bts_get_options)
	{
		d.d(bts_admin_port, admin_port());
		d.d(bts_peer_port, peer_port());
		d.d(bts_tracker_port, tracker_port());
		d.d(bts_upload_rate, upload_rate());
		d.d(bts_upload_slots, upload_slots());
		d.d(bts_seeding_ratio, seeding_ratio());
		d.d(bts_peer_limit, peer_limit());
		d.d(bts_torrent_limit, torrent_limit());
		d.d(bts_user_agent, user_agent());
		d.d(bts_completes_dir, native_slashes(completes_dir()));
		d.d(bts_incompletes_dir, native_slashes(incompletes_dir()));
		d.d(bts_torrents_dir, native_slashes(torrents_dir()));
	}
	else if (action == bts_get_status)
	{
		Cbvalue files(Cbvalue::vt_dictionary);
		for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
		{
			Cbvalue events;
			for (Calerts::const_reverse_iterator j = i->m_alerts.rbegin(); j != i->m_alerts.rend(); j++)
				events.l(Cbvalue().d(bts_time, j->time()).d(bts_message, j->message()));
			Cbvalue file;
			file.d(bts_complete, i->c_seeders());
			file.d(bts_complete_total, i->mc_seeders_total);
			file.d(bts_events, events);
			file.d(bts_incomplete, i->c_leechers());
			file.d(bts_incomplete_total, i->mc_leechers_total);
			file.d(bts_left, i->m_left);
			file.d(bts_priority, i->priority());
			file.d(bts_size, i->m_size);
			file.d(bts_state, i->state());
			file.d(bts_total_downloaded, i->m_total_downloaded);
			file.d(bts_total_uploaded, i->m_total_uploaded);
			file.d(bts_down_rate, i->m_down_counter.rate(time()));
			file.d(bts_up_rate, i->m_up_counter.rate(time()));
			file.d(bts_name, i->m_name);
			file.d(bts_completed_at, i->m_completed_at);
			file.d(bts_started_at, i->m_started_at);
			files.d(i->m_info_hash, file);
		}
		d.d(bts_files, files);
		d.d(bts_version, xbt_version2a(version()));
	}
	else if (action == bts_open_torrent)
		open(Cvirtual_binary(s.d(bts_torrent).s().c_str(), s.d(bts_torrent).s().size()), "");
	else if (action == bts_set_options)
	{
		if (s.d_has(bts_peer_port))
			peer_port(s.d(bts_peer_port).i());
		if (s.d_has(bts_tracker_port))
			tracker_port(s.d(bts_tracker_port).i());
		if (s.d_has(bts_upload_rate))
			upload_rate(s.d(bts_upload_rate).i());
		if (s.d_has(bts_upload_slots))
			upload_slots(s.d(bts_upload_slots).i());
		if (s.d_has(bts_seeding_ratio))
			seeding_ratio(s.d(bts_seeding_ratio).i());
		if (s.d_has(bts_peer_limit))
			peer_limit(s.d(bts_peer_limit).i());
		if (s.d_has(bts_torrent_limit))
			torrent_limit(s.d(bts_torrent_limit).i());
		if (s.d_has(bts_user_agent))
			user_agent(s.d(bts_user_agent).s());
		if (s.d_has(bts_completes_dir))
			completes_dir(s.d(bts_completes_dir).s());
		if (s.d_has(bts_incompletes_dir))
			incompletes_dir(s.d(bts_incompletes_dir).s());
		if (s.d_has(bts_torrents_dir))
			torrents_dir(s.d(bts_torrents_dir).s());
	}
	else if (action == bts_set_priority)
		file_priority(s.d(bts_hash).s(), s.d(bts_priority).i());
	else if (action == bts_set_state)
		file_state(s.d(bts_hash).s(), static_cast<Cbt_file::t_state>(s.d(bts_state).i()));
	return d;
}

void Cserver::term()
{
	g_sig_term = true;
}

int Cserver::version()
{
	return 70;
}

Chttp_link* Cserver::http_request(int h, int p, const string& request, Chttp_response_handler* response_handler)
{
	m_http_links.push_back(Chttp_link(this));
	Chttp_link* l = &m_http_links.back();
	if (!l->set_request(h, p, request, response_handler))
		return l;
	m_http_links.pop_back();
	return NULL;
}

void Cserver::check_remote_links()
{
	m_check_remote_links_time = time();
	int c_local_links = 0;
	int c_remote_links = 0;
	for (t_files::const_iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		c_local_links += i->c_local_links();
		c_remote_links += i->c_remote_links();
	}
	if (c_remote_links || c_local_links < 10)
		return;
	alert(Calert(Calert::warn, "Did you forget to open a port in your firewall or router?"));
	alert(Calert(Calert::warn, n(c_local_links) + " local links, but no remote links have been established."));
}

string Cserver::peer_id_prefix() const
{
	return m_config.m_peer_id_prefix;
}

void Cserver::peer_id_prefix(const string& v)
{
	m_config.m_peer_id_prefix = v;
}

string Cserver::user_agent() const
{
	return m_config.m_user_agent;
}

void Cserver::user_agent(const string& v)
{
	m_config.m_user_agent = v;
}

int Cserver::peer_connect(const string& id, int ipa, int port)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	if (!f)
		return 1;
	f->peer_connect(ipa, port);
	return 0;
}

int Cserver::peer_disconnect(const string& id, int ipa)
{
	Clock l(m_cs);
	Cbt_file* f = find_torrent(id);
	if (!f)
		return 1;
	f->peer_disconnect(ipa);
	return 0;
}

int Cserver::peer_block(int ipa)
{
	Clock l(m_cs);
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
		i->peer_disconnect(ipa);
	m_block_list.insert(ipa);
	return 0;
}

Cbt_file* Cserver::find_torrent(const string& id)
{
	for (t_files::iterator i = m_files.begin(); i != m_files.end(); i++)
	{
		if (i->m_info_hash == id)
			return &*i;
	}
	return NULL;
}
