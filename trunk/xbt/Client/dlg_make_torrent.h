#pragma once

#include "ListCtrlEx.h"
#include "resource.h"

class Cdlg_make_torrent: public ETSLayoutDialog
{
public:
	void post_insert();
	int compare(int id_a, int id_b) const;
	void insert(const std::string& name);
	void sort();
	Cdlg_make_torrent(CWnd* pParent = NULL);

	const std::string& torrent_fname() const
	{
		return m_torrent_fname;
	}

	enum { IDD = IDD_MAKE_TORRENT };
	CButton	m_save;
	CListCtrlEx	m_list;
	CString	m_tracker;
	CString	m_name;
	BOOL	m_use_merkle;
	CString	m_trackers;
	BOOL	m_seed_after_making;
protected:
	virtual void DoDataExchange(CDataExchange* pDX);
	virtual BOOL OnInitDialog();
	afx_msg void OnDropFiles(HDROP hDropInfo);
	afx_msg void OnGetdispinfoList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSave();
	afx_msg void OnColumnclickList(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnLoadTrackers();
	DECLARE_MESSAGE_MAP()
private:
	struct t_map_entry
	{
		std::string name;
		long long size;
	};

	typedef std::map<int, t_map_entry> t_map;

	t_map m_map;
	int m_sort_column;
	bool m_sort_reverse;
	std::string m_torrent_fname;
};
