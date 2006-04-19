#if !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
#define AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>
#include "sha1.h"
#include "virtual_binary.h"

using namespace std;

string b2a(long long v, const char* postfix = NULL);
string backward_slashes(string);
string duration2a(float);
string escape_string(const string&);
string forward_slashes(string);
string get_env(const string&);
int hms2i(int h, int m, int s);
bool is_private_ipa(int a);
int merkle_tree_size(int v);
int mkpath(const string&);
string n(long long);
string native_slashes(const string&);
string hex_decode(const string&);
string hex_encode(int l, int v);
string hex_encode(const string&);
string js_encode(const string&);
string peer_id2a(const string&);
string time2a(time_t);
string uri_decode(const string&);
string uri_encode(const string&);
long long xbt_atoll(const char*);
string xbt_version2a(int);

inline string compute_sha1(const void* s, int cb_s)
{
	return Csha1(s, cb_s).read();
}

inline string compute_sha1(const Cvirtual_binary& s)
{
	return compute_sha1(s, s.size());
}

inline long long htonll(long long v)
{
	const unsigned char* a = reinterpret_cast<const unsigned char*>(&v);
	long long b = a[0] << 24 | a[1] << 16 | a[2] << 8 | a[3];
	return b << 32 | static_cast<long long>(a[4]) << 24 | a[5] << 16 | a[6] << 8 | a[7];
}

inline long long ntohll(long long v)
{
	return htonll(v);
}

enum
{
	hs_name_size = 0,
	hs_name = 1,
	hs_reserved = 20,
	hs_info_hash = 28,
	hs_size = 48,
};

enum
{
	uta_connect,
	uta_announce,
	uta_scrape,
	uta_error,
};

enum
{
	uti_connection_id = 0,
	uti_action = 8,
	uti_transaction_id = 12,
	uti_size = 16,

	utic_size = 16,

	utia_info_hash = 16,
	utia_peer_id = 36,
	utia_downloaded = 56,
	utia_left = 64,
	utia_uploaded = 72,
	utia_event = 80,
	utia_ipa = 84,
	utia_key = 88,
	utia_num_want = 92,
	utia_port = 96,
	utia_size = 98,

	utis_size = 16,

	uto_action = 0,
	uto_transaction_id = 4,
	uto_size = 8,

	utoc_connection_id = 8,
	utoc_size = 16,

	utoa_interval = 8,
	utoa_leechers = 12,
	utoa_seeders = 16,
	utoa_size = 20,

	utos_size = 8,

	utoe_size = 8,
};

#endif // !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
