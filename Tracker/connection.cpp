#include "stdafx.h"
#include "connection.h"

#include "epoll.h"
#include "tracker.h"

connection_t::connection_t(const Csocket& s, const sockaddr_in6& a)
{
  m_s = s;
  addr_ = a;
  ctime_ = srv_time();

  w_ = read_b_;
}

int connection_t::pre_select(fd_set& fd_read_set, fd_set& fd_write_set)
{
  FD_SET(m_s, &fd_read_set);
  if (!r_.empty())
    FD_SET(m_s, &fd_write_set);
  return m_s;
}

int connection_t::post_select(fd_set& fd_read_set, fd_set& fd_write_set)
{
  return (FD_ISSET(m_s, &fd_read_set) && recv())
    || (FD_ISSET(m_s, &fd_write_set) && send())
    || srv_time() - ctime_ > 10
    || (state_ == 5 && r_.empty());
}

int connection_t::recv()
{
  int r = m_s.recv(w_);
  if (!r)
  {
    state_ = 5;
    return 0;
  }
  if (r == SOCKET_ERROR)
  {
    int e = WSAGetLastError();
    switch (e)
    {
    case WSAECONNABORTED:
    case WSAECONNRESET:
      return 1;
    case WSAEWOULDBLOCK:
      return 0;
    }
    std::cerr << "recv failed: " << Csocket::error2a(e) << std::endl;
    return 1;
  }
  if (state_ == 5)
    return 0;
  const char* a = w_.data();
  w_.advance_begin(r);
  int state;
  do
  {
    state = state_;
    while (a < w_.begin() && *a != '\n' && *a != '\r')
    {
      a++;
      if (state_)
        state_ = 1;
    }
    if (a < w_.begin())
    {
      switch (state_)
      {
      case 0:
        read(std::string(&read_b_.front(), a - &read_b_.front()));
        state_ = 1;
        [[fallthrough]];
      case 1:
      case 3:
        state_ += *a == '\n' ? 2 : 1;
        break;
      case 2:
      case 4:
        state_++;
        break;
      }
      a++;
    }
  }
  while (state != state_);
  return 0;
}

int connection_t::send()
{
  if (r_.empty())
    return 0;
  int r = m_s.send(r_);
  if (r == SOCKET_ERROR)
  {
    int e = WSAGetLastError();
    switch (e)
    {
    case WSAECONNABORTED:
    case WSAECONNRESET:
      return 1;
    case WSAEWOULDBLOCK:
      return 0;
    }
    std::cerr << "send failed: " << Csocket::error2a(e) << std::endl;
    return 1;
  }
  r_.advance_begin(r);
  if (r_.empty())
    write_b_.clear();
  return 0;
}

