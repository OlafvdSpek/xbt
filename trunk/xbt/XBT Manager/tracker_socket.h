#if !defined(AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_)
#define AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class CXBTManagerDlg;

class Ctracker_socket: public CAsyncSocket
{
public:
	void dlg(CXBTManagerDlg* v);
	void hash(const string& v);
	void output_error(const string& s);
	bool connect(const string& v);
	//{{AFX_VIRTUAL(Ctracker_socket)
	public:
	virtual void OnConnect(int nErrorCode);
	virtual void OnReceive(int nErrorCode);
	virtual void OnSend(int nErrorCode);
	virtual void OnClose(int nErrorCode);
	//}}AFX_VIRTUAL

	//{{AFX_MSG(Ctracker_socket)
	//}}AFX_MSG
protected:
private:
	CXBTManagerDlg* m_dlg;
	string m_hash;
	string m_recv_buffer;
};

//{{AFX_INSERT_LOCATION}}

#endif // !defined(AFX_TRACKER_SOCKET_H__50E394AF_B639_472E_A434_52596BBA4927__INCLUDED_)
