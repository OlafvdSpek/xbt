#include "stdafx.h"

#include <sys/stat.h>
#include "bt_strings.h"

struct t_map_entry
{
	string name;
	__int64 size;
};

typedef map<int, t_map_entry> t_map;

t_map g_map;
string g_name;

static string base_name(const string& v)
{
	int i = v.rfind('\\');
	return i == string::npos ? v : v.substr(i + 1);
}

static Cvirtual_binary gzip(const Cvirtual_binary& s)
{
	Cvirtual_binary d = xcc_z::gzip(s);
	return d.size() < s.size() ? d : s;
}

void insert(const string& name)
{
	struct _stati64 b;
	if (!stricmp(base_name(name).c_str(), "desktop.ini")
		|| !stricmp(base_name(name).c_str(), "thumbs.db")
		|| _stati64(name.c_str(), &b))
		return;
	if (g_map.empty())
		g_name = base_name(name).c_str();
	if (b.st_mode & _S_IFDIR)
	{
		WIN32_FIND_DATA finddata;
		HANDLE findhandle = FindFirstFile((name + "\\*").c_str(), &finddata);
		if (findhandle != INVALID_HANDLE_VALUE)
		{
			do
			{
				if (*finddata.cFileName != '.')
					insert(name + "\\" + finddata.cFileName);
			}
			while (FindNextFile(findhandle, &finddata));
			FindClose(findhandle);
		}
		return;
	}
	if (!b.st_size)
		return;
	int id = g_map.empty() ? 0 : g_map.rbegin()->first + 1;
	t_map_entry& e = g_map[id];
	e.name = name;
	e.size = b.st_size;
}

int main(int argc, char* argv[])
{
	if (argc < 2)
	{
		cerr << "Usage: " << argv[0] << " <file> <tracker>" << endl;
		return 1;
	}
	string tracker = argc >= 3 ? argv[2] : "udp://localhost:2710";
	bool use_merkle = true;
	insert(argv[1]);
	int cb_piece = 1 << 20;
	if (!use_merkle)
	{
		__int64 cb_total = 0;
		for (t_map::const_iterator i = g_map.begin(); i != g_map.end(); i++)
			cb_total += i->second.size;
		cb_piece = 256 << 10;
		while (cb_total / cb_piece > 4 << 10)
			cb_piece <<= 1;
	}
	Cbvalue files;
	string pieces;
	Cvirtual_binary d;
	byte* w = d.write_start(cb_piece);
	for (t_map::const_iterator i = g_map.begin(); i != g_map.end(); i++)
	{
		int f = _open(i->second.name.c_str(), _O_BINARY | _O_RDONLY);
		if (!f)
			continue;
		__int64 cb_f = 0;
		string merkle_hash;
		int cb_d;
		if (use_merkle)
		{
			typedef map<int, string> t_map;

			t_map map;
			char d[1025];
			while (cb_d = _read(f, d + 1, 1024))
			{
				if (cb_d < 0)
					break;
				*d = 0;
				string h = Csha1(d, cb_d + 1).read();
				*d = 1;
				int i;
				for (i = 0; map.find(i) != map.end(); i++)
				{
					memcpy(d + 1, map.find(i)->second.c_str(), 20);
					memcpy(d + 21, h.c_str(), 20);
					h = Csha1(d, 41).read();
					map.erase(i);
				}
				map[i] = h;
				cb_f += cb_d;
			}
			*d = 1;
			while (map.size() > 1)
			{
				memcpy(d + 21, map.begin()->second.c_str(), 20);
				map.erase(map.begin());
				memcpy(d + 1, map.begin()->second.c_str(), 20);
				map.erase(map.begin());
				map[0] = Csha1(d, 41).read();
			}
			if (!map.empty())
				merkle_hash = map.begin()->second;
		}
		else
		{
			while (cb_d = _read(f, w, d.data_end() - w))
			{
				if (cb_d < 0)
					break;
				w += cb_d;
				if (w == d.data_end())
				{
					pieces += Csha1(d, w - d).read();
					w = d.data_edit();
				}
				cb_f += cb_d;
			}
		}
		_close(f);
		files.l(merkle_hash.empty()
			? Cbvalue().d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(i->second.name)))
			: Cbvalue().d(bts_merkle_hash, merkle_hash).d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(i->second.name))));
	}
	if (w != d)
		pieces += Csha1(d, w - d).read();
	Cbvalue info;
	info.d(bts_piece_length, cb_piece);
	if (!pieces.empty())
		info.d(bts_pieces, pieces);
	if (g_map.size() == 1)
	{
		if (use_merkle)
			info.d(bts_merkle_hash, files.l().front().d(bts_merkle_hash));
		info.d(bts_length, files.l().front().d(bts_length));
		info.d(bts_name, files.l().front().d(bts_path).l().front());
	}
	else
	{
		info.d(bts_files, files);
		info.d(bts_name, g_name);
	}
	Cbvalue torrent;
	torrent.d(bts_announce, tracker);
	torrent.d(bts_info, info);
	Cvirtual_binary s = torrent.read();
	if (use_merkle)
		s = gzip(s);
	s.save(g_name + ".torrent");
	return 0;
}
