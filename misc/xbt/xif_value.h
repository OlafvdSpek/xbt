#pragma once

#include <string.h>
#include <xbt/shared_data.h>

enum t_vt {vt_bin32, vt_binary, vt_int32, vt_string, vt_external_binary, vt_float, vt_unknown};

class Cxif_value
{
public:
  Cxif_value()
  {
    m_type = vt_unknown;
  }

  Cxif_value(float v)
  {
    m_type = vt_float;
    m_value_float = v;
  }

  Cxif_value(t_vt type, int v)
  {
    m_type = type;
    m_value_int = v;
  }

  Cxif_value(const shared_data& v)
  {
    m_type = vt_binary;
    m_data = v;
  }

  Cxif_value(const std::string& v)
  {
    m_type = vt_string;
    m_data = make_shared_data(v.c_str(), v.size() + 1);
  }

  shared_data get_vdata() const
  {
    assert(!idata());
    return m_data;
  }

  const byte* get_data() const
  {
    return idata() ? m_value : m_data.data();
  }

  int get_size() const
  {
    return idata() ? 4 : m_data.size();
  }

  float get_float() const
  {
    assert(get_size() == 4);
    return m_value_float;
  }

  float get_float(float v) const
  {
    return get_size() ? get_float() : v;
  }

  int get_int() const
  {
    assert(get_size() == 4);
    return m_value_int;
  }

  int get_int(int v) const
  {
    return get_size() ? get_int() : v;
  }

  std::string get_string() const
  {
    assert(get_size());
    return reinterpret_cast<const char*>(get_data());
  }

  std::string get_string(const std::string& v) const
  {
    return get_size() ? get_string() : v;
  }

  bool idata() const
  {
    // internal data?
    return get_type() == vt_bin32 || get_type() == vt_float || get_type() == vt_int32;
  }

  void dump(std::ostream& os, int depth = 0) const;
  t_vt get_type() const;
  void load_old(const byte*& data);
  void load_new(const byte*& data);
  void load_external(const byte*& data);
  void save(byte*& data) const;
  static int skip(const byte* s);
  void external_save(byte*& data) const;
private:
  shared_data m_data;
  t_vt m_type;

  union
  {
    byte m_value[4];
    float m_value_float;
    int m_value_int;
  };
};
