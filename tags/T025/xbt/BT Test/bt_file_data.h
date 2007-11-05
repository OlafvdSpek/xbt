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

	long long m_downloaded;
	long long m_downloaded_l5;
	long long m_left;
	long long m_size;
	long long m_total_downloaded;
	long long m_total_uploaded;
	long long m_uploaded;
	long long m_uploaded_l5;
	long long mcb_piece;
	bool m_allow_end_mode;
	bool m_end_mode;
	bool m_seeding_ratio_override;
	bool m_upload_slots_max_override;
	bool m_upload_slots_min_override;
	time_t m_completed_at;
	time_t m_last_chunk_downloaded_at;
	time_t m_last_chunk_uploaded_at;
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
	std::string m_info_hash;
	std::string m_name;
	t_state m_state;
};

#endif // !defined(AFX_BT_FILE_DATA_H__2F382742_37BF_4FA1_9DD5_8973FC283EA5__INCLUDED_)
