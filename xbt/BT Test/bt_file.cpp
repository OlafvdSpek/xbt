// bt_file.cpp: implementation of the Cbt_file class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_file.h"

#include "../misc/bt_strings.h"

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_file::Cbt_file()
{
}

Cbt_file::~Cbt_file()
{
}

int Cbt_file::info(const Cvirtual_binary& v)
{
	Cbvalue a;
	return a.write(v) || info(a);
}

int Cbt_file::info(const Cbvalue& v)
{
	m_trackers.push_back(v.d(bts_announce).s());
	const Cbvalue& info = v.d(bts_info);
	m_name = info.d(bts_name).s();
	m_info_hash = compute_sha1(info.read());
	mcb_piece = info.d(bts_piece_length).i();
	{
		mcb_f = 0;
		const Cbvalue::t_list& files = info.d(bts_files).l();
		for (Cbvalue::t_list::const_iterator i = files.begin(); i != files.end(); i++)
		{
			string name;
			{
				const Cbvalue::t_list& path = i->d(bts_path).l();
				for (Cbvalue::t_list::const_iterator i = path.begin(); i != path.end(); i++)
					name += i->s();
			}
			int size = i->d(bts_length).i();
			if (name.empty() || size < 1)
				return 1;
			mcb_f += size;
			m_sub_files.push_back(t_sub_file(name, size));
		}
	}
	if (m_sub_files.empty())
		m_sub_files.push_back(t_sub_file("", mcb_f = info.d(bts_length).i()));
	if (m_trackers.empty()
		|| m_trackers.front().empty()
		|| mcb_f < 1 
		|| mcb_piece < 16 << 10
		|| mcb_piece > 16 << 20)
		return 1;
	m_pieces.resize((mcb_f + mcb_piece - 1) / mcb_piece);
	string pieces = info.d(bts_pieces).s();
	if (pieces.length() != 20 * m_pieces.size())
		return 1;
	for (int i = 0; i < m_pieces.size(); i++)
	{
		m_pieces[i].mcb_d = min(mcb_piece * (i + 1), mcb_f) - mcb_piece * i;
		memcpy(m_pieces[i].m_hash, pieces.c_str() + 20 * i, 20);
	}

	m_downloaded = 0;
	m_uploaded = 0;

	return 0;
}

int Cbt_file::open(const string& name)
{
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		i->m_f = fopen((i->m_name.empty() ? name : name + '/' + i->m_name).c_str(), "r+b");
		if (!i->m_f)
		{
			char b = 0;
			if ((i->m_f = fopen((i->m_name.empty() ? name : name + '/' + i->m_name).c_str(), "w+b"))
				&& i->m_size
				&& !fseek(i->m_f, i->m_size - 1, SEEK_SET)
				&& fwrite(&b, 1, 1, i->m_f) == 1)
				fflush(i->m_f);
		}
		if (!i->m_f)
		{
			close();
			return 1;
		}
	}
	if (1)
	{
		Cvirtual_binary d;
		for (int i = 0; i < m_pieces.size(); i++)
		{
			Cbt_piece& piece = m_pieces[i];
			piece.m_valid = !read_piece(i, d.write_start(piece.mcb_d))
				&& !memcmp(compute_sha1(d).c_str(), piece.m_hash, 20);
		}
		cout << c_valid_pieces() << '/' << m_pieces.size() << endl;
	}
	return 0;
}

void Cbt_file::close()
{
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (i->m_f)
			fclose(i->m_f);
		i->m_f = NULL;
	}
}

int Cbt_file::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	int n = m_tracker.pre_select(*this, fd_read_set, fd_write_set, fd_except_set);
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
		n = max(n, z);
	}
	return n;
}

void Cbt_file::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	m_tracker.post_select(*this, fd_read_set, fd_write_set, fd_except_set);
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); )
	{
		i->post_select(fd_read_set, fd_write_set, fd_except_set);
		if (i->m_state == -1)
			i = m_peers.erase(i);
		else
			i++;
	}
}

void Cbt_file::insert_peer(const sockaddr_in& a)
{
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_a.sin_addr.s_addr == a.sin_addr.s_addr)
			return;
	}
	Cbt_peer_link peer;
	peer.m_a = a;
	peer.m_f = this;
	m_peers.push_back(peer);
}

void Cbt_file::insert_peer(const sockaddr_in& a, const Csocket& s)
{
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_a.sin_addr.s_addr == a.sin_addr.s_addr)
			return;
	}
	Cbt_peer_link peer;
	peer.m_a = a;
	peer.m_f = this;
	peer.m_s = s;
	peer.m_state = 2;
	peer.write_handshake();
	m_peers.push_back(peer);
}

int Cbt_file::c_pieces() const
{
	return m_pieces.size();
}

int Cbt_file::c_invalid_pieces() const
{
	int r = 0;
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		r += !i->m_valid;
	return r;
}

int Cbt_file::c_valid_pieces() const
{
	int r = 0;
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		r += i->m_valid;
	return r;
}

