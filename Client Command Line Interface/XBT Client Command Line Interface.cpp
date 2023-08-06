#include "stdafx.h"
#include "../BT Test/bt_file.h"
#include <boost/program_options.hpp>
#include <xbt/bt_misc.h>
#include <bt_strings.h>
#include <bvalue.h>
#include <iomanip>
#include <iostream>

namespace asio = boost::asio;
namespace po = boost::program_options;
using asio::ip::tcp;

int recv(tcp::socket& s, Cbvalue* v)
{
	std::vector<char> d(5);
	memory_range w(&*d.begin(), d.size());
	int r;
	while (w.size() && (r = s.receive(asio::mutable_buffers_1(asio::mutable_buffer(w.begin, w.size())))))
	{
		if (r == SOCKET_ERROR)
			return r;
		w += r;
	}
	d.resize(read_int(4, &d.front()) - 1);
	w = memory_range(&*d.begin(), d.size());
	while (w.size() && (r = s.receive(asio::mutable_buffers_1(asio::mutable_buffer(w.begin, w.size())))))
	{
		if (r == SOCKET_ERROR)
			return r;
		w += r;
	}
	return v ? v->write(&d.front(), d.size()) : 0;
}

int send(tcp::socket& s, const Cbvalue& v)
{
	char d0[5];
	Cvirtual_binary d1 = v.read();
	write_int(4, d0, d1.size() + 1);
	d0[4] = bti_bvalue;
	if (s.send(asio::const_buffers_1(asio::const_buffer(d0, 5))) != 5)
		return 1;
	return s.send(asio::const_buffers_1(asio::const_buffer(d1, d1.size()))) != d1.size();
}

Cbvalue send_recv(tcp::socket& s, const Cbvalue& v)
{
	if (int r = send(s, v))
		throw std::runtime_error(("Csocket::send failed: " + Csocket::error2a(WSAGetLastError())));
	Cbvalue w;
	if (int r = recv(s, &w))
		throw std::runtime_error(("Csocket::recv failed: " + Csocket::error2a(WSAGetLastError())));
	if (w.d_has(bts_failure_reason))
		throw std::runtime_error(("admin request failed: " + w[bts_failure_reason].s()));
	return w;
}

std::string strip_name(const std::string& v)
{
	int i = v.find_last_of("/\\");
	return i == std::string::npos ? v : v.substr(i + 1);
}

std::ostream& show_options(std::ostream& os, const Cbvalue& v)
{
	os
		<< "admin port:      " << v[bts_admin_port].i() << std::endl
		<< "peer port:       " << v[bts_peer_port].i() << std::endl
		<< "tracker port:    " << v[bts_tracker_port].i() << std::endl
		<< "upload rate:     " << v[bts_upload_rate].i() << std::endl
		<< "upload slots:    " << v[bts_upload_slots].i() << std::endl
		<< "seeding ratio:   " << v[bts_seeding_ratio].i() << std::endl
		<< "completes dir:   " << v[bts_completes_dir].s() << std::endl
		<< "incompletes dir: " << v[bts_incompletes_dir].s() << std::endl
		<< "torrents dir:    " << v[bts_torrents_dir].s() << std::endl
		<< "user agent:      " << v[bts_user_agent].s() << std::endl
		;
	return os;
}

std::ostream& show_status(std::ostream& os, const Cbvalue& v)
{
	os << "    left     size downloaded  uploaded down_rate   up_rate   leechers      seeders  info_hash                                 name" << std::endl;
	const Cbvalue& files = v[bts_files];
	for (Cbvalue::t_map::const_iterator i = files.d().begin(); i != files.d().end(); i++)
	{
		os
			<< std::setw(8) << b2a(i->second[bts_left].i())
			<< std::setw(10) << b2a(i->second[bts_size].i())
			<< std::setw(10) << b2a(i->second[bts_total_downloaded].i())
			<< std::setw(10) << b2a(i->second[bts_total_uploaded].i())
			<< std::setw(10) << b2a(i->second[bts_down_rate].i())
			<< std::setw(10) << b2a(i->second[bts_up_rate].i())
			<< std::setw(5) << i->second[bts_incomplete].i()
			;
		if (i->second[bts_incomplete_total].i())
			os << " / " << std::setw(3) << i->second[bts_incomplete_total].i();
		else
			os << "      ";
		os << "    " << std::setw(3) << i->second[bts_complete].i();
		if (i->second[bts_complete_total].i())
			os << " / " << std::setw(3) << i->second[bts_complete_total].i();
		else
			os << "      ";
		os
			<< "  " << hex_encode(i->first)
			<< "  " << strip_name(i->second[bts_name].s())
			<< std::endl;
	}
	return os;
}

