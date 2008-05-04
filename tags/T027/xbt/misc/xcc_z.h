#pragma once

#include <virtual_binary.h>

namespace xcc_z
{
	Cvirtual_binary gunzip(const_memory_range);
	Cvirtual_binary gzip(const_memory_range);
	void gzip_out(const_memory_range);
}
