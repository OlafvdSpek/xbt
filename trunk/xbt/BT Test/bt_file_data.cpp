#include "stdafx.h"
#include "bt_file_data.h"

Cbt_file_data::Cbt_file_data()
{
	m_seeding_ratio = 0;
	m_seeding_ratio_override = false;
	m_upload_slots_max = 0;
	m_upload_slots_max_override = false;
	m_upload_slots_min = 0;
	m_upload_slots_min_override = false;
}
