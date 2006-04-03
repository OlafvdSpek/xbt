#if !defined(XBT_CONFIG_BASE_H__INCLUDED)
#define XBT_CONFIG_BASE_H__INCLUDED

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <boost/algorithm/string.hpp>
#include <fstream>
#include <string>

class Cconfig_base
{
public:
	virtual int set(const string& name, const string& value) = 0;

	template <class T>
	struct t_attribute
	{
		const char* key;
		T* value;
	};

	template <class T>
	t_attribute<T>* find(t_attribute<T>* attributes, const std::string& key)
	{
		t_attribute<T>* i = attributes; 
		while (i->key && i->key != key)
			i++;
		return i->key ? i : NULL;
	}

	istream& load(istream& is)
	{
		for (string s; getline(is, s); )
		{
			size_t i = s.find('=');
			if (i != string::npos)
				set(boost::trim_copy(s.substr(0, i)), boost::trim_copy(s.substr(i + 1)));
		}
		return is;
	}

	int load(const string& file)
	{
		ifstream is(file.c_str());
		if (!is)
			return 1;
		load(is);
		return !is.eof();
	}
};

#endif
