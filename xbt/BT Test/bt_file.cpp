// bt_file.cpp: implementation of the Cbt_file class.
//
//////////////////////////////////////////////////////////////////////

#include "stdafx.h"
#include "bt_file.h"

#include <sys/stat.h>
#include <fcntl.h>
#include "bt_hasher.h"
#include "bt_strings.h"
#include "server.h"

#define for if (0) {} else for

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

Cbt_file::Cbt_file()
{
	m_downloaded = 0;
	m_downloaded_l5 = 0;
	m_left = -1;
	m_uploaded = 0;
	m_uploaded_l5 = 0;
	m_total_downloaded = 0;
	m_total_uploaded = 0;
	mc_leechers_total = 0;
	mc_seeders_total = 0;
	mc_rejected_chunks = 0;
	mc_rejected_pieces = 0;
	m_hasher = NULL;
	m_started_at = time(NULL);
	m_session_started_at = time(NULL);
	m_completed_at = 0;
	m_seeding_ratio_reached_at = 0;
	m_priority = 0;
	m_size = -1;
	m_state = s_queued;
	m_validate = true;
}

Cbt_file::~Cbt_file()
{
}

int Cbt_file::torrent(const Cbvalue& v)
{
	const Cbvalue::t_list& trackers = v.d(bts_announce_list).l();
	for (Cbvalue::t_list::const_iterator i = trackers.begin(); i != trackers.end(); i++)
	{
		for (Cbvalue::t_list::const_iterator j = i->l().begin(); j != i->l().end(); j++)
			m_trackers.push_back(j->s());		
	}
	if (m_trackers.empty())
		m_trackers.push_back(v.d(bts_announce).s());
	return info(v.d(bts_info));
}

