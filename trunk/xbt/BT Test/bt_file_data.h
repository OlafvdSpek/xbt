#if !defined(AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_)
#define AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_file_data  
{
public:
	Cbt_file_data();

	__int64 m_downloaded;
	__int64 m_downloaded_l5;
	__int64 m_left;
	__int64 m_total_downloaded;
	__int64 m_total_uploaded;
	__int64 m_uploaded;
	__int64 m_uploaded_l5;
	__int64 mcb_f;
	__int64 mcb_piece;
	bool m_seeding_ratio_override;
	int m_completed_at;
	int m_seeding_ratio;
	int m_session_started_at;
	int m_started_at;
	int mc_leechers_total;
	int mc_rejected_chunks;
	int mc_rejected_pieces;
	int mc_seeders_total;
};

#endif // !defined(AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_)
