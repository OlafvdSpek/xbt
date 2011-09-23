#pragma once

#include <boost/array.hpp>
#include <cstring>
#include <string>
#include <vector>

template <class T>
class mutable_data_ref_base
{
public:
	mutable_data_ref_base()
	{
		begin = NULL;
		end = NULL;
	}

	template<class U>
	mutable_data_ref_base(const mutable_data_ref_base<U>& v)
	{
		assign(v.begin, v.end);
	}

	mutable_data_ref_base(void* begin_, void* end_)
	{
		assign(begin_, end_);
	}

	mutable_data_ref_base(void* begin_, size_t size)
	{
		assign(begin_, size);
	}

	template<size_t U>
	mutable_data_ref_base(boost::array<char, U>& v)
	{
		assign(&v.front(), v.size());
	}

	template<size_t U>
	mutable_data_ref_base(boost::array<unsigned char, U>& v)
	{
		assign(&v.front(), v.size());
	}

	mutable_data_ref_base(std::vector<char>& v)
	{
		assign(&v.front(), v.size());
	}

	mutable_data_ref_base(std::vector<unsigned char>& v)
	{
		assign(&v.front(), v.size());
	}

	mutable_data_ref_base assign(void* begin_, void* end_)
	{
		begin = reinterpret_cast<T>(begin_);
		end = reinterpret_cast<T>(end_);
		return *this;
	}
	
	mutable_data_ref_base assign(void* begin_, size_t size)
	{
		begin = reinterpret_cast<T>(begin_);
		end = begin + size;
		return *this;
	}
	
	void clear()
	{
		begin = end = NULL;
	}
	
	bool empty() const
	{
		return begin == end;
	}

	size_t size() const
	{
		return end - begin;
	}

	std::string string() const
	{
		return std::string(reinterpret_cast<const char*>(begin), size());
	}

	mutable_data_ref_base sub_range(size_t o, size_t s)
	{
		return mutable_data_ref_base(begin + o, s);
	}

	operator T() const
	{
		return begin;
	}

	mutable_data_ref_base operator++(int)
	{
		mutable_data_ref_base t = *this;
		begin++;
		return t;
	}

	mutable_data_ref_base operator+=(size_t v)
	{
		begin += v;
		return *this;
	}

	T begin;
	T end;
};

template <class T>
class data_ref_base
{
public:
	data_ref_base()
	{
		begin = NULL;
		end = NULL;
	}

	template<class U>
	data_ref_base(const data_ref_base<U>& v)
	{
		assign(v.begin, v.end);
	}

	template<class U>
	data_ref_base(const mutable_data_ref_base<U>& v)
	{
		assign(v.begin, v.end);
	}

	data_ref_base(const void* begin_, const void* end_)
	{
		assign(begin_, end_);
	}

	data_ref_base(const void* begin_, size_t size)
	{
		assign(begin_, size);
	}

	data_ref_base(const std::string& v)
	{
		assign(v.data(), v.size());
	}

	template<size_t U>
	data_ref_base(const boost::array<char, U>& v)
	{
		assign(&v.front(), v.size());
	}

	template<size_t U>
	data_ref_base(const boost::array<unsigned char, U>& v)
	{
		assign(&v.front(), v.size());
	}

	data_ref_base(const std::vector<char>& v)
	{
		assign(&v.front(), v.size());
	}

	data_ref_base(const std::vector<unsigned char>& v)
	{
		assign(&v.front(), v.size());
	}

	data_ref_base assign(const void* begin_, const void* end_)
	{
		begin = reinterpret_cast<T>(begin_);
		end = reinterpret_cast<T>(end_);
		return *this;
	}
	
	data_ref_base assign(const void* begin_, size_t size)
	{
		begin = reinterpret_cast<T>(begin_);
		end = begin + size;
		return *this;
	}
	
	void clear()
	{
		begin = end = NULL;
	}
	
	bool empty() const
	{
		return begin == end;
	}

	template<class U>
	data_ref_base find(U v) const
	{
		data_ref_base t = *this;
		while (!t.empty() && *t != v)
			t++;
		return t;
	}

	long long i() const
	{
		return atoll(reinterpret_cast<const char*>(begin));
	}

	size_t size() const
	{
		return end - begin;
	}

	std::string string() const
	{
		return std::string(reinterpret_cast<const char*>(begin), size());
	}

	data_ref_base sub_range(size_t o, size_t s)
	{
		return data_ref_base(begin + o, s);
	}

	operator T() const
	{
		return begin;
	}

	data_ref_base operator++(int)
	{
		data_ref_base t = *this;
		begin++;
		return t;
	}

	data_ref_base operator+=(size_t v)
	{
		begin += v;
		return *this;
	}

	T begin;
	T end;
};

typedef data_ref_base<const unsigned char*> data_ref;
typedef mutable_data_ref_base<unsigned char*> mutable_data_ref;

inline size_t memcpy(void* d, data_ref s)
{
  memcpy(d, s, s.size());
  return s.size();
}
