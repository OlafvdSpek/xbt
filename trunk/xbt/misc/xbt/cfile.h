#pragma once

#include <boost/noncopyable.hpp>
#include <cstdio>

class cfile : boost::noncopyable
{
public:
	cfile() = default;

	cfile(cfile&& v) : f_(v.release())
	{
	}

	explicit cfile(FILE* f) : f_(f)
	{
	}

	explicit cfile(const char* name, const char* mode) : f_(fopen(name, mode))
	{
	}

	~cfile()
	{
		close();
	}

	FILE* release()
	{
		FILE* f = f_;
		f_ = NULL;
		return f;
	}

	operator FILE*()
	{
		return f_;
	}

	size_t read(void* d, size_t cb_d)
	{
		return fread(d, 1, cb_d, f_);
	}

	size_t write(const void* d, size_t cb_d)
	{
		return fwrite(d, 1, cb_d, f_);
	}

	void close()
	{
		if (!f_)
			return;
		fclose(f_);
		f_ = NULL;
	}
private:
	FILE* f_ = NULL;
};
