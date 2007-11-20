#include "stdafx.h"
#include "dlg_profile.h"

Cdlg_profile::Cdlg_profile(CWnd* pParent /*=NULL*/):
	ETSLayoutDialog(Cdlg_profile::IDD, pParent, "Cdlg_profile")
{
	//{{AFX_DATA_INIT(Cdlg_profile)
	m_peer_limit_value = 0;
	m_seeding_ratio_value = 0;
	m_torrent_limit_value = 0;
	m_upload_rate_value = 0;
	m_upload_slots_value = 0;
	m_peer_limit_enable = FALSE;
	m_seeding_ratio_enable = FALSE;
	m_torrent_limit_enable = FALSE;
	m_upload_rate_enable = FALSE;
	m_upload_slots_enable = FALSE;
	m_name = _T("");
	//}}AFX_DATA_INIT
}


void Cdlg_profile::DoDataExchange(CDataExchange* pDX)
{
	ETSLayoutDialog::DoDataExchange(pDX);
	//{{AFX_DATA_MAP(Cdlg_profile)
	DDX_Control(pDX, IDC_UPLOAD_SLOTS, m_upload_slots);
	DDX_Control(pDX, IDC_UPLOAD_RATE, m_upload_rate);
	DDX_Control(pDX, IDC_TORRENT_LIMIT, m_torrent_limit);
	DDX_Control(pDX, IDC_SEEDING_RATIO, m_seeding_ratio);
	DDX_Control(pDX, IDC_PEER_LIMIT, m_peer_limit);
	DDX_Text(pDX, IDC_PEER_LIMIT, m_peer_limit_value);
	DDX_Text(pDX, IDC_SEEDING_RATIO, m_seeding_ratio_value);
	DDX_Text(pDX, IDC_TORRENT_LIMIT, m_torrent_limit_value);
	DDX_Text(pDX, IDC_UPLOAD_RATE, m_upload_rate_value);
	DDX_Text(pDX, IDC_UPLOAD_SLOTS, m_upload_slots_value);
	DDX_Check(pDX, IDC_PEER_LIMIT_ENABLE, m_peer_limit_enable);
	DDX_Check(pDX, IDC_SEEDING_RATIO_ENABLE, m_seeding_ratio_enable);
	DDX_Check(pDX, IDC_TORRENT_LIMIT_ENABLE, m_torrent_limit_enable);
	DDX_Check(pDX, IDC_UPLOAD_RATE_ENABLE, m_upload_rate_enable);
	DDX_Check(pDX, IDC_UPLOAD_SLOTS_ENABLE, m_upload_slots_enable);
	DDX_Text(pDX, IDC_NAME, m_name);
	//}}AFX_DATA_MAP
}


BEGIN_MESSAGE_MAP(Cdlg_profile, ETSLayoutDialog)
	//{{AFX_MSG_MAP(Cdlg_profile)
	ON_BN_CLICKED(IDC_SEEDING_RATIO_ENABLE, OnSeedingRatioEnable)
	ON_BN_CLICKED(IDC_UPLOAD_RATE_ENABLE, OnUploadRateEnable)
	ON_BN_CLICKED(IDC_UPLOAD_SLOTS_ENABLE, OnUploadSlotsEnable)
	ON_BN_CLICKED(IDC_PEER_LIMIT_ENABLE, OnPeerLimitEnable)
	ON_BN_CLICKED(IDC_TORRENT_LIMIT_ENABLE, OnTorrentLimitEnable)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()

BOOL Cdlg_profile::OnInitDialog()
{
	ETSLayoutDialog::OnInitDialog();
	update_controls();
	CreateRoot(VERTICAL)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< (pane(VERTICAL, NORESIZE)
				<< item(IDC_NAME_STATIC)
				<< item(IDC_SEEDING_RATIO_ENABLE)
				<< item(IDC_UPLOAD_RATE_ENABLE)
				<< item(IDC_UPLOAD_SLOTS_ENABLE)
				<< item(IDC_PEER_LIMIT_ENABLE)
				<< item(IDC_TORRENT_LIMIT_ENABLE)
				)
			<< (pane(VERTICAL, ABSOLUTE_VERT)
				<< item(IDC_NAME)
				<< item(IDC_SEEDING_RATIO)
				<< item(IDC_UPLOAD_RATE)
				<< item(IDC_UPLOAD_SLOTS)
				<< item(IDC_PEER_LIMIT)
				<< item(IDC_TORRENT_LIMIT)
				)
			)
		<< (pane(HORIZONTAL, ABSOLUTE_VERT)
			<< itemGrowing(HORIZONTAL)
			<< item(IDOK, NORESIZE)
			<< item(IDCANCEL, NORESIZE)
			)
		;
	UpdateLayout();

	return true;
}

void Cdlg_profile::update_controls()
{
	UpdateData(true);
	m_seeding_ratio.EnableWindow(m_seeding_ratio_enable);
	m_upload_rate.EnableWindow(m_upload_rate_enable);
	m_upload_slots.EnableWindow(m_upload_slots_enable);
	m_peer_limit.EnableWindow(m_peer_limit_enable);
	m_torrent_limit.EnableWindow(m_torrent_limit_enable);
}

void Cdlg_profile::OnSeedingRatioEnable()
{
	update_controls();
}

void Cdlg_profile::OnUploadRateEnable()
{
	update_controls();
}

void Cdlg_profile::OnUploadSlotsEnable()
{
	update_controls();
}

void Cdlg_profile::OnPeerLimitEnable()
{
	update_controls();
}

void Cdlg_profile::OnTorrentLimitEnable()
{
	update_controls();
}
