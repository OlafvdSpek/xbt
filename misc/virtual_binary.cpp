#include "xbt/virtual_binary.h"

#include <sys/stat.h>
#include <cstdio>
#include <cstring>

void Cvirtual_binary::assign(data_ref v)
{
  if (v.size())
  {
    m_source = boost::make_shared<Cvirtual_binary_source>(v.size());
    if (v.begin())
      memcpy(data_edit(), v.data(), v.size());
  }
  else
    m_source.reset();
}

unsigned char* Cvirtual_binary::write_start(size_t cb_d)
{
  if (size() != cb_d)
    assign(cb_d);
  return data_edit();
}
