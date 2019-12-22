#pragma once

#include <boost/convert.hpp>
#include <boost/convert/strtol.hpp>
#include <string>
#include <string_view>

inline std::string& operator<<(std::string& a, std::string_view b)
{
	return a += b;
}

template<class T>
inline std::enable_if_t<std::is_integral<T>::value, std::string&> operator<<(std::string& a, T b)
{
	return a += std::to_string(b);
}

inline float to_float(std::string_view v)
{
	return boost::convert<float>(v, boost::cnv::strtol(), 0.0f);
}

inline long long to_int(std::string_view v)
{
	return boost::convert<long long>(v, boost::cnv::strtol(), 0);
}

inline std::string_view read_until(std::string_view& v, char sep, bool keep_sep = false)
{
	size_t i = v.find(sep);
	std::string_view ret = v.substr(0, i);
	v.remove_prefix(i == std::string_view::npos ? v.size() : keep_sep ? i : i + 1);
	return ret;
}
