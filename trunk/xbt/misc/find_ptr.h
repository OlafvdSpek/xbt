template <class T, class U>
typename T::pointer find_ptr(T& c, U v)
{
	typename T::iterator i = c.find(v);
	return i == c.end() ? NULL : &*i;
}

template <class T, class U>
typename T::const_pointer find_ptr(const T& c, U v)
{
        typename T::const_iterator i = c.find(v);
        return i == c.end() ? NULL : &*i;
}
