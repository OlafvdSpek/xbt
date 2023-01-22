#pragma once

template <class T, class U>
const typename T::value_type::second_type* find_ptr(const T& c, const U& v)
{
  typename T::const_iterator i = c.find(v);
  return i == c.end() ? NULL : &i->second;
}

template <class T, class U>
typename T::value_type::second_type* find_ptr(T& c, const U& v)
{
  typename T::iterator i = c.find(v);
  return i == c.end() ? NULL : &i->second;
}

template <class T, class U>
const typename T::value_type::second_type& find_ptr2(const T& c, const U& v)
{
  static typename T::value_type::second_type z = typename T::value_type::second_type();
  typename T::const_iterator i = c.find(v);
  return i == c.end() ? z : i->second;
}

template <class T, class U>
typename T::value_type::second_type& find_ptr2(T& c, const U& v)
{
  static typename T::value_type::second_type z = typename T::value_type::second_type();
  typename T::iterator i = c.find(v);
  return i == c.end() ? z : i->second;
}

template <class T, class U>
const typename T::value_type* find_ptr0(const T& c, const U& v)
{
  typename T::const_iterator i = c.find(v);
  return i == c.end() ? NULL : &*i;
}

template <class T, class U>
typename T::value_type::second_type& find_ref(T& c, const U& v)
{
  typename T::iterator i = c.find(v);
  assert(i != c.end());
  return i->second;
}

template <class T, class U>
const typename T::value_type::second_type& find_ref(const T& c, const U& v)
{
  typename T::const_iterator i = c.find(v);
  assert(i != c.end());
  return i->second;
}

template <class T, class U>
const typename T::value_type::second_type& find_ref(const T& c, const U& v, const typename T::value_type::second_type& z)
{
  typename T::const_iterator i = c.find(v);
  return i == c.end() ? z : i->second;
}

template <class T, class U>
typename T::value_type::second_type& find_ref(T& c, const U& v, typename T::value_type::second_type& z)
{
  typename T::iterator i = c.find(v);
  return i == c.end() ? z : i->second;
}
