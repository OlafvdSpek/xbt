/*
	XBT Make Torrent
	Olaf van der Spek
	OvdSpek@LIACS.NL
	http://sourceforge.net/projects/xbtt/
	http://xbtt.sourceforge.net/udp_tracker_protocol.html
	http://open-content.net/specs/draft-jchapweske-thex-02.html

	This application creates a gzipped merkle torrent from an input file or directory.
	The code also contains a path for standard non-merkle (v1) torrents.
*/

#include "stdafx.h"

#include <boost/algorithm/string.hpp>
#include <sys/stat.h>
#include "bt_strings.h"

struct t_map_entry
{
	std::string name;
	long long size;
};

typedef std::map<int, t_map_entry> t_map;

t_map g_map;
std::string g_name;

static std::string base_name(const std::string& v)
{
	int i = v.rfind('/');
	int j = v.rfind('\\');
	if (i == std::string::npos)
		return j == std::string::npos ? v : v.substr(j + 1);
	return j == std::string::npos ? v.substr(i + 1) : v.substr(max(i, j) + 1);
}

static Cvirtual_binary gzip(const Cvirtual_binary& s)
{
	// gzip input if it results in a smaller size
	Cvirtual_binary d = xcc_z::gzip(s);
	return d.size() < s.size() ? d : s;
}

void insert(const std::string& name)
{
	struct _stati64 b;
	if (boost::iequals(base_name(name), "desktop.ini")
		|| boost::iequals(base_name(name), "thumbs.db")
		|| _stati64(name.c_str(), &b))
		return;
	if (g_map.empty())
		g_name = base_name(name).c_str();
	if (b.st_mode & S_IFDIR)
	{
		// name is a directory, so add it's contents
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
	// don't add empty files
	if (!b.st_size)
		return;
	int id = g_map.empty() ? 0 : g_map.rbegin()->first + 1;
	t_map_entry& e = g_map[id];
	e.name = name;
	e.size = b.st_size;
}

int main(int argc, char* argv[])
{
	time_t t = time(NULL);
	if (argc < 2)
	{
		std::cerr << "Usage: " << argv[0] << " <file> <tracker> [--v1]" << std::endl;
		return 1;
	}
	std::string tracker = argc >= 3 ? argv[2] : "udp://localhost:2710";
	bool use_merkle = argc >= 4 ? strcmp(argv[3], "--v1") : true; // set to false for a non-merkle torrent
	insert(argv[1]);
	// use 1 mbyte pieces by default
	int cb_piece = 1 << 20;
	if (!use_merkle)
	{
		// find optimal piece size for a non-merkle torrent
		long long cb_total = 0;
		for (t_map::const_iterator i = g_map.begin(); i != g_map.end(); i++)
			cb_total += i->second.size;
		cb_piece = 256 << 10;
		while (cb_total / cb_piece > 4 << 10)
			cb_piece <<= 1;
	}
	Cbvalue files;
	std::string pieces;
	Cvirtual_binary d;
	byte* w = d.write_start(cb_piece);
	for (t_map::const_iterator i = g_map.begin(); i != g_map.end(); i++)
	{
		int f = open(i->second.name.c_str(), O_BINARY | O_RDONLY);
		if (!f)
			continue;
		long long cb_f = 0;
		std::string merkle_hash;
		int cb_d;
		if (use_merkle)
		{
			// calculate merkle root hash as explained in XBT Make Merkle Tree.cpp
			typedef std::map<int, std::string> t_map;

			t_map map;
			char d[1025];
			while (cb_d = read(f, d + 1, 1024))
			{
				if (cb_d < 0)
					break;
				*d = 0;
				std::string h = Csha1(const_memory_range(d, cb_d + 1)).read();
				*d = 1;
				int i;
				for (i = 0; map.find(i) != map.end(); i++)
				{
					memcpy(d + 1, map.find(i)->second.c_str(), 20);
					memcpy(d + 21, h.c_str(), 20);
					h = Csha1(const_memory_range(d, 41)).read();
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
				map[0] = Csha1(const_memory_range(d, 41)).read();
			}
			if (!map.empty())
				merkle_hash = map.begin()->second;
		}
		else
		{
			// calculate piece hashes
			while (cb_d = read(f, w, d.end() - w))
			{
				if (cb_d < 0)
					break;
				w += cb_d;
				if (w == d.end())
				{
					pieces += Csha1(const_memory_range(d, w - d)).read();
					w = d.data_edit();
				}
				cb_f += cb_d;
			}
		}
		close(f);
		// add file to files key
		files.l(merkle_hash.empty()
			? Cbvalue().d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(i->second.name)))
			: Cbvalue().d(bts_merkle_hash, merkle_hash).d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(i->second.name))));
	}
	if (w != d)
		pieces += Csha1(const_memory_range(d, w - d)).read();
	Cbvalue info;
	info.d(bts_piece_length, cb_piece);
	if (!pieces.empty())
		info.d(bts_pieces, pieces);
	if (g_map.size() == 1)
	{
		// single-file torrent
		if (use_merkle)
			info.d(bts_merkle_hash, files.l().front()[bts_merkle_hash]);
		info.d(bts_length, files.l().front()[bts_length]);
		info.d(bts_name, files.l().front()[bts_path].l().front());
	}
	else
	{
		// multi-file torrent
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
	std::cout << time(NULL) - t << " s" << std::endl;
	return 0;
}
