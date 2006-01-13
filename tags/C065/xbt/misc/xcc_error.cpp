#include "stdafx.h"
#include "xcc_error.h"

Cxcc_error::Cxcc_error()
{
}

Cxcc_error::Cxcc_error(const string& message)
{
	m_message = message;
}