void connection_t::read(std::string_view v)
{
#ifndef NDEBUG
  // std::cout << Csocket::inet_ntoa(addr_.sin6_addr) << "; ";
  std::cout << v << std::endl;
#endif
  if (srv_config().log_access_)
  {
    static std::ofstream f("xbt_tracker_raw.log");
    f << srv_time() << '\t' << Csocket::inet_ntoa(addr_.sin6_addr) << '\t' << ntohs(addr_.sin6_port) << '\t' << v << std::endl;
  }
  tracker_input_t ti;
  size_t e = v.find('?');
  if (e == std::string::npos)
    e = v.size();
  else
  {
    size_t a = e + 1;
    size_t b = v.find(' ', a);
    if (b == std::string::npos)
      return;
    while (a < b)
    {
      size_t c = v.find('=', a);
      if (c++ == std::string::npos)
        break;
      size_t d = v.find_first_of(" &", c);
      if (d == std::string::npos)
        break;
      ti.set(v.substr(a, c - a - 1), uri_decode(v.substr(c, d - c)));
      a = d + 1;
    }
  }
  // if (ti.ipv6_ == std::array<char, 16>() || !is_private_ipa(addr_.sin6_addr.s6_addr))
    memcpy(ti.ipv6_.data(), addr_.sin6_addr.s6_addr, 16);
  std::string_view torrent_pass;
  size_t a = 4;
  if (a < e && v[a] == '/')
  {
    a++;
    if (a + 32 < e && v[a + 32] == '/')
    {
      torrent_pass = std::string_view(&v[a], 32);
      a += 33;
    }
  }
  std::string h = "HTTP/1.0 200 OK\r\n";
  std::string s;
  bool gzip = true;
  switch (a < v.size() ? v[a] : 0)
  {
  case 'a':
    if (ti.valid())
    {
      gzip = false;
      std::string error = srv_insert_peer(ti, false, find_user_by_torrent_pass(torrent_pass, ti.info_hash_));
      s = error.empty() ? srv_select_peers(ti) : (boost::format("d14:failure reason%d:%se") % error.size() % error).str();
    }
    break;
  case 'd':
    if (srv_config().debug_)
    {
      h += "Content-Type: text/html; charset=us-ascii\r\n";
      s = srv_debug(ti);
    }
    break;
  case 's':
    if (v.size() >= 7 && v[6] == 't')
    {
      h += "Content-Type: text/html; charset=us-ascii\r\n";
      s = srv_statistics();
    }
    else if (srv_config().full_scrape_ || !ti.info_hash_.empty())
    {
      gzip = srv_config().gzip_scrape_ && ti.info_hash_.empty();
       s = srv_scrape(ti, find_user_by_torrent_pass(torrent_pass, ti.info_hash_));
    }
    break;
  case 'm':
    gzip = false;
    h += "Content-Type: text/plain; charset=us-ascii\r\n";
    s = srv_metrics();
    break;
  }
  if (s.empty())
  {
    if (!ti.info_hash_.empty() || srv_config().redirect_url_.empty())
      h = "HTTP/1.0 404 Not Found\r\n";
    else
    {
      h = "HTTP/1.0 302 Found\r\n"
        "Location: " + srv_config().redirect_url_ + (ti.info_hash_.empty() ? "" : "?info_hash=" + uri_encode(ti.info_hash_)) + "\r\n";
    }
  }
  else if (gzip)
  {
    shared_data s2 = xcc_z::gzip(s);
#ifndef NDEBUG
    static std::ofstream f("xbt_tracker_gzip.log");
    f << srv_time() << '\t' << v[5] << '\t' << s.size() << '\t' << s2.size() << std::endl;
#endif
    if (s2.size() + 24 < s.size())
    {
      h += "Content-Encoding: gzip\r\n";
      s = to_string(s2);
    }
  }
  h += "\r\n";
#ifdef WIN32
  write_b_ = shared_data(h.size() + s.size());
  memcpy(write_b_.data(), h);
  memcpy(write_b_.data() + h.size(), s);
  int r = m_s.send(write_b_);
#else
  std::array<iovec, 2> d;
  d[0].iov_base = const_cast<char*>(h.data());
  d[0].iov_len = h.size();
  d[1].iov_base = const_cast<char*>(s.data());
  d[1].iov_len = s.size();
  msghdr m;
  m.msg_name = NULL;
  m.msg_namelen = 0;
  m.msg_iov = const_cast<iovec*>(d.data());
  m.msg_iovlen = d.size();
  m.msg_control = NULL;
  m.msg_controllen = 0;
  m.msg_flags = 0;
  int r = sendmsg(m_s, &m, MSG_NOSIGNAL);
#endif
  if (r == SOCKET_ERROR)
  {
    if (WSAGetLastError() != WSAECONNRESET)
      std::cerr << "send failed: " << Csocket::error2a(WSAGetLastError()) << std::endl;
  }
  else if (r != ssize(h) + ssize(s))
  {
#ifndef WIN32
    if (r < ssize(h))
    {
      write_b_ = shared_data(h.size() + s.size());
      memcpy(write_b_.data(), h);
      memcpy(write_b_.data() + h.size(), s);
    }
    else
    {
      write_b_ = make_shared_data(s);
      r -= h.size();
    }
#endif
    r_ = write_b_;
    r_.advance_begin(r);
  }
  if (r_.empty())
    write_b_.clear();
}

void connection_t::process_events(int events)
{
  if ((events & (EPOLLIN | EPOLLPRI | EPOLLERR | EPOLLHUP) && recv())
    || (events & EPOLLOUT && send())
    || (state_ == 5 && write_b_.empty()))
    m_s.close();
}

int connection_t::run()
{
  return s() == INVALID_SOCKET || srv_time() - ctime_ > 10;
}
