#pragma once

#include <boost/range/iterator_range.hpp>
#include <boost/type_traits/is_class.hpp>
#include <boost/utility/enable_if.hpp>
#include <cstdlib>
#include <cstring>
#include <string>
#include <xbt/string_view.h>

template <class T, class U>
class data_ref_base : public boost::iterator_range<T>
{
public:
	data_ref_base()
	{
		clear();
	}

	template<class V>
	data_ref_base(const V& v, typename boost::enable_if<typename boost::is_class<V> >::type* = 0)
	{
		if (v.end() != v.begin())
			assign(&*v.begin(), v.end() - v.begin() + &*v.begin());
		else
			clear();
	}

	template<class V>
	data_ref_base(V& v, typename boost::enable_if<typename boost::is_class<V> >::type* = 0)
	{
		if (v.end() != v.begin())
			assign(&*v.begin(), v.end() - v.begin() + &*v.begin());
		else
			clear();
	}

	explicit data_ref_base(const char* v)
	{
		if (v)
			assign(v, strlen(v));
		else
			clear();
	}

	data_ref_base(U begin, U end)
	{
		assign(begin, end);
	}

	data_ref_base(U begin, size_t size)
	{
		assign(begin, size);
	}

	void assign(U begin, U end)
	{
		static_cast<base_t&>(*this) = base_t(reinterpret_cast<T>(begin), reinterpret_cast<T>(end));
	}

	void assign(U begin, size_t size)
	{
		assign(begin, reinterpret_cast<T>(begin) + size);
	}

	void set_begin(U v)
	{
		assign(v, base_t::end());
	}

	void set_end(U v)
	{
		assign(base_t::begin(), v);
	}

	void clear()
	{
		assign(T(NULL), T(NULL));
	}

	T data() const
	{
		return base_t::begin();
	}

	operator std::string_view() const
	{
		return std::string_view(data(), base_t::size());
	}

	float f() const
	{
		return to_float(*this);
	}

	long long i() const
	{
		return to_int(*this);
	}

	const std::string s() const
	{
		return std::string(reinterpret_cast<const char*>(data()), base_t::size());
	}

	data_ref_base substr(size_t pos)
	{
		return data_ref_base(base_t::begin() + pos, base_t::size() - pos);
	}

	data_ref_base substr(size_t pos, size_t sz)
	{
		return data_ref_base(base_t::begin() + pos, sz);
	}
private:
	typedef boost::iterator_range<T> base_t;
};

typedef data_ref_base<const unsigned char*, const void*> data_ref;
typedef data_ref_base<unsigned char*, void*> mutable_data_ref;
typedef data_ref_base<const char*, const void*> str_ref;
typedef data_ref_base<char*, void*> mutable_str_ref;

// bool operator==(str_ref a, const char* b);

inline size_t memcpy(void* d, data_ref s)
{
	memcpy(d, s.data(), s.size());
	return s.size();
}

inline size_t memcpy(mutable_data_ref d, data_ref s)
{
	assert(d.size() >= s.size());
	memcpy(d.data(), s.data(), s.size());
	return s.size();
}

inline int eat(str_ref& s, char v)
{
	if (!s || s.front() != v)
		return 1;
	s.pop_front();
	return 0;
}

inline str_ref read_until(str_ref& is, char sep)
{
	const char* a = is.begin();
	const char* b = std::find(is.begin(), is.end(), sep);
	is.set_begin(b == is.end() ? b : b + 1);
	return str_ref(a, b);
}

template<class T>
int try_parse(T& d, str_ref s)
{
	if (!s)
		return 1;
	bool neg = !eat(s, '-');
	d = 0;
	for (; s; s.pop_front())
	{
		char c = s.front();
		if (c < '0' || c > '9')
			return 1;
		d = neg ? 10 * d - (c - '0') : 10 * d + (c - '0');
	}
	return 0;
}

template<class T>
T parse(str_ref s)
{
	T d;
	return try_parse(d, s) ? 0 : d;
}

template<class T>
void parse(T& d, str_ref s)
{
	if (try_parse(d, s))
		d = 0;
}

inline const std::string to_string(str_ref v)
{
	return std::string(v.data(), v.size());
}
