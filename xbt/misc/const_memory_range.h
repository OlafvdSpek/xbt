#if !defined(AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_)
#define AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

class const_memory_range
{
public:
	const_memory_range()
	{
		begin = NULL;
		end = NULL;
	}

	const_memory_range(const void* begin_, const void* end_)
	{
		begin = reinterpret_cast<const unsigned char*>(begin_);
		end = reinterpret_cast<const unsigned char*>(end_);
	}

	const_memory_range(const void* begin_, size_t size)
	{
		begin = reinterpret_cast<const unsigned char*>(begin_);
		end = begin + size;
	}

	const_memory_range(const std::string& v)
	{
		begin = reinterpret_cast<const unsigned char*>(v.data());
		end = reinterpret_cast<const unsigned char*>(v.data() + v.size());
	}

	size_t size() const
	{
		return end - begin;
	}

	operator const unsigned char*() const
	{
		return begin;
	}

	const unsigned char* operator++()
	{
		return begin++;
	}

	const unsigned char* operator+=(size_t v)
	{
		return begin += v;
	}

	const unsigned char* begin;
	const unsigned char* end;
};

class memory_range
{
public:
	memory_range()
	{
		begin = NULL;
		end = NULL;
	}

	memory_range(void* begin_, void* end_)
	{
		begin = reinterpret_cast<unsigned char*>(begin_);
		end = reinterpret_cast<unsigned char*>(end_);
	}

	memory_range(void* begin_, size_t size)
	{
		begin = reinterpret_cast<unsigned char*>(begin_);
		end = begin + size;
	}

	size_t size() const
	{
		return end - begin;
	}

	operator unsigned char*() const
	{
		return begin;
	}

	unsigned char* operator++()
	{
		return begin++;
	}

	unsigned char* operator+=(size_t v)
	{
		return begin += v;
	}

	unsigned char* begin;
	unsigned char* end;
};

#endif // !defined(AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_)
