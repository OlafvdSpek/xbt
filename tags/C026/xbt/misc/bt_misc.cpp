// bt_misc.cpp: implementation of the Cbt_misc class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_misc.h"

#include "socket.h"

string escape_string(const string& v)
{
	string w;
	w.reserve(v.length());
	for (int i = 0; i < v.length(); i++)
	{
		if (isgraph(v[i]))
			w += v[i];
		else
		{
			switch (v[i])
			{
			case '\0':
				w += "\\0";
				break;
			default:
				w += "\\x" + hex_encode(2, v[i]);
			}
		}
	}
	return w;
}

static int hex_decode(char v)
{
	if (v >= '0' && v <= '9')
		return v - '0';
	if (v >= 'A' && v <= 'Z')
		return v - 'A' + 10;
	if (v >= 'a' && v <= 'z')
		return v - 'a' + 10;
	return -1;
};

string hex_decode(const string& v)
{
	string r;
	r.resize(v.length() >> 1);
	for (int i = 0; i + 2 <= v.length(); i += 2)
	{
		int a = hex_decode(v[i]);
		r[i >> 1] = a << 4 | hex_decode(v[i + 1]);
	}
	return r;
}

string hex_encode(int l, int v)
{
	string r;
	r.resize(l);
	while (l--)
	{
		r[l] = "0123456789abcdef"[v & 0xf];
		v >>= 4;
	}
	return r;
};

string n(__int64 v)
{
	char b[21];
#ifdef WIN32
	sprintf(b, "%I64d", v);
#else
	sprintf(b, "%lld", v);
#endif
	return b;
}

string hex_encode(const string& v)
{
	string r;
	r.reserve(v.length() << 1);
	for (int i = 0; i < v.length(); i++)
		r += hex_encode(2, v[i]);
	return r;
}

string uri_decode(const string& v)
{
	string r;
	r.reserve(v.length());
	for (int i = 0; i < v.length(); i++)
	{
		char c = v[i];
		switch (c)
		{
		case '%':
			{
				if (i + 1 > v.length())
					return "";
				int l = v[++i];
				r += hex_decode(l) << 4 | hex_decode(v[++i]);
				break;
			}
		case '+':
			r += ' ';
			break;
		default:
			r += c;
		}
	}
	return r;
};

string uri_encode(const string& v)
{
	string r;
	r.reserve(v.length());
	for (int i = 0; i < v.length(); i++)
	{
		char c = v[i];
		if (isalpha(c & 0xff) || isdigit(c & 0xff))
			r += c;
		else
		{
			switch (c)
			{
			case ' ':
				r += '+';
				break;
			case '-':
			case ',':
			case '.':
			case '@':
			case '_':
				r += c;
				break;
			default:
				r += "%" + hex_encode(2, c);
			}
		}
	}
	return r;
};

bool is_private_ipa(int a)
{
	return (ntohl(a) & 0xff000000) == 0x0a000000
		|| (ntohl(a) & 0xff000000) == 0x7f000000
		|| (ntohl(a) & 0xfff00000) == 0xac100000
		|| (ntohl(a) & 0xffff0000) == 0xc0a80000;
}

string b2a(__int64 v, const char* postfix)
{
	int l;
	for (l = 0; v < -9999 || v > 9999; l++)
		v >>= 10;
	const char* a[] = {"", " k", " m", " g", " t", " p"};
	if (postfix)
		return n(v) + (l ? a[l] : " ") + postfix;
	return n(v) + a[l];
}

static string peer_id2a(const string& name, const string& peer_id, int i)
{
	for (int j = i; j < peer_id.size(); j++)
	{
		if (!isalnum(peer_id[j]))
			return name + peer_id.substr(i, j - i);
	}
	return name + peer_id.substr(i);
}

string peer_id2a(const string& v)
{
	if (v.length() != 20)
		return "";
	if (v[7] == '-')
	{
		switch (v[0])
		{
		case '-':
			if (v[1] == 'A' && v[2] == 'Z')
				return peer_id2a("Azureus ", v, 3);
			if (v[1] == 'T' && v[2] == 'S')
				return peer_id2a("TorrentStorm ", v, 3);
			break;
		case 'A':
			return peer_id2a("ABC ", v, 1);
		case 'M':
			return peer_id2a("Mainline ", v, 1);
		case 'S':
			return peer_id2a("Shadow ", v, 1);
		case 'T':
			return peer_id2a("BitTornado ", v, 1);
		case 'X':
			if (v[1] == 'B' && v[2] == 'T')
				return peer_id2a("XBT Client ", v, 3);
			break;
		}
	}
	switch (v[0])
	{
	case '-':
		if (v[1] == 'G' && v[2] == '3')
			return "G3";
		break;
	case 'S':
		if (v[1] == 5 && v[2] == 7 && v[3] >= 0 && v[3] < 10)
			return "Shadow 57" + n(v[3]);
		break;
	case 'e':
		if (v[1] == 'x' && v[2] == 'b' && v[3] == 'c' && v[4] >= 0 && v[4] < 10 && v[5] >= 0 && v[5] < 100)
			return "BitComet " + n(v[4]) + '.' + n(v[5] / 10) + n(v[5] % 10);
	}
	return "Unknown";
}

int merkle_tree_size(int v)
{
	int r = 0;
	while (v > 1)
	{
		r += v++;
		v >>= 1;
	}
	if (v == 1)
		r++;
	return r;
}
