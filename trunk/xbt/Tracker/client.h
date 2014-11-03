#pragma once

class Cserver;

class Cclient
{
public:
	virtual void process_events(int) = 0;

	virtual ~Cclient() {}

	const Csocket& s() const
	{
		return m_s;
	}
protected:
	Csocket m_s;
};
