#include "stdafx.h"
#include "dlg_make_torrent.h"

#include "bt_strings.h"
#include "bt_torrent.h"
#include "xbt/xcc_z.h"

#include <sys/stat.h>
#include <fcntl.h>
#include <io.h>
#include "bvalue.h"

Cdlg_make_torrent::Cdlg_make_torrent(CWnd* pParent):
	ETSLayoutDialog(Cdlg_make_torrent::IDD, pParent, "Cdlg_make_torrent")
{
	//{{AFX_DATA_INIT(Cdlg_make_torrent)
	m_tracker = _T("");
	m_name = _T("");
	m_use_merkle = FALSE;
	m_trackers = _T("");
	m_seed_after_making = TRUE;
	//}}AFX_DATA_INIT
}


void Cdlg_make_torrent::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_make_torrent)
	DDX_Control(pDX, IDC_SAVE, m_save);
	DDX_Control(pDX, IDC_LIST, m_list);
	DDX_Text(pDX, IDC_TRACKER, m_tracker);
	DDX_Text(pDX, IDC_NAME, m_name);
	DDX_Check(pDX, IDC_USE_MERKLE, m_use_merkle);
	DDX_Text(pDX, IDC_TRACKERS, m_trackers);
	DDX_Check(pDX, IDC_SEED_AFTER_MAKING, m_seed_after_making);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_make_torrent, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_make_torrent)
	ON_WM_DROPFILES()
	ON_NOTIFY(LVN_GETDISPINFO, IDC_LIST, OnGetdispinfoList)
	ON_WM_SIZE()
	ON_BN_CLICKED(IDC_SAVE, OnSave)
	ON_NOTIFY(LVN_COLUMNCLICK, IDC_LIST, OnColumnclickList)
	ON_BN_CLICKED(IDC_LOAD_TRACKERS, OnLoadTrackers)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_make_torrent::OnInitDialog()
{
	m_tracker = AfxGetApp()->GetProfileString(m_strRegStore, "tracker");
	m_trackers = AfxGetApp()->GetProfileString(m_strRegStore, "trackers");
	m_use_merkle = AfxGetApp()->GetProfileInt(m_strRegStore, "use_merkle", false);
	ETSLayoutDialog::OnInitDialog();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_NAME_STATIC, NORESIZE)
			<< item(IDC_NAME, GREEDY)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_TRACKER_STATIC, NORESIZE)
			<< item(IDC_TRACKER, GREEDY)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_TRACKERS_STATIC, NORESIZE)
			<< item(IDC_TRACKERS, GREEDY)
			)
		<< item(IDC_LIST, GREEDY)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< item(IDC_SEED_AFTER_MAKING, NORESIZE)
			<< item(IDC_USE_MERKLE, NORESIZE)
			<< itemGrowing(HORIZONTAL)
			<< item(IDC_LOAD_TRACKERS, NORESIZE)
			<< item(IDC_SAVE, NORESIZE)
			)
		;
	UpdateLayout();

	m_list.InsertColumn(0, "Name");
	m_list.InsertColumn(1, "Size", LVCFMT_RIGHT);
	m_list.InsertColumn(2, "");
	m_sort_column = 0;
	m_sort_reverse = false;

	for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
		m_list.InsertItemData(i->first);
	post_insert();

	return true;
}

static std::string base_name(const std::string& v)
{
	int i = v.rfind('\\');
	return i == std::string::npos ? v : v.substr(i + 1);
}

void Cdlg_make_torrent::OnDropFiles(HDROP hDropInfo)
{
	int c_files = DragQueryFile(hDropInfo, 0xFFFFFFFF, NULL, 0);

	for (int i = 0; i < c_files; i++)
	{
		char name[MAX_PATH];
		DragQueryFile(hDropInfo, i, name, MAX_PATH);
		insert(name);
	}
	ETSLayoutDialog::OnDropFiles(hDropInfo);
	post_insert();
}

