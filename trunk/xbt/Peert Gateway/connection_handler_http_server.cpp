#include "stdafx.h"
#include "connection_handler_http_server.h"

#include "connection.h"
#include "server.h"

Cconnection_handler_http_server::Cconnection_handler_http_server()
{
}

Cconnection_handler_http_server::~Cconnection_handler_http_server()
{
}

int Cconnection_handler_http_server::post_recv(Cconnection* con)
{
	string s(con->read_b().r(), con->read_b().cb_r());
	if (s.find("\r\n\r\n") != string::npos)
	{
		int i = s.find("\r\n");
		read(con, s.substr(0, i));
		return 1;
	}
	return 0;
}

int Cconnection_handler_http_server::post_send(Cconnection* con)
{
	return 0;
}

static int send(const Cbvalue& v, Cbvalue* v1 = NULL)
{
	Csocket a;
	if (a.open(SOCK_STREAM, true) == INVALID_SOCKET)
		return 1;
	if (a.connect(htonl(INADDR_LOOPBACK), htons(6879)))
		return 1;
	char d0[5];
	Cvirtual_binary d1 = v.read();
	*reinterpret_cast<__int32*>(d0) = htonl(d1.size() + 1);
	d0[4] = bti_bvalue;
	if (a.send(d0, 5) != 5)
		return 1;
	if (a.send(d1, d1.size()) != d1.size())
		return 1;
	const int cb_d = 1 << 20;
	vector<char> d(cb_d);
	char* w = &d.front();
	int r;
	while (w - &d.front() != cb_d && (r = a.recv(w, &d.front() + cb_d - w)))
	{
		if (r == SOCKET_ERROR)
			break;
		w += r;
		if (w - &d.front() >= 4 && w - &d.front() >= ntohl(*reinterpret_cast<__int32*>(&d.front())))
			return v1 ? v1->write(&d.front() + 5, ntohl(*reinterpret_cast<__int32*>(&d.front())) - 1) : 0;
	}
	return 1;
}

