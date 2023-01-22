#pragma once

#include <vector>
#include "xif_value.h"

class Cxif_key_r
{
public:
  typedef std::vector<std::pair<int, Cxif_key_r> > t_key_map;
  typedef std::vector<std::pair<int, Cxif_value> > t_value_map;

  const Cxif_key_r& get_key(int id) const
  {
    return *find_key(id);
  }

  const Cxif_value& get_value(int id) const
  {
    static Cxif_value z;
    const Cxif_value* i = find_value(id);
    return i ? *i : z;
  }

  float get_value_float(int id) const
  {
    return get_value(id).get_float();
  }

  float get_value_float(int id, float v) const
  {
    return get_value(id).get_float(v);
  }

  int get_value_int(int id) const
  {
    return get_value(id).get_int();
  }

  int get_value_int(int id, int v) const
  {
    return get_value(id).get_int(v);
  }

  long long get_value_int64(int id) const
  {
    return *reinterpret_cast<const long long*>(get_value(id).get_data());
  }

  std::string get_value_string(int id) const
  {
    return get_value(id).get_string();
  }

  std::string get_value_string(int id, const std::string& v) const
  {
    return get_value(id).get_string(v);
  }

  const t_key_map& keys() const
  {
    return m_keys;
  }

  const t_value_map& values() const
  {
    return m_values;
  }

  int c_keys() const
  {
    return keys().size();
  }

  int c_values() const
  {
    return values().size();
  }

  bool has_key(int id) const
  {
    return find_key(id);
  }

  bool has_value(int id) const
  {
    return find_value(id);
  }

  const Cxif_key_r* find_key(int id) const;
  const Cxif_value* find_value(int id) const;
  int import(data_ref);
private:
  int load(const byte* s);

  t_key_map m_keys;
  t_value_map m_values;
};