void Cdlg_make_torrent::insert(const std::string& name)
{
	struct _stati64 b;
	if (boost::iequals(base_name(name), "desktop.ini")
		|| boost::iequals(base_name(name), "thumbs.db")
		|| _stati64(name.c_str(), &b))
		return;
	if (b.st_mode & S_IFDIR)
	{
		if (m_name.IsEmpty())
		{
			if (GetSafeHwnd())
				UpdateData(true);
			m_name = base_name(name).c_str();
			if (GetSafeHwnd())
				UpdateData(false);
		}
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
	int id = m_map.empty() ? 0 : m_map.rbegin()->first + 1;
	t_map_entry& e = m_map[id];
	e.name = name;
	e.size = b.st_size;
	if (GetSafeHwnd())
		m_list.InsertItemData(id);
}

void Cdlg_make_torrent::post_insert()
{
	if (m_map.size() == 1)
	{
		UpdateData(true);
		m_name = base_name(m_map.begin()->second.name).c_str();
		UpdateData(false);
	}
	m_list.auto_size();
	sort();
	m_save.EnableWindow(!m_map.empty() && m_map.size() < 256);
}

void Cdlg_make_torrent::OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult)
{
	LV_DISPINFO* pDispInfo = reinterpret_cast<LV_DISPINFO*>(pNMHDR);
	std::string& buffer = m_list.get_buffer();
	const t_map_entry& e = m_map.find(pDispInfo->item.lParam)->second;
	switch (pDispInfo->item.iSubItem)
	{
	case 0:
		buffer = base_name(e.name);
		break;
	case 1:
		buffer = n(e.size);
		break;
	}
	pDispInfo->item.pszText = const_cast<char*>(buffer.c_str());
	*pResult = 0;
}

static Cvirtual_binary gzip(const Cvirtual_binary& s)
{
	Cvirtual_binary d = xcc_z::gzip(s);
	return d.size() < s.size() ? d : s;
}

static Cbvalue parse_trackers(const std::string& v)
{
	Cbvalue announce_list;
	for (size_t i = 0; i < v.length(); )
	{
		int j = v.find_first_of("\t\n\r ", i);
		if (i == j)
		{
			i++;
			continue;
		}
		if (j == std::string::npos)
			j = v.length();
		std::string url = v.substr(i, j - i);
		announce_list.l(Cbvalue().l(url));
		i = j + 1;
	}
	return announce_list;
}

