#pragma once

#include <map>
#include <stdexcept>
#include <xbt/sql_result.h>
#include <xbt/string_view.h>

class bad_query : public std::runtime_error
{
public:
  using runtime_error::runtime_error;
};

class Cdatabase : boost::noncopyable
{
private:
  MYSQL handle_;
  std::map<std::string, std::string, std::less<>> names_;
  std::ostream* query_log_ = NULL;
public:
  void open(const std::string& host, const std::string& user, const std::string& password, const std::string& database);
  std::string_view name(std::string_view) const;
  Csql_result query(std::string_view);
  int query_nothrow(std::string_view);
  void set_name(const std::string&, std::string);
  void set_query_log(std::ostream*);
  std::string replace_names(std::string_view) const;

  Cdatabase()
  {
    mysql_init(&handle_);
  }

  ~Cdatabase()
  {
    close();
  }

  operator MYSQL* ()
  {
    return &handle_;
  }

  void close()
  {
    mysql_close(&handle_);
  }

  int affected_rows()
  {
    return mysql_affected_rows(&handle_);
  }

  int insert_id()
  {
    return mysql_insert_id(&handle_);
  }

  int select_db(const std::string& v)
  {
    return mysql_select_db(&handle_, v.c_str());
  }
};
