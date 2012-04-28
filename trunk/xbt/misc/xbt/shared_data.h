#pragma once

#include <boost/checked_delete.hpp>
#include <boost/range/iterator_range.hpp>
#include <boost/scoped_ptr.hpp>
#include <boost/shared_ptr.hpp>
// #include <boost/type_traits/is_class.hpp>
// #include <boost/utility/enable_if.hpp>
#include <cstdio>
#include <cstring>
#include <string>
#include <sys/stat.h>
#include <xbt/data_ref.h>

template<class T>
class shared_array2 : public boost::iterator_range<T*>
{
public:
	shared_array2()
	{
	}

	explicit shared_array2(size_t sz)
	{
		if (!sz)
			return;
		boost::shared_ptr<T> n(new T[sz], boost::checked_array_deleter<T>());
		static_cast<base_t&>(*this) = base_t(n.get(), n.get() + sz);
		n_ = n;
	}

	template<class V>
	shared_array2(const shared_array2<V>& v) : // , typename boost::enable_if<typename boost::is_class<V>>::type* = 0) :
		base_t(v.data(), v.data() + v.size()),
		n_(v.n())
	{
	}

	shared_array2(T* b, T* e, boost::shared_ptr<void> const& n) :
		base_t(b, e),
		n_(n)
	{
	}

	shared_array2(T* b, size_t sz, boost::shared_ptr<void> const& n) :
		base_t(b, b + sz),
		n_(n)
	{
	}

	void clear()
	{
		*this = shared_array2();
	}

	T* data() const
	{
		return base_t::begin();
	}

	boost::shared_ptr<void> const& n() const
	{
		return n_;
	}

	shared_array2 substr(size_t ofs, size_t sz) const
	{
		return shared_array2(data() + ofs, sz, n());
	}
private:
	typedef boost::iterator_range<T*> base_t;

	boost::shared_ptr<void> n_;
};

typedef shared_array2<unsigned char> shared_data;

inline shared_data make_shared_data(data_ref v)
{
  shared_data d(v.size());
  memcpy(d.data(), v);
  return d;
}

inline shared_data make_shared_data(const void* d, size_t sz)
{
	return make_shared_data(data_ref(d, sz));
}

inline shared_data file_get(const std::string& fname)
{
	shared_data d;
	FILE* f = fopen(fname.c_str(), "rb");
	if (!f)
		return d;
	struct stat b;
	if (!fstat(fileno(f), &b))
	{
		d = shared_data(b.st_size);
		if (fread(d.data(), b.st_size, 1, f) != 1)
			d.clear();
	}
	fclose(f);
	return d;
}

inline int file_put(const std::string& fname, data_ref v)
{
	FILE* f = fopen(fname.c_str(), "wb");
	if (!f)
		return 1;
	int error = fwrite(v.data(), v.size(), 1, f) != 1;
	fclose(f);
	return error;
}