int Cbt_file::info(const Cbvalue& info)
{
	m_name = info.d(bts_name).s();
	if (m_name.empty())
		return 1;
	m_info = info.read();
	m_info_hash = compute_sha1(m_info);
	m_merkle = info.d(bts_pieces).s().empty();
	mcb_piece = info.d(bts_piece_length).i();
	{
		m_size = 0;
		__int64 offset = 0;
		const Cbvalue::t_list& files = info.d(bts_files).l();
		for (Cbvalue::t_list::const_iterator i = files.begin(); i != files.end(); i++)
		{
			string name;
			{
				const Cbvalue::t_list& path = i->d(bts_path).l();
				for (Cbvalue::t_list::const_iterator i = path.begin(); i != path.end(); i++)
					name += '/' + i->s();
			}
			__int64 size = i->d(bts_length).i();
			if (name.empty() || size < 0)
				return 1;
			m_size += size;
			m_sub_files.push_back(t_sub_file(i->d(bts_merkle_hash).s(), name, offset, 0, size));
			offset += size;
			if (m_merkle)
				offset += - offset & (mcb_piece - 1);
		}
	}
	if (m_sub_files.empty())
		m_sub_files.push_back(t_sub_file(info.d(bts_merkle_hash).s(), "", 0, 0, m_size = info.d(bts_length).i()));
	if (m_size < 1 
		|| mcb_piece < 16 << 10
		|| mcb_piece > 16 << 20)
		return 1;
	if (m_merkle)
	{
		int c_pieces = 0;
		for (t_sub_files::const_iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		{
			if (i->merkle_hash().length() != 20)
				return 1;
			c_pieces += (i->size() + mcb_piece - 1) / mcb_piece;
		}
		m_pieces.resize(c_pieces);
		Cbt_piece* piece = &m_pieces.front();
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		{
			__int64 size = i->size();
			while (size)
				size -= piece++->resize(min(size, mcb_piece));
		}
		assert(piece - &m_pieces.front() == m_pieces.size());
		return 0;
	}
	m_pieces.resize((m_size + mcb_piece - 1) / mcb_piece);
	string piece_hashes = info.d(bts_pieces).s();
	if (piece_hashes.length() != 20 * m_pieces.size())
		return 1;
	for (int i = 0; i < m_pieces.size(); i++)
	{
		m_pieces[i].resize(min(m_size - mcb_piece * i, mcb_piece));
		memcpy(m_pieces[i].m_hash, piece_hashes.c_str() + 20 * i, 20);
	}
	return 0;
}

int Cbt_file::t_sub_file::c_pieces(int cb_piece) const
{
	return (size() + cb_piece - 1) / cb_piece;
}

void Cbt_file::t_sub_file::close()
{
	if (m_f != -1)
		::close(m_f);
	m_f = -1;
}

void Cbt_file::t_sub_file::erase(const string& parent_name)
{
	::unlink((parent_name + m_name).c_str());
}

void Cbt_file::t_sub_file::dump(Cstream_writer& w) const
{
	w.write_int(8, left());
	w.write_string(name());
	w.write_int(4, priority());
	w.write_int(8, size());
	w.write_string(merkle_hash());
}

bool Cbt_file::t_sub_file::open(const string& parent_name, int oflag)
{
#ifdef WIN32
	m_f = ::open((parent_name + m_name).c_str(), oflag | O_BINARY, S_IREAD | S_IWRITE);
#else
	m_f = ::open((parent_name + m_name).c_str(), oflag | O_BINARY | O_LARGEFILE, 0666);
#endif
	return *this;
}

int Cbt_file::t_sub_file::pre_dump() const
{
	return name().size() + merkle_hash().size() + 28;
}

int Cbt_file::t_sub_file::read(__int64 offset, void* s, int cb_s)
{
	return !*this
		|| _lseeki64(m_f, offset, SEEK_SET) != offset
		|| ::read(m_f, s, cb_s) != cb_s;
}

int Cbt_file::t_sub_file::write(__int64  offset, const void* s, int cb_s)
{
	return !*this
		|| ::_lseeki64(m_f, offset, SEEK_SET) != offset
		|| ::write(m_f, s, cb_s) != cb_s;
}

void Cbt_file::open()
{
	if (is_open())
		return;
	if (m_name.find_first_of("/\\") == string::npos)
	{   
		struct stat b;   
		m_name = (stat((m_server->completes_dir() + '/' + m_name).c_str(), &b) ? m_server->incompletes_dir() : m_server->completes_dir()) + '/' + m_name;   
	} 
	__int64 offset = 0;
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (!i->open(m_name, O_RDWR)
			&& !i->open(m_name, O_RDONLY)
			&& i->size())
		{
			int b = (offset + i->size() - 1) / mcb_piece;
			for (int a = offset / mcb_piece; a <= b; a++)
				m_pieces[a].valid(false);
		}
		offset += i->size();
	}
	if (m_info.size())
	{
		Cvirtual_binary d;
		m_left = 0;
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
			i->left(0);
		m_hasher = new Cbt_hasher(m_validate);
		if (!m_validate)
		{
			while (m_hasher->run(*this))
				;
			delete m_hasher;
			m_hasher = NULL;
		}
	}
	announce();
	m_state = m_hasher ? s_hashing : s_running;
}

bool Cbt_file::hash()
{
	if (!m_hasher)
		return false;
	int i = max(1, (4 << 20) / mcb_piece);
	while (i && m_hasher->run(*this))
		i--;
	if (!i)
		return true;
	delete m_hasher;
	m_hasher = NULL;
	m_validate = false;
	m_state = s_running;
	return false;
}

void Cbt_file::close()
{
	if (!is_open())
		return;
	m_validate = m_hasher;
	delete m_hasher;
	m_hasher = NULL;
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
		i->close();
	m_peers.clear();
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		i->close();
	m_state = s_stopped;
	
	if (!m_server->send_stop_event() || m_trackers.empty())
		return;
	Cbt_tracker_url m_url = m_trackers.front();
	if (!m_url.valid() || m_url.m_protocol != Cbt_tracker_url::tp_http)
		return;
	int h = Csocket::get_host(m_url.m_host);
	if (h == INADDR_NONE)
		return;
	m_tracker.event(Cbt_tracker_link::e_stopped);
	m_server->http_request(h, htons(m_url.m_port), m_tracker.http_request(*this), NULL);
	m_downloaded = 0;
	m_uploaded = 0;
}

void Cbt_file::erase()
{
#ifdef WIN32
	SHFILEOPSTRUCT op;
	ZeroMemory(&op, sizeof(SHFILEOPSTRUCT));
	op.wFunc = FO_DELETE;
	char b[MAX_PATH];
	strcpy(b, native_slashes(m_name).c_str());
	b[m_name.size() + 1] = 0;
	op.pFrom = b;
	op.fFlags = FOF_ALLOWUNDO | FOF_NOERRORUI;
	SHFileOperation(&op);
#else
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		i->erase(m_name);
	if (m_sub_files.size() != 1)
		unlink(m_name.c_str());
#endif
}

