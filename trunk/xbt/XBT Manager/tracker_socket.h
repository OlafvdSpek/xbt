#if !defined(AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_)
#define AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

// tracker_socket.h : header file
//

class CXBTManagerDlg;

/////////////////////////////////////////////////////////////////////////////
// Ctracker_socket command target

class Ctracker_socket : public CAsyncSocket
{
// Attributes
public:
	void dlg(CXBTManagerDlg* v);
	void hash(const string& v);

// Operations
public:

// Overrides
public:
	void output_error(const string& s);
	bool connect(const string& v);
	// ClassWizard generated virtual function overrides
	//{{AFX_VIRTUAL(Ctracker_socket)
	public:
	virtual void OnConnect(int nErrorCode);
	virtual void OnReceive(int nErrorCode);
	virtual void OnSend(int nErrorCode);
	virtual void OnClose(int nErrorCode);
	//}}AFX_VIRTUAL

	// Generated message map functions
	//{{AFX_MSG(Ctracker_socket)
		// NOTE - the ClassWizard will add and remove member functions here.
	//}}AFX_MSG

// Implementation
protected:
private:
	CXBTManagerDlg* m_dlg;
	string m_hash;
	string m_recv_buffer;
};

/////////////////////////////////////////////////////////////////////////////

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_)
