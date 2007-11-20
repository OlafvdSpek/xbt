#pragma once

class CURLStatic: public CStatic
{
protected:
	afx_msg HBRUSH CtlColor(CDC* pDC, UINT nCtlColor);
	DECLARE_MESSAGE_MAP()
};