int Cbt_file::pre_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (m_hasher)
		return 0;
	if (state() == s_running && !m_left && seeding_ratio() && 100 * m_total_uploaded / seeding_ratio() > m_size)
	{
		m_seeding_ratio_reached_at = m_server->time();
		alert(Calert(Calert::notice, "Seeding ratio reached"));
		close();
	}
	else if (state() == s_running)
	{
		for (t_new_peers::const_iterator i = m_new_peers.begin(); i != m_new_peers.end() && m_server->below_peer_limit(); )
		{
			if (!find_peer(i->first) && !m_server->block_list_has(i->first))
			{
				Cbt_peer_link peer;
				peer.m_a.sin_family = AF_INET;
				peer.m_a.sin_addr.s_addr = i->first;
				peer.m_a.sin_port = i->second;
				peer.m_f = this;
				peer.m_local_link = true;
				m_peers.push_back(peer);
			}
			m_new_peers.erase(i++->first);
		}
	}
	int n = m_tracker.pre_select(*this, fd_read_set, fd_write_set, fd_except_set);
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); )
	{
		int z = i->pre_select(fd_read_set, fd_write_set, fd_except_set);
		if (i->m_state == -1)
		{
			i->close();
			i = m_peers.erase(i);
		}
		else
			i++;
		n = max(n, z);
	}
	return n;
}

void Cbt_file::post_select(fd_set* fd_read_set, fd_set* fd_write_set, fd_set* fd_except_set)
{
	if (m_hasher)
		return;
	m_tracker.post_select(*this, fd_read_set, fd_write_set, fd_except_set);
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); )
	{
		if (i->post_select(fd_read_set, fd_write_set, fd_except_set))
		{
			i->close();
			i = m_peers.erase(i);
		}
		else
			i++;
	}
}

void Cbt_file::insert_old_peer(int h, int p)
{
	m_old_peers[h] = p;
}

void Cbt_file::insert_peer(int h, int p)
{
	m_new_peers[h] = p;
}

void Cbt_file::insert_peer(const char* r, const sockaddr_in& a, const Csocket& s)
{
	switch (state())
	{
	case s_running:
	case s_paused:
		break;
	default:
		return;
	}
	if (find_peer(a.sin_addr.s_addr))
		return;
	Cbt_peer_link peer;
	peer.m_a = a;
	peer.m_f = this;
	peer.m_s = s;
	peer.m_local_link = false;
	peer.m_state = 4;
	if (peer.read_handshake(r))
	{
		assert(false);
		return;
	}
	peer.write_handshake();
	m_peers.push_back(peer);
}

int Cbt_file::c_pieces() const
{
	return m_pieces.size();
}

int Cbt_file::c_invalid_pieces() const
{
	return (m_left + mcb_piece - 1) / mcb_piece;
}

int Cbt_file::c_valid_pieces() const
{
	return (m_size - m_left + mcb_piece - 1) / mcb_piece;
}

