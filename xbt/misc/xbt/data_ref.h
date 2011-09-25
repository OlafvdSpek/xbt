#pragma once

#include <boost/type_traits.hpp>
#include <cstdlib>
#include <cstring>
#include <string>

template <class T0>
class data_ref_base
{
public:
  typedef T0* T;
  typedef typename boost::conditional<boost::is_const<T0>::value, const void*, void*>::type U;

  typedef T const_iterator;
  typedef T iterator;

	data_ref_base()
	{
		clear();
	}

	template<class V>
  data_ref_base(const V& v)
	{
    if (v.end() == v.begin())
      clear();
    else
		  assign(&*v.begin(), &*v.end());
	}

	template<class V>
  data_ref_base(V& v)
	{
    if (v.end() == v.begin())
      clear();
    else
		  assign(&*v.begin(), &*v.end());
	}

  explicit data_ref_base(const char* v)
  {
    assign(v, strlen(v));
  }

  explicit data_ref_base(char* v)
  {
    assign(v, strlen(v));
  }

	data_ref_base(U begin, U end)
	{
		assign(begin, end);
	}

	data_ref_base(U begin, size_t size)
	{
		assign(begin, size);
	}

	data_ref_base assign(U begin, U end)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = reinterpret_cast<T>(end);
		return *this;
	}
	
	data_ref_base assign(U begin, size_t size)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = begin_ + size;
		return *this;
	}
	
	void clear()
	{
		begin_ = end_ = NULL;
	}
	
	T begin() const
  {
    return begin_;
  }

	T end() const
  {
    return begin_;
  }

	T data() const
  {
    return begin();
  }

	size_t size() const
	{
		return end() - begin();
	}

	bool empty() const
	{
		return end() == begin();
	}

  T0& operator[](size_t i) const
  {
    return data()[i];
  }

	template<class V>
	data_ref_base find(V v) const
	{
		data_ref_base t = *this;
		while (!t.empty() && *t != v)
			t++;
		return t;
	}

	long long i() const
	{
		return atoll(reinterpret_cast<const char*>(data()));
	}

	std::string string() const
	{
		return std::string(reinterpret_cast<const char*>(data()), size());
	}

	data_ref_base sub_range(size_t o, size_t s)
	{
		return data_ref_base(begin_ + o, s);
	}

	operator T() const
	{
		return begin_;
	}

	data_ref_base operator++(int)
	{
		data_ref_base t = *this;
		begin_++;
		return t;
	}

	data_ref_base operator+=(size_t v)
	{
		begin_ += v;
		return *this;
	}
private:
	T begin_;
	T end_;
};

typedef data_ref_base<const unsigned char> data_ref;
typedef data_ref_base<unsigned char> mutable_data_ref;
typedef data_ref_base<const char> str_ref;
typedef data_ref_base<char> mutable_str_ref;

inline size_t memcpy(void* d, data_ref s)
{
  memcpy(d, s.data(), s.size());
  return s.size();
}
