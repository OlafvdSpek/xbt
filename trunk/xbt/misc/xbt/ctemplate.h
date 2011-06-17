#pragma once

#include <ctemplate/template.h>
#include <xbt/sql_result.h>

inline void set(ctemplate::TemplateDictionary& d, const char* name, long long v)
{
	d.SetIntValue(name, v);
}

inline void set(ctemplate::TemplateDictionary& d, const char* name, const std::string& v)
{
	d.SetValue(name, v);
}

inline void set(ctemplate::TemplateDictionary& d, const char* name, const Csql_field& v)
{
	set(d, name, v.s());
}