void Cconnection_handler_http_server::read(Cconnection* con, const string& v)
{
#if 1 // ndef NDEBUG
	cout << v << endl;
#endif
	string h = "HTTP/1.0 200 OK\r\n";
	string s;
	h += "Content-Type: text/plain; charset=utf-8\r\n";
	int a = v.find(' ');
	if (a == string::npos)
		return;
	string request_method = v.substr(0, a);
	string script_name;
	string info_hash;
	string pass_id;
	int priority = 0;
	string session_id;
	int state = 0;
	string sub_domain;
	string url;
	a++;
	int b = v.find_first_of(" ?", a);
	if (b != string::npos)
	{
		script_name = v.substr(a, b - a);
		if (v[b] == '?')
		{
			a = b + 1;
			int b = v.find(' ', a);
			if (b == string::npos)
				return;
			while (a < b)
			{
				int c = v.find('=', a);
				if (c++ == string::npos)
					break;
				int d = v.find_first_of(" &;", c);
				assert(d != string::npos);
				string name = v.substr(a, c - a - 1);
				string value = uri_decode(v.substr(c, d - c));
				if (name == "info_hash")
					info_hash = hex_decode(value);
				else if (name == "session_token")
					pass_id = value;
				else if (name == "priority")
					priority = atoi(value.c_str());
				else if (name == "session_id")
					session_id = value;
				else if (name == "state")
					state = atoi(value.c_str());
				else if (name == "sub_domain")
					sub_domain = value;
				else if (name == "url")
					url = value;
				a = d + 1;
			}
		}
	}
	bool pass_valid = pass_id == con->server()->pass();
	if (script_name == "/port/")
	{
		s += "_xbt.callback_port(" + n(con->server()->port()) + ");\n";
	}
	else if (script_name == "/session/")
	{
		if (sub_domain.empty() || sub_domain.size() > 64 || sub_domain.find_first_not_of("abcdefghijklmnopqrstuvwxyz0123456789") != string::npos)
			s += "_xbt.callback_error();\n";
		else
		{
			string d;
			string service = "http://" + sub_domain + ".peert.com/services/rest/";
			con->server()->http_get(service + "?method=peert.session.setToken&session_id=" + uri_encode(session_id) + "&session_token=" + uri_encode(con->server()->pass()), d);
			s += "_xbt.callback_session();\n";
		}
	}
	else if (!pass_valid)
	{
		s += "_xbt.callback_error(0, 'authentication error');\n";
	}
	else if (script_name == "/close/")
	{
		s += "_xbt.callback_error();\n";
		Cbvalue v;
		v.d(bts_action, bts_close_torrent);
		v.d(bts_hash, info_hash);
		if (::send(v))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_close('" + hex_encode(info_hash) + "');\n";
		}
	}
	else if (script_name == "/delete/")
	{
		Cbvalue v;
		v.d(bts_action, bts_erase_torrent);
		v.d(bts_hash, info_hash);
		if (::send(v))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_delete('" + hex_encode(info_hash) + "');\n";
		}
	}
	else if (script_name == "/make/")
	{
	}
	else if (script_name == "/open/")
	{
		string d;
		con->server()->http_get(url, d);
		Cbvalue v;
		v.d(bts_action, bts_open_torrent);
		v.d(bts_torrent, d);
		if (::send(v))
			s += "_xbt.callback_error();\n";
		else
		{
			Cbvalue d1(d.c_str(), d.size());
			s += "_xbt.callback_open('" + hex_encode(compute_sha1(d1.d(bts_info).read())) + "', '" + d1.d(bts_info).d(bts_name).s() + "', " + n(d1.d(bts_info).d(bts_length).i()) + ");\n";
		}
	}
	else if (script_name == "/options/")
	{
	}
	else if (script_name == "/priority/")
	{
		Cbvalue v;
		v.d(bts_action, bts_set_priority);
		v.d(bts_hash, info_hash);
		v.d(bts_priority, priority);
		if (::send(v))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_priority('" + hex_encode(info_hash) + "', " + n(state) + ");\n";
		}		
	}
	else if (script_name == "/state/")
	{
		Cbvalue v;
		v.d(bts_action, bts_set_state);
		v.d(bts_hash, info_hash);
		v.d(bts_state, state);
		if (::send(v))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_state('" + hex_encode(info_hash) + "', " + n(state) + ");\n";
		}		
	}
	else if (script_name == "/update/")
	{
		Cbvalue v, d;
		v.d(bts_action, bts_get_status);
		if (::send(v, &d))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_beginUpdate();\n";
			const Cbvalue& files = d.d(bts_files);
			for (Cbvalue::t_map::const_iterator i = files.d().begin(); i != files.d().end(); i++)
			{
				s += "_xbt.callback_update('" + hex_encode(i->first) + "', " 
					+ "'" + js_encode(i->second.d(bts_name).s()) + "', " 
					+ n(i->second.d(bts_left).i()) + ", " 
					+ n(i->second.d(bts_size).i()) + ", " 
					+ n(i->second.d(bts_total_downloaded).i()) + ", " 
					+ n(i->second.d(bts_total_uploaded).i()) + ", " 
					+ n(i->second.d(bts_down_rate).i()) + ", " 
					+ n(i->second.d(bts_up_rate).i()) + ", " 
					+ n(i->second.d(bts_incomplete).i()) + ", " 
					+ n(i->second.d(bts_incomplete_total).i()) + ", " 
					+ n(i->second.d(bts_complete).i()) + ", " 
					+ n(i->second.d(bts_complete_total).i()) + ", " 
					+ n(i->second.d(bts_priority).i()) + ", " 
					+ n(i->second.d(bts_state).i()) + ", " 
					+ n(i->second.d(bts_completed_at).i()) + ", " 
					+ n(i->second.d(bts_started_at).i()) + ");\n";
			}
			s += "_xbt.callback_endUpdate();\n";
		}
	}
	else if (script_name == "/version/")
	{
		Cbvalue v, d;
		v.d(bts_action, bts_get_status);
		if (::send(v, &d))
			s += "_xbt.callback_error();\n";
		else
		{
			s += "_xbt.callback_version(0, '" + (d.d(bts_version).s()) + "');\n";;
		}
	}
	else
	{
		h = "HTTP/1.0 404 Not Found\r\n";
	}
	h += "\r\n";
	Cvirtual_binary d;
	memcpy(d.write_start(h.size() + s.size()), h.c_str(), h.size());
	memcpy(d.data_edit() + h.size(), s.c_str(), s.size());
	int r = con->s().send(d, d.size());
	if (r == SOCKET_ERROR)
		cerr << "send failed: " << Csocket::error2a(WSAGetLastError()) << endl;
	else if (r != d.size())
	{
		cerr << "send failed: partial send" << endl;
	}
}