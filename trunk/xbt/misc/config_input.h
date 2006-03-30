#if !defined(XBT_CONFIG_INPUT_H__INCLUDED)
#define XBT_CONFIG_INPUT_H__INCLUDED

#if _MSC_VER > 1000
#pragma once
#endif

#include <boost/algorithm/string.hpp>
#include <fstream>
#include <string>

template <class T>
istream& read_config(istream& is, T& config)
{
	for (string s; getline(is, s); )
	{
		size_t i = s.find('=');
		if (i != string::npos)
			config.set(trim_copy(s.substr(0, i)), trim_copy(s.substr(i + 1)));
	}
	return is;
}

template <class T>
int read_config(const string& file, T& config)
{
	ifstream is(file.c_str());
	if (!is)
		return 1;
	read_config<T>(is, config);
	return is.bad();
}

#endif
