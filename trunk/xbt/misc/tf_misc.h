#pragma once

#include <string>

std::string encode_field(str_ref);
std::string encode_text(str_ref, bool add_span);
std::string trim_field(const std::string&);
std::string trim_text(const std::string&);
