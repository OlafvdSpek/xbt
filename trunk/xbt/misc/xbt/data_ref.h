#pragma once

#include <cstring>
#include <string>

template <class T>
class mutable_data_ref_base
{
public:
	mutable_data_ref_base()
	{
    clear();
	}

	template<class V>
  mutable_data_ref_base(V& v)
	{
    if (v.end() == v.begin())
      clear();
    else
		  assign(&*v.begin(), &*v.end());
	}

	mutable_data_ref_base(void* begin, void* end)
	{
		assign(begin, end);
	}

	mutable_data_ref_base(void* begin, size_t size)
	{
		assign(begin, size);
	}

	mutable_data_ref_base assign(void* begin, void* end)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = reinterpret_cast<T>(end);
		return *this;
	}
	
	mutable_data_ref_base assign(void* begin, size_t size)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = begin_ + size;
		return *this;
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

  void clear()
	{
		begin_ = end_ = NULL;
	}
	
	bool empty() const
	{
		return begin_ == end_;
	}

	size_t size() const
	{
		return end_ - begin_;
	}

	std::string string() const
	{
		return std::string(reinterpret_cast<const char*>(begin_), size());
	}

	mutable_data_ref_base sub_range(size_t o, size_t s)
	{
		return mutable_data_ref_base(begin_ + o, s);
	}

	operator T() const
	{
		return begin_;
	}

	mutable_data_ref_base operator++(int)
	{
		mutable_data_ref_base t = *this;
		begin_++;
		return t;
	}

	mutable_data_ref_base operator+=(size_t v)
	{
		begin_ += v;
		return *this;
	}

	T begin_;
	T end_;
};

template <class T>
class data_ref_base
{
public:
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

  explicit data_ref_base(const char* v)
  {
    assign(v, strlen(v));
  }

	data_ref_base(const void* begin, const void* end)
	{
		assign(begin, end);
	}

	data_ref_base(const void* begin, size_t size)
	{
		assign(begin, size);
	}

	data_ref_base assign(const void* begin, const void* end)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = reinterpret_cast<T>(end);
		return *this;
	}
	
	data_ref_base assign(const void* begin, size_t size)
	{
		begin_ = reinterpret_cast<T>(begin);
		end_ = begin_ + size;
		return *this;
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

	void clear()
	{
		begin_ = end_ = NULL;
	}
	
	bool empty() const
	{
		return begin_ == end_;
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
		return atoll(reinterpret_cast<const char*>(begin_));
	}

	size_t size() const
	{
		return end_ - begin_;
	}

	std::string string() const
	{
		return std::string(reinterpret_cast<const char*>(begin_), size());
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

	T begin_;
	T end_;
};

typedef data_ref_base<const unsigned char*> data_ref;
typedef mutable_data_ref_base<unsigned char*> mutable_data_ref;
typedef data_ref_base<const char*> str_ref;
typedef mutable_data_ref_base<char*> mutable_str_ref;

inline size_t memcpy(void* d, data_ref s)
{
  memcpy(d, s, s.size());
  return s.size();
}
