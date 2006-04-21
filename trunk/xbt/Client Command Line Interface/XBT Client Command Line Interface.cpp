#include "stdafx.h"
#include <boost/program_options.hpp>
#include "../BT Test/bt_file.h"
#include "bt_misc.h"
#include "bt_strings.h"
#include "bvalue.h"

namespace po = boost::program_options;

int recv(Csocket& s, Cbvalue* v)
{
	vector<char> d(5);
	vector<char>::iterator w = d.begin();
	int r;
	while (w != d.end() && (r = s.recv(&*w, d.end() - w)))
	{
		if (r == SOCKET_ERROR)
			return r;
		w += r;
	}
	d.resize(read_int(4, &d.front()) - 1);
	w = d.begin();
	while (w != d.end() && (r = s.recv(&*w, d.end() - w)))
	{
		if (r == SOCKET_ERROR)
			return r;
		w += r;
	}
	return v ? v->write(&d.front(), d.size()) : 0;
}

int send(Csocket& s, const Cbvalue& v)
{
	char d0[5];
	Cvirtual_binary d1 = v.read();
	write_int(4, d0, d1.size() + 1);
	d0[4] = bti_bvalue;
	if (s.send(d0, 5) != 5)
		return 1;
	return s.send(d1, d1.size()) != d1.size();
}

Cbvalue send_recv(Csocket& s, const Cbvalue& v)
{
	if (int r = send(s, v))
		throw runtime_error(("Csocket::send failed: " + Csocket::error2a(WSAGetLastError())));
	Cbvalue w;
	if (int r = recv(s, &w))
		throw runtime_error(("Csocket::recv failed: " + Csocket::error2a(WSAGetLastError())));
	if (w.d_has(bts_failure_reason))
		throw runtime_error(("admin request failed: " + w.d(bts_failure_reason).s()));
	return w;
}

string strip_name(const string& v)
{
	int i = v.find_last_of("/\\");
	return i == string::npos ? v : v.substr(i + 1);
}

ostream& show_options(ostream& os, const Cbvalue& v)
{
	cout
		<< "admin port:      " << v.d(bts_admin_port).i() << endl
		<< "peer port:       " << v.d(bts_peer_port).i() << endl
		<< "tracker port:    " << v.d(bts_tracker_port).i() << endl
		<< "upload rate:     " << v.d(bts_upload_rate).i() << endl
		<< "upload slots:    " << v.d(bts_upload_slots).i() << endl
		<< "seeding ratio:   " << v.d(bts_seeding_ratio).i() << endl
		<< "completes dir:   " << v.d(bts_completes_dir).s() << endl
		<< "incompletes dir: " << v.d(bts_incompletes_dir).s() << endl
		<< "torrents dir:    " << v.d(bts_torrents_dir).s() << endl
		<< "user agent:      " << v.d(bts_user_agent).s() << endl
		;
	return os;
}

ostream& show_status(ostream& os, const Cbvalue& v)
{
	cout << "    left     size downloaded  uploaded down_rate   up_rate leechers     seeders" << endl;
	const Cbvalue& files = v.d(bts_files);
	for (Cbvalue::t_map::const_iterator i = files.d().begin(); i != files.d().end(); i++)
	{
		cout << strip_name(i->second.d(bts_name).s())
			<< endl
			<< setw(8) << b2a(i->second.d(bts_left).i())
			<< setw(10) << b2a(i->second.d(bts_size).i())
			<< setw(10) << b2a(i->second.d(bts_total_downloaded).i())
			<< setw(10) << b2a(i->second.d(bts_total_uploaded).i())
			<< setw(10) << b2a(i->second.d(bts_down_rate).i())
			<< setw(10) << b2a(i->second.d(bts_up_rate).i())
			<< setw(7) << i->second.d(bts_incomplete).i();
		if (i->second.d(bts_incomplete_total).i())
			cout << " / " << setw(3) << i->second.d(bts_incomplete_total).i();
		else
			cout << "      ";
		cout << "    " << setw(3) << i->second.d(bts_complete).i();
		if (i->second.d(bts_complete_total).i())
			cout << " / " << setw(3) << i->second.d(bts_complete_total).i();
		else
			cout << "      ";
		cout << "    " << hex_encode(i->first)
			<< endl;
	}
	return os;
}

