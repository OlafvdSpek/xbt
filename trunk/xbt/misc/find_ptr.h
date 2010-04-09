template <class T, class U>
typename T::pointer find_ptr(T& c, U v)
{
	typename T::iterator i = c.find(v);
	return i == c.end() ? NULL : &*i;
}
