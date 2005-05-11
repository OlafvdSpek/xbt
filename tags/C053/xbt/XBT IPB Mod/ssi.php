<?php

/*
+--------------------------------------------------------------------------
|   IBFORUMS v1.2
|   ========================================
|   by Matthew Mecham
|   (c) 2001,2002 IBForums
|   http://www.ibforums.com
|   ========================================
|   Web: http://www.ibforums.com
|   Email: phpboards@ibforums.com
|   Licence Info: phpib-licence@ibforums.com
+---------------------------------------------------------------------------
|
|   > SSI script
|   > Script written by Matt Mecham
|   > Date started: 29th April 2002
|
+--------------------------------------------------------------------------
*/

/* USAGE:
   ------
   
   Simply call this script via PHP includes, or SSI .shtml tags to generate content
   on the fly, streamed into your own webpage.
   
   To show the last 10 topics and posts in the news forums...
   
   include("http://domain.com/forums/ssi.php?a=news&show=10");
   
   You can adjust the "show" attribute to display a different amount of topics.
   
   To show the board statistics
   
   include("http://domain.com/forums/ssi.php?a=stats");
   
   To show the active users stats (x Members, X Guests, etc)
   
   include("http://domain.com/forums/ssi.php?a=active");
   
   Syndication..
   
   RSS:
   
   http://domain.com/forums/ssi.php?a=out&f=1,2,3,4,5&show=10&type=rss
   http://domain.com/forums/ssi.php?a=out&f=1,2,3,4,5&show=10&type=xml
   
   Will show last 10 topics in reverse chronological last post date order from
   all the forums in the comma separated list
   
*/

//-----------------------------------------------
// USER CONFIGURABLE ELEMENTS
//-----------------------------------------------
 
// Root path

$root_path = "./";

$templates_dir = "./ssi_templates";

$max_show  = 100;  // Maximum number of topics possible to show...

$allow_syndication = 1;  // To turn off, use $allow_syndication = 0;

//-----------------------------------------------
// NO USER EDITABLE SECTIONS BELOW
//-----------------------------------------------
 
error_reporting  (E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);

class info {

	var $input      = array();
	var $base_url   = "";
	var $vars       = "";
	function info() {
		global $sess, $std, $DB, $root_path, $INFO;
		
		$this->vars = &$INFO;
		
	}
}

//--------------------------------
// Import $INFO, now!
//--------------------------------

require $root_path."conf_global.php";

//--------------------------------
// Require our global functions
//--------------------------------

require $root_path."sources/functions.php";

$std   = new FUNC;

//--------------------------------
// Load the DB driver and such
//--------------------------------

$INFO['sql_driver'] = !$INFO['sql_driver'] ? 'mySQL' : $INFO['sql_driver'];

$to_require = $root_path."sources/Drivers/".$INFO['sql_driver'].".php";
require ($to_require);

$DB = new db_driver;

$DB->obj['sql_database']     = $INFO['sql_database'];
$DB->obj['sql_user']         = $INFO['sql_user'];
$DB->obj['sql_pass']         = $INFO['sql_pass'];
$DB->obj['sql_host']         = $INFO['sql_host'];
$DB->obj['sql_tbl_prefix']   = $INFO['sql_tbl_prefix'];

// Get a DB connection

$DB->connect();

//--------------------------------
// Wrap it all up in a nice easy to
// transport super class
//--------------------------------

$ibforums             = new info();

//--------------------------------
//  Set up our vars
//--------------------------------

$ibforums->input      = $std->parse_incoming();
$ibforums->base_url   = $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'];

//--------------------------------
// What to do?
//--------------------------------

switch ($ibforums->input['a'])
{
	case 'news':
		do_news();
		break;
		
	case 'active':
		do_active();
		break;
		
	case 'stats':
		do_stats();
		break;
		
	case 'out':
		if ( $allow_syndication == 1 )
		{
			do_syndication();
		}
		else
		{
			exit();
		}
		break;
		
	default:
		echo("An error occured whilst processing this directive");
		exit();
		break;
}

//+-------------------------------------------------
// SYNDICATION!
//+-------------------------------------------------

