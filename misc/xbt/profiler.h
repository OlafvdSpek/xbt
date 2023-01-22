#pragma once

#include <boost/date_time/posix_time/posix_time_types.hpp>
#include <string>
#include <xbt/bt_misc.h>

class xbt_profiler
{
public:
  xbt_profiler(int limit, const std::string& text)
  {
    limit_ = limit;
    t0_ = boost::posix_time::microsec_clock::universal_time();
    text_ = text;
  }

  ~xbt_profiler()
  {
    auto ms = (boost::posix_time::microsec_clock::universal_time() - t0_).total_milliseconds();
    if (ms < limit_)
      return;
    std::string s;
    xbt_syslog(s << ms << " ms: " << text_);
  }
private:
  int limit_;
  boost::posix_time::ptime t0_;
  std::string text_;
};
