#include <cstdio>

class bstream
{
public:
	bstream() = default;

	bstream(bstream&& v) : f_(v.f_)
	{
		v.f_ = NULL;
	}

	bstream(FILE* f) : f_(f)
	{
	}

	~bstream()
	{
		close();
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
