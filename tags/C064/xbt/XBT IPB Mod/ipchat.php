<?

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001,2002 Invision Power Services, Inc
|   http://www.ibforums.com
|   ========================================
|   Web: http://www.ibforums.com
|   Email: phpboards@ibforums.com
|   Licence Info: phpib-licence@ibforums.com
+---------------------------------------------------------------------------
|
|   > IP Chat => IPB Bridge Script
|   > Script written by Matt Mecham
|   > Date started: 17th February 2003
|
+--------------------------------------------------------------------------
*/

$root_path = './';

//----------------------------------------------
// END OF USER EDITABLE COMPONENTS
//---------------------------------------------

require $root_path."conf_global.php";

define( 'DENIED', 0 );
define( 'ACCESS', 1 );
define( 'ADMIN' , 2 );

$db_info = array();

$db_info['host']       = $INFO['sql_host'];
$db_info['user']       = $INFO['sql_user'];
$db_info['pass']       = $INFO['sql_pass'];
$db_info['database']   = $INFO['sql_database'];
$db_info['tbl_prefix'] = $INFO['sql_tbl_prefix'];

$allowed_groups        = $INFO['chat_admin_groups'];
$access_groups         = $INFO['chat_access_groups'];
$autologin             = 0;
$allow_guest_access    = $INFO['chat_allow_guest'] == 1 ? ACCESS : DENIED;

// Stupid PHP changing it's mind on HTTP args

$username  = $_GET['username']  != "" ? $_GET['username'] : $HTTP_GET_VARS['username'];
$password  = $_GET['password']  != "" ? $_GET['password'] : $HTTP_GET_VARS['password'];
$ip        = $_GET['ip']        != "" ? $_GET['ip']       : $HTTP_GET_VARS['ip'];

//----------------------------------------------
// Test for autologin.
//----------------------------------------------

if ( preg_match( "/^(?:[0-9a-z]){32}$/", $password ) )
{
	$autologin = 1;
}


// Remove URL encoding (%20, etc)

$username = clean_value(urldecode(trim($username)));
$password = clean_value(urldecode(trim($password)));
$ip       = clean_value(urldecode(trim($ip)));

//----------------------------------------------
// Main code
//----------------------------------------------

// Start off with the lowest accessibility

$output_int  = $allow_guest_access;
$output_name = "";



$DB = @mysql_connect( $db_info['host'], $db_info['user'], $db_info['pass'] );

if ( ! @mysql_select_db( $db_info['database'] ) )
{
	die_nice();
	
	//-- script exits --//
}

//------------------------------
// Attempt to find the user
//------------------------------

if ( ! $autologin )
{
	$md5_password = md5($password);
}
else
{
	$md5_password = $password;
}

$query_id = @mysql_query("SELECT m.mgroup, m.password, m.name, m.id  FROM {$db_info['tbl_prefix']}members m
						  WHERE m.name='".addslashes($username)."' LIMIT 1"
						 , $DB );
						
if ( ! $query_id )
{
	die_nice();
	
	//-- script exits --//
}


if ( ! $member = @mysql_fetch_array( $query_id, MYSQL_ASSOC ) )
{
	// No member found - allow guest access?
	
	die_nice($allow_guest_access);
	
	//-- script exits --//
}


@mysql_close();


//------------------------------
// Check password - member exists
//------------------------------

if ( $password != "" )
{
	// Password was entered..
	
	if ( $md5_password != $member['password'] )
	{
		// Password incorrect..
		
		die_nice();
		
		//-- script exits --//
	}
	else
	{
		$output_int = ACCESS;
	}
}
else
{
	// No password entered - die!
	// Do not allow guest access on reg. name
	
	die_nice();
		
	//-- script exits --//
}


//------------------------------
// Do we have any access?
//------------------------------


if ( ! preg_match( "/(^|,)".$member['mgroup']."(,|$)/", $access_groups ) )
{
	die_nice();
}

//------------------------------
// Do we have admin access?
//------------------------------

if ( preg_match( "/(^|,)".$member['mgroup']."(,|$)/", $allowed_groups ) )
{
	$output_int = ADMIN;
}


//------------------------------
// Spill the beans
//------------------------------

print $output_int;

exit();
	 
	 
function die_nice( $access=0 )
{
	// Simply error out silently, showing guest access only for the user
	@mysql_close();
	print $access;
	exit();
}

//------------------------------
// Var cleaner
//------------------------------

function clean_value($val)
{
    global $INFO;
    
	if ($val == "")
	{
		return "";
	}
	
	$val = str_replace( "&#032;", " ", $val );
	
	if ( $INFO['strip_space_chr'] )
	{
		$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
	}
	
	$val = str_replace( "&"            , "&amp;"         , $val );
	$val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
	$val = str_replace( "-->"          , "--&#62;"       , $val );
	$val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
	$val = str_replace( ">"            , "&gt;"          , $val );
	$val = str_replace( "<"            , "&lt;"          , $val );
	$val = str_replace( "\""           , "&quot;"        , $val );
	$val = preg_replace( "/\n/"        , "<br>"          , $val ); // Convert literal newlines
	$val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
	$val = preg_replace( "/\r/"        , ""              , $val ); // Remove literal carriage returns
	$val = str_replace( "!"            , "&#33;"         , $val );
	$val = str_replace( "'"            , "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.
	
	// Ensure unicode chars are OK
	
	$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );
	
	// Strip slashes if not already done so.
	
	if ( get_magic_quotes_gpc() )
	{
		$val = stripslashes($val);
	}
	
	// Swop user inputted backslashes
	
	$val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val ); 
	
	return $val;
}
	 
?>