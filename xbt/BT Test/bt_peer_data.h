#if !defined(AFX_BT_PEER_DATA_H__BB3CD676_063C_4194_B0F6_BE7BCD05EB36__INCLUDED_)
#define AFX_BT_PEER_DATA_H__BB3CD676_063C_4194_B0F6_BE7BCD05EB36__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class Cbt_peer_data  
{
public:
	Cbt_peer_data();

	__int64 m_downloaded;
	__int64 m_left;
	__int64 m_uploaded;
	bool m_local_link;
	bool m_local_choked;
	bool m_local_interested;
	bool m_remote_choked;
	bool m_remote_interested;
	string m_remote_peer_id;
};

#endif // !defined(AFX_BT_PEER_DATA_H__BB3CD676_063C_4194_B0F6_BE7BCD05EB36__INCLUDED_)
