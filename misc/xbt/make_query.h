#pragma once

#include <xbt/database.h>

class raw
{
public:
	raw(std::string_view v) : v_(v)
	{
	}

	friend void query_append(Cdatabase&, std::string& s, raw v)
	{
		s << v.v_;
	}
private:
	std::string_view v_;
};

inline void query_append(Cdatabase& db, std::string& s, std::string_view v)
{
	size_t sz = s.size();
	s.resize(sz + 2 + 2 * v.size());
	s[sz] = '\'';
	s.resize(sz + 2 + mysql_real_escape_string(db, &s[sz + 1], v.data(), v.size()));
	s.back() = '\'';
}

template<size_t N>
inline void query_append(Cdatabase& db, std::string& s, std::array<char, N> v)
{
	query_append(db, s, std::string_view(v.data(), v.size()));
}

template<class T>
inline std::enable_if_t<std::is_integral<T>::value> query_append(Cdatabase&, std::string& s, T v)
{
	s << v;
}

inline void query0(Cdatabase& db, std::string& s, std::string_view q)
{
	s << db.replace_names(q);
}

template<class T, class... A>
void query0(Cdatabase& db, std::string& s, std::string_view q, const T& v, const A&... a)
{
	auto i = q.find('?');
	assert(i != std::string::npos);
  if (i == std::string::npos)
  {
    s << db.replace_names(q);
    return;
  }
  s += db.replace_names(q.substr(0, i));
  query_append(db, s, v);
	query0(db, s, q.substr(i + 1), a...);
}

template<class... A>
std::string make_query(Cdatabase& db, std::string_view q, const A&... a)
{
	std::string s;
	query0(db, s, q, a...);
	return s;
}

template<class... A>
Csql_result query(Cdatabase& db, std::string_view q, const A&... a)
{
	return db.query(make_query(db, q, a...));
}

template<class... A>
void query_nothrow(Cdatabase& db, std::string_view q, const A&... a)
{
  db.query_nothrow(make_query(db, q, a...));
}
