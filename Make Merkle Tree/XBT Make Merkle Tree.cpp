/*
	XBT Make Merkle Tree
	Olaf van der Spek
	olafvdspek@gmail.com
	http://sourceforge.net/projects/xbtt/
	http://open-content.net/specs/draft-jchapweske-thex-02.html

	This application creates a merkle tree with a 1024 byte segment size from an input file.
	The tree is created bottom up and written to stdout.
*/

#include <cstdint>
#include <cstring>
#include <ctime>
#include <fstream>
#include <iostream>
#include <list>
#include <map>
#include <sha1.h>
#include <socket.h>
#include <vector>
#include <xbt/bt_misc.h>
#include <xbt/virtual_binary.h>

typedef std::map<int, std::string> t_map;

int main(int argc, char* argv[])
{
	if (argc < 2)
	{
		std::cerr << "Usage: " << argv[0] << " <file>" << std::endl;
		return 1;
	}
	FILE* f = fopen(argv[1], "rb");
	if (!f)
	{
		std::cerr << "Unable to open " << argv[1] << std::endl;
		return 1;
	}
	t_map map;
	char d[1025];
	int cb_d;
	while (cb_d = fread(d + 1, 1, 1024, f))
	{
		// calculate hash of next leaf node (one 0 byte and up to 1024 data bytes)
		*d = 0;
		std::string h = Csha1(data_ref(d, cb_d + 1)).read();
		std::cout << "0: " << hex_encode(h) << std::endl;
		// combine two hashes on the same tree level for one hash on the next level
		*d = 1;
		int i;
		for (i = 0; map.find(i) != map.end(); i++)
		{
			// calculate hash of the next intermediate node (one 1 byte and two hashes)
			memcpy(d + 1, map.find(i)->second.c_str(), 20);
			memcpy(d + 21, h.c_str(), 20);
			h = Csha1(data_ref(d, 41)).read();
			std::cout << i + 1 << ": " << hex_encode(h) << std::endl;
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
			std::cout << i + 1 << ": " << hex_encode(std::string(d + 21, 20)) << std::endl;
		memcpy(d + 1, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		map[0] = Csha1(data_ref(d, 41)).read();
		std::cout << i + 1 << ": " << hex_encode(map[0]) << std::endl;
	}
	return 0;
}
