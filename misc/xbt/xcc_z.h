#pragma once

#include <xbt/shared_data.h>

namespace xcc_z
{
  shared_data gunzip(data_ref);
  shared_data gzip(data_ref);
  void gzip_out(data_ref);
}
