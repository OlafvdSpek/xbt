// tracker_socket.cpp : implementation file
//

#include "stdafx.h"
#include "xbt manager.h"
#include "XBT ManagerDlg.h"
#include "tracker_socket.h"

#include <wininet.h>
#include "bt_misc.h"
#include "bt_strings.h"
#include "bvalue.h"

#ifdef _DEBUG
#define new DEBUG_NEW
#undef THIS_FILE
static char THIS_FILE[] = __FILE__;
#endif

/////////////////////////////////////////////////////////////////////////////
// Ctracker_socket

// Do not edit the following lines, which are needed by ClassWizard.
#if 0
BEGIN_MESSAGE_MAP(Ctracker_socket, CAsyncSocket)
	//{{AFX_MSG_MAP(Ctracker_socket)
	//}}AFX_MSG_MAP
END_MESSAGE_MAP()
#endif	// 0

/////////////////////////////////////////////////////////////////////////////
// Ctracker_socket member functions

static string error_string(int v)
{
	const int cb_b = 1 << 10;
	char b[cb_b];
	return FormatMessage(FORMAT_MESSAGE_FROM_SYSTEM, NULL, v, 0, b, cb_b, NULL)
		? b
		: error_string(v);

}

void Ctracker_socket::OnConnect(int nErrorCode) 
{
	if (nErrorCode)
		output_error("connect failed: " + error_string(nErrorCode));
	else
	{
		string b = "GET /scrape?info_hash=" + uri_encode(m_hash) + " HTTP/1.0\r\n\r\n";
		if (Send(b.c_str(), b.length()) != b.length())
			output_error("send failed: " + error_string(GetLastError()));
	}
}

void Ctracker_socket::dlg(CXBTManagerDlg* v)
{
	m_dlg = v;
}

void Ctracker_socket::hash(const string& v)
{
	m_hash = v;
}

bool Ctracker_socket::connect(const string& v)
{
	if (!Create())
	{
		output_error("socket failed: " + error_string(GetLastError()));
		return false;
	}
	URL_COMPONENTS components;
	ZeroMemory(&components, sizeof(URL_COMPONENTS));
	components.dwStructSize = sizeof(URL_COMPONENTS);
	components.dwHostNameLength = -1;
	components.dwUrlPathLength = -1;
	if (!InternetCrackUrl(v.c_str(), v.length(), 0, &components))
	{
		output_error("InternetCrackUrl failed: " + error_string(::GetLastError()));
		return false;
	}
	if (Connect(string(components.lpszHostName, components.dwHostNameLength).c_str(), components.nPort) && GetLastError() != WSAEWOULDBLOCK)
	{
		output_error("connect failed: " + error_string(GetLastError()));
		return false;
	}
	return true;
}

void Ctracker_socket::OnReceive(int nErrorCode) 
{
	if (nErrorCode)
	{
		output_error("recv failed: " + error_string(nErrorCode));
		return;
	}
	const int cb_b = 4 << 10;
	char b[cb_b];
	while (1)
	{
		int r = Receive(b, cb_b);
		switch (r)
		{
		case SOCKET_ERROR:
			if (GetLastError() != WSAEWOULDBLOCK)
				output_error("recv failed: " + error_string(GetLastError()));
		case 0:
			return;
		}
		m_recv_buffer.append(b, r);
	}		
}

void Ctracker_socket::output_error(const string& s)
{
	Cbvalue v;
	v.d(bts_failure_reason, s);
	m_dlg->tracker_output(m_hash, v);
}

void Ctracker_socket::OnSend(int nErrorCode) 
{
	if (nErrorCode)
		output_error("send failed: " + error_string(nErrorCode));
}

void Ctracker_socket::OnClose(int nErrorCode) 
{
	if (nErrorCode)
	{
		output_error("closed: " + error_string(nErrorCode));
		return;
	}
	int a = m_recv_buffer.find(' ');
	if (a == string::npos)
	{
		output_error("invalid HTTP output");
		return;
	}
	if (atoi(m_recv_buffer.c_str() + a) != 200)
	{
		output_error("http error: " + error_string(atoi(m_recv_buffer.c_str() + a)));
		return;
	}
	a = m_recv_buffer.find("\r\n\r\n");
	if (a == string::npos)
	{
		a = m_recv_buffer.find("\n\n");
		if (a == string::npos)
		{
			output_error("invalid HTTP output");
			return;
		}
		a += 2;
	}
	else
		a += 4;
	Cbvalue v;
	if (v.write(m_recv_buffer.c_str() + a, m_recv_buffer.length() - a))
	{
		output_error("invalid BT tracker output");
		return;
	}
	m_dlg->tracker_output(m_hash, v);
}
