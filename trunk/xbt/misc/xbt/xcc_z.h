#pragma once

#include <xbt/virtual_binary.h>

namespace xcc_z
{
	Cvirtual_binary gunzip(data_ref);
	Cvirtual_binary gzip(data_ref);
	void gzip_out(data_ref);
}
