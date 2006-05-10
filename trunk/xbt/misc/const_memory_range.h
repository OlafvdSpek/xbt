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
		begin_ = NULL;
		end_ = NULL;
	}

	const_memory_range(const void* begin, const void* end)
	{
		begin_ = reinterpret_cast<const unsigned char*>(begin);
		end_ = reinterpret_cast<const unsigned char*>(end);
	}

	const_memory_range(const void* begin, size_t size)
	{
		begin_ = reinterpret_cast<const unsigned char*>(begin);
		end_ = begin_ + size;
	}

	const_memory_range(const std::string& v)
	{
		begin_ = reinterpret_cast<const unsigned char*>(v.data());
		end_ = reinterpret_cast<const unsigned char*>(v.data() + v.size());
	}

	template<class T>
	const_memory_range(const T& v)
	{
		begin_ = v.data();
		end_ = v.data_end();
	}

	const unsigned char* begin() const
	{
		return begin_;
	}

	const unsigned char* end() const
	{
		return end_;
	}

	size_t size() const
	{
		return end() - begin();
	}
private:
	const unsigned char* begin_;
	const unsigned char* end_;
};

#endif // !defined(AFX_CONST_MEMORY_RANGE_H__83C523AF_357D_4ED5_B17A_92F0CED89F1A__INCLUDED_)
