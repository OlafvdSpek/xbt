#pragma once

#include <boost/checked_delete.hpp>
#include <boost/range/iterator_range.hpp>
#include <boost/smart_ptr/shared_ptr.hpp>
#include <boost/type_traits/is_class.hpp>
#include <boost/utility/enable_if.hpp>

template<class T> 
class shared_array2 : public boost::iterator_range<T*>
{
public:
	shared_array2()
	{
	}

	explicit shared_array2(size_t sz)
	{
		boost::shared_ptr<T> n(new T[sz], boost::checked_array_deleter<T>());
		static_cast<base_t&>(*this) = base_t(n.get(), n.get() + sz);
		n_ = n;
	}

	template<class V>
	shared_array2(const V& v, typename boost::enable_if<typename boost::is_class<V>>::type* = 0) :
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

typedef shared_array2<const unsigned char> shared_data;
typedef shared_array2<unsigned char> shared_mutable_data;
typedef shared_array2<const char> shared_str;
typedef shared_array2<char> shared_mutable_str;
