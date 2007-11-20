#pragma once

#include "ListCtrlEx.h"

class CListCtrlEx1: public CListCtrlEx
{
public:
	std::string Conf() const;
	void Conf(const std::string&);
	void DeleteAllColumns();
	int GetColumnID(int index) const;
	void InsertColumn(int id, const std::string& name, const std::string& description = "", int format = LVCFMT_LEFT, bool show = true);
	void ShowColumn(int id, bool show);
protected:
	virtual void PreSubclassWindow();
	virtual BOOL OnCommand(WPARAM wParam, LPARAM lParam);
	afx_msg void OnContextMenu(CWnd* pWnd, CPoint point);
	DECLARE_MESSAGE_MAP()
private:
	void DeleteColumn();

	class Ccolumn
	{
	public:
		Ccolumn();

		std::string description;
		int format;
		int index;
		std::string name;
		int order;

		bool show() const
		{
			return index != -1;
		}
	};

	typedef std::map<int, Ccolumn> Ccolumns;

	Ccolumns m_columns;
	std::string m_conf;
};
