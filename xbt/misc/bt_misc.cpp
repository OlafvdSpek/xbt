// bt_misc.cpp: implementation of the Cbt_misc class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_misc.h"

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

string n(int v)
{
	char b[12];
	sprintf(b, "%d", v);
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

string b2a(__int64 v)
{
	int l;
	for (l = 0; v < -9999 || v > 9999; l++)
		v >>= 10;
	const char* a[] = {"", " k", " m", " g", " t", " p"};
	return n(v) + a[l];
}

string peer_id2a(const string& v)
{
	if (v.length() != 20)
		return hex_encode(v);
	switch (v[0])
	{
	case 0:
		{
			int i = v.find_first_not_of('\0');
			if (i != string::npos)
				return i < 20 ? "0 - " + hex_encode(v.substr(i)) : "0";
		}
		break;
	case '-':
		if (v[1] == 'A' && v[2] == 'Z' && v[7] == '-')
		{
			int i;
			for (i = 3; i < 7; i++)
			{
				if (!isdigit(v[i]))
					break;
			}
			return "Azureus " + v.substr(3, i - 3) + " - " + hex_encode(v.substr(i));
		}
		break;
	case 'S':
		if (v[1] == 5 && v[2] == 7 && v[3] >= 0 && v[3] < 10)
			return "Shadow 57" + n(v[3]) + " - " + hex_encode(v.substr(4));
		else if (v[1] == '5' && v[7] == '-')
		{
			int i;
			for (i = 1; i < 7; i++)
			{
				if (!isalnum(v[i]))
					break;
			}
			return "Shadow " + v.substr(1, i - 1) + " - " + hex_encode(v.substr(i));
		}
		break;
	case 'T':
		if (v[7] == '-')
		{
			int i;
			for (i = 1; i < 7; i++)
			{
				if (!isalnum(v[i]))
					break;
			}
			return "BitTornado " + v.substr(1, i - 1) + " - " + hex_encode(v.substr(i));
		}
		break;
	case 'X':
		if (v[1] == 'B' && v[2] == 'T')
		{
			int i;
			for (i = 3; i < 6; i++)
			{
				if (!isalnum(v[i]))
					break;
			}
			return "XBT Client " + v.substr(3, i - 3) + " - " + hex_encode(v.substr(i));
		}
		break;
	}
	return hex_encode(v);
}
