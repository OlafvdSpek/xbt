#pragma once

class client_t
{
public:
	virtual void process_events(int) = 0;

	virtual ~client_t() = default;

	const Csocket& s() const
	{
		return m_s;
	}
protected:
	Csocket m_s;
};
