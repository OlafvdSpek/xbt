#pragma once

#include <string>
#include <xbt/data_ref.h>

std::string bbformat(str_ref);
std::string encode_field(str_ref, bool add_br = false);
std::string encode_text(str_ref, bool add_quote_class);
std::string trim_field(const std::string&);
std::string trim_text(const std::string&);
