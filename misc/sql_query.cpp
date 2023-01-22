#include <xbt/sql_query.h>

#include <cstdio>
#include <vector>
#include <xbt/database.h>

Csql_query::Csql_query(Cdatabase& database, std::string v) :
  m_database(database),
  m_in(std::move(v))
{
}

Csql_result Csql_query::execute() const
{
  return m_database.query(read());
}

int Csql_query::execute_nothrow() const
{
  return m_database.query_nothrow(read());
}

std::string Csql_query::replace_names(std::string_view v) const
{
  std::string r;
  for (size_t i = 0; ; )
  {
    size_t j = v.find('@', i);
    if (j == std::string::npos)
    {
      r.append(v, i, v.size() - i);
      break;
    }
    r.append(v, i, j - i);
    i = j + 1;
    j = v.find_first_of(" ,", i);
    if (j == std::string::npos)
      j = v.size();
    r += m_database.name(v.substr(i, j - i));
    i = j;
  }
  return r;
}

std::string Csql_query::read() const
{
  return m_out + replace_names(m_in);
}

void Csql_query::operator=(std::string v)
{
  m_in = std::move(v);
  m_out.clear();
}

void Csql_query::operator+=(std::string_view v)
{
  m_in += v;
}

Csql_query& Csql_query::p_name(std::string_view v0)
{
  std::string_view v = m_database.name(v0);
  std::vector<char> r(2 * v.size() + 2);
  r.resize(mysql_real_escape_string(m_database, &r[1], v.data(), v.size()) + 2);
  r.front() = '`';
  r.back() = '`';
  p_raw(r);
  return *this;
}

Csql_query& Csql_query::p_raw(data_ref v)
{
  size_t i = m_in.find('?');
  assert(i != std::string::npos);
  if (i == std::string::npos)
    return *this;
  m_out.append(replace_names(m_in.substr(0, i)));
  m_in.erase(0, i + 1);
  m_out.append(v.begin(), v.end());
  return *this;
}

Csql_query& Csql_query::operator()(long long v)
{
  p_raw(std::to_string(v));
  return *this;
}

Csql_query& Csql_query::operator()(str_ref v)
{
  std::vector<char> r(2 * v.size() + 2);
  r.resize(mysql_real_escape_string(m_database, &r[1], v.data(), v.size()) + 2);
  r.front() = '\'';
  r.back() = '\'';
  p_raw(r);
  return *this;
}
