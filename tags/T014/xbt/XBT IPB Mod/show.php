<?php

/*

Script Name: show.php
Script Author: Matt Mecham
Package: Invision Board
Date: 16th September 2002
-------------------------
What is this?
-------------------------
It's a little "add-on" that simply allows for a neater / easier to read
URL to an invision board URL.
Example: http://www.domain.com/forums/show.php/act/ST/f/3/t/45
Resolves to: http://www.domain.com/forums/index.php?act=ST&f=3&t=45

It's not used in Invision Board itself, but might come in handy in your
own projects (such as a search engine friendly menu, etc).

Probably only works with PHP 4.1+

*/

$base_url = 'http://localhost/invboard/index.php';  //Edit this to suit

$redirect = "";

if ( $_SERVER['PATH_INFO'] != "" )
{
	$c = 0;
	
	foreach( explode( "/", $_SERVER['PATH_INFO'] ) as $bit)
	{
		if ($bit != "")
		{
			if ($c == 0)
			{
				$c++;
				$redirect .= $bit.'=';
			}
			else
			{
				$c = 0;
				$redirect .= $bit.'&';
			}
		}
	}
}

header("Location: $base_url?".$redirect);

exit();

?>

