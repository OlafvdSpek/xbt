// bt_misc.h: interface for the Cbt_misc class.
//
//////////////////////////////////////////////////////////////////////

#if !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
#define AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>
#include "sha1.h"
#include "virtual_binary.h"

using namespace std;

string b2a(__int64 v, const char* postfix = NULL);
string escape_string(const string& v);
bool is_private_ipa(int a);
string n(__int64 v);
string hex_decode(const string&);
string hex_encode(int l, int v);
string hex_encode(const string& v);
string peer_id2a(const string& v);
string uri_decode(const string& v);
string uri_encode(const string& v);

inline string compute_sha1(const void* s, int cb_s)
{
	char d[20];
	compute_sha1(s, cb_s, d);
	return string(d, 20);
}

inline void compute_sha1(const Cvirtual_binary& s, void* d)
{
	compute_sha1(s, s.size(), d);
}

inline string compute_sha1(const Cvirtual_binary& s)
{
	return compute_sha1(s, s.size());
}

inline __int64 htonll(__int64 v)
{
	const unsigned char* a = reinterpret_cast<const unsigned char*>(&v);
	__int64 b = a[0] << 24 | a[1] << 16 | a[2] << 8 | a[3];
	return b << 32 | a[4] << 24 | a[5] << 16 | a[6] << 8 | a[7];
}

inline __int64 ntohll(__int64 v)
{
	return htonll(v);
}

enum
{
	uta_connect,
	uta_announce,
	uta_scrape,
	uta_error,
};

#ifdef _MSC_VER
#pragma pack(push, 1)
#else
#pragma pack(1)
#endif

struct t_udp_tracker_input
{
	__int64 m_connection_id;

	int action() const
	{
		return ntohl(m_action);
	}

	void action(int v)
	{
		m_action = htonl(v);
	}

	int transaction_id() const
	{
		return m_transaction_id;
	}

	void transaction_id(int v)
	{
		m_transaction_id = v;
	}
private:
	int m_action;
	int m_transaction_id;
};

struct t_udp_tracker_input_connect: t_udp_tracker_input
{
};

struct t_udp_tracker_input_announce: t_udp_tracker_input
{
	char m_info_hash[20];
	char m_peer_id[20];

	__int64 downloaded() const
	{
		return ntohll(m_downloaded);
	}

	void downloaded(__int64 v)
	{
		m_downloaded = htonll(v);
	}

	int event() const
	{
		return ntohl(m_event);
	}

	void event(int v)
	{
		m_event = htonl(v);
	}

	string info_hash() const
	{
		return string(m_info_hash, 20);
	}

	int ipa() const
	{
		return m_ipa;
	}

	void ipa(int v)
	{
		m_ipa = v;
	}

	int num_want() const
	{
		return ntohl(m_num_want);
	}

	void num_want(int v)
	{
		m_num_want = htonl(v);
	}

	__int64 left() const
	{
		return ntohll(m_left);
	}

	void left(__int64 v)
	{
		m_left = htonll(v);
	}

	string peer_id() const
	{
		return string(m_peer_id, 20);
	}

	int port() const
	{
		return m_port;
	}

	void port(int v)
	{
		m_port = v;
	}

	__int64 uploaded() const
	{
		return ntohll(m_uploaded);
	}

	void uploaded(__int64 v)
	{
		m_uploaded = htonll(v);
	}
private:
	__int64 m_downloaded;
	__int64 m_left;
	__int64 m_uploaded;
	int m_event;
	int m_ipa;
	int m_num_want;
	short m_port;
};

struct t_udp_tracker_input_scrape: t_udp_tracker_input
{
};

struct t_udp_tracker_output
{
	int action() const
	{
		return ntohl(m_action);
	}

	void action(int v)
	{
		m_action = htonl(v);
	}

	int transaction_id() const
	{
		return m_transaction_id;
	}

	void transaction_id(int v)
	{
		m_transaction_id = v;
	}
private:
	int m_action;
	int m_transaction_id;
};

struct t_udp_tracker_output_connect: t_udp_tracker_output
{
	__int64 m_connection_id;
};

struct t_udp_tracker_output_announce: t_udp_tracker_output
{
	int interval() const
	{
		return ntohl(m_interval);
	}

	void interval(int v)
	{
		m_interval = htonl(v);
	}

	int leechers() const
	{
		return ntohl(m_leechers);
	}

	void leechers(int v)
	{
		m_leechers = htonl(v);
	}

	int seeders() const
	{
		return ntohl(m_seeders);
	}

	void seeders(int v)
	{
		m_seeders = htonl(v);
	}
private:
	int m_interval;
	int m_leechers;
	int m_seeders;
};

struct t_udp_tracker_output_scrape: t_udp_tracker_output
{
};

struct t_udp_tracker_output_error: t_udp_tracker_output
{
};

struct t_udp_tracker_output_file
{
	int complete() const
	{
		return ntohl(m_complete);
	}

	void complete(int v)
	{
		m_complete = htonl(v);
	}

	int downloaded() const
	{
		return ntohl(m_downloaded);
	}

	void downloaded(int v)
	{
		m_downloaded = htonl(v);
	}

	int incomplete() const
	{
		return ntohl(m_incomplete);
	}

	void incomplete(int v)
	{
		m_incomplete = htonl(v);
	}
private:
	int m_complete;
	int m_downloaded;
	int m_incomplete;
};

struct t_udp_tracker_output_peer
{
	int host() const
	{
		return m_host;
	}

	void host(int v)
	{
		m_host = v;
	}

	int port() const
	{
		return m_port;
	}

	void port(int v)
	{
		m_port = v;
	}
private:
	int m_host;
	short m_port;
};

struct t_bt_handshake
{
	string info_hash() const
	{
		return string(m_info_hash, 20);
	}

	void info_hash(const string& v)
	{
		assert(v.length() == 20);
		memcpy(m_info_hash, v.c_str(), 20);
	}

	unsigned char cb_name;
	char name[19];
	char reserved[8];
private:
	char m_info_hash[20];
};

#ifdef _MSC_VER
#pragma pack(pop, 1)
#endif

#endif // !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
