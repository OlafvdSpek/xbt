#if !defined(AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_)
#define AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_file_data  
{
public:
	enum t_state
	{
		s_queued,
		s_hashing,
		s_running,
		s_paused,
		s_stopped,
		s_unknown,
	};

	Cbt_file_data();

	__int64 m_downloaded;
	__int64 m_downloaded_l5;
	__int64 m_left;
	__int64 m_size;
	__int64 m_total_downloaded;
	__int64 m_total_uploaded;
	__int64 m_uploaded;
	__int64 m_uploaded_l5;
	__int64 mcb_piece;
	bool m_allow_end_mode;
	bool m_end_mode;
	bool m_seeding_ratio_override;
	bool m_upload_slots_max_override;
	bool m_upload_slots_min_override;
	time_t m_completed_at;
	int m_priority;
	int m_seeding_ratio;
	time_t m_seeding_ratio_reached_at;
	time_t m_session_started_at;
	time_t m_started_at;
	int m_upload_slots_max;
	int m_upload_slots_min;
	int mc_leechers_total;
	int mc_rejected_chunks;
	int mc_rejected_pieces;
	int mc_seeders_total;
	string m_info_hash;
	string m_name;
	t_state m_state;
};

#endif // !defined(AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_)
