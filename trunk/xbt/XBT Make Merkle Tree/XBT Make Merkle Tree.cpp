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
		*d = 0;
		string h = Csha1(d, cb_d + 1).read();
		cout << "0: " << hex_encode(h) << endl;
		*d = 1;
		int i;
		for (i = 0; map.find(i) != map.end(); i++)
		{
			memcpy(d + 1, map.find(i)->second.c_str(), 20);
			memcpy(d + 21, h.c_str(), 20);
			h = Csha1(d, 41).read();
			cout << i + 1 << ": " << hex_encode(h) << endl;
			map.erase(i);
		}
		map[i] = h;
	}
	fclose(f);
	*d = 1;
	for (int i = 0; map.size() > 1; i++)
	{

		memcpy(d + 21, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		for (; map.begin()->first != i; i++)
			cout << i + 1 << ": " << hex_encode(string(d + 21, 20)) << endl;
		memcpy(d + 1, map.begin()->second.c_str(), 20);
		map.erase(map.begin());
		map[0] = Csha1(d, 41).read();
		cout << i + 1 << ": " << hex_encode(map[0]) << endl;
	}
	if (0 && !map.empty())
		cout << hex_encode(map.begin()->second) << endl;
	return 0;
}
