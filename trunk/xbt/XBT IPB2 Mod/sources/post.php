<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v2.0.0 
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Time: Tue, 21 Sep 2004 16:34:28 GMT
|   Release: 150aa7a702c3c8b6f6eb90ad49305d2f
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > Post core module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|   > Module Version 1.0.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class post {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $parser    = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_groups = array();
    var $mem_titles = array();
    var $obj        = array();
    var $email      = "";
    var $can_upload = 0;
    var $md5_check  = "";
    var $module     = "";
    var $attach_sum = -1;
    
    
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function init()
    {
		global $ibforums, $forums, $DB, $std, $print, $skin_universal;
        
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/lib/post_parser.php" );
        
        $this->parser = new post_parser(1);
        
        $this->parser->bypass_badwords = intval($ibforums->member['g_bypass_badwords']);
        
        //-----------------------------------------
        // Load the email libby
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/classes/class_email.php" );
		
		$this->email = new emailer();
        
        //-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_post', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_post');
    }
    
    /*-------------------------------------------------------------------------*/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		global $ibforums, $forums, $DB, $std, $print, $skin_universal;
        
        $this->init();
        
        //-----------------------------------------
    	// Get the sync module
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
		}
        
        //-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->md5_check = $std->return_md5_check();
        
        if ($ibforums->input['t'])
        {
        	$ibforums->input['t'] = intval($ibforums->input['t']);
        	if (! $ibforums->input['t'] )
        	{
        		$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        	}
        }
        
        if ($ibforums->input['p'])
        {
        	$ibforums->input['p'] = intval($ibforums->input['p']);
        	if (! $ibforums->input['p'] )
        	{
        		$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        	}
        }
        
        $ibforums->input['f'] = intval($ibforums->input['f']);
        if (! $ibforums->input['f'] )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        $ibforums->input['st'] = $ibforums->input['st'] ?intval($ibforums->input['st']) : 0;
        
        // Did the user press the "preview" button?
        
        $this->obj['preview_post'] = $ibforums->input['preview'];
        
        //-----------------------------------------
        // Get the forum info based
        //-----------------------------------------
        
        $this->forum = $forums->forum_by_id[ $ibforums->input['f'] ];
        
        //-----------------------------------------
        // Can we upload stuff?
        //-----------------------------------------
        
        if ( $std->check_perms($this->forum['upload_perms']) == TRUE )
        {
        	if ( $ibforums->member['g_attach_max'] != -1 )
        	{
        		$this->can_upload = 1;
				$this->obj['form_extra']   = " enctype='multipart/form-data'";
				$this->obj['hidden_field'] = "<input type='hidden' name='MAX_FILE_SIZE' value='".($ibforums->member['g_attach_max']*1024)."' />";
        	}
        }
        
        //-----------------------------------------
        // Is this forum switched off?
        //-----------------------------------------
        
        if ( ! $this->forum['status'] )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'forum_read_only') );
        }
        
        //-----------------------------------------
        // Check access
        //-----------------------------------------
        
        $forums->forums_check_access( $this->forum['id'], 1 );
        
		//-----------------------------------------
		// Get navigation station
		//-----------------------------------------
        
        $this->nav = $forums->forums_breadcrumb_nav( $this->forum['id'] );
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        $this->base_url = $ibforums->base_url;
        
        //-----------------------------------------
        // Is this forum moderated?
        //-----------------------------------------
        
        $this->obj['moderate'] = intval($this->forum['preview_posts']);
        
        // Can we bypass it?
        
        if ($ibforums->member['g_avoid_q'])
        {
        	$this->obj['moderate'] = 0;
        }
        
		//-----------------------------------------
        // Does this member have mod_posts enabled?
		//-----------------------------------------
         
        if ( $ibforums->member['mod_posts'] )
		{
			if ( $ibforums->member['mod_posts'] == 1 )
			{
				$this->obj['moderate'] = 1;
			}
			else
			{
				$mod_arr = $std->hdl_ban_line( $ibforums->member['mod_posts'] );
				
				if ( time() >= $mod_arr['date_end'] )
				{
					// Update this member's profile
					
					$DB->simple_construct( array( 'update' => 'members',
												  'set'    => 'mod_posts=0',
												  'where'  => "id=".intval($ibforums->member['id'])
										)       );
										
					$DB->simple_exec();
					
					$this->obj['moderate'] = intval($this->forum['preview_posts']);
				}
				else
				{
					$this->obj['moderate'] = 1;
				}
			}
		}
        
        //-----------------------------------------
        // Are we allowed to post at all?
        //-----------------------------------------
        
        if ($ibforums->member['id'])
        {
        	if ( $ibforums->member['restrict_post'] )
        	{
        		if ( $ibforums->member['restrict_post'] == 1 )
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'posting_off') );
        		}
        		
        		$post_arr = $std->hdl_ban_line( $ibforums->member['restrict_post'] );
        		
        		if ( time() >= $post_arr['date_end'] )
        		{
        			// Update this member's profile
        			
					$DB->simple_construct( array( 'update' => 'members',
												  'set'    => 'restrict_post=0',
												  'where'  => "id=".intval($ibforums->member['id'])
										)       );
										
					$DB->simple_exec();
        		}
        		else
        		{
        			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'posting_off_susp', 'EXTRA' => $std->get_date($post_arr['date_end'], 'LONG') ) );
        		}
        	}
        	
        	// Flood check..
        	
        	if ( $ibforums->input['CODE'] != "08" and $ibforums->input['CODE'] != "09" and $ibforums->input['CODE'] != "14" and $ibforums->input['CODE'] != "15" )
        	{
				if ( $ibforums->vars['flood_control'] > 0 )
				{
					if ($ibforums->member['g_avoid_flood'] != 1)
					{
						if ( time() - $ibforums->member['last_post'] < $ibforums->vars['flood_control'] )
						{
							$std->Error( array( 'LEVEL' => 1, 'MSG' => 'flood_control' , 'EXTRA' => $ibforums->vars['flood_control'] ) );
						}
					}
				}
			}
        }
        else if ( $ibforums->is_bot == 1 )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'posting_off') );
        }
        
        if ($ibforums->member['id'] != 0 and $ibforums->member['g_is_supmod'] == 0)
        {
        	$DB->cache_add_query('topics_check_for_mod',  array( 'fid' => $this->forum['id'], 'mid' => $ibforums->member['id'], 'gid' => $ibforums->member['mgroup'] ) );
			$DB->simple_exec();
			
        	$this->moderator = $DB->fetch_row();
        }
        
        //-----------------------------------------
        // Convert the code ID's into something
        // use mere mortals can understand....
        //-----------------------------------------
        
        $this->obj['action_codes'] = array ( '00'  => array( '0'  , 'new_post'     ),
        									 '01'  => array( '1'  , 'new_post'     ),
        									 '02'  => array( '0'  , 'reply_post'   ),
        									 '03'  => array( '1'  , 'reply_post'   ),
        									 '08'  => array( '0'  , 'edit_post'    ),
        									 '09'  => array( '1'  , 'edit_post'    ),
        									 '10'  => array( '0'  , 'poll'         ),
        									 '11'  => array( '1'  , 'poll'         ),
        									 '14'  => array( '0'  , 'poll_after'   ),
        									 '15'  => array( '1'  , 'poll_after'   ),
        								   );
        
        //-----------------------------------------								   
        // Make sure our input CODE element is legal.
        //-----------------------------------------
        
        if (! isset($this->obj['action_codes'][ $ibforums->input['CODE'] ]) )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-----------------------------------------
        // Require and run our associated library file for this action.
        // this imports an extended class for this Post class.
        //-----------------------------------------
        
        require ROOT_PATH."sources/lib/post_" . $this->obj['action_codes'][ $ibforums->input['CODE'] ][1] . ".php";
        
        $post_functions = new post_functions(&$this);
        
        //-----------------------------------------
        // If the first CODE array bit is set to "0" - show the relevant form.
        // If it's set to "1" process the input.
        //
        // We pass a reference to this classes object so we can manipulate this classes
        // data from our sub class.
        //-----------------------------------------
        
        if ($this->obj['action_codes'][ $ibforums->input['CODE'] ][0])
        {
        	//-----------------------------------------
        	// Make sure we have a valid auth key
        	//-----------------------------------------
        	
        	if ( $ibforums->input['auth_key'] != $this->md5_check )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
			}
        	
        	//-----------------------------------------
        	// Make sure we have a "Guest" Name..
        	//-----------------------------------------
        	
        	if (!$ibforums->member['id'])
        	{
        	
        		$ibforums->input['UserName'] = trim($ibforums->input['UserName']);
        		$ibforums->input['UserName'] = str_replace( "<br>", "", $ibforums->input['UserName']);
        		$ibforums->input['UserName'] = $ibforums->input['UserName'] ? $ibforums->input['UserName'] : 'Guest';
        		
        		if ($ibforums->input['UserName'] != 'Guest')
        		{
					$DB->cache_add_query( 'login_getmember', array( 'username' => trim(strtolower($ibforums->input['UserName'])) ) );
					$DB->cache_exec_query();
					
        			if ( $DB->get_num_rows() )
        			{
        				$ibforums->input['UserName'] = $ibforums->vars['guest_name_pre'].$ibforums->input['UserName'].$ibforums->vars['guest_name_suf'];
        			}
        		}
        	}
        	
        	//-----------------------------------------
        	// Stop the user hitting the submit button in the hope that multiple topics
        	// or replies will be added. Or if the user accidently hits the button
        	// twice.
        	//-----------------------------------------
        	
        	if ( $this->obj['preview_post'] == "" )
        	{
				if ( preg_match( "/Post,.*,(01|03|07|11)$/", $ibforums->location ) )
				{
					if ( time() - $ibforums->lastclick < 2 )
					{
						if ( $ibforums->input['CODE'] == '01' or $ibforums->input['CODE'] == '11' )
						{
							//-----------------------------------------
							// Redirect to the newest topic in the forum
							//-----------------------------------------
							
							$DB->simple_construct( array( 'select' => 'tid',
														  'from'   => 'topics',
														  'where'  => "forum_id='".$this->forum['id']."' AND approved=1",
														  'order'  => 'last_post DESC',
														  'limit'  => array( 0, 1 )
												)       );
												
							$DB->simple_exec();
					
							$topic = $DB->fetch_row();
					
							$std->boink_it($ibforums->base_url."act=ST&f=".$this->forum['id']."&t=".$topic['tid']);
							exit();
						}
						else
						{
							//-----------------------------------------
							// It's a reply, so simply show the topic...
							//-----------------------------------------
							
							$std->boink_it($ibforums->base_url."act=ST&f=".$this->forum['id']."&t=".$ibforums->input['t']."&view=getlastpost");
							exit();
						}
					}
				}
        	}
        	
        	//-----------------------------------------
       
        	$post_functions->process(&$this);
        }
        else
        {
        	$post_functions->show_form(&$this);
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// Notify new topic mod Q
	// ----------------------
	/*-------------------------------------------------------------------------*/
	
	function notify_new_topic_approval($tid, $title, $author, $pid=0, $type='new')
	{
		global $ibforums, $DB, $std;
		
		$tmp = $DB->simple_exec_query( array( 'select' => 'notify_modq_emails', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
		
		if ( $tmp['notify_modq_emails'] == "" )
		{ 
			return;
		}
		
		if ( $type == 'new' )
		{
			$this->email->get_template("new_topic_queue_notify");
		}
		else
		{
			$this->email->get_template("new_post_queue_notify");
		}
		
		$this->email->build_message( array(
											'TOPIC'  => $title,
											'FORUM'  => $this->forum['name'],
											'POSTER' => $author,
											'DATE'   => $std->get_date( time(), 'SHORT' ),
											'LINK'   => $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?act=findpost&pid='.$pid,
										  )
									);
		
		foreach( explode( ",", $tmp['notify_modq_emails'] ) as $email )
		{
			$this->email->to = trim($email);
			$this->email->send_mail();
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// topic tracker
	// ------------------
	// Checks and sends out the emails as needed.
	/*-------------------------------------------------------------------------*/
	
	function topic_tracker($tid="", $post="", $poster="", $last_post="" )
	{
		global $ibforums, $DB, $std;
		
		if ($tid == "")
		{
			return TRUE;
		}
		
		$count = 0;
		
		//-----------------------------------------
		// Get the email addy's, topic ids and email_full stuff - oh yeah.
		// We only return rows that have a member last_activity of greater than the post itself
		// Ergo:
		//  Last topic post: 8:50am
		//  Last topic visit: 9:00am
		//  Next topic reply: 9:10am
		// if ( last.activity > last.topic.post ) { send.... }
		//  Next topic reply: 9:20am
		// if ( last.activity > last.topic.post ) { will fail as 9:10 > 8:50 }
		//-----------------------------------------
		
		$DB->cache_add_query( 'post_topic_tracker', array( 'tid' => $tid, 'mid' => $ibforums->member['id'], 'last_post' => $last_post ) );
		
		$outer = $DB->simple_exec();
		
		if ( $DB->get_num_rows($outer) )
		{
			$trids = array();
			
			while ( $r = $DB->fetch_row($outer) )
			{
				$count++;
				
				$r['language'] = $r['language'] ? $r['language'] : 'en';
				
				if ($r['email_full'] == 1)
				{
					$this->email->get_template("subs_with_post", $r['language']);
			
					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['name'],
														'POSTER'          => $poster,
														'POST'            => $post,
													  )
												);
					
				}
				else
				{
				
					$this->email->get_template("subs_no_post", $r['language']);
			
					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['name'],
														'POSTER'          => $poster,
													  )
												);
					
				}
				
				$trids[] = $r['trid'];
				
				//-----------------------------------------
				// Add to mail queue
				//-----------------------------------------
				
				$DB->do_insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_date' => time(), 'mail_subject' => $ibforums->lang['tt_subject'], 'mail_content' => $this->email->message ) );
			}
			
			$ibforums->cache['systemvars']['mail_queue'] += $count;
			
			//-----------------------------------------
			// Update cache with remaning email count
			//-----------------------------------------
			
			$DB->do_update( 'cache_store', array( 'cs_array' => 1, 'cs_value' => addslashes(serialize($ibforums->cache['systemvars'])) ), "cs_key='systemvars'" );
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum tracker
	// ------------------
	// Checks and sends out the new topic notification if
	// needed
	/*-------------------------------------------------------------------------*/
	
	function forum_tracker($fid="", $this_tid="", $title="", $forum_name="", $post="")
	{
		global $ibforums, $DB, $std;
		
		if ($this_tid == "")
		{
			return TRUE;
		}
		
		if ($fid == "")
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Work out the time stamp needed to "guess"
		// if the user is still active on the board
		// We will base this guess on a period of
		// non activity of time_now - 30 minutes.
		//-----------------------------------------
		
		$time_limit = time() - (30*60);
		
		$count = 0;
		
		$gotem  = array();
		
		$DB->cache_add_query( 'post_forum_tracker', array( 'fid'       => $fid,
														   'mid'       => $ibforums->member['id'],
														   'last_post' => $time_limit ) );
		
		$DB->simple_exec();
		
		while ( $r = $DB->fetch_row() )
		{
			$gotem[ $r['id'] ] = $r;
		}
		
		//-----------------------------------------
		// Get "all" groups?
		//-----------------------------------------
		
		if ( $ibforums->vars['autoforum_sub_groups'] )
		{
			$DB->cache_add_query( 'post_forum_tracker_all', array( 'groups'    => $ibforums->vars['autoforum_sub_groups'],
																   'last_post' => $time_limit,
																   'mid'       => $ibforums->member['id'] ) );
			
			$DB->simple_exec();
			
			while ( $r = $DB->fetch_row() )
			{
				$gotem[ $r['id'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// Row, row and parse, parse
		//-----------------------------------------
		
		if ( count( $gotem ) )
		{
			foreach( $gotem as $mid => $r )
			{
				$count++;
				
				$perm_id = ( $r['org_perm_id'] ) ? $r['org_perm_id'] : $r['g_perm_id'];
				
				if ($this->forum['read_perms'] != '*')
				{
					if ( ! preg_match("/(^|,)".str_replace( ",", '|', $perm_id )."(,|$)/", $this->forum['read_perms'] ) )
        			{
        				continue;
       				}
				}
        
				$r['language'] = $r['language'] ? $r['language'] : 'en';
				
				$this->email->get_template("subs_new_topic", $r['language']);
		
				$this->email->build_message( array(
													'TOPIC_ID'        => $this_tid,
													'FORUM_ID'        => $fid,
													'TITLE'           => $title,
													'NAME'            => $r['name'],
													'POSTER'          => $ibforums->member['name'],
													'FORUM'           => $forum_name,
													'POST'            => $post,
												  )
											);
				
				$DB->do_insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_date' => time(), 'mail_subject' => $ibforums->lang['ft_subject'], 'mail_content' => $this->email->message ) );
			}
		}
		
		$ibforums->cache['systemvars']['mail_queue'] += $count;
			
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		$DB->do_update( 'cache_store', array( 'cs_array' => 1, 'cs_value' => addslashes(serialize($ibforums->cache['systemvars'])) ), "cs_key='systemvars'" );
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// compile post
	// ------------------
	// Compiles all the incoming information into an array
	// which is returned to the accessor
	/*-------------------------------------------------------------------------*/
	
	function compile_post()
	{
		global $ibforums, $std;
		
		$ibforums->vars['max_post_length'] = $ibforums->vars['max_post_length'] ? $ibforums->vars['max_post_length'] : 2140000;
		
		//-----------------------------------------
		// Sort out some of the form data, check for posting length, etc.
		// THIS MUST BE CALLED BEFORE CHECKING ATTACHMENTS
		//-----------------------------------------
		
		$ibforums->input['enablesig']   = $ibforums->input['enablesig']   == 'yes' ? 1 : 0;
		$ibforums->input['enableemo']   = $ibforums->input['enableemo']   == 'yes' ? 1 : 0;
		$ibforums->input['enabletrack'] = intval($ibforums->input['enabletrack']) != 0 ? 1 : 0;
		
		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if (strlen( trim($_POST['Post']) ) < 1)
		{
			if ( ! $_POST['preview'] )
			{
				$std->Error( array( LEVEL => 1, MSG => 'no_post') );
			}
		}
		
		if (strlen( $_POST['Post'] ) > ($ibforums->vars['max_post_length']*1024))
		{
			$std->Error( array( LEVEL => 1, MSG => 'post_too_long') );
		}
		
		$post = array(
						'author_id'   => $ibforums->member['id'] ? $ibforums->member['id'] : 0,
						'use_sig'     => $ibforums->input['enablesig'],
						'use_emo'     => $ibforums->input['enableemo'],
						'ip_address'  => $ibforums->input['IP_ADDRESS'],
						'post_date'   => time(),
						'icon_id'     => $ibforums->input['iconid'],
						'post'        => $this->parser->convert( array( 'TEXT'    => $ibforums->input['Post'],
																		'SMILIES' => $ibforums->input['enableemo'],
																		'CODE'    => $this->forum['use_ibc'],
																		'HTML'    => $this->forum['use_html']
																	  )
															   ),
						'author_name' => $ibforums->member['id'] ? $ibforums->member['name'] : $ibforums->input['UserName'],
						'topic_id'    => "",
						'queued'      => ( $this->obj['moderate'] == 1 || $this->obj['moderate'] == 3 ) ? 1 : 0,
						'post_htmlstate' => intval($ibforums->input['post_htmlstatus']),
					 );
					 
	    // If we had any errors, parse them back to this class
	    // so we can track them later.
	    
	    $this->obj['post_errors'] = $this->parser->error;
					 
		return $post;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: mod_options.
	// ------------------
	// Returns the HTML for the mod options drop down box
	/*-------------------------------------------------------------------------*/
	
	function mod_options($is_reply=0)
	{
		global $ibforums, $DB;
		
		$can_close = 0;
		$can_pin   = 0;
		$can_move  = 0;
		
		$html = "<select id='forminput' name='mod_options' class='forminput'>\n<option value='nowt'>".$ibforums->lang['mod_nowt']."</option>\n";
		
		if ($ibforums->member['g_is_supmod'])
		{
			$can_close = 1;
			$can_pin   = 1;
			$can_move  = 1;
		}
		else if ($ibforums->member['id'] != 0)
		{
			if ($this->moderator['mid'] != "" )
			{
				if ($this->moderator['close_topic'])
				{
					$can_close = 1;
				}
				if ($this->moderator['pin_topic'])
				{
					$can_pin   = 1;
				}
				if ($this->moderator['move_topic'])
				{
					$can_move  = 1;
				}
			}
		}
		else
		{
			return "";
		}
		
		if ($can_pin == 0 and $can_close == 0 and $can_move == 0)
		{
			return "";
		}
		
		if ($can_pin)
		{
			$html .= "<option value='pin'>".$ibforums->lang['mod_pin']."</option>";
		}
		if ($can_close)
		{
			$html .= "<option value='close'>".$ibforums->lang['mod_close']."</option>";
		}
		
		if ($can_close and $can_pin)
		{
			$html .= "<option value='pinclose'>".$ibforums->lang['mod_pinclose']."</option>";
		}
		
		if ($can_move and $is_reply)
		{
			$html .= "<option value='move'>".$ibforums->lang['mod_move']."</option>";
		}
		
		return $this->html->mod_options($html);
	}
	
	
	/*-------------------------------------------------------------------------*/
	// HTML: start form.
	// ------------------
	// Returns the HTML for the <FORM> opening tag
	/*-------------------------------------------------------------------------*/
	
	function html_start_form($additional_tags=array())
	{
		global $ibforums;
		
		$form = $this->html->get_javascript();
		
		$form .= "<form action='{$this->base_url}' method='post' name='REPLIER' onsubmit='return ValidateForm()'".$this->obj['form_extra'].">".
				"<input type='hidden' name='st' value='".$ibforums->input[st]."' />\n".
				"<input type='hidden' name='act' value='Post' />\n".
				"<input type='hidden' name='s' value='".$ibforums->session_id."' />\n".
				"<input type='hidden' name='f' value='".$this->forum['id']."' />\n".
				"<input type='hidden' name='auth_key' value='".$this->md5_check."' />\n".
				"<input type='hidden' name='removeattachid' value='0' />\n".
				$this->obj['hidden_field'];
				
		// Any other tags to add?
		
		if (isset($additional_tags))
		{
			foreach($additional_tags as $k => $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}
		
		return $form;
    }
		
	/*-------------------------------------------------------------------------*/
	// HTML: name fields.
	// ------------------
	// Returns the HTML for either text inputs or membername
	// depending if the member is a guest.
	/*-------------------------------------------------------------------------*/
	
	function html_name_field()
	{
		global $ibforums;
		
		return $ibforums->member['id'] ? $this->html->nameField_reg() : $this->html->nameField_unreg( $ibforums->input[UserName] );
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Post body.
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*-------------------------------------------------------------------------*/
	
	function html_post_body($raw_post="")
	{
		global $ibforums;
		
		$ibforums->lang['the_max_length'] = $ibforums->vars['max_post_length'] * 1024;
		
		return $this->html->postbox_buttons($raw_post);
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Post Icons
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*-------------------------------------------------------------------------*/
	
	function html_post_icons($post_icon="")
	{
		global $ibforums;
		
		if ($ibforums->input['iconid'])
		{
			$post_icon = $ibforums->input['iconid'];
		}
		
		$ibforums->lang['the_max_length'] = $ibforums->vars['max_post_length'] * 1024;
		
		$html = $this->html->PostIcons();
		
		if ($post_icon) {
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']$post_icon\s?[\"']/", "name='iconid' value='$post_icon' checked", $html );
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']0[\"']\s*checked=['\"]checked['\"]/i"  , "name='iconid' value='0'", $html );
		}
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: checkboxes
	// ------------------
	// Returns the HTML for sig/emo/track boxes
	/*-------------------------------------------------------------------------*/
	
	function html_checkboxes($type="", $tid="", $fid="") 
	{
		global $ibforums, $DB;
		
		$default_checked = array(
								  'sig' => 'checked="checked"',
						  		  'emo' => 'checked="checked"',
						  		  'tra' => $ibforums->member['auto_track'] ? 'checked="checked"' : ''
						        );
						        
		
		// Make sure we're not previewing them and they've been unchecked!
		
		if ( isset( $ibforums->input['enablesig'] ) AND ( ! $ibforums->input['enablesig'] ) )
		{
			$default_checked['sig'] = "";
		}
		
		if ( isset( $ibforums->input['enableemo'] ) AND ( ! $ibforums->input['enableemo'] ) )
		{
			$default_checked['emo'] = "";
		}
		
		if ( isset( $ibforums->input['enabletrack'] ) AND ( ! $ibforums->input['enabletrack'] ) )
		{
			$default_checked['tra'] = "";
		}
		else if ( isset( $ibforums->input['enabletrack'] ) AND ( $ibforums->input['enabletrack'] == 1 ) )
		{
			$default_checked['tra'] = 'checked="checked"';
		}
		
		$this->output = str_replace( '<!--IBF.EMO-->'  , $this->html->get_box_enableemo( $default_checked['emo'] )  , $this->output );
		
		if ( $ibforums->member['id'] )
		{
			$this->output = str_replace( '<!--IBF.SIG-->'  , $this->html->get_box_enablesig( $default_checked['sig'] )  , $this->output );
		}
		
		if ( $ibforums->cache['forum_cache'][$fid]['use_html'] and $ibforums->cache['group_cache'][ $ibforums->member['mgroup'] ]['g_dohtml'] )
		{
			$this->output = str_replace( '<!--IBF.HTML-->' , $this->html->get_box_html( array( intval($ibforums->input['post_htmlstatus']) => ' selected="selected"' ) ), $this->output );
		}
		
		if ( $type == 'reply' )
		{
			if ( $tid and $ibforums->member['id'] )
			{
				$DB->simple_construct( array( 'select' => 'trid', 'from' => 'tracker', 'where' => "topic_id=$tid AND member_id=".$ibforums->member['id'] ) );
				$DB->simple_exec();
				
				if ( $DB->get_num_rows() )
				{
					$this->output = str_replace( '<!--IBF.TRACK-->',$this->html->get_box_alreadytrack(), $this->output );
				}
				else
				{
					$this->output = str_replace( '<!--IBF.TRACK-->', $this->html->get_box_enabletrack( $default_checked['tra'] ), $this->output );
				}
			}
		}
		else if ( $type != 'edit' )
		{
			if ( $ibforums->member['id'] )
			{
				$this->output = str_replace( '<!--IBF.TRACK-->', $this->html->get_box_enabletrack( $default_checked['tra'] ), $this->output );
			}
		}
	}
	
    /*-------------------------------------------------------------------------*/
	// HTML: add smilie box.
	// ------------------
	// Inserts the clickable smilies box
	/*-------------------------------------------------------------------------*/
	
	function html_add_smilie_box($in_html="")
	{
		global $ibforums, $DB;
		
		$show_table = 0;
		$count      = 0;
		$smilies    = "<tr align='center'>\n";
		
		//-----------------------------------------
		// Get the smilies from the DB
		//-----------------------------------------
		
		if ( ! is_array( $ibforums->cache['emoticons'] ) )
		{
			$ibforums->cache['emoticons'] = array();
			
			$DB->simple_construct( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
			$DB->simple_exec();
		
			while ( $r = $DB->fetch_row() )
			{
				$ibforums->cache['emoticons'][] = $r;
			}
		}
		
		usort( $ibforums->cache['emoticons'] , array( 'post', 'smilie_alpha_sort' ) );
		
		foreach( $ibforums->cache['emoticons'] as $a_id => $elmo )
		{
			if ( $elmo['emo_set'] != $ibforums->skin['_emodir'] )
			{
				continue;
			}
			
			if ( ! $elmo['clickable'] )
			{
				continue;
			}
					
			$show_table++;
			$count++;
			
			//-----------------------------------------
			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o
			//-----------------------------------------
			
			if (strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
				$out_delim = "'";
			}
			else
			{
				$in_delim  = "'";
				$out_delim = '"';
			}
			
			$smilies .= "<td><a href={$out_delim}javascript:emoticon($in_delim".$elmo['typed']."$in_delim){$out_delim}><img src=\"".$ibforums->vars['EMOTICONS_URL']."/".$elmo['image']."\" alt='smilie' border='0' /></a>&nbsp;</td>\n";
			
			if ($count == $ibforums->vars['emo_per_row'])
			{
				$smilies .= "</tr>\n\n<tr align='center'>";
				$count = 0;
			}
		}
		
		if ($count != $ibforums->vars['emo_per_row'])
		{
			for ($i = $count ; $i < $ibforums->vars['emo_per_row'] ; ++$i)
			{
				$smilies .= "<td>&nbsp;</td>\n";
			}
			$smilies .= "</tr>";
		}
		
		$table = $this->html->smilie_table();
		
		if ($show_table != 0)
		{
			$table   = preg_replace( "/<!--THE SMILIES-->/", $smilies, $table );
			$in_html = preg_replace( "/<!--SMILIE TABLE-->/", $table, $in_html );
		}
		
		return $in_html;
	}
		
	/*-------------------------------------------------------------------------*/
	// HTML: topic summary.
	// ------------------
	// displays the last 10 replies to the topic we're
	// replying in.
	/*-------------------------------------------------------------------------*/
	
	function html_topic_summary($topic_id)
	{
		global $ibforums, $std, $DB;
		
		if (! $topic_id ) return;
		
		$cached_members = array();
		
		$this->output .= $this->html->TopicSummary_top();
		
		//-----------------------------------------
		// Get the posts
		// This section will probably change at some point
		//-----------------------------------------
		
		$DB->cache_add_query( 'post_get_topic_review', array( 'tid' => $topic_id ) );
							 
		$post_query = $DB->cache_exec_query();
		
		while ( $row = $DB->fetch_row($post_query) )
		{
		    $row['author'] = $row['author_name'];
			
			$row['date']   = $std->get_date( $row['post_date'], 'LONG' );
			
			if (!$ibforums->member['view_img'])
			{
				// unconvert smilies first, or it looks a bit crap.
				
				$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );
				
				$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>)", $row['post'] );
			}
			
			$this->parser->pp_do_html  = ( $this->forum['use_html'] and $ibforums->cache['group_cache'][ $row['mgroup'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
			$this->parser->pp_wordwrap = $ibforums->vars['post_wordwrap'];
			$this->parser->pp_nl2br    = $row['post_htmlstate'] == 2 ? 1 : 0;
			
			$row['post'] = $this->parser->post_db_parse( $row['post'] );
			
			$row['post'] = str_replace( "<br>", "<br />", $row['post'] );
			
			$this->output .= $this->html->TopicSummary_body( $row );
		}
		
		$this->output .= $this->html->TopicSummary_bottom();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Get used space so far
	//
	/*-------------------------------------------------------------------------*/
	
	function _get_attachment_sum()
	{
		global $ibforums, $std, $DB;
		
		if ( $this->attach_sum == -1 )
		{
			$stats = $DB->simple_exec_query( array( 'select' => 'sum(attach_filesize) as sum',
												    'from'   => 'attachments',
												    'where'  => 'attach_member_id='.$ibforums->member['id'] ) );
												    
			$this->attach_sum = intval( $stats['sum'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Build Upload Area - yay
	/*-------------------------------------------------------------------------*/
	
	function html_build_uploads($post_key="",$type="",$pid="")
	{
		global $ibforums, $std, $DB;
		
		$this->_get_attachment_sum();
		
		if ( $ibforums->member['g_attach_max'] > 0 )
		{
			$size = intval( ( $ibforums->member['g_attach_max'] * 1024 ) - $this->attach_sum );
			$size = $size < 0 ? 0 : $size;
			$main_space_left = $std->size_format( $size );
		}
		else
		{
			$main_space_left = $ibforums->lang['upload_unlimited'];
		}
												
		$upload_field = $this->html->Upload_field(  $main_space_left );
		
		if ( $post_key != "" )
		{
			//-----------------------------------------
			// Check for current uploads based on temp
			// key
			//-----------------------------------------
			
			if ( ! is_array( $this->cur_post_attach ) or ! count( $this->cur_post_attach ) )
			{
				$DB->simple_construct( array( "select" => '*', 'from' => 'attachments', 'where' => "attach_post_key='$post_key'") );
				$DB->simple_exec();
				
				while ( $r = $DB->fetch_row() )
				{
					$this->cur_post_attach[] = $r;
				}
			}
			
			if ( is_array( $this->cur_post_attach ) and count( $this->cur_post_attach ) )
			{ 
				$upload_tmp  = $this->html->uploadbox_tabletop();
				$upload_size = 0;
				
				foreach( $this->cur_post_attach as $row )
				{
					$upload_size += $row['attach_filesize'];
					$row['image'] = $ibforums->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'];
					$row['size']  = $std->size_format( $row['attach_filesize'] );
					
					if ( strlen( $row['attach_file'] ) > 40 )
					{
						$row['attach_file'] = substr( $row['attach_file'], 0, 35 ) .'...';
					}
					
					$upload_tmp .= $this->html->uploadbox_entry($row);
				}
				
				$space_used  = $std->size_format( intval( $upload_size ) );
				
				if ( $ibforums->member['g_attach_max'] > 0 )
				{
					if ( $ibforums->member['g_attach_per_post'] )
					{
						//-----------------------------------------
						// Max + per post: show per post
						//-----------------------------------------
						
						$space_left = $std->size_format( intval( ( $ibforums->member['g_attach_per_post'] * 1024 ) - $upload_size ) );
					}
					else
					{
						//-----------------------------------------
						// Max + no per post: Show max
						//-----------------------------------------
						
						$space_left = $std->size_format( intval( ( $ibforums->member['g_attach_max'] * 1024 ) - $upload_size - $this->attach_sum ) );
					}
				}
				else
				{
					if ( $ibforums->member['g_attach_per_post'] )
					{
						//-----------------------------------------
						// No Max + per post: show per post
						//-----------------------------------------
						
						$space_left = $std->size_format( intval( ( $ibforums->member['g_attach_per_post'] * 1024 ) - $upload_size ) );
					}
					else
					{
						//-----------------------------------------
						// No Max + no per post: Show unlimited
						//-----------------------------------------
						
						$space_left = $ibforums->lang['upload_unlimited'];
					}
				}
				
				$upload_text = sprintf( $ibforums->lang['attach_space_left'], $space_used, $space_left );
				
				$upload_tmp .= $this->html->uploadbox_tableend( $upload_text );
			}
		}
		
		if ( $upload_tmp )
		{
			$upload_field = str_replace( '<!--IBF.UPLOADED_ITEMS-->', $upload_tmp, $upload_field );
		}
		
		return $upload_field;
	}
	
	/*-------------------------------------------------------------------------*/
	// Moderators log
	// ------------------
	// Simply adds the last action to the mod logs
	/*-------------------------------------------------------------------------*/
	
	function moderate_log($title = 'unknown', $topic_title)
	{
		global $std, $ibforums, $DB, $HTTP_REFERER, $QUERY_STRING;
		
		$DB->do_insert( 'moderator_logs', array (
												'forum_id'    => $ibforums->input['f'],
												'topic_id'    => $ibforums->input['t'],
												'post_id'     => $ibforums->input['p'],
												'member_id'   => $ibforums->member['id'],
												'member_name' => $ibforums->member['name'],
												'ip_address'  => $ibforums->input['IP_ADDRESS'],
												'http_referer'=> $HTTP_REFERER,
												'ctime'       => time(),
												'topic_title' => $topic_title,
												'action'      => $title,
												'query_string'=> $QUERY_STRING,
										     ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for new topic
	/*-------------------------------------------------------------------------*/
	
	function check_for_new_topic( $topic=array() )
	{
		global $ibforums, $std, $DB;
		
		if (! $ibforums->member['g_post_new_topics'])
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_starting' ) );
		}
		
		if ( $std->check_perms($this->forum['start_perms']) == FALSE )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_starting' ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for reply
	/*-------------------------------------------------------------------------*/
	
	function check_for_reply( $topic=array() )
	{
		global $ibforums, $std, $DB;
		
		if ($topic['poll_state'] == 'closed' and $ibforums->member['g_is_supadmin'] != 1)
		{
			$std->Error( array( 'LEVEL' => '1', MSG => 'no_replies') );
		}
		
		if ($topic['starter_id'] == $ibforums->member['id'])
		{
			if (! $ibforums->member['g_reply_own_topics'])
			{
				$std->Error( array( 'LEVEL' => '1', MSG => 'no_replies') );
			}
		}
		
		if ($topic['starter_id'] != $ibforums->member['id'])
		{
			if (! $ibforums->member['g_reply_other_topics'])
			{
				$std->Error( array( 'LEVEL' => '1', MSG => 'no_replies') );
			}
		}

		if ( $std->check_perms($this->forum['reply_perms']) == FALSE )
		{
			$std->Error( array( 'LEVEL' => '1', MSG => 'no_replies') );
		}
		
		// Is the topic locked?
		
		if ($topic['state'] != 'open')
		{
			if ($ibforums->member['g_post_closed'] != 1)
			{
				$std->Error( array( 'LEVEL' => '1', MSG => 'locked_topic') );
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for edit
	/*-------------------------------------------------------------------------*/
	
	function check_for_edit( $topic=array() )
	{
		global $ibforums, $std, $DB;
		
		//-----------------------------------------
		// Is the topic locked?
		//-----------------------------------------
		
		if (($topic['state'] != 'open') and (!$ibforums->member['g_is_supmod']))
		{
			if ($ibforums->member['g_post_closed'] != 1)
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'locked_topic' ) );
			}
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// process upload
	// ------------------
	// checks for an entry in the upload field, and uploads
	// the file if it meets our criteria. This also inserts
	// a new row into the attachments database if successful
	/*-------------------------------------------------------------------------*/
	
	function process_upload()
	{
		global $ibforums, $std, $DB;
		
		//-----------------------------------------
		// Got attachment types?
		//-----------------------------------------
		
		if ( ! is_array( $ibforums->cache['attachtypes'] ) )
		{
			$ibforums->cache['attachtypes'] = array();
				
			$DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
			$DB->simple_exec();
		
			while ( $r = $DB->fetch_row() )
			{
				$ibforums->cache['attachtypes'][ $r['atype_extension'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// Set up array
		//-----------------------------------------
		
		$attach_data = array( 
							  'attach_ext'            => "",
							  'attach_file'           => "",
							  'attach_location'       => "",
							  'attach_thumb_location' => "",
							  'attach_hits'           => "",
							  'attach_date'           => time(),
							  'attach_temp'           => 0,
							  'attach_pid'            => "",
							  'attach_post_key'       => $ibforums->input['post_key'],
							  'attach_member_id'      => $ibforums->member['id'],
							  'attach_filesize'       => 0,
							);
		
		if ( ($this->can_upload != 1) or ($ibforums->member['g_attach_max'] == -1 ) )
		{
			return $attach_data;
		}
		
		//-----------------------------------------
		// Space left...
		//-----------------------------------------
		
		$this->_get_attachment_sum();
		
		$this->cur_post_attach = array();
		$this->per_post_count  = 0;
	
		if ( $ibforums->input['post_key'] )
		{
			$DB->simple_construct( array( "select" => '*', 'from' => 'attachments', 'where' => "attach_post_key='{$ibforums->input['post_key']}'") );
			$DB->simple_exec();
			
			while( $r = $DB->fetch_row() )
			{
				$this->per_post_count   += $r['attach_filesize'];
				$this->cur_post_attach[] = $r;
			}
		}
		
		if ( $ibforums->member['g_attach_max'] > 0 )
		{
			if ( $ibforums->member['g_attach_per_post'] )
			{
				$main_space_left = intval( ( $ibforums->member['g_attach_per_post'] * 1024 ) - $this->per_post_count );
			}
			else
			{
				$main_space_left = intval( ( $ibforums->member['g_attach_max'] * 1024 ) - $this->attach_sum );
			}
		}
		else
		{
			if ( $ibforums->member['g_attach_per_post'] )
			{
				$main_space_left = intval( ( $ibforums->member['g_attach_per_post'] * 1024 ) - $this->per_post_count );
			}
			else
			{
				$main_space_left = 1000000000;
			}
		}
					
		//-----------------------------------------
		// Load the library
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_upload.php' );
		$upload = new class_upload();
		
		//-----------------------------------------
		// Set up the variables
		//-----------------------------------------
		
		$upload->out_file_name    = 'post-'.$ibforums->member['id'].'-'.time();
		$upload->out_file_dir     = $ibforums->vars['upload_dir'];
		$upload->max_file_size    = $main_space_left;
		$upload->make_script_safe = 1;
		$upload->force_data_ext   = 'ipb';
		
		//-----------------------------------------
		// Populate allowed extensions
		//-----------------------------------------
		
		if ( is_array( $ibforums->cache['attachtypes'] ) and count( $ibforums->cache['attachtypes'] ) )
		{
			foreach( $ibforums->cache['attachtypes'] as $idx => $data )
			{
				if ( $data['atype_post'] )
				{
					$upload->allowed_file_ext[] = $data['atype_extension'];
				}
			}
		}
		
		//-----------------------------------------
		// Upload...
		//-----------------------------------------
		
		$upload->upload_process();
		
		//-----------------------------------------
		// Error?
		//-----------------------------------------
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{
				case 1:
					// No upload
					return $attach_data;
				case 2:
					// Invalid file ext
					$this->obj['post_errors'] = 'invalid_mime_type';
					return $attach_data;
				case 3:
					// Too big...
					$this->obj['post_errors'] = 'upload_to_big';
					return $attach_data;
				case 4:
					// Cannot move uploaded file
					$this->obj['post_errors'] = 'upload_failed';
					return $attach_data;
			}
		}
					
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		if ( $upload->saved_upload_name and @file_exists( $upload->saved_upload_name ) )
		{
			$attach_data['attach_filesize']   = @filesize( $upload->saved_upload_name  );
			$attach_data['attach_location']   = $upload->parsed_file_name;
			$attach_data['attach_file']       = $upload->original_file_name;
			$attach_data['attach_is_image']   = $upload->is_image;
			$attach_data['attach_ext']        = $upload->real_file_extension;
			
			if ( $attach_data['attach_is_image'] == 1 )
			{
				$thumb_data = $this->create_thumbnail( $attach_data );
				
				if ( $thumb_data['thumb_location'] )
				{
					$attach_data['attach_thumb_width']    = $thumb_data['thumb_width'];
					$attach_data['attach_thumb_height']   = $thumb_data['thumb_height'];
					$attach_data['attach_thumb_location'] = $thumb_data['thumb_location'];
				}
			}
			
			$DB->do_insert( 'attachments', $attach_data );
			
			$newid = $DB->get_insert_id();
			
			$attach_data['attach_id'] = $newid;
			
			$this->per_post_count    += $attach_data['attach_filesize'];
			$this->cur_post_attach[]  = $attach_data;
			
			return $newid;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Create thumbnail
	/*-------------------------------------------------------------------------*/
	
	function create_thumbnail( $data )
	{
		global $DB, $ibforums, $std;
		
		//-----------------------------------------
		// Load class
		//-----------------------------------------
		
		$return = array();
		
		require_once( KERNEL_PATH.'class_image.php' );
		$image = new class_image();
		
		$image->in_type        = 'file';
		$image->out_type       = 'file';
		$image->in_file_dir    = $ibforums->vars['upload_dir'];
		$image->in_file_name   = $data['attach_location'];
		$image->desired_width  = $ibforums->vars['siu_width'];
		$image->desired_height = $ibforums->vars['siu_height'];
		$image->gd_version     = $ibforums->vars['gd_version'];
		
		if ( $ibforums->vars['siu_thumb'] )
		{
			$return = $image->generate_thumbnail();
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Increment user's post
	// ------------------
	// if +1 post, +1 member's cumulative
	/*-------------------------------------------------------------------------*/
	
	function pf_increment_user_post_count()
	{
		global $ibforums, $DB, $std;
		
		$pcount = "";
		$mgroup = "";
		
		if ($ibforums->member['id'])
		{
			if ($this->forum['inc_postcount'])
			{
				// Increment the users post count
				
				$pcount = "posts=posts+1, ";
			}
			
			// Are we checking for auto promotion?
			
			if ($ibforums->member['g_promotion'] != '-1&-1')
			{
				list($gid, $gposts) = explode( '&', $ibforums->member['g_promotion'] );
				
				if ( $gid > 0 and $gposts > 0 )
				{
					if ( $ibforums->member['posts'] + 1 >= $gposts )
					{
						$mgroup = "mgroup='$gid', ";
						
						if ( USE_MODULES == 1 )
						{
							$this->modules->register_class(&$this);
							$this->modules->on_group_change($ibforums->member['id'], $gid);
						}
					}
				}
			}
			
			$ibforums->member['last_post'] = time();
			
			$DB->simple_construct( array( 'update' => 'members',
										  'set'    => $pcount.$mgroup." last_post=".intval($ibforums->member['last_post']),
										  'where'  => 'id='.$ibforums->member['id']
								 )      );
								 
			$DB->simple_exec();
		}	
	}
	
	/*-------------------------------------------------------------------------*/
	// Update forum's last information
	// ------------------
	// ^^ proper chrimbo!
	/*-------------------------------------------------------------------------*/
	
	function pf_update_forum_and_stats($tid, $title, $type='new')
	{
		global $ibforums, $DB, $std;
		
		$moderated = 0;
		
		//-----------------------------------------
		// Moderated?
		//-----------------------------------------
		
		if ( $this->obj['moderate'] )
		{
			if ( $type == 'new' and ( $this->obj['moderate'] == 1 or $this->obj['moderate'] == 2 ) )
			{
				$moderate = 1;
			}
			else if ( $type == 'reply' and ( $this->obj['moderate'] == 1 or $this->obj['moderate'] == 3 ) )
			{
				$moderate = 1;
			}
		}
		
		//-----------------------------------------
		// Add to forum's last post?
		//-----------------------------------------
		
		if ( ! $moderate )
		{
			$dbs = array( 'last_title'       => $title,
						  'last_id'          => $tid,
						  'last_post'        => time(),
						  'last_poster_name' => $ibforums->member['id'] ?  $ibforums->member['name'] : $ibforums->input['UserName'],
						  'last_poster_id'   => $ibforums->member['id'],
					   );
		
			if ( $type == 'new' )
			{
				$ibforums->cache['stats']['total_topics']++;
				
				$this->forum['topics'] = intval($this->forum['topics']);
				$dbs['topics']         = ++$this->forum['topics'];
			}
			else
			{
				$ibforums->cache['stats']['total_replies']++;
				
				$this->forum['posts'] = intval($this->forum['posts']);
				$dbs['posts']         = ++$this->forum['posts'];
			}
		}
		else
		{
			if ( $type == 'new' )
			{
				$this->forum['queued_topics'] = intval($this->forum['queued_topics']);
				$dbs['queued_topics']         = ++$this->forum['queued_topics'];
			}
			else
			{
				$this->forum['queued_posts'] = intval($this->forum['queued_posts']);
				$dbs['queued_posts']         = ++$this->forum['queued_posts'];
			}
		}
		
		$DB->do_update( 'forums', $dbs, "id=".intval($this->forum['id']) );
		
		//-----------------------------------------
		// Update forum cache
		//-----------------------------------------
		
		$std->update_forum_cache();
		
		$std->update_cache( array( 'name' => 'stats'      , 'array' => 1, 'deletefirst' => 0, 'donow' => 1 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment
	// ------------------
	// ^^ proper new year!
	/*-------------------------------------------------------------------------*/
	
	function pf_remove_attachment($aid, $post_key)
	{
		global $ibforums, $DB, $std;
		
		$DB->simple_construct( array( "select" => '*', 'from' => 'attachments',  'where' => "attach_post_key='$post_key' AND attach_id=$aid") );
		$o = $DB->simple_exec();
		
		if ( $killmeh = $DB->fetch_row( $o ) )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $ibforums->vars['upload_dir']."/".$killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $ibforums->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
			}
			
			$DB->simple_construct( array( 'delete' => 'attachments', 'where' => "attach_id={$killmeh['attach_id']}" ) );
			$DB->simple_exec();
			
			//-----------------------------------------
			// Remove from post
			//-----------------------------------------
			
			$_POST['Post'] = str_replace( '[attachmentID='.$aid.']', '', $_POST['Post'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert temp uploads into permanent ones! YAY
	// ------------------
	// ^^ proper chinese new year!
	/*-------------------------------------------------------------------------*/
	
	function pf_make_attachments_permanent($post_key="", $tid="", $pid="", $msg="")
	{
		global $ibforums, $DB, $std;
		
		//-----------------------------------------
		// Delete old unattached uploads
		//-----------------------------------------
		
		$time_cutoff = time() - 7200;
		$deadid      = array();
		
		$DB->simple_construct( array( "select" => '*', 'from' => 'attachments',  'where' => "attach_pid=0 and attach_msg=0 and attach_date < $time_cutoff") );
		$DB->simple_exec();
		
		while( $killmeh = $DB->fetch_row() )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $ibforums->vars['upload_dir']."/".$killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $ibforums->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
			}
			
			$deadid[] = $killmeh['attach_id'];
		}
		
		if ( count($deadid) )
		{
			$DB->simple_construct( array( 'delete' => 'attachments', 'where' => "attach_id IN(".implode( ",",$deadid ).")" ) );
			$DB->simple_exec();
		}
		
		if ( $post_key AND ( $pid or $msg ) )
		{
			$DB->simple_construct( array( "select" => 'count(*) as cnt', 'from' => 'attachments',  'where' => "attach_post_key='{$post_key}'") );
			$DB->simple_exec();
		
			$cnt = $DB->fetch_row();
			
			if ( $cnt['cnt'] )
			{
				if ( $msg != "" )
				{
					$DB->simple_construct( array( 'update' => 'attachments', 'set' => "attach_msg={$msg}", 'where' => "attach_post_key='{$post_key}'" ) );
					$DB->simple_exec();
				}
				else
				{
					$DB->simple_construct( array( 'update' => 'attachments', 'set' => "attach_pid={$pid}", 'where' => "attach_post_key='{$post_key}'" ) );
					$DB->simple_exec();
				
					$DB->simple_construct( array( 'update' => 'topics', 'set' => "topic_hasattach=topic_hasattach+{$cnt['cnt']}", 'where' => "tid={$tid}" ) );
					$DB->simple_exec();
				}
			}
		}
		
		return $cnt['cnt'];
	}
	
	/*-------------------------------------------------------------------------*/
	// Recount how many attachments a topic has
	// ------------------
	//
	/*-------------------------------------------------------------------------*/
	
	function pf_recount_topic_attachments($tid="")
	{
		global $ibforums, $DB, $std;
		
		if ($tid == "")
		{
			return;
		}
		
		//-----------------------------------------
		// GET PIDS
		//-----------------------------------------
		
		$pids  = array();
		$count = 0;
		
		$DB->simple_construct( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id=$tid" ) );
		$DB->simple_exec();
				
		while ( $p = $DB->fetch_row() )
		{
			$pids[] = $p['pid'];
		}
		
		//-----------------------------------------
		// GET ATTACHMENT COUNT
		//-----------------------------------------
		
		if ( count($pids) )
		{
			$DB->simple_construct( array( "select" => 'count(*) as cnt', 'from' => 'attachments',  'where' => "attach_pid IN(".implode(",",$pids).")") );
			$DB->simple_exec();
			
			$cnt = $DB->fetch_row();
			
			$count = intval( $cnt['cnt'] );
		}
		
		$DB->simple_construct( array( 'update' => 'topics', 'set' => "topic_hasattach=$count", 'where' => "tid={$tid}" ) );
		$DB->simple_exec();
	}
	
	/*-------------------------------------------------------------------------*/
	// Check out the tracker whacker
	// ------------------
	// ^^ proper er... May Day!
	/*-------------------------------------------------------------------------*/
	
	function pf_add_tracked_topic($tid="",$check_first=0)
	{
		global $ibforums, $DB, $std;
		
		if ( ! $tid )
		{
			return;
		}
		
		if ( $ibforums->member['id'] AND $ibforums->input['enabletrack'] == 1 )
		{
			if ( $check_first )
			{
				$DB->simple_construct( array( 'select' => 'trid', 'from' => 'tracker', 'where' => "topic_id=".intval($tid)." AND member_id=".$ibforums->member['id'] ) );
				$DB->simple_exec();
				
				if ( $DB->get_num_rows() )
				{
					//-----------------------------------------
					// Already tracking...
					//-----------------------------------------
					
					return;
				}
			}
				
			$DB->do_insert( 'tracker', array (
											  'member_id'        => $ibforums->member['id'],
											  'topic_id'         => $tid,
											  'start_date'       => time(),
											  'topic_track_type' => $ibforums->member['auto_track'] ? $ibforums->member['auto_track'] : 'delayed' ,
									)       );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean topic title
	// ------------------
	// ^^ proper er... um
	/*-------------------------------------------------------------------------*/
	
	function pf_clean_topic_title($title="")
	{
		global $ibforums, $DB, $std;
		
		if ($ibforums->vars['etfilter_punct'])
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}
		
		if ($ibforums->vars['etfilter_shout'])
		{
			$title = ucwords(strtolower($title));
		}
		
		return $title;
	}
	
	/*-------------------------------------------------------------------------*/
	// QUOTIN' DA' POSTAY IN DO HoooD'
	/*-------------------------------------------------------------------------*/
	
	function check_multi_quote()
	{
		global $DB, $ibforums, $std, $forums;
		
		$add_tags = 0;
		
		if ( ! $ibforums->input['qpid'] )
		{
			$ibforums->input['qpid'] = preg_replace( "/[^,\d]/", "", trim($std->my_getcookie('mqtids')) );
			
			if ($ibforums->input['qpid'] == ",")
			{
				$ibforums->input['qpid'] = "";
			}
		}
		else
		{
			//-----------------------------------------
			// Came from reply button
			//-----------------------------------------
			
			$ibforums->input['parent_id'] = $ibforums->input['qpid'];
		}
		
		
		if ( $ibforums->input['qpid'] )
		{
			$std->my_setcookie('mqtids', ',', 0);
			
			$this->quoted_pids = preg_split( '/,/', $ibforums->input['qpid'], -1, PREG_SPLIT_NO_EMPTY );
			
			//-----------------------------------------
			// Do we have right and snapback in BBCode?
			//-----------------------------------------
			
			if ( is_array( $ibforums->cache['bbcode'] ) and count( $ibforums->cache['bbcode'] ) )
			{
				foreach( $ibforums->cache['bbcode'] as $id => $data )
				{
					if ( $data['bbcode_tag'] == 'snapback' )
					{
						$add_tags++;
					}
					
					if ( $data['bbcode_tag'] == 'right' )
					{
						$add_tags++;
					}
					
					if ( $add_tags == 2 )
					{
						break;
					}
				}
			}
			
			//-----------------------------------------
			// Get the posts from the DB and ensure we have
			// suitable read permissions to quote them
			//-----------------------------------------
			
			if ( count($this->quoted_pids) )
			{
				$DB->cache_add_query( 'post_get_quoted', array( 'quoted_pids' => $this->quoted_pids ) );
				$DB->cache_exec_query();
				
				while ( $tp = $DB->fetch_row() )
				{
					if ( $std->check_perms( $forums->forum_by_id[ $tp['forum_id'] ]['read_perms']) == TRUE )
					{
						$tmp_post = trim( $this->parser->unconvert( $tp['post'] ) );
				
						if ($ibforums->vars['strip_quotes'])
						{
							$tmp_post = preg_replace( "#\[QUOTE(=.+?,.+?)?\].+?\[/QUOTE\]#is", "", $tmp_post );
							
							$tmp_post = preg_replace( "#(?:\n|\r){3,}#s", "\n", trim($tmp_post) );
						}
						
						$extra = "";
						
						if ( $add_tags == 2 )
						{
							$extra = '[right][snapback]'.$tp['pid'].'[/snapback][/right]'."\n";
						}
				
						$raw_post .= '[quote='.str_replace(']', chr(173).']', $tp['author_name']).','.$std->get_date( $tp['post_date'], 'LONG', 1 ).']'."\n$tmp_post\n".$extra.'[/quote]'."\n\n\n";
					}
				}
				
				$raw_post = trim($raw_post)."\n";
			}
		}
		
		if ( $_POST['Post'] )
		{
			//-----------------------------------------
			// Raw post from preview?
			//-----------------------------------------
		
			$raw_post .= isset($_POST['Post']) ? $std->txt_htmlspecialchars($_POST['Post']) : "";
	
			if (isset($raw_post))
			{
				$raw_post = $std->txt_raw2form($raw_post);
			}
		}
		
		return $raw_post;
	}
	
	function smilie_alpha_sort($a, $b)
	{
		return strcmp( $a['typed'], $b['typed'] );
	}
}

?>