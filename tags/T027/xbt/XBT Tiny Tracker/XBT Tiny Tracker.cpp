#include <ctime>
#include <map>
#include <string>
#ifdef WIN32
#include <windows.h>

typedef int socklen_t;
#else
#include <netinet/in.h>
#include <sys/socket.h>

typedef int SOCKET;
#endif

struct t_peer
{
	int port;
	time_t mtime;
};

typedef std::map<int, t_peer> t_peers;

int main()
{
	SOCKET s = socket(PF_INET, SOCK_DGRAM, 0);
	sockaddr_in a;
	a.sin_family = AF_INET;
	a.sin_addr.s_addr = INADDR_ANY;
	a.sin_port = htons(2710);
	bind(s, reinterpret_cast<sockaddr*>(&a), sizeof(sockaddr_in));
	std::map<std::string, t_peers> files;
	while (1)
	{
		const int cb_b = 2 << 10;
		char b[cb_b];
		socklen_t cb_a = sizeof(sockaddr_in);
		int r = recvfrom(s, b, cb_b, 0, reinterpret_cast<sockaddr*>(&a), &cb_a);
		if (r < 94 || b[8] || b[9] || b[10] || b[11] != 1)
			continue;
		t_peers& peers = files[std::string(b + 16, 20)];
		t_peer& peer = peers[a.sin_addr.s_addr];
		memcpy(&peer.port, b + 92, 2);
		peer.mtime = time(NULL);
		char d[2 << 10];
		memcpy(d, b + 8, 8);
		d[8] = d[9] = 0;
		d[10] = 7;
		d[11] = 8;
		char* w = d + 12;
		int c = 200;
		for (t_peers::const_iterator i = peers.begin(); c-- && i != peers.end(); i++, w += 6)
		{
			memcpy(w, &i->first, 4);
			memcpy(w + 4, &i->second.port, 2);
		}
		sendto(s, d, w - d, 0, reinterpret_cast<sockaddr*>(&a), cb_a);
	}
	return 0;
}