void Cdlg_make_torrent::OnSave()
{
	if (!UpdateData())
		return;
	m_name.TrimRight(" ");
	m_tracker.TrimRight(" ");
	CFileDialog dlg(false, "torrent", m_name + ".torrent", OFN_ENABLESIZING | OFN_HIDEREADONLY | OFN_OVERWRITEPROMPT | OFN_PATHMUSTEXIST, "Torrents|*.torrent|", this);
	if (IDOK != dlg.DoModal())
		return;
	CWaitCursor wc;
	AfxGetApp()->WriteProfileString(m_strRegStore, "tracker", m_tracker);
	AfxGetApp()->WriteProfileString(m_strRegStore, "trackers", m_trackers);
	AfxGetApp()->WriteProfileInt(m_strRegStore, "use_merkle", m_use_merkle);
	int cb_piece = 1 << 20;
	if (!m_use_merkle)
	{
		long long cb_total = 0;
		for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
			cb_total += i->second.size;
		cb_piece = 256 << 10;
		while (cb_total / cb_piece > 4 << 10)
			cb_piece <<= 1;
	}
	typedef std::set<std::string> t_set;
	t_set set;
	for (t_map::const_iterator i = m_map.begin(); i != m_map.end(); i++)
		set.insert(i->second.name);
	Cbvalue files;
	std::string pieces;
	Cvirtual_binary d;
	byte* w = d.write_start(cb_piece);
	for (t_set::const_iterator i = set.begin(); i != set.end(); i++)
	{
		int f = open(i->c_str(), _O_BINARY | _O_RDONLY);
		if (!f)
			continue;
		long long cb_f = 0;
		std::string merkle_hash;
		int cb_d;
		if (m_use_merkle)
		{
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
		files.l(merkle_hash.empty()
			? Cbvalue().d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(*i)))
			: Cbvalue().d(bts_merkle_hash, merkle_hash).d(bts_length, cb_f).d(bts_path, Cbvalue().l(base_name(*i))));
	}
	if (w != d)
		pieces += Csha1(const_memory_range(d, w - d)).read();
	Cbvalue info;
	info.d(bts_piece_length, cb_piece);
	if (!pieces.empty())
		info.d(bts_pieces, pieces);
	if (m_map.size() == 1)
	{
		if (m_use_merkle)
			info.d(bts_merkle_hash, files.l().front()[bts_merkle_hash]);
		info.d(bts_length, files.l().front()[bts_length]);
		info.d(bts_name, files.l().front()[bts_path].l().front());
	}
	else
	{
		info.d(bts_files, files);
		info.d(bts_name, static_cast<std::string>(m_name));
	}
	Cbvalue torrent;
	torrent.d(bts_announce, static_cast<std::string>(m_tracker));
	if (!m_trackers.IsEmpty())
		torrent.d(bts_announce_list, parse_trackers(static_cast<std::string>(m_trackers)));
	torrent.d(bts_info, info);
	Cvirtual_binary s = torrent.read();
	if (m_use_merkle)
		s = gzip(s);
	s.save(static_cast<std::string>(dlg.GetPathName()));
	m_torrent_fname = dlg.GetPathName();
	EndDialog(IDOK);
}

void Cdlg_make_torrent::OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult)
{
	NM_LISTVIEW* pNMListView = (NM_LISTVIEW*)pNMHDR;
	m_sort_reverse = pNMListView->iSubItem == m_sort_column && !m_sort_reverse;
	m_sort_column = pNMListView->iSubItem;
	sort();
	*pResult = 0;
}

template <class T>
static int compare(const T& a, const T& b)
{
	return a < b ? -1 : a != b;
}

int Cdlg_make_torrent::compare(int id_a, int id_b) const
{
	if (m_sort_reverse)
		std::swap(id_a, id_b);
	const t_map_entry& a = m_map.find(id_a)->second;
	const t_map_entry& b = m_map.find(id_b)->second;
	switch (m_sort_column)
	{
	case 0:
		return ::compare(a.name, b.name);
	case 1:
		return ::compare(a.size, b.size);
	}
	return 0;
}

static int CALLBACK compare(LPARAM lParam1, LPARAM lParam2, LPARAM lParamSort)
{
	return reinterpret_cast<Cdlg_make_torrent*>(lParamSort)->compare(lParam1, lParam2);
}

void Cdlg_make_torrent::sort()
{
	m_list.SortItems(::compare, reinterpret_cast<DWORD>(this));
}

void Cdlg_make_torrent::OnLoadTrackers()
{
	UpdateData(true);
	CFileDialog dlg(true, "torrent", NULL, OFN_ENABLESIZING | OFN_FILEMUSTEXIST | OFN_HIDEREADONLY, "Torrents|*.torrent|", this);
	if (IDOK != dlg.DoModal())
		return;
	Cvirtual_binary d;
	d.load(static_cast<std::string>(dlg.GetPathName()));
	Cbt_torrent torrent(d.range());
	if (!torrent.valid())
		return;
	m_tracker = torrent.announce().c_str();
	m_trackers.Empty();
	const Cbt_torrent::t_announces& announces = torrent.announces();
	for (Cbt_torrent::t_announces::const_iterator i = announces.begin(); i != announces.end(); i++)
	{
		m_trackers += i->c_str();
		m_trackers += "\r\n";
	}
	UpdateData(false);
}