int main(int argc, char* argv[])
{
	try
	{
		po::options_description desc;
		desc.add_options()
			("backend_host", po::value<string>()->default_value("localhost"))
			("backend_port", po::value<int>()->default_value(6879))
			("backend_user", po::value<string>()->default_value(string()))
			("backend_pass", po::value<string>()->default_value(string()))
			("close", po::value<string>())
			("conf_file", po::value<string>()->default_value("xbt_client_cli.conf"))
			("erase", po::value<string>())
			("help", "")
			("open", po::value<string>())
			("options", "")
			("pause", po::value<string>())
			("peer_port", po::value<int>())
			("queue", po::value<string>())
			("start", po::value<string>())
			("status,s", "")
			("stop", po::value<string>())
			("tracker_port", po::value<int>())
			("upload_rate", po::value<int>())
			("upload_slots", po::value<int>())
			("user_agent", po::value<string>())
			;
		po::variables_map vm;
		po::store(po::parse_command_line(argc, argv, desc), vm);
		ifstream is(vm["conf_file"].as<string>().c_str());
		po::store(po::parse_config_file(is, desc), vm);
		po::notify(vm);
		Csocket s;
		if (s.open(SOCK_STREAM, true) == INVALID_SOCKET)
			throw runtime_error(("Csocket::open failed: " + Csocket::error2a(WSAGetLastError())));
		if (s.connect(Csocket::get_host(vm["backend_host"].as<string>()), htons(vm["backend_port"].as<int>())))
			throw runtime_error(("Csocket::connect failed: " + Csocket::error2a(WSAGetLastError())));
		Cbvalue v;
		if (vm.count("close"))
		{
			v.d(bts_action, bts_close_torrent);
			v.d(bts_hash, hex_decode(vm["close"].as<string>()));
		}
		else if (vm.count("erase"))
		{
			v.d(bts_action, bts_erase_torrent);
			v.d(bts_hash, hex_decode(vm["erase"].as<string>()));
		}
		else if (vm.count("open"))
		{
			Cvirtual_binary a;
			if (a.load(vm["open"].as<string>()))
				throw runtime_error("Unable to load .torrent");
			Cbvalue b;
			if (b.write(a))
				throw runtime_error("Unable to parse .torrent");
			v.d(bts_action, bts_open_torrent);
			v.d(bts_torrent, string(reinterpret_cast<const char*>(a.data()), a.size()));
		}
		else if (vm.count("pause"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["pause"].as<string>()));
			v.d(bts_state, Cbt_file::s_paused);
		}
		else if (vm.count("peer_port"))
		{
			v.d(bts_action, bts_set_options);
			v.d(bts_peer_port, vm["peer_port"].as<int>());
		}
		else if (vm.count("queue"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["queue"].as<string>()));
			v.d(bts_state, Cbt_file::s_queued);
		}
		else if (vm.count("start"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["start"].as<string>()));
			v.d(bts_state, Cbt_file::s_running);
		}
		else if (vm.count("stop"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["stop"].as<string>()));
			v.d(bts_state, Cbt_file::s_stopped);
		}
		else if (vm.count("tracker_port"))
		{
			v.d(bts_action, bts_set_options);
			v.d(bts_tracker_port, vm["tracker_port"].as<int>());
		}
		else if (vm.count("upload_rate"))
		{
			v.d(bts_action, bts_set_options);
			v.d(bts_upload_rate, vm["upload_rate"].as<int>() << 10);
		}
		else if (vm.count("upload_slots"))
		{
			v.d(bts_action, bts_set_options);
			v.d(bts_upload_slots, vm["upload_slots"].as<int>());
		}
		else if (vm.count("user_agent"))
		{
			v.d(bts_action, bts_set_options);
			v.d(bts_user_agent, string(vm["user_agent"].as<string>() == "-" ? "" : vm["user_agent"].as<string>()));
		}
		if (!v.d().empty())
		{
			v.d(bts_admin_user, vm["backend_user"].as<string>());
			v.d(bts_admin_pass, vm["backend_pass"].as<string>());
			send_recv(s, v);
		}
		if (vm.count("options"))
		{
			Cbvalue v;
			v.d(bts_action, bts_get_options);
			v.d(bts_admin_user, vm["backend_user"].as<string>());
			v.d(bts_admin_pass, vm["backend_pass"].as<string>());
			show_options(cout, send_recv(s, v));
		}
		if (vm.count("status"))
		{
			Cbvalue v;
			v.d(bts_action, bts_get_status);
			v.d(bts_admin_user, vm["backend_user"].as<string>());
			v.d(bts_admin_pass, vm["backend_pass"].as<string>());
			show_status(cout, send_recv(s, v));
		}
		if (vm.count("help") || argc < 2)
		{
			cerr << desc;
			return 1;
		}
		return 0;
	}
	catch (exception& e)
	{
		cerr << e.what() << endl;
		return 1;
	}
}
