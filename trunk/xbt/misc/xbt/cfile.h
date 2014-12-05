#pragma once

#include <boost/noncopyable.hpp>
#include <cstdio>
#include <string>

inline size_t read(FILE* f, void* d, size_t cb_d)
{
	return fread(d, 1, cb_d, f);
}

inline size_t write(FILE* f, const void* d, size_t cb_d)
{
	return fwrite(d, 1, cb_d, f);
}

class cfile : boost::noncopyable
{
public:
	typedef FILE* handle_type;

	cfile() = default;

	cfile(cfile&& v) : f_(v.release())
	{
	}

	explicit cfile(handle_type f) : f_(f)
	{
	}

	explicit cfile(const char* name, const char* mode) : f_(fopen(name, mode))
	{
	}

	explicit cfile(const std::string& name, const char* mode) : f_(fopen(name.c_str(), mode))
	{
	}

	~cfile()
	{
		close();
	}

	handle_type release()
	{
		handle_type f = f_;
		f_ = NULL;
		return f;
	}

	handle_type get()
	{
		return f_;
	}

	operator handle_type()
	{
		return f_;
	}

	bool is_open() const
	{
		return !!f_;
	}

	explicit operator bool() const
	{
		return is_open();
	}

	size_t read(void* d, size_t cb_d)
	{
		return fread(d, 1, cb_d, f_);
	}

	size_t write(const void* d, size_t cb_d)
	{
		return fwrite(d, 1, cb_d, f_);
	}

	int close()
	{
		return is_open() ? fclose(release()) : 0;
	}
private:
	handle_type f_ = NULL;
};
