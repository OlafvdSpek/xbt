#pragma once

#include <io.h>

class bstream
{
public:
	bstream() = default;

	bstream(bstream&& v) : f_(v.release())
	{
	}

	bstream(int f) : f_(f == -1 ? 0 : f)
	{
	}

	~bstream()
	{
		close();
	}

	int release()
	{
		int f = f_;
		f_ = 0;
		return f;
	}

	int get()
	{
		return f_;
	}

	explicit operator int()
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
		return f_ ? ::close(release()) : 0;
	}
private:
	int f_ = 0;
};