int Cbt_file::write_data(__int64 offset, const char* s, int cb_s, Cbt_peer_link*)
{
	if (offset < 0 || cb_s < 0)
		return 1;
	int a = offset / mcb_piece;
	if (a >= m_pieces.size())
		return 1;
	Cbt_piece& piece = m_pieces[a];
	if (piece.valid() || piece.write(offset % mcb_piece, s, cb_s))
	{
		mc_rejected_chunks++;
		return 1;
	}
	int size = cb_s;
	const char* r = s;
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (offset >= i->offset() + i->size())
			continue;
		if (!*i && i->size())
		{
			string path = m_name + i->name();
			int a = path.find_last_of("/\\");
			if (a != string::npos)
				mkpath(path.substr(0, a));
			if (i->open(m_name, O_CREAT | O_RDWR))
			{
				char b = 0;
				i->write(i->size() - 1, &b, 1);
			}
			else
				alert(Calert(Calert::error, "File " + native_slashes(m_name + i->name()) + ": open failed"));
		}
		int cb_write = min(size, i->offset() + i->size() - offset);
		if (i->write(offset - i->offset(), r, cb_write))
		{
			alert(Calert(Calert::error, "Piece " + n(a) + ": write failed"));
			m_state = s_paused;
		}
		size -= cb_write;
		if (!size)
			break;
		offset += cb_write;
		r += cb_write;
	}
	if (piece.c_sub_pieces_left())
		return 0;
	Cvirtual_binary d;
	read_data(a * mcb_piece, d.write_start(piece.size()), piece.size());
	piece.valid(m_merkle || !memcmp(compute_sha1(d).c_str(), piece.m_hash, 20));
	if (!piece.valid())
	{
		mc_rejected_pieces++;
		alert(Calert(Calert::warn, "Piece " + n(a) + ": invalid"));
		logger().invalid(m_info_hash, false, a);
		return 0;
	}
	m_left -= piece.size();
	if (!m_left)
	{
		m_completed_at = m_server->time();
		m_tracker.event(Cbt_tracker_link::e_completed);
	}
	{
		offset = a * mcb_piece;
		size = piece.size();
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		{
			if (offset >= i->offset() + i->size())
				continue;
			int cb_write = min(size, i->offset() + i->size() - offset);
			if (!i->left(i->left() - cb_write))
			{
				i->close();
				i->open(m_name, O_RDONLY);
			}
			size -= cb_write;
			if (!size)
				break;
			offset += cb_write;
		}
		for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
			i->queue_have(a);
	}
	if (!m_left && m_name.substr(0, m_server->incompletes_dir().size()) == m_server->incompletes_dir())
	{
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
			i->close();
		string new_name = m_server->completes_dir() + m_name.substr(m_server->incompletes_dir().size());
		mkpath(m_server->completes_dir());
		if (!rename(m_name.c_str(), new_name.c_str()))
			m_name = new_name;
		for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
			i->open(m_name, O_RDONLY);
	}
	if (m_server->log_piece_valid())
		alert(Calert(Calert::debug, "Piece " + n(a) + ": valid"));
	logger().valid(m_info_hash, false, a);
	return 0;
}

int Cbt_file::read_data(__int64 offset, byte* d, int cb_d)
{
	int size = cb_d;
	byte* w = d;
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (offset >= i->offset() + i->size())
			continue;
		int cb_read = min(size, i->offset() + i->size() - offset);
		if (i->read(offset - i->offset(), w, cb_read))
			return 1;
		size -= cb_read;
		if (!size)
			return 0;
		offset += cb_read;
		w += cb_read;
	}
	return 1;
}

int Cbt_file::next_invalid_piece(const Cbt_peer_link& peer)
{
	assert(peer.m_remote_pieces.size() == m_pieces.size());
	if (!m_left || peer.m_remote_pieces.size() != m_pieces.size())
		return -1;

	vector<int> invalid_pieces;

	invalid_pieces.reserve(c_invalid_pieces());
	bool begin_mode = Cbt_file::begin_mode();
	bool end_mode = Cbt_file::end_mode();
	int rank = INT_MAX;
	int c_unrequested_sub_pieces = 0;
	for (int i = 0; i < m_pieces.size(); i++)
	{
		if (m_pieces[i].valid()
			|| m_pieces[i].priority() == -10
			|| !m_pieces[i].c_unrequested_sub_pieces())
			continue;
		c_unrequested_sub_pieces += m_pieces[i].c_unrequested_sub_pieces();
		if (!peer.m_remote_pieces[i] )
			continue;
		if (begin_mode && !m_pieces[i].sub_pieces().empty())
			return i;
		int piece_rank = m_pieces[i].rank();
		if (piece_rank > rank)
			continue;
		if (piece_rank < rank)
			invalid_pieces.clear();
		rank = piece_rank;
		invalid_pieces.push_back(i);
	}
	if (c_unrequested_sub_pieces < 256)
		m_end_mode = true;
	return invalid_pieces.empty() ? -1 : invalid_pieces[rand() % invalid_pieces.size()];
}