void Cbt_file::write_data(int o, const char* s, int cb_s)
{
	if (o < 0 || cb_s < 0 || o + cb_s > mcb_f)
		return;
	int a = o / mcb_piece;
	if (a < m_pieces.size())
	{
		Cbt_piece& piece = m_pieces[a];
		if (!piece.m_valid)
			piece.write(o % mcb_piece, s, cb_s);
		if (!piece.m_valid)
			return;
		int offset = a * mcb_piece;
		int size = piece.m_d.size();
		const byte* r = piece.m_d;
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		{
			if (offset < i->m_size)
			{					
				int cb_write = min(size, i->m_size - offset);
				if (fseek(i->m_f, offset, SEEK_SET))
					cerr << "fseek failed" << endl;
				else if (fwrite(r, cb_write, 1, i->m_f) != 1)
					cerr << "fwrite failed" << endl;
				else
					fflush(i->m_f);
				size -= cb_write;
				if (!size)
					break;
				offset = 0;
				r += cb_write;
			}
			else
				offset -= i->m_size;
		}
		piece.m_d.clear();
		write_have(a);
	}
}

int Cbt_file::read_piece(int a, byte* d)
{
	assert(a >= 0 && a < m_pieces.size());
	Cbt_piece& piece = m_pieces[a];
	if (piece.m_d)
	{
		piece.m_d.read(d);
		return 0;
	}
	int offset = a * mcb_piece;
	int size = piece.mcb_d;
	byte* w = d;
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (offset < i->m_size)
		{
			int cb_read = min(size, i->m_size - offset);
			if (fseek(i->m_f, offset, SEEK_SET))
				return 1;
			if (fread(w, cb_read, 1, i->m_f) != 1)
				return 1;
			size -= cb_read;
			if (!size)
				break;
			offset = 0;
			w += cb_read;
		}
		else
			offset -= i->m_size;
	}
	return 0;
}

int Cbt_file::next_invalid_piece(const Cbt_peer_link::t_remote_pieces& remote_pieces) const
{
	vector<int> invalid_pieces;

	invalid_pieces.reserve(c_invalid_pieces());
	for (int i = 0; i < m_pieces.size(); i++)
	{
		if (!m_pieces[i].m_valid && !m_pieces[i].m_peer && remote_pieces[i])
		{
			if (!m_pieces[i].m_sub_pieces.empty())
				return i;
			invalid_pieces.push_back(i);
		}
	}
	return invalid_pieces.empty() ? -1 : invalid_pieces[rand() % invalid_pieces.size()];
}

void Cbt_file::write_have(int a)
{
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
		i->write_have(a);
}

void Cbt_file::dump(ostream& os)
{
	os << "<table>"
		<< "<tr><td align=right>invalid pieces:<td align=right>" << c_invalid_pieces()
		<< "<tr><td align=right>valid pieces:<td align=right>" << c_valid_pieces()
		<< "<tr><td align=right>downloaded:<td align=right>" << static_cast<int>(m_downloaded >> 20) << " mb<td align=right>" << (m_down_counter.rate() >> 10) << " kb/s<td align=right>";
	int t = time_remaining();
	if (t != -1)
	{
		if (t / 3600)
			os << t / 3600 << " h ";
		os << (t % 3600) / 60 << " m " << t % 60 << " s";
	}
	os << "<tr><td align=right>uploaded:<td align=right>" << static_cast<int>(m_uploaded >> 20) << " mb<td align=right>" << (m_up_counter.rate() >> 10) << " kb/s"
		<< "</table>"
		<< "<hr>"
		<< "<table>";
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		os << "<tr><td>" << inet_ntoa(i->m_a.sin_addr) 
			<< "<td>" << ntohs(i->m_a.sin_port) 
			<< "<td align=right>" << static_cast<int>(i->m_downloaded >> 10) 
			<< "<td align=right>" << static_cast<int>(i->m_uploaded >> 10)
			<< "<td>" << (i->m_local_choked ? 'C' : ' ') 
			<< "<td>" << (i->m_local_interested ? 'I' : ' ')
			<< "<td>" << (i->m_remote_choked ? 'C' : ' ') 
			<< "<td>" << (i->m_remote_interested ? 'I' : ' ')
			<< "<td align=right>" << i->m_read_b.cb_read()
			<< "<td align=right>" << i->m_write_b.size()
			<< "<td align=right>" << i->m_remote_requests.size()
			<< "<td align=right>" << time(NULL) - i->m_piece_rtime
			<< "<td align=right>" << i->m_piece
			<< "<td align=right>" << (i->m_piece && !i->m_piece->m_sub_pieces.empty() ? i->m_piece->mc_sub_pieces_left : 0)
			<< "<td>" << escape_string(i->m_remote_peer_id);
	}
	os << "</table>";
}

int Cbt_file::time_remaining()
{
	int rate = m_down_counter.rate();
	return rate ? mcb_piece * c_invalid_pieces() / rate : -1;
}

__int64 Cbt_file::left() const
{
	return mcb_piece * c_invalid_pieces();
}
