/*
	XBT Make Merkle Tree
	Olaf van der Spek
	OvdSpek@LIACS.NL
	http://sourceforge.net/projects/xbtt/
	http://open-content.net/specs/draft-jchapweske-thex-02.html

	This application creates a merkle tree with a 1024 byte segment size from an input file.
	The tree is created bottom up and written to stdout.
*/

#include "stdafx.h"

#include "bt_misc.h"
#include "sha1.h"

typedef map<int, string> t_map;

int main(int argc, char* argv[])
{
	if (argc < 2)
	{
		cerr << "Usage: " << argv[0] << " <file>" << endl;
		return 1;
	}
	FILE* f = fopen(argv[1], "rb");
	if (!f)
	{
		cerr << "Unable to open " << argv[1] << endl;
		return 1;
	}
	t_map map;
	char d[1025];
	int cb_d;
	while (cb_d = fread(d + 1, 1, 1024, f))
	{
		// calculate hash of next leaf node (one 0 byte and up to 1024 data bytes)
		*d = 0;
		string h = Csha1(d, cb_d + 1).read();
		cout << "0: " << hex_encode(h) << endl;
		// combine two hashes on the same tree level for one hash on the next level
		*d = 1;
		int i;
		for (i = 0; map.find(i) != map.end(); i++)
		{
			// calculate hash of the next intermediate node (one 1 byte and two hashes)
			memcpy(d + 1, map.find(i)->second.c_str(), 20);
			memcpy(d + 21, h.c_str(), 20);
			h = Csha1(d, 41).read();
			cout << i + 1 << ": " << hex_encode(h) << endl;
			map.erase(i);
		}
		// store hash
		map[i] = h;
	}
	fclose(f);
	// combine hashes until only the root hash remains
	*d = 1;
	for (int i = 0; map.size() > 1; i++)
	{

		memcpy(d + 21, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		// promote hash to the next level if there's only one hash on the current level
		for (; map.begin()->first != i; i++)
			cout << i + 1 << ": " << hex_encode(string(d + 21, 20)) << endl;
		memcpy(d + 1, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		map[0] = Csha1(d, 41).read();
		cout << i + 1 << ": " << hex_encode(map[0]) << endl;
	}
	return 0;
}
