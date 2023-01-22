#pragma once

#include <xbt/data_ref.h>

class Cdatabase;
class Csql_result;

class Csql_query
{
public:
  Csql_result execute() const;
  int execute_nothrow() const;
  std::string read() const;
  void operator=(std::string);
  void operator+=(std::string_view);
  Csql_query& p_name(std::string_view);
  Csql_query& p_raw(data_ref);
  Csql_query& operator()(long long);
  Csql_query& operator()(str_ref);
  Csql_query(Cdatabase&, std::string = "");
private:
  std::string replace_names(std::string_view) const;

  Cdatabase& m_database;
  std::string m_in;
  std::string m_out;
};