int main(int argc, char* argv[])
{
	try
	{
		po::options_description desc;
		desc.add_options()
			("backend_host", po::value<std::string>()->default_value("localhost"))
			("backend_port", po::value<std::string>()->default_value("6879"))
			("backend_user", po::value<std::string>()->default_value("xbt"))
			("backend_pass", po::value<std::string>()->default_value(""))
			("close", po::value<std::string>())
			("conf_file", po::value<std::string>()->default_value("xbt_client_cli.conf"))
			("erase", po::value<std::string>())
			("help", "")
			("open", po::value<std::string>())
			("options", "")
			("pause", po::value<std::string>())
			("peer_port", po::value<int>())
			("queue", po::value<std::string>())
			("start", po::value<std::string>())
			("status", "")
			("stop", po::value<std::string>())
			("tracker_port", po::value<int>())
			("upload_rate", po::value<int>())
			("upload_slots", po::value<int>())
			("user_agent", po::value<std::string>())
			;
		po::variables_map vm;
		po::store(po::parse_command_line(argc, argv, desc), vm);
		std::ifstream is(vm["conf_file"].as<std::string>().c_str());
		po::store(po::parse_config_file(is, desc), vm);
		po::notify(vm);
		if (vm.count("help"))
		{
			std::cerr << desc;
			return 1;
		}
		asio::io_service io_service;
		tcp::resolver resolver(io_service);
		tcp::resolver::query query(vm["backend_host"].as<std::string>(), vm["backend_port"].as<std::string>());
		tcp::resolver::iterator endpoint_iterator = resolver.resolve(query);
		tcp::resolver::iterator end;
		tcp::socket s(io_service);
		boost::system::error_code error = asio::error::host_not_found;
		while (error && endpoint_iterator != end)
		{
			s.close();
			s.connect(*endpoint_iterator++, error);
		}
		if (error)
			throw error;
		Cbvalue v;
		if (vm.count("close"))
		{
			v.d(bts_action, bts_close_torrent);
			v.d(bts_hash, hex_decode(vm["close"].as<std::string>()));
		}
		else if (vm.count("erase"))
		{
			v.d(bts_action, bts_erase_torrent);
			v.d(bts_hash, hex_decode(vm["erase"].as<std::string>()));
		}
		else if (vm.count("open"))
		{
			Cvirtual_binary a;
			if (a.load(vm["open"].as<std::string>()))
				throw std::runtime_error("Unable to load .torrent");
			Cbvalue b;
			if (b.write(a))
				throw std::runtime_error("Unable to parse .torrent");
			v.d(bts_action, bts_open_torrent);
			v.d(bts_torrent, std::string(reinterpret_cast<const char*>(a.data()), a.size()));
		}
		else if (vm.count("pause"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["pause"].as<std::string>()));
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
			v.d(bts_hash, hex_decode(vm["queue"].as<std::string>()));
			v.d(bts_state, Cbt_file::s_queued);
		}
		else if (vm.count("start"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["start"].as<std::string>()));
			v.d(bts_state, Cbt_file::s_running);
		}
		else if (vm.count("stop"))
		{
			v.d(bts_action, bts_set_state);
			v.d(bts_hash, hex_decode(vm["stop"].as<std::string>()));
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
			v.d(bts_user_agent, std::string(vm["user_agent"].as<std::string>() == "-" ? "" : vm["user_agent"].as<std::string>()));
		}
		std::string backend_user = vm["backend_user"].as<std::string>();
		std::string backend_pass = vm["backend_pass"].as<std::string>();
		if (!v.d().empty())
		{
			v.d(bts_admin_user, backend_user);
			v.d(bts_admin_pass, backend_pass);
			send_recv(s, v);
		}
		if (vm.count("options"))
		{
			Cbvalue v;
			v.d(bts_action, bts_get_options);
			v.d(bts_admin_user, backend_user);
			v.d(bts_admin_pass, backend_pass);
			show_options(std::cout, send_recv(s, v));
		}
		if (vm.count("status") || argc < 2)
		{
			Cbvalue v;
			v.d(bts_action, bts_get_status);
			v.d(bts_admin_user, backend_user);
			v.d(bts_admin_pass, backend_pass);
			show_status(std::cout, send_recv(s, v));
		}
		return 0;
	}
	catch (std::exception& e)
	{
		std::cerr << e.what() << std::endl;
		return 1;
	}
	catch (boost::system::error_code& e)
	{
		std::cerr << e.message() << std::endl;
		return 1;
	}
}
