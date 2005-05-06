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
|   > ICQ / AIM / EMAIL functions
|   > Module written by Matt Mecham
|   > Date started: 28th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Contact;

class Contact {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    
    var $nav       = array();
    var $page_title= "";
    var $email     = "";
    var $forum     = "";
    var $email     = "";

	var $int_error  = "";
	var $int_extra  = "";
	
    /***********************************************************************************/
	//
	// Our constructor, load words, load skin
	//
	/***********************************************************************************/
    
    function Contact() {
    
        global $ibforums, $DB, $std, $print, $skin_universal;
        
        
        // What to do?
        
        switch($ibforums->input['act']) {
        	case 'Mail':
        		$this->mail_member();
        		break;
        	case 'AOL':
        		$this->show_aim();
        		break;
        	case 'integ':
        		$this->show_integ();
        		break;
        	case 'ICQ':
        		$this->show_icq();
        		break;
        	case 'MSN':
        		$this->show_msn();
        		break;
        	case 'YAHOO':
        		$this->show_yahoo();
        		break;
        	case 'Invite':
        		$this->invite_member();
        		break;
        		
        	case 'chat':
        		$this->chat_display();
        		break;
        	
        	case 'report':
        		if ($ibforums->input['send'] != 1)
        		{
        			$this->report_form();
        		}
        		else
        		{
        			$this->send_report();
        		}
        		break;
        		
        	case 'boardrules':
        		$this->board_rules();
        		break;
        	
        	default:
        		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
        		break;
        }
        
        $print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        
	}
	
