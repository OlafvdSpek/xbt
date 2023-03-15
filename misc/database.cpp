#include <xbt/database.h>

#include <iostream>
#include <xbt/find_ptr.h>

void Cdatabase::open(const std::string& host, const std::string& user, const std::string& password, const std::string& database)
{
  if (!mysql_init(&handle_)
    || mysql_options(&handle_, MYSQL_READ_DEFAULT_GROUP, "")
    || !mysql_real_connect(&handle_, host.c_str(), user.c_str(), password.empty() ? NULL : password.c_str(), database.c_str(), database == "sphinx" ? 9306 : 0, NULL, 0))
    throw bad_query(mysql_error(&handle_));
  char a0 = true;
  mysql_options(&handle_, MYSQL_OPT_RECONNECT, &a0);
}

int Cdatabase::query_nothrow(std::string_view q)
{
  if (query_log_)
    *query_log_ << q.substr(0, 999) << "\n";
  if (mysql_real_query(&handle_, q.data(), q.size()))
  {
    std::cerr << mysql_error(&handle_) << "\n"
      << q.substr(0, 239) << "\n";
    return 1;
  }
  return 0;
}

Csql_result Cdatabase::query(std::string_view q)
{
  if (query_nothrow(q))
    throw bad_query(mysql_error(&handle_));
  MYSQL_RES* result = mysql_store_result(&handle_);
  if (!result && mysql_errno(&handle_))
    throw bad_query(mysql_error(&handle_));
  return Csql_result(result);
}

void Cdatabase::set_query_log(std::ostream* v)
{
  query_log_ = v;
}

void Cdatabase::set_name(const std::string& a, std::string b)
{
  names_[a] = std::move(b);
}

std::string_view Cdatabase::name(std::string_view v) const
{
  const std::string* i = find_ptr(names_, v);
  return i ? *i : v;
}

std::string Cdatabase::replace_names(std::string_view v) const
{
  std::string r;
  while (1)
  {
    r += read_until(v, '@');
    if (v.empty())
      break;
    size_t i = v.find_first_of(" ),");
    if (i == std::string_view::npos)
      i = v.size();
    r += name(std::string(v.substr(0, i)));
    v.remove_prefix(i);
  }
  return r;
}