int Cbt_file::pre_dump(int flags) const
{
	int size = m_info_hash.length() + m_name.length() + 208;
	if (flags & Cserver::df_trackers)
	{
		for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
			size += i->size() + 4;
	}
	if (flags & Cserver::df_peers)
	{
		for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
			size += i->pre_dump();
	}
	if (flags & Cserver::df_alerts)
	{
		for (Calerts::const_iterator i = m_alerts.begin(); i != m_alerts.end(); i++)
			size += i->pre_dump();
	}
	if (flags & Cserver::df_files)
	{
		for (t_sub_files::const_iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
			size += i->pre_dump();
	}
	if (flags & Cserver::df_pieces)
	{
		for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
			size += i->pre_dump();
	}
	return size;
}

void Cbt_file::dump(Cstream_writer& w, int flags) const
{
	w.write_string(m_info_hash);
	w.write_string(m_name);
	if (flags & Cserver::df_trackers)
	{
		w.write_int(4, m_trackers.size());
		for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
			w.write_string(*i);
	}
	else
		w.write_int(4, 0);
	int c_distributed_copies = INT_MAX;
	int c_distributed_copies_remainder = 0;
	int c_invalid_chunks = 0;
	int c_valid_chunks = 0;
	for (t_pieces::const_iterator i = m_pieces.begin(); i < m_pieces.end(); i++)
	{
		c_distributed_copies = min(c_distributed_copies, i->mc_peers + (m_left && i->valid()) - c_seeders());
		if (!i->sub_pieces().empty() && i->c_sub_pieces_left() != i->c_sub_pieces())
		{
			c_invalid_chunks += i->c_sub_pieces_left();
			c_valid_chunks += i->c_sub_pieces() - i->c_sub_pieces_left();
		}
	}
	for (t_pieces::const_iterator i = m_pieces.begin(); i < m_pieces.end(); i++)
		c_distributed_copies_remainder += i->mc_peers + (m_left && i->valid()) - c_seeders() > c_distributed_copies;
	w.write_int(8, m_downloaded);
	w.write_int(8, m_downloaded_l5);
	w.write_int(8, m_left);
	w.write_int(8, size());
	w.write_int(8, m_uploaded);
	w.write_int(8, m_uploaded_l5);
	w.write_int(8, m_total_downloaded);
	w.write_int(8, m_total_uploaded);
	w.write_int(4, m_down_counter.rate(m_server->time()));
	w.write_int(4, m_up_counter.rate(m_server->time()));
	w.write_int(4, c_leechers());
	w.write_int(4, c_seeders());
	w.write_int(4, mc_leechers_total);
	w.write_int(4, mc_seeders_total);
	w.write_int(4, c_invalid_chunks);
	w.write_int(4, c_invalid_pieces());
	w.write_int(4, mc_rejected_chunks);
	w.write_int(4, mc_rejected_pieces);
	w.write_int(4, c_valid_chunks);
	w.write_int(4, c_valid_pieces());
	w.write_int(4, 32 << 10);
	w.write_int(4, mcb_piece);
	w.write_int(4, state());
	w.write_int(4, m_started_at);
	w.write_int(4, m_session_started_at);
	w.write_int(4, m_left ? 0 : m_completed_at);
	w.write_int(4, c_distributed_copies);
	w.write_int(4, c_distributed_copies_remainder);
	w.write_int(4, m_priority);
	w.write_int(4, m_allow_end_mode);
	w.write_int(4, seeding_ratio());
	w.write_int(4, m_seeding_ratio_override);
	w.write_int(4, m_seeding_ratio_reached_at);
	w.write_int(4, upload_slots_max());
	w.write_int(4, m_upload_slots_max_override);
	w.write_int(4, upload_slots_min());
	w.write_int(4, m_upload_slots_min_override);
	if (flags & Cserver::df_peers)
	{
		w.write_int(4, m_peers.size());
		for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
			i->dump(w);
	}
	else
		w.write_int(4, 0);	
	if (flags & Cserver::df_alerts)
	{
		w.write_int(4, m_alerts.size());
		for (Calerts::const_iterator i = m_alerts.begin(); i != m_alerts.end(); i++)
			i->dump(w);
	}
	else
		w.write_int(4, 0);
	if (flags & Cserver::df_files)
	{
		w.write_int(4, m_sub_files.size());
		for (t_sub_files::const_iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
			i->dump(w);
	}
	else
		w.write_int(4, 0);
	if (flags & Cserver::df_pieces)
	{
		w.write_int(4, m_pieces.size());
		for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
			i->dump(w);
	}
	else
		w.write_int(4, 0);
}

int Cbt_file::c_leechers() const
{
	int c = 0;
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_left && i->m_state == 3)
			c++;
	}
	return c;
}

