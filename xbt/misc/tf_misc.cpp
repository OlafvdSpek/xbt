#include "tf_misc.h"

#include <boost/algorithm/string.hpp>
#include <boost/format.hpp>

static std::string web_encode(str_ref v)
{
	std::string d;
	d.reserve(v.size() << 1);
	while (v)
	{
		switch (v.front())
		{
		case '"': 
			d += "&quot;"; 
			break;
		case '&': 
			d += "&amp;"; 
			break;
		case '<':
			d += "&lt;";
			break;
		default:
			d += v.front();
		}
		v.pop_front();
	}
	return d;
}

static std::string web_link(str_ref title, str_ref link)
{
	return (boost::format("<a href=\"%s\">%s</a>") % web_encode(link) % web_encode(title.empty() ? link : title)).str();
}

std::string encode_field(const std::string& v)
{
	std::string r;
	r.reserve(v.size() << 1);
	for (size_t i = 0; i < v.size(); )
	{
		if (boost::istarts_with(v.c_str() + i, "ftp://")
			|| boost::istarts_with(v.c_str() + i, "http://")
			|| boost::istarts_with(v.c_str() + i, "https://")
			|| boost::istarts_with(v.c_str() + i, "mailto:"))
		{
			size_t p = i;
			while (p < v.size()
				&& !isspace(v[p] & 0xff)
				&& v[p] != '\"'
				&& v[p] != '<'
				&& v[p] != '>'
				&& v[p] != '['
				&& v[p] != ']')
			{
				p++;
			}
			if (v[p - 1] == '!' || v[p - 1] == ',' || v[p - 1] == '.' || v[p - 1] == '?')
				p--;
			if (v[p - 1] == ')')
				p--;
			std::string url = web_encode(v.substr(i, p - i));
			if (boost::istarts_with(v.c_str() + i, "ftp."))
				r += web_link(url, "ftp://" + url);
			else if (boost::istarts_with(v.c_str() + i, "www."))
				r += web_link(url, "http://" + url);
			else
				r += web_link(boost::istarts_with(v.c_str() + i, "mailto:") ? url.substr(7) : url, url);
			i = p;
		}
		else
		{
			char c = v[i++];
			switch (c)
			{
			case '<':
				r += "&lt;";
				break;
			case '&':
				r += "&amp;";
				break;
			default:
				r += c;
			}
		}
	}
	return r;
}

std::string encode_field(str_ref v)
{
	return encode_field(v.s());
}

std::string encode_text(const std::string& v, bool add_span)
{
	std::string r;
	r.reserve(v.size() << 1);
	for (size_t i = 0; i < v.size(); )
	{
		size_t p = v.find('\n', i);
		if (p == std::string::npos)
			p = v.size();
		std::string line = v.substr(i, p - i);
		line = encode_field(line);
		r += add_span && boost::istarts_with(line, "> ") ? "<span class=quote>" + line + "</span>" : line;
		r += "<br>";
		i = p + 1;
	}
	return r;
}

std::string encode_text(str_ref v, bool add_span)
{
	return encode_text(v.s(), add_span);
}

std::string trim_field(const std::string& v)
{
	return boost::find_format_all_copy(boost::trim_copy(v), boost::token_finder(boost::is_space(), boost::token_compress_on), boost::const_formatter(" "));
}

std::string trim_text(const std::string& v)
{
	std::string r;
	bool copy_white = false;
	for (size_t i = 0; i < v.size(); )
	{
		size_t p = v.find('\n', i);
		if (p == std::string::npos)
			p = v.size();
		std::string line = trim_field(v.substr(i, p - i));
		if (line.empty())
			copy_white = true;
		else
		{
			if (copy_white)
			{
				if (!r.empty())
					r += '\n';
				copy_white = false;
			}
			r += line + '\n';
		}
		i = p + 1;
	}
	return r;
}
