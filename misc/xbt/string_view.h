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
