<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.2 Module File
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > Hive Mail Module
|   > Module written by Matt Mecham
|   > Date started: 8th July 2003
|
+--------------------------------------------------------------------------
*/

//=====================================
// Define class, this must be the same
// in all modules
//=====================================

class module extends module_loader
{

	var $class  = "";
	var $module = "";
	var $html   = "";
	
	var $result = "";
	var $hive   = "";
	var $hiveid = "";
	
	function module()
	{
		global $ibforums, $DB, $std, $print;
		
		//-------------------------------------------
		// Load skin / lang stuff
		//-------------------------------------------
			
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_register', $ibforums->lang_id);
        $this->html     = $std->load_template('skin_register');
        
        //-------------------------------------------
		// Are we a hivemail member?
		//-------------------------------------------
		
        $DB->query("SELECT id, hiveuserid FROM ibf_member_extra WHERE id={$ibforums->member['id']}");
			
		$r = $DB->fetch_row();
			
		$this->hiveid = intval($r['hiveuserid']);
			
        //-------------------------------------------
		// Load Hivemail plug in
		//-------------------------------------------
		
        define('IPB_PLUGIN', true);
		require_once('../mail/includes/invision_plugin.php');
			
		$this->hive = new invision_plugin();
		
		//-------------------------------------------
		// If we're not a member...
		//-------------------------------------------
		
		if ( ! $ibforums->member['id'] )
		{
			$std->boink_it( $ibforums->base_url );
		}
		
		//-------------------------------------------
		// Do we already have a hivemail membership?
		//-------------------------------------------
		
		if ( $this->hiveid AND $hiveuser = $this->hive->DB_Hive->query_first("SELECT userid FROM ".HIVE_TBL."user WHERE userid = {$this->hiveid}"))
		{
			$this->hive->destructor();
			$std->boink_it( $ibforums->base_url );
		}
		
		//-------------------------------------------
		// Whatcha doin'? Yes - you?
		//-------------------------------------------
		
		switch( $ibforums->input['show'] )
		{
			case 'forumform':
				$this->show_forumform();
				break;
			case 'addmail':
				$this->add_mail();
				
			default:
				$this->show_forumform();
				break;
		}
		
	}
	
	function add_mail()
	{
		global $ibforums, $DB, $std, $print;
		
		$hive_username = trim($ibforums->input['hive_username']);
		
		//------------------------------------------
		// Check for errors
		//------------------------------------------
		
		if ( $hive_username == "" )
		{
			$this->show_forumform('js_blanks');
		}

		$this->hive->hivemail_register_user($ibforums->member, false);
			
		if ( $this->hive->hive_error != "" )
		{
			$ibforums->lang['thing_name_taken']     = sprintf( $ibforums->lang['thing_name_taken']    , 'Board Email Address' );
			$ibforums->lang['incorrect_thing_name'] = sprintf( $ibforums->lang['incorrect_thing_name'], 'Board Email Address' );
	
			$this->show_forumform($this->hive->hive_error);
			$this->error = 1;
			$this->hive->destructor();
			return;
		}
		
		$this->hive->destructor();
		
		$print->redirect_screen("your email account has been created, forwarding you to the email log-in page", "/mail/index.php", 1);
		
	}
	
	//------------------------------------------
	// Show Forum Form
	// 
	// Show the already reg'd member's mail form
	//
	//------------------------------------------
	
	function show_forumform($errors="")
	{
		global $ibforums, $DB, $std, $print;
		
		$this->page_title = "Sign up for your email account";
		
		$hidden = "";
		$action = $ibforums->base_url.'act=module&amp;module=hivemail&amp;show=addmail';
		
		$content = $this->html->field_entry('Account Name:',
		                                    'The password for your email account will be the same as your forum account.<br />Letters and numbers only please.',
		                                    $this->html->field_textinput('hive_username', $ibforums->input['hive_username'])."&nbsp;<select name='hive_userdomain' class='forminput'>".$this->hive->hive_domainname_options."</select>"
		                                   );
		                                   
		if ($errors != "")
    	{
    		$this->output .= $this->html->errors( $ibforums->lang[$errors] );
    	}
		                                   
		$this->output .= $this->html->tmpl_form($action, $hidden, $this->page_title, $content );
		
		$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
		
	}
	
	
}


?>