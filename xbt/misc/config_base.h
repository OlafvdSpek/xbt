#if !defined(XBT_CONFIG_BASE_H__INCLUDED)
#define XBT_CONFIG_BASE_H__INCLUDED

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#include <string>

class Cconfig_base
{
public:
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
};

#endif
