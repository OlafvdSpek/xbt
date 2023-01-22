#pragma once

#include <io.h>
#include <string>

class bstream_handle
{
public:
  typedef int handle_type;

  bstream_handle() = default;

  explicit bstream_handle(handle_type f) : f_(f)
  {
  }

  handle_type get() const
  {
    return f_;
  }

  bool is_open() const
  {
    return get() != -1;
  }

  explicit operator bool() const
  {
    return is_open();
  }

  void reset(handle_type f = -1)
  {
    f_ = f;
  }

  ptrdiff_t read(void* d, size_t cb_d)
  {
    return ::read(get(), d, cb_d);
  }

  ptrdiff_t write(const void* d, size_t cb_d)
  {
    return ::write(get(), d, cb_d);
  }
private:
  handle_type f_ = -1;
};

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
    handle_type f = f_.get();
    f_.reset();
    return f;
  }

  handle_type get() const
  {
    return f_.get();
  }

  bool is_open() const
  {
    return f_.is_open();
  }

  explicit operator bool() const
  {
    return is_open();
  }

  ptrdiff_t read(void* d, size_t cb_d)
  {
    return f_.read(d, cb_d);
  }

  ptrdiff_t write(const void* d, size_t cb_d)
  {
    return f_.write(d, cb_d);
  }

  int close()
  {
    return is_open() ? ::close(release()) : 0;
  }
private:
  bstream_handle f_;
};
