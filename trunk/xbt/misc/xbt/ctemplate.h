#pragma once

#include <ctemplate/template.h>
#include <xbt/sql_result.h>

inline void set(ctemplate::TemplateDictionary& d, const char* name, long long v)
{
	d.SetIntValue(name, v);
}

inline void set(ctemplate::TemplateDictionary& d, const char* name, const char* v)
{
	d.SetValue(name, v);
}

inline void set(ctemplate::TemplateDictionary& d, const char* name, data_ref v)
{
	d.SetValue(name, v.s());
}
