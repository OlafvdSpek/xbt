#if !defined(AFX_LISTCTRLEX1_H__EB7D38BC_3076_4F00_B25A_2442DA63FAFB__INCLUDED_)
#define AFX_LISTCTRLEX1_H__EB7D38BC_3076_4F00_B25A_2442DA63FAFB__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include "ListCtrlEx.h"

class CListCtrlEx1: public CListCtrlEx
{
public:
	//{{AFX_VIRTUAL(CListCtrlEx1)
	protected:
	virtual void PreSubclassWindow();
	//}}AFX_VIRTUAL
public:
	string Conf() const;
	void Conf(const string&);
	void DeleteAllColumns();
	int GetColumnID(int index) const;
	void InsertColumn(int id, const string& name, int format = LVCFMT_LEFT);
	void ShowColumn(int id, bool show);
protected:
	virtual BOOL OnCommand(WPARAM wParam, LPARAM lParam);
	//{{AFX_MSG(CListCtrlEx1)
	afx_msg void OnContextMenu(CWnd* pWnd, CPoint point);
	//}}AFX_MSG
	DECLARE_MESSAGE_MAP()
private:
	void DeleteColumn();

	class Ccolumn
	{
	public:
		Ccolumn();

		int format;
		int index;
		string name;
		int order;

		bool show() const
		{
			return index != -1;
		}
	};

	typedef std::map<int, Ccolumn> Ccolumns;

	Ccolumns m_columns;
	string m_conf;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_LISTCTRLEX1_H__EB7D38BC_3076_4F00_B25A_2442DA63FAFB__INCLUDED_)
