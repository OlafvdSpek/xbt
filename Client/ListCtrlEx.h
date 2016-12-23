#pragma once

#include <string>

class CListCtrlEx: public CListCtrl
{
public:
	virtual BOOL PreTranslateMessage(MSG* pMsg);
public:
	std::string& get_buffer();
	std::string get_selected_rows_tsv();
	void select_all();
	void DeleteAllColumns();
	DWORD GetItemData(int nItem) const;
	int InsertItemData(int nItem, DWORD dwData);
	int InsertItemData(DWORD dwData);
	void auto_size();
protected:
	virtual void PreSubclassWindow();
	afx_msg void OnCustomDraw(NMHDR* pNMHDR, LRESULT* pResult);
	afx_msg void OnSize(UINT nType, int cx, int cy);
	DECLARE_MESSAGE_MAP()
private:
	std::string m_buffer[4];
	int m_buffer_w;
};