function do_syndication()
{
	global $DB, $ibforums, $root_path, $templates_dir, $std, $max_show;
	
	//----------------------------------------
	// Sort out the forum ids
	//----------------------------------------
	
	$tmp_forums = array();
	$forums     = array();
	
	if ( $ibforums->input['f'] )
	{
		$tmp_forums = explode( ",", $ibforums->input['f'] );
	}
	else
	{
		fatal_error("Fatal error: no forum id specified");
	}
	
	foreach ($tmp_forums as $f )
	{
		$f = intval($f);
		
		if ( $f )
		{
			$forums[] = $f;
		}
	}
	
	if ( count($forums) < 1 )
	{
		fatal_error("Fatal error: no forum id specified");
	}
	
	$sql_fields = implode( ",", $forums );
	
	//----------------------------------------
	// Number of topics to return?
	//----------------------------------------
	
	$perpage = intval($ibforums->input['show']) ? intval($ibforums->input['show']) : 10;
	
	$perpage = ( $perpage > $max_show ) ? $max_show : $perpage;
	
	//----------------------------------------
	// Load the template...
	//----------------------------------------
	
	if ( $ibforums->input['type'] == 'xml' )
	{
		$template = load_template("syndicate_xml.html");
	}
	else
	{
		$template = load_template("syndicate_rss.html");
	}
	
	//----------------------------------------
	// parse..
	//----------------------------------------
	
	$to_echo = "";
	$top     = "";
	$row     = "";
	$bottom  = "";
	
	preg_match( "#\[TOP\](.+?)\[/TOP\]#is", $template, $match );
	
	$top    = trim($match[1]);
	
	preg_match( "#\[ROW\](.+?)\[/ROW\]#is", $template, $match );
	
	$row    = trim($match[1]);
	
	preg_match( "#\[BOTTOM\](.+?)\[/BOTTOM\]#is", $template, $match );
	
	$bottom = trim($match[1]);
	
	//----------------------------------------
	// Header parse...
	//----------------------------------------
	
	@header('Content-Type: text/xml');
	@header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	@header('Pragma: public');
	
	$to_echo .= parse_template( $top, array (
											  'board_url'  => $ibforums->base_url          ,
											  'board_name' => $ibforums->vars['board_name'],
							  )     	    );
	
	$DB->query("SELECT g_id, g_perm_id FROM ibf_groups WHERE g_id={$ibforums->vars['guest_group']}");
	
	$group = $DB->fetch_row();
	
	$ibforums->perm_id_array = explode( ",", $group['g_perm_id'] );
	
	//----------------------------------------
	// Get the topics, member info and other stuff
	//----------------------------------------
	
	$DB->query("SELECT t.*, f.name as forum_name, f.read_perms, f.password
				 FROM ibf_topics t
				  LEFT JOIN ibf_forums f ON ( f.id=t.forum_id )
				WHERE t.forum_id IN ($sql_fields)
			    AND t.approved=1 ORDER BY t.last_post DESC LIMIT 0, $perpage");
			   
	if ( ! $DB->get_num_rows() )
	{
		fatal_error("Could not get the information from the database");
	}

	while ( $i = $DB->fetch_row() )
	{
		if ( $std->check_perms( $i['read_perms'] ) != TRUE )
		{
			continue;
		}
		
		if ($i['password'] != "" )
		{
			continue;
		}
		
		$to_echo .= parse_template( $row, array (
								    		 	 'topic_title'    => str_replace( '&#', '&amp;#', $i['title'] ),
								    		 	 'topic_id'       => $i['tid'],
								    		 	 'topic_link'     => $ibforums->base_url."?showtopic=".$i['tid'],
								    		 	 'forum_title'    => htmlspecialchars($i['forum_name']),
								    		 	 'forum_id'       => $i['forum_id'],
								    		 	 'last_poster_id' => $i['last_poster_id'],
								    		 	 'last_post_name' => $i['last_poster_name'],
								    		 	 'last_post_time' => $std->get_date( $i['last_post'] , 'LONG' ),
								    		 	 'timestamp'      => $i['last_post'],
								    		 	 'starter_id'     => $i['starter_id'],
								    		 	 'starter_name'   => $i['starter_name'],
								    		 	 'board_url'      => $ibforums->base_url          ,
											     'board_name'     => $ibforums->vars['board_name'],
											     'rfc_date'       => date( 'r', $i['last_post'] ),
								  )             ) . "\r\n";
	}
	
	echo $to_echo."\r\n".$bottom;
	
	exit();
	
}


//+-------------------------------------------------
// Import the stats! WOOHOO
//+-------------------------------------------------

function do_stats()
{
	global $DB, $ibforums, $root_path, $templates_dir, $std;
	
	// Load the template...
	
	$template = load_template("stats.html");
	
	$to_echo = "";
	
	// Get the topics, member info and other stuff
	$time = time() - 900;
			
	$DB->query("SELECT * FROM ibf_stats");
	$stats = $DB->fetch_row();
	
	$total_posts = $stats['TOTAL_REPLIES']+$stats['TOTAL_TOPICS'];
	
	$to_echo  = parse_template( $template,
								array (
										 'total_posts'  => $total_posts,
										 'topics'       => $stats['TOTAL_TOPICS'],
										 'replies'      => $stats['TOTAL_REPLIES'],
										 'members'      => $stats['MEM_COUNT']
									  )
								);
	
	
	echo $to_echo;
	
	exit();
	
}


function do_news()
{
	global $DB, $ibforums, $root_path, $templates_dir, $std, $max_show;
	
	if ( (! $ibforums->vars['news_forum_id']) or ($ibforums->vars['news_forum_id'] == "" ) )
	{
		fatal_error("No news forum assigned");
	}
	
	require $root_path."sources/lib/post_parser.php";
        
	$parser = new post_parser();
        
	$perpage = intval($ibforums->input['show']) > 0 ? intval($ibforums->input['show']) : 10;
	
	$perpage = ( $perpage > $max_show ) ? $max_show : $perpage;
	
	// Load the template...
	
	$template = load_template("news.html");
	
	$to_echo = "";
	
	// Get the topics, member info and other stuff
	
	$DB->query("SELECT m.name as member_name, m.id as member_id,m.title as member_title, m.avatar, m.avatar_size, m.posts, t.*, p.*, g.g_dohtml, f.use_html
	            FROM ibf_topics t
	            	LEFT JOIN ibf_posts p ON (p.new_topic=1 AND p.topic_id=t.tid)
	            	LEFT JOIN ibf_members m ON (m.id=t.starter_id)
	            	LEFT JOIN ibf_groups g ON (g.g_id=m.mgroup)
	            	LEFT JOIN ibf_forums f ON (t.forum_id=f.id)
			    WHERE t.forum_id={$ibforums->vars['news_forum_id']} AND t.approved=1 ORDER BY t.tid DESC LIMIT 0, $perpage");
			   
	if ( ! $DB->get_num_rows() )
	{
		fatal_error("Could not get the information from the database");
	}

	while ( $row = $DB->fetch_row() )
	{
		$row['post'] = str_replace( '<br>', '<br />', $row['post'] );
		
		$to_echo .= parse_template( $template,
								    array (
								    		 'profile_link'   => $ibforums->base_url."?act=Profile&CODE=03&MID=".$row['member_id'],
								    		 'member_name'    => $row['member_name'],
								    		 'post_date'      => $std->get_date( $row['start_date'], 'LONG' ),
								    		 'topic_title'    => $row['title'],
								    		 'post'           => $parser->post_db_parse($row['post'], ($row['use_html'] AND $row['g_dohtml']) ? 1 : 0 ),
								    		 'comments'       => $row['posts'],
								    		 'view_all_link'  => $ibforums->base_url."?act=ST&f={$row['forum_id']}&t={$row['tid']}"
								    	  )
								    );
	}
	
	echo $to_echo;
	
	exit();
	
}


function do_active()
{
	global $DB, $ibforums, $root_path, $templates_dir, $std;
	
	// Load the template...
	
	$template = load_template("active.html");
	
	$to_echo = "";
	
	// Get the topics, member info and other stuff
	$time = time() - 900;
			
	$DB->query("SELECT s.member_id, s.member_name, s.login_type, g.suffix, g.prefix FROM ibf_sessions s, ibf_groups g WHERE running_time > '$time' AND g.g_id=s.member_group ORDER BY running_time DESC");
	
	// cache all printed members so we don't double print them
	$cached = array();
	
	$active = array();
	
	while ($result = $DB->fetch_row() )
	{
		if ($result['member_id'] == 0)
		{
			$active['GUESTS']++;
		}
		else
		{
			if (empty( $cached[ $result['member_id'] ] ) )
			{
				$cached[ $result['member_id'] ] = 1;
				if ($result['login_type'] == 1)
				{
					$active['ANON']++;
				}
				else
				{
					$active['MEMBERS']++;
				}
			}
			
		}
	}
	
	$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			   
	
	$to_echo  = parse_template( $template,
								array (
										 'total'   => $active['TOTAL']   ? $active['TOTAL']   : 0 ,
										 'members' => $active['MEMBERS'] ? $active['MEMBERS'] : 0,
										 'guests'  => $active['GUESTS']  ? $active['GUESTS']  : 0,
										 'anon'    => $active['ANON']    ? $active['ANON']    : 0,
									  )
								);
	
	
	echo $to_echo;
	
	exit();
	
}






//+-------------------------------------------------
// GLOBAL ROUTINES
//+-------------------------------------------------


function parse_template( $template, $assigned=array() )
{
	
	foreach( $assigned as $word => $replace)
	{
		$template = preg_replace( "/\{$word\}/i", "$replace", $template );
	}
	
	return $template;
}



function load_template($template="")
{
	global $templates_dir;
	
	$filename = $templates_dir."/".$template;
	
	if ( file_exists($filename) )
	{
		if ( $FH = fopen($filename, 'r') )
		{
			$template = fread( $FH, filesize($filename) );
			fclose($FH);
		}
		else
		{
			fatal_error("Couldn't open the template file");
		}
	}
	else
	{
		fatal_error("Template file does not exist");
	}
	
	return $template;

}

function fatal_error($message="") {
	echo("An error occured whilst processing this directive");
	if ($message)
	{
		echo("<br>$message");
	}
	exit();
}
?>
