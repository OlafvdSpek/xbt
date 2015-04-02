#include "tf_misc.h"

#include <algorithm>
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

std::string encode_field(str_ref v, bool add_br)
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
			case '\n':
				r += add_br ? "<br>" : " ";
				break;
			case '\r':
				break;
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

enum bb_t
{
	bb_literal,
	bb_none,
	bb_bold,
	bb_bold_close,
	bb_center,
	bb_center_close,
	bb_color,
	bb_color_close,
	bb_quote,
	bb_quote_close,
	bb_strike,
	bb_strike_close,
	bb_underline,
	bb_underline_close,
	bb_url,
	bb_unknown,
	bb_video,
	bb_end,
};

static bool operator==(str_ref a, const char* b)
{
	return a.size() == strlen(b) && !memcmp(a.data(), b, a.size());
}

bb_t get_next(str_ref& s, str_ref& a0)
{
	if (!s)
		return bb_end;
	if (s.front() != '[')
	{
		auto a = std::find(s.begin(), s.end(), '[');
		if (a == s.end())
		{
			a0 = s;
			s.clear();
		}
		else
		{
			a0 = str_ref(s.begin(), a);
			s.set_begin(a);
		}
		return bb_literal;
	}
	auto a = std::find(s.begin(), s.end(), ']');
	if (a == s.end())
	{
		a0 = s;
		s.clear();
		return bb_literal;
	}
	str_ref tag = { &s[1], a };
	s.set_begin(a + 1);
	a0.clear();
	if (tag == "b")
		return bb_bold;
	if (tag == "/b")
		return bb_bold_close;
	if (tag == "center")
		return bb_center;
	if (tag == "/center")
		return bb_center_close;
	if (boost::starts_with(tag, "color="))
	{
		a0 = tag.substr(6);
		return bb_color;
	}
	if (tag == "/color")
		return bb_color_close;
	if (boost::starts_with(tag, "font=") || tag == "/font")
		return bb_none;
	if (tag == "i" || tag == "/i")
		return bb_none;
	if (tag == "img" || tag == "IMG" || tag == "/img" || tag == "/IMG")
		return bb_none;
	if (boost::starts_with(tag, "img="))
	{
		a0 = tag.substr(4);
		return bb_literal;
	}
	if (tag == "q" || tag == "quote")
		return bb_quote;
	if (boost::starts_with(tag, "quote="))
	{
		a0 = tag.substr(6);
		return bb_quote;
	}
	if (tag == "/q" || tag == "/quote")
		return bb_quote_close;
	if (tag == "s")
		return bb_strike;
	if (tag == "/s")
		return bb_strike_close;
	if (boost::starts_with(tag, "size=") || tag == "/size")
		return bb_none;
	if (tag == "u")
		return bb_underline;
	if (tag == "/u")
		return bb_underline_close;
	if (boost::starts_with(tag, "url="))
	{
		a0 = tag.substr(4);
		return bb_url;
	}
	if (tag == "/url")
		return bb_none;
	if (boost::starts_with(tag, "video="))
	{
		a0 = tag.substr(6);
		return bb_video;
	}
	a0 = tag;
	return bb_unknown;
}

std::string bbformat(str_ref s)
{
	std::string d;
	str_ref a0;
	while (1)
	{
		switch (get_next(s, a0))
		{
		case bb_literal:
			d += encode_field(a0, true);
			break;
		case bb_none:
			break;
		case bb_bold:
			d += "<b>";
			break;
		case bb_bold_close:
			d += "</b>";
			break;
		case bb_center:
			d += "<center>";
			break;
		case bb_center_close:
			d += "</center>";
			break;
		case bb_color:
			d += "<font color=\"" + encode_field(a0) + "\">"; // escape a0
			break;
		case bb_color_close:
			d += "</font>";
			break;
		case bb_quote:
			d += "<blockquote class=bq>";
			if (a0)
				d += "<b>" + encode_field(a0) + " wrote:</b>";
			break;
		case bb_quote_close:
			d += "</blockquote>";
			break;
		case bb_strike:
			d += "<s>";
			break;
		case bb_strike_close:
			d += "</s>";
			break;
		case bb_underline:
			d += "<u>";
			break;
		case bb_underline_close:
			d += "</u>";
			break;
		case bb_url:
			d += encode_field(a0, true) + " ";
			break;
		case bb_video:
			d += encode_field(a0, true) + " ";
			break;
		case bb_unknown:
			d += "[" + encode_field(a0) + "]";
			break;
		case bb_end:
			return d;
		default:
			assert(false);
		}
	}
}
