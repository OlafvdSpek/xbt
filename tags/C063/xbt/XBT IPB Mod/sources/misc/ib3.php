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
|   > Date started: 12th December 2001
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
    
    function Login() {
    	global $ibforums, $DB, $std, $print;
    	
    	// Make sure our code number is numerical only
    	
    	//$ibforums->input[CODE] = preg_replace("/^([0-9]+)$/", "$1", $ibforums->input[CODE]);
    	
    	// Require the HTML and language modules
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_login', $ibforums->lang_id);
    	
    	$this->login_html = $std->load_template('skin_login');
    	
    	// What to do?
    	
    	switch($ibforums->input[CODE]) {
    		case '01':
    			$this->do_log_in();
    			break;
    		case '02':
    			$this->log_in_form();
    			break;
    		case '03':
    			$this->do_log_out();
    			break;
    		default:
    			$this->log_in_form();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
    		
 	}   
    
    
    
    
    
    function log_in_form($message="") {
        global $ibforums, $DB, $std, $print, $HTTP_REFERER;
        
        //+--------------------------------------------
		//| Are they banned?
		//+--------------------------------------------
		
		if ($message != "")
		{
			$this->output .= $this->login_html->errors( $ibforums->lang[ $message ] );
		}
		
$html = <<<EOF

    <script language='JavaScript'>
    <!--
    function ValidateForm() {
        var Check = 0;
        if (document.LOGIN.UserName.value == '') { Check = 1; }
        if (document.LOGIN.PassWord.value == '') { Check = 1; }

        if (Check == 1) {
            alert("{$ibforums->lang[blank_fields]}");
            return false;
        } else {
            document.LOGIN.submit.disabled = true;
            return true;
        }
    }
    //-->
    </script>     
     <br>
     <table cellpadding='3' cellspacing='1' border='0' align='center' width='{$ibforums->skin['tbl_width']}'>
     <tr>
     <td align='left'>{$ibforums->lang['login_text']}</td>
     </tr>
     <tr>
     <td align='left'><b>{$ibforums->lang[forgot_pass]} <a href='{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}?act=Reg&CODE=10'>{$ibforums->lang[pass_link]}</a></b></td>
     </tr>
     </table>
     <form action="{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}" method="post" name='LOGIN' onSubmit='return ValidateForm()'>
     <input type='hidden' name='act' value='ib3'>
     <input type='hidden' name='CODE' value='01'>
     <input type='hidden' name='s' value='{$ibforums->session_id}'>
     <input type='hidden' name='referer' value="">
     <table cellpadding='0' cellspacing='0' border='0' width='{$ibforums->skin['tbl_width']}' bgcolor='{$ibforums->skin['tbl_border']}' align='center'>
        <tr>
            <td>
                <table cellpadding='3' cellspacing='1' border='0' width='100%'>
                <tr>
                <td align='left' colspan='2' id='titlemedium'>Please enter your old Ikonboard 3 Username and Password</td>
                </tr>
                <tr>
                <td id='row1' width='40%'>Your Ikonboard 3 Username</td>
                <td id='row1'><input type='text' size='20' maxlength='64' name='UserName' class='forminput'></td>
                </tr>
                <tr>
                <td id='row1' width='40%'>Your Ikonboard 3 Password</td>
                <td id='row1'><input type='password' size='20' name='PassWord' class='forminput'></td>
                </tr>
                </table>
             </td>
         </tr>
     </table>
     <br>
     <table cellpadding='0' cellspacing='0' border='0' width='{$ibforums->skin['tbl_width']}' bgcolor='{$ibforums->skin['tbl_border']}' align='center'>
        <tr>
            <td>
                <table cellpadding='3' cellspacing='1' border='0' width='100%'>
                <tr>
                <td align='left' colspan='2' id='titlemedium'>{$ibforums->lang[options]}</td>
                </tr>
                <tr>
                <td id='row1' width='40%' align='left' valign='top'>{$ibforums->lang[cookies]}</td>
                <td id='row1' width='40%'><input type="radio" name="CookieDate" value="1" checked>{$ibforums->lang[cookie_yes]}<br><input type="radio" name="CookieDate" value="0">{$ibforums->lang[cookie_no]}</td>
                </tr>
                <tr>
                <td id='row1' width='40%' align='left' valign='top'>{$ibforums->lang[privacy]}</td>
                <td id='row1' width='40%'><input type="checkbox" name="Privacy" value="1">{$ibforums->lang[anon_name]}</td>
                </tr>
                <tr>
                <td id='row2' align='center' colspan='2'>
                <input type="submit" name='submit' value="{$ibforums->lang[log_in_submit]}" class='forminput'>
                </td></tr></table>
                </td></tr></table>
                </form>


EOF;
		
		$this->output .= $html;
		
		$this->nav        = array( "Upgrade my old Ikonboard Account" );
	 	$this->page_title = "Upgrade my old Ikonboard Account";
		
		$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        
        exit();
        
    }
    
    function do_log_in() {
    	global $DB, $ibforums, $std, $print, $sess, $HTTP_USER_AGENT, $HTTP_POST_VARS;
    	
    	$url = "";
    	
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
		
		if (strlen($ibforums->input['UserName']) > 32)
		{
			$std->Error( array( LEVEL => 1, MSG => 'username_long' ) );
		}
		
		if (strlen($ibforums->input['PassWord']) > 32)
		{
			$std->Error( array( LEVEL => 1, MSG => 'pass_too_long' ) );
		}
		
		$username    = strtolower($ibforums->input['UserName']);
		$password    = crypt( $ibforums->input['PassWord'], substr( strtolower($ibforums->input['UserName']), 0, 2) );
		
		//-------------------------------------------------
		// Attempt to get the user details
		//-------------------------------------------------
		
		$DB->query("SELECT id, name, mgroup, password, new_pass FROM ibf_members WHERE LOWER(name)='$username'");
		
		if ($DB->get_num_rows())
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
			
			// SET REAL PASSY
			
			$real_pass = md5($ibforums->input['PassWord']);
			
			//------------------------------
			
			if ($ibforums->input['s'])
			{
				$session_id = $ibforums->input['s'];
				
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
														  
				$db_query = "UPDATE ibf_sessions SET $db_string WHERE id='".$ibforums->input['s']."'";
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
																	 'browser'      => substr($HTTP_USER_AGENT, 0, 50),
																	 'login_type'   => $ibforums->input['Privacy'] ? 1 : 0
														  )       );
														 
				$db_query = "INSERT INTO ibf_sessions (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")";
			}
			
			$DB->query( $db_query );
			
			//-----------------------------------
			// RESET PASS IN MD5
			//-----------------------------------
			
			$DB->query("UPDATE ibf_members SET password='$real_pass' WHERE id='".$member['id']."'");
			
			$ibforums->member           = $member;
			$ibforums->session_id       = $session_id;
			
			//------------------------------
			
			if ($ibforums->input['CookieDate'])
			{
				$std->my_setcookie("pass_hash"   , $real_pass, 1);
				$std->my_setcookie("member_id"   , $member['id'], 1);
			}
			
			//-----------------------------------
			// set our privacy cookie
			//-----------------------------------
			
			if ($ibforums->input[Privacy] == 1)
			{
				$std->my_setcookie( "anonlogin", 1 );
			}
			
			//-----------------------------------
			// Redirect them to either the board
			// index, or where they came from
			//-----------------------------------
			
			$print->redirect_screen( "{$ibforums->lang[thanks_for_login]} {$ibforums->member['name']}", $url );
			
			
		}
		else
		{
			$this->log_in_form( 'wrong_name' );
		}
		
	}
	
	
	
	
	

	function do_log_out() {
		global $std, $ibforums, $DB, $print;
		
		if(! $ibforums->member['id'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_guests') );
		}
		
		// Update the DB
		
		$DB->query("UPDATE ibf_sessions SET ".
				     "member_name='NULL',".
				     "member_id='0',".
				     "member_pass='NULL',".
				     "login_type='0' ".
				     "WHERE id='". $ibforums->session_id ."'");
				     
		// Set some cookies
		
		$std->my_setcookie( "member_id" , "0"  );
		$std->my_setcookie( "pass_hash" , "0"  );
		$std->my_setcookie( "skin"       , "-1" );
		
		// Redirect...
		
		$print->redirect_screen( $ibforums->lang['thanks_for_logout'], "" );
		
	}




        
}

?>
