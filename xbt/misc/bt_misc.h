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

inline void compute_sha1(const Cvirtual_binary& s, void* d)
{
	Csha1(s, s.size()).read(d);
}

inline string compute_sha1(const Cvirtual_binary& s)
{
	return Csha1(s, s.size()).read();
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

#endif // !defined(AFX_BT_MISC_H__C8A447CF_4F41_429E_9437_55453B6A85D8__INCLUDED_)
