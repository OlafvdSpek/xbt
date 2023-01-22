#pragma once

#include <boost/algorithm/string.hpp>
#include <fstream>
#include <map>
#include <set>
#include <string>

class config_base_t
{
public:
  template <class T>
  struct attribute_t
  {
    const char* key;
    T* value;
    T default_value;
  };

  template <class T>
  using attributes_t = std::map<std::string, attribute_t<T>, std::less<>>;

  virtual int set(std::string_view name, std::string_view value)
  {
    if (attribute_t<std::string>* i = find_ptr(attributes_string_, name))
      *i->value = value;
    else
      return set(name, int(to_int(value)));
    return 0;
  }

  virtual int set(std::string_view name, int value)
  {
    if (attribute_t<int>* i = find_ptr(attributes_int_, name))
      *i->value = value;
    else
      return set(name, static_cast<bool>(value));
    return 0;
  }

  virtual int set(std::string_view name, bool value)
  {
    if (attribute_t<bool>* i = find_ptr(attributes_bool_, name))
      *i->value = value;
    else
      return 1;
    return 0;
  }

  std::istream& load(std::istream& is)
  {
    for (std::string s; getline(is, s); )
    {
      size_t i = s.find('=');
      if (i != std::string::npos)
        set(boost::trim_copy(s.substr(0, i)), boost::trim_copy(s.substr(i + 1)));
    }
    return is;
  }

  int load(const std::string& file)
  {
    std::ifstream is(file.c_str());
    if (!is)
      return 1;
    load(is);
    return !is.eof();
  }

  std::ostream& save(std::ostream& os) const
  {
    save_map(os, attributes_bool_);
    save_map(os, attributes_int_);
    save_map(os, attributes_string_);
    return os;
  }

protected:
  attributes_t<bool> attributes_bool_;
  attributes_t<int> attributes_int_;
  attributes_t<std::string> attributes_string_;

  template <class T>
  void fill_map(attribute_t<T>* attributes, const attributes_t<T>* s, attributes_t<T>& d)
  {
    for (attribute_t<T>* i = attributes; i->key; i++)
    {
      *i->value = s ? *find_ref(*s, i->key).value : i->default_value;
      d[i->key] = *i;
    }
  }

  template <class T>
  void save_map(std::ostream& os, const T& v) const
  {
    for (auto& i : v)
    {
      if (*i.second.value == i.second.default_value)
        os << "# ";
      os << i.first << " = " << *i.second.value << "\n";
    }
  }
};
