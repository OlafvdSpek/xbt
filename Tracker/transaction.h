#pragma once

class Ctransaction
{
public:
	long long connection_id() const;
	void recv();
	void send(data_ref);
	void send_announce(data_ref);
	void send_connect(data_ref);
	void send_scrape(data_ref);
	void send_error(data_ref, std::string_view msg);
	Ctransaction(const Csocket&);
private:
	const Csocket& m_s;
	sockaddr_in6 m_a;
};