int Cbt_file::c_seeders() const
{
	int c = 0;
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (!i->m_left && i->m_state == 3)
			c++;
	}
	return c;
}

int Cbt_file::c_local_links() const
{
	int c = 0;
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_local_link && i->m_state == 3)
			c++;
	}
	return c;
}

int Cbt_file::c_remote_links() const
{
	int c = 0;
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (!i->m_local_link && i->m_state == 3)
			c++;
	}
	return c;
}

__int64 Cbt_file::size() const
{
	return m_size;
}

void Cbt_file::load_state(Cstream_reader& r)
{
	for (int c_trackers = r.read_int(4); c_trackers--; )
		m_trackers.push_back(r.read_string());
	info(r.read_data());
	if (!m_info.size())
		m_info_hash = r.read_string();
	m_name = r.read_string();
	m_total_downloaded = r.read_int(8);
	m_total_uploaded = r.read_int(8);
	m_validate = r.read_int(4);
	m_completed_at = r.read_int(4);
	m_started_at = r.read_int(4);
	m_priority = static_cast<char>(r.read_int(1));
	m_state = static_cast<t_state>(r.read_int(4));
	mc_rejected_chunks = r.read_int(4);
	mc_rejected_pieces = r.read_int(4);
	m_seeding_ratio = r.read_int(4);
	m_seeding_ratio_override = r.read_int(4);
	int c_pieces = r.read_int(4);
	for (int i = 0; i < c_pieces; i++)
		m_pieces[i].load_state(r);
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		i->priority(static_cast<char>(r.read_int(1)));
	update_piece_priorities();
	{
		for (int c_peers = r.read_int(4); c_peers--; )
		{
			int h = r.read_int(4);
			m_old_peers[h] = m_new_peers[h] = r.read_int(4);
		}
	}
	m_upload_slots_max = r.read_int(4);
	m_upload_slots_max_override = r.read_int(4);
	m_upload_slots_min = r.read_int(4);
	m_upload_slots_min_override = r.read_int(4);
	m_seeding_ratio_reached_at = r.read_int(4);
	r.read(44);
	if (!is_open())
		return;
	m_state = s_stopped;
	open();
}

int Cbt_file::pre_save_state(bool intermediate) const
{
	int c = m_info.size() + m_name.size() + m_sub_files.size() + 8 * m_old_peers.size() + 133;
	if (!m_info.size())
		c += m_info_hash.size() + 4;
	for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
		c += i->size() + 4;
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		c += i->pre_save_state();
	return c;
}

void Cbt_file::save_state(Cstream_writer& w, bool intermediate) const
{
	w.write_int(4, m_trackers.size());	
	for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
		w.write_string(*i);
	w.write_data(m_info);
	if (!m_info.size())
		w.write_string(m_info_hash);
	w.write_string(m_name);
	w.write_int(8, m_total_downloaded);
	w.write_int(8, m_total_uploaded);
	w.write_int(4, intermediate && m_left || m_validate);
	w.write_int(4, m_completed_at);
	w.write_int(4, m_started_at);
	w.write_int(1, m_priority);
	w.write_int(4, state());
	w.write_int(4, mc_rejected_chunks);
	w.write_int(4, mc_rejected_pieces);
	w.write_int(4, m_seeding_ratio);
	w.write_int(4, m_seeding_ratio_override);
	w.write_int(4, m_pieces.size());
	for (t_pieces::const_iterator i = m_pieces.begin(); i != m_pieces.end(); i++)
		i->save_state(w);
	for (t_sub_files::const_iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
		w.write_int(1, i->priority());
	w.write_int(4, m_old_peers.size());
	for (t_old_peers::const_iterator i = m_old_peers.begin(); i != m_old_peers.end(); i++)
	{
		w.write_int(4, i->first);
		w.write_int(4, i->second);
	}
	w.write_int(4, m_upload_slots_max);
	w.write_int(4, m_upload_slots_max_override);
	w.write_int(4, m_upload_slots_min);
	w.write_int(4, m_upload_slots_min_override);
	w.write_int(4, m_seeding_ratio_reached_at);
	w.write(44);
}

void Cbt_file::alert(const Calert& v)
{
	m_alerts.push_back(v);
}

string Cbt_file::get_url() const
{
	string v = "xbtp://";
	for (t_trackers::const_iterator i = m_trackers.begin(); i != m_trackers.end(); i++)
		v += uri_encode(*i) + ',';
	v += '/' + hex_encode(m_info_hash) + '/';
	if (local_ipa())
	{
		v += hex_encode(8, ntohl(local_ipa()))
			+ hex_encode(4, local_port());
	}
	for (t_peers::const_iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_state != 3)
			continue;
		v += hex_encode(8, ntohl(i->m_a.sin_addr.s_addr))
			+ hex_encode(4, ntohs(i->m_a.sin_port));
	}
	return v;
}

