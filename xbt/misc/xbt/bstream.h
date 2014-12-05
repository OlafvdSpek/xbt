#pragma once

#include <io.h>

class bstream
{
public:
	typedef int handle_type;

	bstream() = default;

	bstream(bstream&& v) : f_(v.release())
	{
	}

	explicit bstream(handle_type f) : f_(f)
	{
	}

	explicit bstream(const char* name, int mode) : f_(open(name, mode))
	{
	}

	explicit bstream(const std::string& name, int mode) : f_(open(name.c_str(), mode))
	{
	}

	~bstream()
	{
		close();
	}

	handle_type release()
	{
		handle_type f = f_;
		f_ = -1;
		return f;
	}

	handle_type get()
	{
		return f_;
	}

	bool is_open() const
	{
		return f_ != -1;
	}

	explicit operator bool() const
	{
		return is_open();
	}

	ptrdiff_t read(void* d, size_t cb_d)
	{
		return ::read(f_, d, cb_d);
	}

	ptrdiff_t write(const void* d, size_t cb_d)
	{
		return ::write(f_, d, cb_d);
	}

	int close()
	{
		return is_open() ? ::close(release()) : 0;
	}
private:
	handle_type f_ = -1;
};
