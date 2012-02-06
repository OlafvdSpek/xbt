#pragma once

#include <boost/lexical_cast.hpp>
#include <boost/range/iterator_range.hpp>
#include <boost/type_traits/is_class.hpp>
#include <boost/utility/enable_if.hpp>
#include <cstdlib>
#include <cstring>
#include <string>

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

	void clear()
	{
		assign(T(NULL), T(NULL));
	}

	T data() const
	{
		return base_t::begin();
	}

	template<class V>
	data_ref_base find(V v) const
	{
		data_ref_base t = *this;
		while (!t.empty() && t.front() != v)
			t.advance_begin(1);
		return t;
	}

	float f() const
	{
		return to_float(*this);
	}

	long long i() const
	{
		return to_int(*this);
	}

	std::string s() const
	{
		return std::string(reinterpret_cast<const char*>(data()), base_t::size());
	}

	data_ref_base substr(size_t ofs, size_t sz)
	{
		return data_ref_base(base_t::begin() + ofs, sz);
	}
private:
	typedef boost::iterator_range<T> base_t;
};

typedef data_ref_base<const unsigned char*, const void*> data_ref;
typedef data_ref_base<unsigned char*, void*> mutable_data_ref;
typedef data_ref_base<const char*, const void*> str_ref;
typedef data_ref_base<char*, void*> mutable_str_ref;

inline size_t memcpy(void* d, data_ref s)
{
	memcpy(d, s.data(), s.size());
	return s.size();
}

inline float to_float(data_ref v)
{
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

inline long long to_int(data_ref v)
{
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

inline std::string to_string(str_ref v)
{
	return std::string(v.data(), v.size());
}
