#pragma once

#include <io.h>

class bstream
{
public:
	bstream() = default;

	bstream(bstream&& v) : f_(v.release())
	{
	}

	explicit bstream(int f) : f_(f)
	{
	}

	~bstream()
	{
		close();
	}

	int release()
	{
		int f = f_;
		f_ = -1;
		return f;
	}

	int get()
	{
		return f_;
	}

	bool is_open() const
	{
		return f_;
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
	int f_ = -1;
};
