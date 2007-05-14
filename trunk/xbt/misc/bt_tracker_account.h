#if !defined(AFX_BT_TRACKER_ACCOUNT_H__D495C350_CA0C_4582_B420_B73A2BC105CC__INCLUDED_)
#define AFX_BT_TRACKER_ACCOUNT_H__D495C350_CA0C_4582_B420_B73A2BC105CC__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <list>
#include "stream_writer.h"
#include "virtual_binary.h"

class Cbt_tracker_account
{
public:
	int pre_dump() const;
	void dump(Cstream_writer&) const;
	Cbt_tracker_account();
	Cbt_tracker_account(const std::string& tracker, const std::string& user, const std::string& pass);

	const std::string& tracker() const
	{
		return m_tracker;
	}
	
	const std::string& user() const
	{
		return m_user;
	}
	
	const std::string& pass() const
	{
		return m_pass;
	}	
private:
	std::string m_tracker;
	std::string m_user;
	std::string m_pass;
};

class Cbt_tracker_accounts: public std::list<Cbt_tracker_account>
{
public:
	Cvirtual_binary dump() const;
	const Cbt_tracker_account* find(const std::string&) const;
	void load(const Cvirtual_binary&);
};

#endif // !defined(AFX_BT_TRACKER_ACCOUNT_H__D495C350_CA0C_4582_B420_B73A2BC105CC__INCLUDED_)
