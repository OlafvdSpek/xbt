#if !defined(AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_)
#define AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

template <class T>
class memory_range_base
{
public:
	memory_range_base()
	{
		begin = NULL;
		end = NULL;
	}

	template <class U>
	memory_range_base(U v)
	{
		begin = reinterpret_cast<T>(v.begin);
		end = reinterpret_cast<T>(v.end);
	}

	memory_range_base(void* begin_, void* end_)
	{
		begin = reinterpret_cast<T>(begin_);
		end = reinterpret_cast<T>(end_);
	}

	memory_range_base(void* begin_, size_t size)
	{
		begin = reinterpret_cast<T>(begin_);
		end = begin + size;
	}

	size_t size() const
	{
		return end - begin;
	}

	operator T() const
	{
		return begin;
	}

	memory_range_base operator++(int)
	{
		memory_range_base t = *this;
		begin++;
		return t;
	}

	memory_range_base operator+=(size_t v)
	{
		begin += v;
		return *this;
	}

	T begin;
	T end;
};

typedef memory_range_base<unsigned char*> memory_range;

template <class T>
class const_memory_range_base
{
public:
	const_memory_range_base()
	{
		begin = NULL;
		end = NULL;
	}

	template <class U>
	const_memory_range_base(U v)
	{
		begin = reinterpret_cast<T>(v.begin);
		end = reinterpret_cast<T>(v.end);
	}

	const_memory_range_base(const void* begin_, const void* end_)
	{
		begin = reinterpret_cast<T>(begin_);
		end = reinterpret_cast<T>(end_);
	}

	const_memory_range_base(const void* begin_, size_t size)
	{
		begin = reinterpret_cast<T>(begin_);
		end = begin + size;
	}

	const_memory_range_base(const std::string& v)
	{
		begin = reinterpret_cast<T>(v.data());
		end = reinterpret_cast<T>(v.data() + v.size());
	}

	size_t size() const
	{
		return end - begin;
	}

	operator T() const
	{
		return begin;
	}

	const_memory_range_base operator++(int)
	{
		const_memory_range_base t = *this;
		begin++;
		return t;
	}

	const_memory_range_base operator+=(size_t v)
	{
		begin += v;
		return *this;
	}

	T begin;
	T end;
};

typedef const_memory_range_base<const unsigned char*> const_memory_range;

#endif // !defined(AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_)
