#pragma once

#include <boost/lexical_cast.hpp>
#include <boost/utility/string_ref.hpp>
#include <string>

namespace std 
{
	using string_view = boost::string_ref;
}

inline std::string& operator+=(std::string& a, std::string_view b)
{
	return a.append(b.data(), b.size());
}

inline std::string& operator<<(std::string& a, std::string_view b)
{
	return a += b;
}

inline std::string& operator<<(std::string& a, long long b)
{
	return a += std::to_string(b);
}

inline float to_float(std::string_view v)
{
	// return boost::convert<float>(v, boost::cnv::strtol(), 0.0f);
	if (v.empty())
		return 0;
	try
	{
		return boost::lexical_cast<float>(v);
	}
	catch (boost::bad_lexical_cast&)
	{
	}
	return 0;
}

inline long long to_int(std::string_view v)
{
	// return boost::convert<long long>(v, boost::cnv::strtol(), 0);
	if (v.empty())
		return 0;
	try
	{
		return boost::lexical_cast<long long>(v);
	}
	catch (boost::bad_lexical_cast&)
	{
	}
	return 0;
}
