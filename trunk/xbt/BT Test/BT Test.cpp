#include "stdafx.h"
#include <boost/program_options.hpp>
#include "windows/nt_service.h"
#include "server.h"

string g_conf_file;
const char* g_service_name = "XBT Client";

int main1()
{
	srand(time(NULL));
	Cserver server;
	server.load_config(g_conf_file);
	server.run();
	return 0;
}

#ifdef WIN32
static SERVICE_STATUS g_service_status;
static SERVICE_STATUS_HANDLE gh_service_status;

void WINAPI nt_service_handler(DWORD op)
{
	switch (op)
	{
	case SERVICE_CONTROL_STOP:
		g_service_status.dwCurrentState = SERVICE_STOP_PENDING;
		SetServiceStatus(gh_service_status, &g_service_status);
		Cserver::term();
		break;
	}
	SetServiceStatus(gh_service_status, &g_service_status);
}

void WINAPI nt_service_main(DWORD argc, LPTSTR* argv)
{
	g_service_status.dwCheckPoint = 0;
	g_service_status.dwControlsAccepted = SERVICE_ACCEPT_STOP;
	g_service_status.dwCurrentState = SERVICE_START_PENDING;
	g_service_status.dwServiceSpecificExitCode = 0;
	g_service_status.dwServiceType = SERVICE_WIN32_OWN_PROCESS;
	g_service_status.dwWaitHint = 0;
	g_service_status.dwWin32ExitCode = NO_ERROR;
	if (!(gh_service_status = RegisterServiceCtrlHandler(g_service_name, nt_service_handler)))
		return;
	SetServiceStatus(gh_service_status, &g_service_status);
	g_service_status.dwCurrentState = SERVICE_RUNNING;
	SetServiceStatus(gh_service_status, &g_service_status);
	main1();
	g_service_status.dwCurrentState = SERVICE_STOPPED;
	SetServiceStatus(gh_service_status, &g_service_status);
}
#endif

int main(int argc, char* argv[])
{
	try
	{
		namespace po = boost::program_options;

		po::options_description desc;
		desc.add_options()
			("conf_file", po::value<string>()->default_value(Cserver().conf_fname()))
			("help", "")
#ifdef WIN32
			("install", "")
			("uninstall", "")
#endif
			;
		po::variables_map vm;
		po::store(po::parse_command_line(argc, argv, desc), vm);
		ifstream is(vm["conf_file"].as<string>().c_str());
		po::store(po::parse_config_file(is, desc), vm);
		po::notify(vm);
		if (vm.count("help"))
		{
			cerr << desc;
			return 1;
		}
		g_conf_file = vm["conf_file"].as<string>();
#ifdef WIN32
		if (vm.count("install"))
		{
			if (nt_service_install(g_service_name))
				return cerr << "Failed to install service " << g_service_name << "." << endl, 1;
			cout << "Service " << g_service_name << " has been installed." << endl;
			return 0;
		}
		if (vm.count("uninstall"))
		{
			if (nt_service_uninstall(g_service_name))
				return cerr << "Failed to uninstall service " << g_service_name << "." << endl, 1;
			cout << "Service " << g_service_name << " has been uninstalled." << endl;
			return 0;
		}
		SERVICE_TABLE_ENTRY st[] =
		{
			{ "", nt_service_main },
			{ NULL, NULL }
		};
		if (StartServiceCtrlDispatcher(st))
			return 0;
		if (GetLastError() != ERROR_CALL_NOT_IMPLEMENTED
			&& GetLastError() != ERROR_FAILED_SERVICE_CONTROLLER_CONNECT)
			return 1;
#endif
		return main1();
	}
	catch (exception& e)
	{
		cerr << e.what() << endl;
		return 1;
	}
}
