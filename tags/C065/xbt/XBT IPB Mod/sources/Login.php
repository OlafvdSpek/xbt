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
|   > Log in / log out module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Login;

class Login {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $login_html = "";
    var $modules    = "";
    
    function Login()
    {
    	global $ibforums, $DB, $std, $print;
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_login', $ibforums->lang_id);
    	
    	$this->login_html = $std->load_template('skin_login');
    	
    	
    	if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
		}

    	
    	// Are we enforcing log ins?
    	
    	if ($ibforums->vars['force_login'] == 1)
    	{
    		$msg = 'admin_force_log_in';
    	}
    	else
    	{
    		$msg = "";
    	}
    	
    	// What to do?
    	
    	switch($ibforums->input['CODE']) {
    		case '01':
    			$this->do_log_in();
    			break;
    		case '02':
    			$this->log_in_form();
    			break;
    		case '03':
    			$this->do_log_out();
    			break;
    			
    		case '04':
    			$this->markforum();
    			break;
    			
    		case '05':
    			$this->markboard();
    			break;
    			
    		case '06':
    			$this->delete_cookies();
    			break;
    			
    		case 'autologin':
    			$this->auto_login();
    			break;
    			
    		default:
    			$this->log_in_form($msg);
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
    		
 	}
 	
 	function auto_login()
 	{
 		global $ibforums, $DB, $std, $print, $sess;
 		
 		// Universal routine.
 		// If we have cookies / session created, simply return to the index screen
 		// If not, return to the log in form
 		
 		$ibforums->member = $sess->authorise();
 		
 		// If there isn't a member ID set, do a quick check ourselves.
 		// It's not that important to do the full session check as it'll
 		// occur when they next click a link.
 		
 		if ( ! $ibforums->member['id'] )
 		{
			$mid = intval($std->my_getcookie('member_id'));
			$pid = $std->my_getcookie('pass_hash');
			
			If ($mid and $pid)
			{
				$DB->query("SELECT * FROM ibf_members WHERE id=$mid AND password='$pid'");
				
				if ( $member = $DB->fetch_row() )
				{
					$ibforums->member = $member;
					$ibforums->session_id = "";
					$std->my_setcookie('session_id', '0', -1 );
				}
			}
 		}
 		
 		$true_words  = $ibforums->lang['logged_in'];
 		$false_words = $ibforums->lang['not_logged_in'];
 		$method = 'no_show';
 		
 		if ($ibforums->input['fromreg'] == 1)
 		{
 			$true_words  = $ibforums->lang['reg_log_in'];
 			$false_words = $ibforums->lang['reg_not_log_in'];
 			$method = 'show';
 		}
 		else if ($ibforums->input['fromemail'] == 1)
 		{
 			$true_words  = $ibforums->lang['email_log_in'];
 			$false_words = $ibforums->lang['email_not_log_in'];
 			$method = 'show';
 		}
 		else if ($ibforums->input['frompass'] == 1)
 		{
 			$true_words  = $ibforums->lang['pass_log_in'];
 			$false_words = $ibforums->lang['pass_not_log_in'];
 			$method = 'show';
 		}
 		
 		if ($ibforums->member['id'])
 		{
 			if ($method == 'show')
 			{
 				$print->redirect_screen( $true_words, "" );
 			}
 			else
 			{
 				$std->boink_it($ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext']);
 			}
 		}
 		else
 		{
 			if ($method == 'show')
 			{
 				$print->redirect_screen( $false_words, 'act=Login&CODE=00' );
 			}
 			else
 			{
 				$std->boink_it($ibforums->base_url.'&act=Login&CODE=00');
 			}
 		}
 		
 		
 	}
 	
 	
 	
 	function delete_cookies()
 	{
 		global $ibforums, $DB, $std, $HTTP_COOKIE_VARS;
 		
 		if (is_array($HTTP_COOKIE_VARS))
 		{
 			foreach( $HTTP_COOKIE_VARS as $cookie => $value)
 			{
 				if (preg_match( "/^(".$ibforums->vars['cookie_id']."ibforum.*$)/i", $cookie, $match))
 				{
 					$std->my_setcookie( str_replace( $ibforums->vars['cookie_id'], "", $match[0] ) , '-', -1 );
 				}
 			}
 		}
 		
 		$std->my_setcookie('pass_hash' , '-1');
 		$std->my_setcookie('member_id' , '-1');
 		$std->my_setcookie('session_id', '-1');
 		$std->my_setcookie('topicsread', '-1');
 		$std->my_setcookie('anonlogin' , '-1');
 		$std->my_setcookie('forum_read', '-1');
 		
		$std->boink_it($ibforums->base_url);
		exit();
	}  
	
 	
 	function markboard()
 	{
 		global $ibforums, $DB, $std;
 		
 		if(! $ibforums->member['id'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_guests') );
		}
		
		$DB->query("UPDATE ibf_members SET last_visit='".time()."', last_activity='".time()."' WHERE id='".$ibforums->member['id']."'");
		
		$std->boink_it($ibforums->base_url);
		exit();
	}  
    
    
    function markforum()
    {
        global $ibforums, $DB, $std;
        
        $ibforums->input['f'] = intval($ibforums->input['f']);
        
        if ($ibforums->input['f'] == "")
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files' ) );
        }
        
        $DB->query("SELECT id, name, subwrap, parent_id FROM ibf_forums WHERE id=".$ibforums->input['f']);
        
        if ( ! $f = $DB->fetch_row() )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files' ) );
        }
        
        //--------------------------------------	
        // Did we come in via the index?
        //--------------------------------------
        
        if ( $ibforums->input['i'] == 1 )
        {	
        	//--------------------------------------
        	// It's also a subforum, so grab all
        	// meh kiddies. "meh kiddies"? That's Rikki's
        	// bad influence. o_O
        	//--------------------------------------
        	
        	$DB->query("SELECT id FROM ibf_forums WHERE parent_id={$ibforums->input['f']}");
        	
        	while ( $r = $DB->fetch_row() )
        	{
        		$ibforums->forum_read[ $r['id'] ] = time();
        	}
        }
        
        //--------------------------------------	
        // Reset cookie (yum)
        //--------------------------------------
        
        $ibforums->forum_read[ $ibforums->input['f'] ] = time();
			
		$std->hdl_forum_read_cookie('set');
		
		//--------------------------------------	
        // Are we getting kicked back to the root forum (if sub forum) or index?
        //--------------------------------------
        
        if ( ($f['parent_id'] > 0) AND ($ibforums->input['i'] != 1) )
        {
        	//--------------------------------------
        	// Its a sub forum, lets go redirect to parent forum
        	//--------------------------------------
        	
        	$std->boink_it($ibforums->base_url."act=SF&f=".$f['parent_id']);
        }
        else
        {
        	$std->boink_it($ibforums->base_url);
        }
        exit();
        
    }
    
    
    
    
    function log_in_form($message="")
    {
        global $ibforums, $DB, $std, $print, $HTTP_REFERER;
        
        //+--------------------------------------------
		//| Are they banned?
		//+--------------------------------------------
		
		if ($ibforums->vars['ban_ip'])
		{
			$ips = explode( "|", $ibforums->vars['ban_ip'] );
			foreach ($ips as $ip)
			{
				$ip = preg_replace( "/\*/", '.*' , $ip );
				if (preg_match( "/$ip/", $ibforums->input['IP_ADDRESS'] ))
				{
					$std->Error( array( LEVEL => 1, MSG => 'you_are_banned' ) );
				}
			}
		}
        
        //+--------------------------------------------
        
        if ($message != "")
        {
        	$message = $ibforums->lang[ $message ];
        	$message = preg_replace( "/<#NAME#>/", "<b>{$ibforums->input[UserName]}</b>", $message );
        
			$this->output .= $this->login_html->errors($message);
		}
		
		$this->output .= $this->login_html->ShowForm( $ibforums->lang['please_log_in'], $HTTP_REFERER );
		
		$this->nav        = array( $ibforums->lang['log_in'] );
	 	$this->page_title = $ibforums->lang['log_in'];
		
		$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        
        exit();
        
    }
    