	//****************************************************************/
	// INTEGRITY MESSENGER
	//
	//****************************************************************/
        
        
	function show_integ()
	{
		global $ibforums, $DB, $std, $print;
		
		//----------------------------------
	
		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
	
		if ( empty($ibforums->input['MID']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		if (! preg_match( "/^(\d+)$/" , $ibforums->input['MID'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, integ_msg from ibf_members WHERE id='".$ibforums->input['MID']."'");

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if (! $member['integ_msg'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_integ' ) );
		}
		
		//----------------------------------
		
		$std->boink_it( "http://www.integritymessenger.com/WebIM/send.php?to=".urlencode($member['integ_msg']) );
		exit();
		
	}
	
	//****************************************************************/
	// BOARD RULES
	//
	//****************************************************************/
        
        
	function board_rules()
	{
		global $ibforums, $DB, $std, $print;
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html     = $std->load_template('skin_emails');
		
		$DB->query("SELECT * FROM ibf_cache_store WHERE cs_key='boardrules'");
		
		$row = $DB->fetch_row();
		
		$row['cs_value'] = $std->my_nl2br(stripslashes($row['cs_value']));
		
		$this->nav[] = $ibforums->vars['gl_title'];
        
        $this->page_title = $ibforums->vars['gl_title'];
        
        $this->output .= $this->html->board_rules( $ibforums->vars['gl_title'],$row['cs_value'] );
		
	}
	
	//****************************************************************/
	// IP CHAT:
	//
	//****************************************************************/
        
        
	function chat_display()
	{
		global $ibforums, $DB, $std, $print;
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html     = $std->load_template('skin_emails');
		
		if ( ! $ibforums->vars['chat_account_no'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		$width  = $ibforums->vars['chat_width']    ? $ibforums->vars['chat_width']  : 600;
		$height = $ibforums->vars['chat_height']   ? $ibforums->vars['chat_height'] : 350;
		
		$lang   = $ibforums->vars['chat_language'] ? $ibforums->vars['chat_language'] : 'en';
		
		$user = "";
		$pass = "";
		
		if ( $ibforums->member['id'] )
		{
			$user = $ibforums->member['name'];
			$pass = $ibforums->member['password'];
		}
		
		if ( $ibforums->input['pop'] )
		{
			$html = $this->html->chat_pop( $ibforums->vars['chat_account_no'], $lang, $width, $height, $user, $pass );
			
			$print->pop_up_window( "CHAT", $html );
			
			exit();
		}
		else
		{
			$this->output .= $this->html->chat_inline( $ibforums->vars['chat_account_no'], $lang, $width, $height, $user, $pass);
		}
		
        $this->nav[] = $ibforums->lang['live_chat'];
        
        $this->page_title = $ibforums->lang['live_chat'];
		
	}
	
	
	
	
	//****************************************************************/
	// REPORT POST FORM:
	//
	//****************************************************************/
        
        
	function report_form()
	{
		global $ibforums, $DB, $std, $print;
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html     = $std->load_template('skin_emails');
		
		$pid = intval($ibforums->input['p']);
		$tid = intval($ibforums->input['t']);
		$fid = intval($ibforums->input['f']);
		$st  = intval($ibforums->input['st']);
		
		if ( (!$pid) and (!$tid) and (!$fid) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		// Do we have permission to do stuff in this forum? Lets hope so eh?!
		
		$this->check_access($fid, $tid);
		
		$this->output .= $this->html->report_form($fid, $tid, $pid, $st, $this->forum['topic_title']);
		
		$this->nav[] = "<a href='".$ibforums->base_url."act=SC&c={$this->forum['cat_id']}'>{$this->forum['cat_name']}</a>";
        $this->nav[] = "<a href='".$ibforums->base_url."act=SF&f={$this->forum['id']}'>{$this->forum['name']}</a>";
        $this->nav[] = $ibforums->lang['report_title'];
        
        $this->page_title = $ibforums->lang['report_title'];
		
	}
	
	
	function send_report()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html     = $std->load_template('skin_emails');
		
		$pid = intval($ibforums->input['p']);
		$tid = intval($ibforums->input['t']);
		$fid = intval($ibforums->input['f']);
		$st  = intval($ibforums->input['st']);
		
		if ( (!$pid) and (!$tid) and (!$fid) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//--------------------------------------------
		// Make sure we came in via a form.
		//--------------------------------------------
		
		if ($HTTP_POST_VARS['message'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form') );
		}
		
		//--------------------------------------------
		// Get the topic title
		//--------------------------------------------
		
		$DB->query("SELECT title FROM ibf_topics WHERE tid='$tid'");
		
		$topic = $DB->fetch_row();
		
		if ( ! $topic['title'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//--------------------------------------------
		// Do we have permission to do stuff in this forum? Lets hope so eh?!
		//--------------------------------------------
		
		$this->check_access($fid, $tid);
		
		$mods = array();
		
		// Check for mods in this forum
		
		$DB->query("SELECT m.name, m.email, mod.member_id FROM ibf_moderators mod, ibf_members m WHERE mod.forum_id='$fid' and mod.member_id=m.id");
		
		if ( $DB->get_num_rows() )
		{
			while( $r = $DB->fetch_row() )
			{
				$mods[] = array(
								 'name'  => $r['name'],
								 'email' => $r['email']
							   );
			}
		}
		else
		{
			//--------------------------------------------
			// No mods? Get those with control panel access
			//--------------------------------------------
			
			$DB->query("SELECT m.id, m.name, m.email FROM ibf_members m, ibf_groups g WHERE g.g_access_cp=1 AND m.mgroup=g.g_id");
			
			while( $r = $DB->fetch_row() )
			{
				$mods[] = array(
								 'name'  => $r['name'],
								 'email' => $r['email']
							   );
			}
		}
		
		//--------------------------------------------
    	// Get the emailer module
		//--------------------------------------------
		
		require "./sources/lib/emailer.php";
		
		$this->email = new emailer();
		
		//--------------------------------------------
		// Loop and send the mail
		//--------------------------------------------
		
		$report = trim(stripslashes($HTTP_POST_VARS['message']));
		
		$report = str_replace( "<!--"    , "" , $report );
		$report = str_replace( "-->"     , "" , $report );
		$report = str_replace( "<script" , "" , $report );
		
		foreach( $mods as $idx => $data )
		{
			$this->email->get_template("report_post");
				
			$this->email->build_message( array(
												'MOD_NAME'     => $data['name'],
												'USERNAME'     => $ibforums->member['name'],
												'TOPIC'        => $topic['title'],
												'LINK_TO_POST' => "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}"."?act=ST&f=$fid&t=$tid&st=$st&#entry$pid",
												'REPORT'       => $report,
											  )
										);
										
			$this->email->subject = $ibforums->lang['report_subject'].' '.$ibforums->vars['board_name'];
			$this->email->to      = $data['email'];
			
			$this->email->send_mail();
		
		}
			
		$print->redirect_screen( $ibforums->lang['report_redirect'], "act=ST&f=$fid&t=$tid&st=$st&#entry$pid");					   
		
	}
	
	//--------------------------------------------
	
     
    function check_access($fid, $tid)
    {
		global $ibforums, $DB, $std, $HTTP_COOKIE_VARS;
		
		if ( ! $ibforums->member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//--------------------------------
		
		$DB->query("SELECT t.title as topic_title, f.*, c.id as cat_id, c.name as cat_name from ibf_forums f, ibf_categories c, ibf_topics t WHERE f.id=".$fid." and c.id=f.category and t.tid=$tid");
        
        $this->forum = $DB->fetch_row();
		
		$return = 1;
		
		if ( $std->check_perms($this->forum['read_perms']) == TRUE )
		{
			$return = 0;
		}
		
		if ($this->forum['password'])
		{
			if ($HTTP_COOKIE_VARS[ $ibforums->vars['cookie_id'].'iBForum'.$this->forum['id'] ] == $this->forum['password'])
			{
				$return = 0;
			}
		}
		
		if ($return == 1)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
	
	}
	
	//****************************************************************/
	// MSN CONSOLE:
	//
	//****************************************************************/
	
	function show_msn() {
		global $ibforums, $DB, $std, $print;
		
		$this->html    = $std->load_template('skin_emails');

		$ibforums->lang    = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );
		
		//----------------------------------
	
		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
	
		if ( empty($ibforums->input['MID']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		if (! preg_match( "/^(\d+)$/" , $ibforums->input['MID'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, msnname from ibf_members WHERE id='".$ibforums->input['MID']."'");

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if (! $member['msnname'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msn' ) );
		}
		
		//----------------------------------
		
		$html  = $this->html->pager_header( array( 'TITLE' => 'MSN' ) );
		
		$html .= $this->html->msn_body( $member['msnname'] );
		
		$html .= $this->html->end_table();
		
		$print->pop_up_window( "MSN CONSOLE", $html );
	
	}
	
	//****************************************************************/
	// Yahoo! CONSOLE:
	//
	//****************************************************************/
	
	function show_yahoo() {
		global $ibforums, $DB, $std, $print;
		
		$this->html    = $std->load_template('skin_emails');

		$ibforums->lang    = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );
		
		//----------------------------------
	
		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
	
		if ( empty($ibforums->input['MID']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		if (! preg_match( "/^(\d+)$/" , $ibforums->input['MID'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, yahoo from ibf_members WHERE id='".$ibforums->input['MID']."'");

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if (! $member['yahoo'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_yahoo' ) );
		}
		
		//----------------------------------
		
		$html  = $this->html->pager_header( array( 'TITLE' => "Yahoo!" ) );
		
		$html .= $this->html->yahoo_body( $member['yahoo'] );
		
		$html .= $this->html->end_table();
		
		$print->pop_up_window( "YAHOO! CONSOLE", $html );
	
	}
     
    //****************************************************************/
	// AOL CONSOLE:
	//
	//****************************************************************/
        
        
	function show_aim() {
		global $ibforums, $DB, $std, $print;
		
		$ibforums->lang    = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html    = $std->load_template('skin_emails');
		
		//----------------------------------
	
		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
	
		if ( empty($ibforums->input['MID']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		if (! preg_match( "/^(\d+)$/" , $ibforums->input['MID'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, aim_name from ibf_members WHERE id='".$ibforums->input['MID']."'");

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if (! $member['aim_name'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_aol' ) );
		}
		
		$member['aim_name'] = str_replace(" ", "", $member['aim_name']);
		
		//----------------------------------
		
		$print->pop_up_window( "AOL CONSOLE", $this->html->aol_body( array( 'AOLNAME' => $member['aim_name'] ) ) );
	
	}
	
	//****************************************************************/
	// ICQ CONSOLE:
	//
	//****************************************************************/
	
	
	function show_icq() {
		global $ibforums, $DB, $std, $print;
		
		$ibforums->lang    = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id);

		$this->html    = $std->load_template('skin_emails');
		
		//----------------------------------
	
		if (empty($ibforums->member['id'])) {
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
	
		if ( empty($ibforums->input['MID']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		if (! preg_match( "/^(\d+)$/" , $ibforums->input['MID'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, icq_number from ibf_members WHERE id='".$ibforums->input['MID']."'");

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if (! $member['icq_number'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_icq' ) );
		}
		
		//----------------------------------
		
		$html  = $this->html->pager_header( array( $ibforums->lang['icq_title'] ) );
		
		$html .= $this->html->icq_body( array( 'UIN' => $member['icq_number'] ) );
		
		$html .= $this->html->end_table();
		
		$print->pop_up_window( "ICQ CONSOLE", $html );
	
	
	}
	
	//****************************************************************/
	// MAIL MEMBER:
	//
	// Handles the routines called by clicking on the "email" button when
	// reading topics
	//****************************************************************/
	
	function mail_member()
	{
		global $ibforums, $DB, $std, $print;
	
		require "./sources/lib/emailer.php";
		$this->email = new emailer();
		
		//----------------------------------
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_emails', $ibforums->lang_id );

		$this->html     = $std->load_template('skin_emails');
		
		//----------------------------------
	
		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		if ( ! $ibforums->member['g_email_friend'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_member_mail' ) );
		}
		
		//----------------------------------
		
		if ($ibforums->input['CODE'] == '01')
		{
		
			$this->mail_member_send();
			
		}
		else
		{
			// Show the form, booo...
			
			$this->mail_member_form();

		}
		
	}
	
	function mail_member_form($errors="", $extra = "")
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;
		
		$ibforums->input['MID'] = intval($ibforums->input['MID']);
		
		if ( $ibforums->input['MID'] < 1 )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, email, hide_email from ibf_members WHERE id=".$ibforums->input['MID']);

		$member = $DB->fetch_row();
		
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		if ($member['hide_email'] == 1)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'private_email' ) );
		}
		
		//----------------------------------
		
		if ( $errors != "" )
		{
			$msg = $ibforums->lang[$errors];
			
			if ( $extra != "" )
			{
				$msg = str_replace( "<#EXTRA#>", $extra, $msg );
			}
			
			$this->output .= $this->html->errors( $msg );
		}
		
		//----------------------------------
		
		$this->output .= $ibforums->vars['use_mail_form']
					  ? $this->html->send_form(
												  array(
														  'NAME'   => $member['name'],
														  'TO'     => $member['id'],
														  'subject'=> $ibforums->input['subject'],
														  'content'=> stripslashes(htmlentities($HTTP_POST_VARS['message'])),
													   )
											   )
					  : $this->html->show_address(
												  array(
														  'NAME'    => $member['name'],
														  'ADDRESS' => $member['email'],
													   )
												 );
												 
		$this->page_title = $ibforums->lang['member_address_title'];
		$this->nav        = array( $ibforums->lang['member_address_title'] );

		
	}
	
	//----------------------------------
	
	function mail_member_send()
	{
		global $ibforums, $DB, $std, $print;
		
		$ibforums->input['to'] = intval($ibforums->input['to']);
	
		if ( $ibforums->input['to'] == 0 )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//----------------------------------
		
		$DB->query("SELECT name, id, email, hide_email from ibf_members WHERE id=".$ibforums->input['to']);

		$member = $DB->fetch_row();
		
		//----------------------------------
		// Check for schtuff
		//----------------------------------
		
		if (! $member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//----------------------------------
		
		if ($member['hide_email'] == 1)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'private_email' ) );
		}
		
		//----------------------------------
		// Check for blanks
		//----------------------------------
		
		$check_array = array ( 
							   'message'   =>  'no_message',
							   'subject'   =>  'no_subject'
							 );
						 
		foreach ($check_array as $input => $msg)
		{
			if (empty($ibforums->input[$input]))
			{
				$ibforums->input['MID'] = $ibforums->input['to'];
				$this->mail_member_form($msg);
				return;
			}
		}
		
		//----------------------------------
		// Check for spam / delays
		//----------------------------------
		
		$email_check = $this->_allow_to_email( $ibforums->member['id'], $ibforums->member['g_email_limit'] );
		
		if ( $email_check != TRUE )
		{
			$ibforums->input['MID'] = $ibforums->input['to'];
			$this->mail_member_form( $this->int_error, $this->int_extra);
			return;
		}
		
		//----------------------------------
		// Send the email
		//----------------------------------
		
		$this->email->get_template("email_member");
			
		$this->email->build_message( array(
											'MESSAGE'     => str_replace( "<br>", "\n", str_replace( "\r", "", $ibforums->input['message'] ) ),
											'MEMBER_NAME' => $member['name'],
											'FROM_NAME'   => $ibforums->member['name']
										  )
									);
									
		$this->email->subject = $ibforums->input['subject'];
		$this->email->to      = $member['email'];
		$this->email->from    = $ibforums->member['email'];
		$this->email->send_mail();
		
		//----------------------------------
		// Store email in the database
		//----------------------------------
		
		$dbs = array( 
						'email_subject'      => $ibforums->input['subject'],
						'email_content'      => $ibforums->input['message'],
						'email_date'         => time(),
						'from_member_id'     => $ibforums->member['id'],
						'from_email_address' => $ibforums->member['email'],
						'from_ip_address'	 => $ibforums->input['IP_ADDRESS'],
						'to_member_id'		 => $member['id'],
						'to_email_address'	 => $member['email'],
					);
					
					
		$db_string = $DB->compile_db_insert_string($dbs);
						
		$DB->query("INSERT INTO ibf_email_logs ({$db_string['FIELD_NAMES']}) VALUES({$db_string['FIELD_VALUES']})");	
		
		//----------------------------------
		// Print the success page
		//----------------------------------
		
		$forum_jump = $std->build_forum_jump();
		
		$this->output  = $this->html->sent_screen($member['name']);
		
		$this->output .= $this->html->forum_jump($forum_jump);
		
		$this->page_title = $ibforums->lang['email_sent'];
		$this->nav        = array( $ibforums->lang['email_sent'] );
	}
	
	
	//----------------------------------
	// CHECK FLOOD LIMIT
	// Returns TRUE if able to email
	// FALSE if not
	//----------------------------------
	
	function _allow_to_email($member_id, $email_limit)
	{
		global $ibforums, $std, $DB;
		
		$member_id = intval($member_id);
		
		if ( ! $member_id )
		{
			$this->int_error = 'gen_error';
			return FALSE;
		}
		
		list( $limit, $flood ) = explode( ':', $email_limit );
		
		if ( ! $limit and ! $flood )
		{
			return TRUE;
		}
		
		//----------------------------------
		// Get some stuff from the DB!
		// 1) FLOOD?
		//----------------------------------
		
		if ( $flood )
		{
			$DB->query("SELECT * FROM ibf_email_logs WHERE from_member_id=$member_id ORDER BY email_date DESC LIMIT 0,1");
		
			$last_email = $DB->fetch_row();

			if ( $last_email['email_date'] + ($flood * 60) > time() )
			{
				$this->int_error = 'exceeded_flood';
				$this->int_extra = $flood;
				return FALSE;
			}
		}
		
		if ( $limit )
		{
			$time_range = time() - 86400;
			
			$DB->query("SELECT count(email_id) as cnt FROM ibf_email_logs WHERE from_member_id=$member_id AND email_date > $time_range");
			
			$quota_sent = $DB->fetch_row();
			
			if ( $quota_sent['cnt'] + 1 > $limit )
			{
				$this->int_error = 'exceeded_quota';
				$this->int_extra = limit;
				return FALSE;
			}
		}
		
		return TRUE; //If we get here...
        		
	}
        		
        		
}






?>