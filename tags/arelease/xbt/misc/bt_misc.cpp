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