    //+--------------------------------------------
    
    function do_log_in() {
    	global $DB, $ibforums, $std, $print, $sess, $HTTP_USER_AGENT, $HTTP_POST_VARS;
    	
    	$url = "";
    	
    	//-------------------------------------------------
		// More unicode..
		//-------------------------------------------------
		
		$len_u = $std->txt_stripslashes($HTTP_POST_VARS['UserName']);
		
		$len_u = preg_replace("/&#([0-9]+);/", "-", $len_u );
		
		$len_p = $std->txt_stripslashes($HTTP_POST_VARS['PassWord']);
		
		$len_p = preg_replace("/&#([0-9]+);/", "-", $len_p );
		
    	//-------------------------------------------------
    	// Make sure the username and password were entered
    	//-------------------------------------------------
    	
    	if ($HTTP_POST_VARS['UserName'] == "")
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
    	}
    
     	if ($HTTP_POST_VARS['PassWord'] == "")
     	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_blank' ) );
    	}   

		
		//-------------------------------------------------
		// Check for input length
		//-------------------------------------------------
		
		if (strlen($len_u) > 32)
		{
			$std->Error( array( LEVEL => 1, MSG => 'username_long' ) );
		}
		
		if (strlen($len_p) > 32)
		{
			$std->Error( array( LEVEL => 1, MSG => 'pass_too_long' ) );
		}
		
		$username    = strtolower(str_replace( '|', '&#124;', $ibforums->input['UserName']) );
		$password    = md5( $ibforums->input['PassWord'] );
		
		//-------------------------------------------------
		// Attempt to get the user details
		//-------------------------------------------------
		
		$DB->query("SELECT id, name, mgroup, password, ip_address FROM ibf_members WHERE LOWER(name)='$username'");
		
		if ( $DB->get_num_rows() )
		{
			$member = $DB->fetch_row();
			
			if ( empty($member['id']) or ($member['id'] == "") )
			{
				$this->log_in_form( 'wrong_name' );
			}
			
			if ($member['password'] != $password)
			{
				$this->log_in_form( 'wrong_pass' );
			}
			
			//------------------------------
			
			if ($ibforums->input['CookieDate'])
			{
				$std->my_setcookie("member_id"   , $member['id'], 1);
				$std->my_setcookie("pass_hash"   , $password, 1);
			}
			
			//------------------------------
			// Update profile if IP addr missing
			//------------------------------
			
			if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
			{
				$DB->query("UPDATE ibf_members SET ip_address='{$ibforums->input['IP_ADDRESS']}' WHERE id={$member['id']}");
			}
			
			//------------------------------
			// Create / Update session
			//------------------------------
			
			$poss_session_id = "";
			
			if ( $cookie_id = $std->my_getcookie('session_id') )
			{
				$poss_session_id = $std->my_getcookie('session_id');
			}
			else if ( $ibforums->input['s'] )
			{
				$poss_session_id = $ibforums->input['s'];
			}
			
			if ($poss_session_id)
			{
				$session_id = $poss_session_id;
				
				// Delete any old sessions with this users IP addy that doesn't match our
				// session ID.
				
				$DB->query("DELETE FROM ibf_sessions WHERE ip_address='".$ibforums->input['IP_ADDRESS']."' AND id <> '$session_id'");
				
				$db_string = $DB->compile_db_update_string( array (
																	 'member_name'  => $member['name'],
																	 'member_id'    => $member['id'],
																	 'running_time' => time(),
																	 'member_group' => $member['mgroup'],
																	 'login_type'   => $ibforums->input['Privacy'] ? 1 : 0
														  )       );
														  
				$db_query = "UPDATE ibf_sessions SET $db_string WHERE id='".$session_id."'";
			}
			else
			{
				$session_id = md5( uniqid(microtime()) );
				
				// Delete any old sessions with this users IP addy.
				
				$DB->query("DELETE FROM ibf_sessions WHERE ip_address='".$ibforums->input['IP_ADDRESS']."'");
				
				$db_string = $DB->compile_db_insert_string( array (
																	 'id'           => $session_id,
																	 'member_name'  => $member['name'],
																	 'member_id'    => $member['id'],
																	 'running_time' => time(),
																	 'member_group' => $member['mgroup'],
																	 'ip_address'   => substr($ibforums->input['IP_ADDRESS'], 0, 50),
																	 'browser'      => substr($std->clean_value($HTTP_USER_AGENT), 0, 50),
																	 'login_type'   => $ibforums->input['Privacy'] ? 1 : 0
														  )       );
														 
				$db_query = "INSERT INTO ibf_sessions (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")";
			}
			
			$DB->query( $db_query );
			
			$ibforums->member           = $member;
			$ibforums->session_id       = $session_id;
			
			if ($ibforums->input['referer'] && ($ibforums->input['act'] != 'Reg'))
			{
				$url = $ibforums->input['referer'];
				$url = str_replace( "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}", "", $url );
				$url = preg_replace( "!^\?!"       , ""   , $url );
				$url = preg_replace( "!s=(\w){32}!", ""   , $url );
				$url = preg_replace( "!act=(login|reg|lostpass)!i", "", $url );
			}
			
			//-----------------------------------
			// set our privacy cookie
			//-----------------------------------
			
			if ($ibforums->input['Privacy'] == 1)
			{
				$std->my_setcookie( "anonlogin", 1 );
			}
			
			//-----------------------------------
			// Clear out any passy change stuff
			//-----------------------------------
			
			$DB->query("DELETE FROM ibf_validating WHERE member_id={$ibforums->member['id']} AND lost_pass=1");
			
			//-----------------------------------
			// Redirect them to either the board
			// index, or where they came from
			//-----------------------------------
			
			$std->my_setcookie("session_id", $ibforums->session_id, -1);
			
			$this->logged_in = 1;
			
			if ( USE_MODULES == 1 )
			{
				$this->modules->register_class(&$this);
				$this->modules->on_login($member);
			}
			
			if ( $ibforums->input['return'] != "" )
			{
				$return = urldecode($ibforums->input['return']);
				
				if ( preg_match( "#^http://#", $return ) )
				{
					$std->boink_it($return);
				}
			}
			
			$print->redirect_screen( "{$ibforums->lang[thanks_for_login]} {$ibforums->member['name']}", $url );
			
			
		}
		else
		{
			$this->logged_in = 0;
			
			if ( USE_MODULES == 1 )
			{
				$this->modules->register_class(&$this);
				$this->modules->on_login($member);
			}
			
			$this->log_in_form( 'wrong_name' );
		}
		
	}
	
	
	
	
	

	function do_log_out()
	{
		global $std, $ibforums, $DB, $print, $sess, $HTTP_COOKIE_VARS;
		
		// Update the DB
		
		$DB->query("UPDATE ibf_sessions SET ".
				     "member_name='',".
				     "member_id='0',".
				     "login_type='0' ".
				     "WHERE id='". $sess->session_id ."'");
				     
		$DB->query("UPDATE ibf_members SET last_visit='".time()."', last_activity='".time()."' WHERE id='".$ibforums->member['id']."'");
				     
		// Set some cookies
		
		$std->my_setcookie( "member_id" , "0"  );
		$std->my_setcookie( "pass_hash" , "0"  );
		$std->my_setcookie( "anonlogin" , "-1" );
		
		if (is_array($HTTP_COOKIE_VARS))
 		{
 			foreach( $HTTP_COOKIE_VARS as $cookie => $value )
 			{
 				if (preg_match( "/^(".$ibforums->vars['cookie_id']."ibforum.*$)/i", $cookie, $match))
 				{
 					if ( strlen( $match[1] ) > 0 )
 					{
 						$std->my_setcookie( str_replace( $ibforums->vars['cookie_id'], "", $match[1] ) , '-', -1 );
 					}
 				}
 			}
 		}
		
		// Redirect...
		
		$url = "";
		
		if ( $ibforums->input['return'] != "" )
		{
			$return = urldecode($ibforums->input['return']);
			
			if ( preg_match( "#^http://#", $return ) )
			{
				$std->boink_it($return);
			}
		}
		
		$print->redirect_screen( $ibforums->lang['thanks_for_logout'], "" );
		
	}




        
}

?>
