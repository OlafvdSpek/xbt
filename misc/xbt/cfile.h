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

class cfile_handle
{
public:
  typedef FILE* handle_type;

  cfile_handle() = default;

  explicit cfile_handle(handle_type f) : f_(f)
  {
  }

  handle_type get() const
  {
    return f_;
  }

  bool is_open() const
  {
    return !!get();
  }

  explicit operator bool() const
  {
    return is_open();
  }

  void reset(handle_type f = NULL)
  {
    f_ = f;
  }

  size_t read(void* d, size_t cb_d)
  {
    return fread(d, 1, cb_d, get());
  }

  size_t write(const void* d, size_t cb_d)
  {
    return fwrite(d, 1, cb_d, get());
  }
private:
  handle_type f_ = NULL;
};

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
    handle_type f = f_.get();
    f_.reset();
    return f;
  }

  handle_type get() const
  {
    return f_.get();
  }

  operator handle_type() const
  {
    return get();
  }

  bool is_open() const
  {
    return f_.is_open();
  }

  explicit operator bool() const
  {
    return is_open();
  }

  size_t read(void* d, size_t cb_d)
  {
    return f_.read(d, cb_d);
  }

  size_t write(const void* d, size_t cb_d)
  {
    return f_.write(d, cb_d);
  }

  int close()
  {
    return is_open() ? fclose(release()) : 0;
  }
private:
  cfile_handle f_;
};
