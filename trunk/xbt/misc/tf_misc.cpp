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
	return "<a href=\"" + web_encode(link) + "\">" + web_encode(title.empty() ? link : title) + "</a>";
}

std::string encode_field(str_ref v)
{
	std::string r;
	r.reserve(v.size() << 1);
	while (v)
	{
		if (boost::istarts_with(v, "ftp://")
			|| boost::istarts_with(v, "http://")
			|| boost::istarts_with(v, "https://")
			|| boost::istarts_with(v, "mailto:"))
		{
			size_t p = 0;
			while (p < v.size() && !isspace(v[p] & 0xff) && v[p] != '\"' && v[p] != '<' && v[p] != '>' && v[p] != '[' && v[p] != ']')
				p++;
			if (v[p - 1] == '!' || v[p - 1] == ',' || v[p - 1] == '.' || v[p - 1] == '?')
				p--;
			if (v[p - 1] == ')')
				p--;
			str_ref url = v.substr(0, p);
			if (boost::istarts_with(v, "ftp."))
				r += web_link(url, "ftp://" + url.s());
			else if (boost::istarts_with(v, "www."))
				r += web_link(url, "http://" + url.s());
			else
				r += web_link(boost::istarts_with(v, "mailto:") ? url.substr(7) : url, url);
			while (p--)
				v.pop_front();
		}
		else
		{
			switch (v.front())
			{
			case '&':
				r += "&amp;";
				break;
			case '<':
				r += "&lt;";
				break;
			default:
				r += v.front();
			}
			v.pop_front();
		}
	}
	return r;
}

std::string encode_text(str_ref v, bool add_quote_class)
{
	std::string r;
	r.reserve(v.size() << 1);
	while (v)
	{
		str_ref line = read_until(v, '\n');
		r += add_quote_class && boost::istarts_with(line, "> ") ? "<span class=quote>" + encode_field(line) + "</span>" : encode_field(line);
		r += "<br>";
	}
	return r;
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