Cbt_peer_link* Cbt_file::find_peer(int h)
{
	for (t_peers::iterator i = m_peers.begin(); i != m_peers.end(); i++)
	{
		if (i->m_a.sin_addr.s_addr == h)
			return &*i;
	}
	return NULL;
}

int Cbt_file::local_ipa() const
{
	return m_server->public_ipa();
}

int Cbt_file::local_port() const
{
	return m_server->peer_port();
}

void Cbt_file::sub_file_priority(const string& id, int priority)
{
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (i->name() != id)
			continue;
		i->priority(priority);
		update_piece_priorities();
		return;
	}
}

void Cbt_file::update_piece_priorities()
{
	for (int i = 0; i < m_pieces.size(); i++)
		m_pieces[i].priority(-128);
	__int64 offset = 0;
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		int b = (offset + i->size() - 1) / mcb_piece;
		for (int a = offset / mcb_piece; a <= b; a++)
			m_pieces[a].priority(max(m_pieces[a].priority(), i->priority()));
		offset += i->size();
	}
}

string Cbt_file::get_hashes(__int64 offset, int c) const
{
	for (t_sub_files::const_iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (offset >= i->offset() + i->size())
			continue;
		return i->merkle_tree().get(offset - i->offset() >> 15, c);
	}
	return "";
}

bool Cbt_file::test_and_set_hashes(__int64 offset, const string& v, const string& w)
{
	for (t_sub_files::iterator i = m_sub_files.begin(); i != m_sub_files.end(); i++)
	{
		if (offset >= i->offset() + i->size())
			continue;
		return i->merkle_tree().test_and_set(offset - i->offset() >> 15, v, w);
	}
	return false;
}

Cbt_logger& Cbt_file::logger()
{
	return m_server->logger();
}

bool Cbt_file::begin_mode() const
{
	return c_valid_pieces() < 4;
}

bool Cbt_file::end_mode() const
{
	return m_end_mode;
}

int Cbt_file::c_max_requests_pending() const
{
	return begin_mode() || end_mode() ? 1 : 8;
}

void Cbt_file::announce()
{
	m_tracker.m_announce_time = 0;
}

void Cbt_file::state(t_state v)
{
	switch (v)
	{
	case s_hashing:
	case s_paused:
	case s_running:
		if (is_open())
			m_state = v;
		else
			open();		
		break;
	case s_queued:
	case s_stopped:
		close();
		m_state = v;
		break;
	}
}

int Cbt_file::seeding_ratio() const
{
	return m_seeding_ratio_override ? m_seeding_ratio : m_server->seeding_ratio();
}

int Cbt_file::upload_slots_max() const
{
	return m_upload_slots_max_override ? m_upload_slots_max : m_server->torrent_upload_slots_max();
}

int Cbt_file::upload_slots_min() const
{
	return m_upload_slots_min_override ? m_upload_slots_min : m_server->torrent_upload_slots_min();
}

void Cbt_file::trackers(const string& v)
{
	m_trackers.clear();
	for (int i = 0; i < v.length(); )
	{
		int j = v.find_first_of("\t\n\r ", i);
		if (i == j)
		{
			i++;
			continue;
		}
		if (j == string::npos)
			j = v.length();
		m_trackers.push_back(v.substr(i, j - i));
		i = j + 1;
	}
}

void Cbt_file::peer_connect(int ipa, int port)
{
	insert_peer(ipa, port);
}

void Cbt_file::peer_disconnect(int ipa)
{
	if (Cbt_peer_link* peer = find_peer(ipa))
		peer->close();
}
