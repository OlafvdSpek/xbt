<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.3.1 Final
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Time: Wed, 05 May 2004 18:09:25 GMT
|   Release: faf4a7c2b8220416837424452a6044e1
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > Wrapper script
|   > Script written by Matt Mecham
|   > Date started: 14th February 2002
|
+--------------------------------------------------------------------------
*/

//-----------------------------------------------
// USER CONFIGURABLE ELEMENTS
//-----------------------------------------------

// Root path

define( 'ROOT_PATH', "./" );

// Enable module usage?
// (Vital for some mods and IPB enhancements)

define ( 'USE_MODULES', 1 );

//-----------------------------------------------
// NO USER EDITABLE SECTIONS BELOW
//-----------------------------------------------

define ( 'IN_IPB', 1 );
 
error_reporting  (E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);

class Debug {
    function startTimer() {
        global $starttime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
    }
    function endTimer() {
        global $starttime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = round (($endtime - $starttime), 5);
        return $totaltime;
    }
}

class info {

	var $member     = array();
	var $input      = array();
	var $session_id = "";
	var $base_url   = "";
	var $vars       = "";
	var $skin_id    = "0";     // Skin Dir name
	var $skin_rid   = "";      // Real skin id (numerical only)
	var $lang_id    = "en";
	var $skin       = "";
	var $lang       = "";
	var $server_load = 0;
	var $version    = "v1.3.1 Final";
	var $lastclick  = "";
	var $location   = "";
	var $debug_html = "";
	var $perm_id    = "";
	var $forum_read = array();
	var $topic_cache = "";
	var $session_type = "";

	function info() {
		global $sess, $std, $DB, $INFO;
		
		$this->vars = &$INFO;
		
		$this->vars['TEAM_ICON_URL']   = $INFO['html_url'] . '/team_icons';
		$this->vars['AVATARS_URL']     = $INFO['html_url'] . '/avatars';
		$this->vars['EMOTICONS_URL']   = $INFO['html_url'] . '/emoticons';
		$this->vars['mime_img']        = $INFO['html_url'] . '/mime_types';

	}
}

//--------------------------------
// Import $INFO, now!
//--------------------------------

$INFO = array();

require ROOT_PATH."conf_global.php";

//--------------------------------
// The clocks a' tickin'
//--------------------------------
		
$Debug = new Debug;
$Debug->startTimer();

//--------------------------------
// Require our global functions
//--------------------------------

require ROOT_PATH."sources/functions.php";

$std   = new FUNC;
$print = new display();
$sess  = new session();

//--------------------------------
// Load the DB driver and such
//--------------------------------

$INFO['sql_driver'] = !$INFO['sql_driver'] ? 'mySQL' : $INFO['sql_driver'];

$to_require = ROOT_PATH."sources/Drivers/".$INFO['sql_driver'].".php";
require ($to_require);

$DB = new db_driver;

$DB->obj['sql_database']     = $INFO['sql_database'];
$DB->obj['sql_user']         = $INFO['sql_user'];
$DB->obj['sql_pass']         = $INFO['sql_pass'];
$DB->obj['sql_host']         = $INFO['sql_host'];
$DB->obj['sql_tbl_prefix']   = $INFO['sql_tbl_prefix'];

$DB->obj['debug']            = ($INFO['sql_debug'] == 1) ? $_GET['debug'] : 0;

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

//--------------------------------
//  Short tags...
//--------------------------------

if ( $ibforums->input['showforum'] != "" )
{
	$ibforums->input['act'] = "SF";
	$ibforums->input['f']   = intval($ibforums->input['showforum']);
}
else if ( $ibforums->input['showtopic'] != "")
{
	$ibforums->input['act'] = "ST";
	$ibforums->input['t']   = intval($ibforums->input['showtopic']);
	
	// Grab and cache the topic now as we need the 'f' attr for
	// the skins...
	
	$DB->query("SELECT t.*, f.topic_mm_id, f.name as forum_name, f.quick_reply, f.id as forum_id, f.read_perms, f.reply_perms, f.parent_id, f.use_html,
                       f.start_perms, f.allow_poll, f.password, f.posts as forum_posts, f.topics as forum_topics, f.upload_perms,
                       f.show_rules, f.rules_text, f.rules_title,
                       c.name as cat_name, c.id as cat_id
                       FROM ibf_topics t, ibf_forums f , ibf_categories c
                       WHERE t.tid=".$ibforums->input['t']." and f.id = t.forum_id and f.category=c.id");
                       
    $ibforums->topic_cache = $DB->fetch_row();
    $ibforums->input['f']  = $ibforums->topic_cache['forum_id'];
}
else if ( $ibforums->input['showuser'] != "")
{
	$ibforums->input['act'] = "Profile";
	$ibforums->input['MID'] = intval($ibforums->input['showuser']);
}
else
{
	$ibforums->input['act'] = $ibforums->input['act'] == '' ? "idx" : $ibforums->input['act'];
}

//--------------------------------
//  The rest :D
//--------------------------------

$ibforums->member     = $sess->authorise();
$ibforums->skin       = $std->load_skin();
$ibforums->lastclick  = $sess->last_click;
$ibforums->location   = $sess->location;
$ibforums->session_id = $sess->session_id;

list($ppu,$tpu) = explode( "&", $ibforums->member['view_prefs'] );
		
$ibforums->vars['display_max_topics'] = ($tpu > 0) ? $tpu : $ibforums->vars['display_max_topics'];
$ibforums->vars['display_max_posts']  = ($ppu > 0) ? $ppu : $ibforums->vars['display_max_posts'];

//--------------------------------
//  Set up the session ID stuff
//--------------------------------

if ( $ibforums->session_type == 'cookie' )
{
	$ibforums->session_id = "";
	$ibforums->base_url   = $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?';
}
else
{
	$ibforums->base_url = $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?s='.$ibforums->session_id.'&amp;';
}

$ibforums->js_base_url = $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?s='.$ibforums->session_id.'&';

//--------------------------------
//  Set up the forum_read cookie
//--------------------------------

$std->hdl_forum_read_cookie();

//--------------------------------
//  Set up the skin stuff
//--------------------------------

$ibforums->skin_rid   = $ibforums->skin['set_id'];
$ibforums->skin_id    = 's'.$ibforums->skin['set_id'];

$ibforums->vars['img_url']   = 'style_images/' . $ibforums->skin['img_dir'];

//--------------------------------
//  Set up our language choice
//--------------------------------

if ($ibforums->vars['default_language'] == "")
{
	$ibforums->vars['default_language'] = 'en';
}

$ibforums->lang_id = $ibforums->member['language'] ? $ibforums->member['language'] : $ibforums->vars['default_language'];

if ( ($ibforums->lang_id != $ibforums->vars['default_language']) and (! is_dir( ROOT_PATH."lang/".$ibforums->lang_id ) ) )
{
	$ibforums->lang_id = $ibforums->vars['default_language'];
}
		
$ibforums->lang = $std->load_words($ibforums->lang, 'lang_global', $ibforums->lang_id);

//--------------------------------

$skin_universal = $std->load_template('skin_global');

//--------------------------------
//  Expire subscription?
//--------------------------------

if ( $ibforums->member['sub_end'] != 0 AND ( $ibforums->member['sub_end'] < time() ) )
{
	$std->expire_subscription();
}

//--------------------------------

if ($ibforums->input['act'] != 'Login' and $ibforums->input['act'] != 'Reg' and $ibforums->input['act'] != 'Attach' and $ibforums->input['module'] != 'subscription')
{

	//--------------------------------
	//  Do we have permission to view
	//  the board?
	//--------------------------------
	
	if ($ibforums->member['g_view_board'] != 1)
	{
		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_board') );
	}
	
	//--------------------------------
	//  Is the board offline?
	//--------------------------------
	
	if ($ibforums->vars['board_offline'] == 1)
	{
		if ($ibforums->member['g_access_offline'] != 1)
		{
			$std->board_offline();
		}
		
	}
	
	//--------------------------------
	//  Is log in enforced?
	//--------------------------------
	
	if ( (! $ibforums->member['id']) and ($ibforums->vars['force_login'] == 1) )
	{
		require ROOT_PATH."sources/Login.php";
		
	}

}

//--------------------------------
// Decide what to do
//--------------------------------

$choice = array(
                 "idx"      => "Boards",
                 "SC"       => "Boards",
                 "SF"       => "Forums",
                 "SR"       => "Forums",
                 "ST"       => "Topics",
                 "Login"    => "Login",
                 "Post"     => "Post",
                 "Poll"     => "lib/add_poll",
                 "Reg"      => "Register",
                 "Online"   => "Online",
                 "Members"  => "Memberlist",
                 "Help"     => "Help",
                 "Search"   => "Search",
                 "Mod"      => "Moderate",
                 "Print"    => "misc/print_page",
                 "Forward"  => "misc/forward_page",
                 "Mail"     => "misc/contact_member",
                 "Invite"   => "misc/contact_member",
                 "ICQ"      => "misc/contact_member",
                 "AOL"      => "misc/contact_member",
                 "YAHOO"    => "misc/contact_member",
                 "MSN"      => "misc/contact_member",
                 "report"   => "misc/contact_member",
                 "chat"     => "misc/contact_member",
                 "integ"    => "misc/contact_member",
                 "Msg"      => "Messenger",
                 "UserCP"   => "Usercp",
                 "Profile"  => "Profile",
                 "Track"    => "misc/tracker",
                 "Stats"    => "misc/stats",
                 "Attach"   => "misc/attach",
                 'legends'  => 'misc/legends',
                 'modcp'    => 'mod_cp',
                 'calendar' => "calendar",
                 'buddy'    => "browsebuddy",
                 'boardrules' => "misc/contact_member",
                 'mmod'     => "misc/multi_moderate",
                 'warn'     => "misc/warn",
                 'home'     => 'dynamiclite/csite',
                 'module'   => 'modules',
               );

                
/***************************************************/
//

// Check to make sure the array key exits..

if (! isset($choice[ $ibforums->input['act'] ]) )
{
	$ibforums->input['act'] = 'idx';
}

if ( $ibforums->input['act'] == 'home' )
{
	if ( $ibforums->vars['csite_on'] )
	{
		require ROOT_PATH."sources/dynamiclite/csite.php";
		$csite = new click_site();
	}
	else
	{
		require ROOT_PATH."sources/Boards.php";
	}
}
else if ( $ibforums->input['act'] == 'module' )
{
	if ( USE_MODULES == 1 )
	{
		require ROOT_PATH."modules/module_loader.php";
		$loader = new module_loader();
	}
	else
	{
		require ROOT_PATH."sources/Boards.php";
	}
}
else
{
	// Require and run
	require ROOT_PATH."sources/".$choice[ $ibforums->input['act'] ].".php";
}

//+-------------------------------------------------
// GLOBAL ROUTINES
//+-------------------------------------------------

function fatal_error($message="", $help="") {
	echo("$message<br><br>$help");
	exit;
}
?>
