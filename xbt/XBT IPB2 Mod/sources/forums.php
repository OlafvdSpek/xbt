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
|   > Forum topic index module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class forums {

    var $output       = "";
    var $base_url     = "";
    var $html         = "";
    var $moderator    = array();
    var $forum        = array();
    var $mods         = array();
    var $show_dots    = "";
    var $nav_extra    = "";
    var $read_array   = array();
    var $board_html   = "";
    var $sub_output   = "";
    var $pinned_print = 0;
    var $new_posts    = 0;
    var $is_mod       = 0;
    var $auth_key     = 0;
    var $announce_out = "";
    var $pinned_topic_count = 0;
    
    /*-------------------------------------------------------------------------*/
	// Init functions
	/*-------------------------------------------------------------------------*/
	
	function init()
	{
		global $ibforums, $std, $DB;
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_forum', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_forum');
        
        $this->auth_key = $std->return_md5_check();
        
        if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));
        }
        
        //-----------------------------------------
		// Multi TIDS?
		// If st is not defined then kill cookie
		// st will always be defined across pages
		//-----------------------------------------
		
		if ( ! isset( $ibforums->input['st'] ) )
		{
			$std->my_setcookie('modtids', ',', 0);
			$ibforums->input['selectedtids'] = "";
		}
		else
		{
			$ibforums->input['selectedtids'] = $std->my_getcookie('modtids');
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Our constructor, load words, load skin, get DB forum/cat data
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		global $ibforums, $forums, $DB, $std, $print;
       
        //-----------------------------------------
        // Are we doing anything with "site jump?"
        //-----------------------------------------
        
        switch( $ibforums->input['f'] )
        {
        	case 'sj_home':
        		$std->boink_it($ibforums->base_url."act=idx");
        		break;
        	case 'sj_search':
        		$std->boink_it($ibforums->base_url."act=Search");
        		break;
        	case 'sj_help':
        		$std->boink_it($ibforums->base_url."act=Help");
        		break;
        	default:
        		$ibforums->input['f'] = intval($ibforums->input['f']);
        		break;
        }
        
        $this->init();
        
        //-----------------------------------------
        // Get the forum info based on the forum ID,
        // and get the category name, ID, etc.
        //-----------------------------------------
        
        $this->forum = $forums->forum_by_id[ $ibforums->input['f'] ]; 
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if (! $this->forum['id'] )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }
        
        //-----------------------------------------
        // Is it a redirect forum?
        //-----------------------------------------
        
        if ( $this->forum['redirect_on'] )
        {
        	$redirect = $DB->simple_exec_query( array( 'select' => 'redirect_url', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
        	
        	if ( $redirect['redirect_url'] )
        	{
        		//-----------------------------------------
				// Update hits:
				//-----------------------------------------
				
				$DB->simple_exec_query( array( 'update' => 'forums', 'set' => 'redirect_hits=redirect_hits+1', 'where' => "id=".$this->forum['id']) );
				
				//-----------------------------------------
				// Recache forum
				//-----------------------------------------
				
				$ibforums->cache['forum_cache'][ $this->forum['id'] ][ 'redirect_hits' ] = $this->forum['redirect_hits'] + 1;
				
				//-----------------------------------------
				// Turn off shutdown queries to get this
				// parsed before the redirect
				//-----------------------------------------
				
				$DB->obj['use_shutdown'] = 0;
				
				$std->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 0 ) );
				
				//-----------------------------------------
				// Boink!
				//-----------------------------------------
				
				$std->boink_it( $redirect['redirect_url'] );
				
				// Game over man!
        	}
        }
        
        //-----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //-----------------------------------------
        
        $this->nav = $forums->forums_breadcrumb_nav( $this->forum['id'] );
        
		$this->forum['FORUM_JUMP'] = $std->build_forum_jump();
		
		//-----------------------------------------
		// Check forum access perms
		//-----------------------------------------
		
		if ( ! $ibforums->input['L'] )
		{
			$forums->forums_check_access( $this->forum['id'], 1 );
		}
		
		//-----------------------------------------
        // Are we viewing the forum, or viewing the forum rules?
        //-----------------------------------------
        
        if ( $ibforums->input['act'] == 'SR' )
        {
        	$this->show_rules();
        }
        else
        {
			$this->show_subforums();
			
			if ( $this->forum['sub_can_post'] )
			{
				$this->show_forum();
			}
			else
			{
				//-----------------------------------------
				// No forum to show, just use the HTML in $this->sub_output
				// or there will be no HTML to use in the str_replace!
				//-----------------------------------------
				
				$this->output  = $std->print_forum_rules($this->forum);
				$this->output .= $this->sub_output;
			}
        }
        
        //-----------------------------------------
		// Subforums
		//-----------------------------------------
		
		if ($this->sub_output != "")
		{
			$this->output = str_replace( "<!--IBF.SUBFORUMS-->", $this->sub_output, $this->output );
		}
		
		if ( $this->announce_out )
		{
			$this->output = str_replace( "<!--IBF.ANNOUNCEMENTS-->", $this->announce_out, $this->output );
		}
		
		$print->add_output($this->output);
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->forum['name'],
        					 	  'JS'       => 0,
        					 	  'NAV'      => $this->nav,
        				 )      );
     }
     
     //-----------------------------------------
	 // Display any sub forums
	 //-----------------------------------------
     
     function show_subforums()
     {
		global $std, $DB, $ibforums, $forums;
		
		if ( $forums->read_topic_only == 1 )
		{
			//$this->sub_output = "";
			//return;
		}
		
		require_once( ROOT_PATH.'sources/boards.php' );
		
		$boards = new boards();
		
		$this->sub_output = $boards->show_subforums($ibforums->input['f']);
    }
    
    /*-------------------------------------------------------------------------*/
	// Show the forum rules on a separate page
	/*-------------------------------------------------------------------------*/
        
	function show_rules()
	{
		global $DB, $ibforums, $std, $print, $forums;
		
		//-----------------------------------------
		// Do we have permission to view these rules?
		//-----------------------------------------
		
		$bad_entry = $forums->forums_check_access($this->forum['id'], 1);
        
        if ($bad_entry == 1)
        {
        	$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
        }
        
        $tmp = $DB->simple_exec_query( array( 'select' => 'rules_title, rules_text', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
             
        if ( $tmp['rules_title'] )
        {
        	$rules['title'] = $tmp['rules_title'];
        	$rules['body']  = $tmp['rules_text'];
        	$rules['fid']   = $this->forum['id'];
        	
        	$this->output .= $this->html->show_rules($rules);
        	
			$print->add_output("$this->output");
			$print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -&gt; ".$this->forum['name'],
									  'JS'       => 0,
									  'NAV'      => array( 
														   $this->forum['name']
														 ),
								  ) );
		}
		else
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum view check for authentication
	/*-------------------------------------------------------------------------*/
   
	function show_forum()
	{
		global $ibforums;
   		
		// are we checking for user authentication via the log in form
		// for a private forum w/password protection?
		
		$ibforums->input['L'] == 1 ? $this->authenticate_user() : $this->render_forum();
	}
	
	/*-------------------------------------------------------------------------*/
	// Authenicate the log in for a password protected forum
	/*-------------------------------------------------------------------------*/
	
	function authenticate_user()
	{
		global $std, $ibforums, $print;
		
		if ($ibforums->input['f_password'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_blank' ) );
		}
		
		if ( $ibforums->input['f_password'] != $this->forum['password'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
		}
		
		$std->my_setcookie( "ipbforumpass_".$this->forum['id'], md5($ibforums->input['f_password']) );
		
		$print->redirect_screen( $ibforums->lang['logged_in'] , "showforum=".$this->forum['id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Main render forum engine
	/*-------------------------------------------------------------------------*/
	
	function render_forum()
	{
		global $ibforums, $forums, $DB, $std, $print;
		
		//-----------------------------------------
	    // Are we actually a moderator for this forum?
	    //-----------------------------------------
	    
	    if ( ! $ibforums->member['g_is_supmod'] AND ! $ibforums->member['g_access_cp'] )
	    {
	    	if ( ! is_array( $ibforums->member['_moderator'][ $this->forum['id'] ] ) )
	    	{
	    		$ibforums->member['is_mod'] = 0;
	    	}
	    }
	    
		//-----------------------------------------
		// Announcements
		//-----------------------------------------
		
		if ( is_array( $ibforums->cache['announcements'] ) and count( $ibforums->cache['announcements'] ) )
		{
			$announcements = array();
			
			foreach( $ibforums->cache['announcements'] as $id => $announce )
			{
				$order = $announce['announce_start'] ? $announce['announce_start'].','.$announce['announce_id'] : $announce['announce_id'];
				
				if (  $announce['announce_forum'] == '*' )
				{
					$announcements[ $order ] = $announce;
				}
				else if ( strstr( ','.$announce['announce_forum'].',', ','.$this->forum['id'].',' ) )
				{
					$announcements[ $order ] = $announce;
				}
			}
			
			if ( count( $announcements ) )
			{
				//-----------------------------------------
				// sort by start date
				//-----------------------------------------
				
				$announce_html = "";
				
				rsort( $announcements );
				
				foreach( $announcements as $id => $announce )
				{
					if ( $announce['announce_start'] )
					{
						$announce['announce_start'] = gmdate( 'jS F Y', $announce['announce_start'] );
					}
					else
					{
						$announce['announce_start'] = '--';
					}
					
					$announce['announce_title'] = $std->txt_stripslashes($announce['announce_title']);
					$announce['forum_id']       = $this->forum['id'];
					$announce['announce_views'] = intval($announce['announce_views']);
					$announce_html .= $this->html->announcement_row( $announce );
				}
				
				$this->announce_out = $this->html->announcement_wrap($announce_html);
			}
		}
		
		//-----------------------------------------
		// Read topics
		//-----------------------------------------
		
		$First = intval($ibforums->input['st']);
		
		$ibforums->input['last_visit'] = $ibforums->forum_read[ $ibforums->input['f'] ] > $ibforums->input['last_visit']
        						       ? $ibforums->forum_read[ $ibforums->input['f'] ] : $ibforums->input['last_visit'];
		
		//-----------------------------------------
		// Over ride with 'master' cookie?
		//-----------------------------------------
		
		if ( $ibforums->forum_read[0] > $ibforums->forum_read[ $ibforums->input['f'] ] )
		{
			$ibforums->forum_read[ $ibforums->input['f'] ] = $ibforums->forum_read[0];
		}
		
		//-----------------------------------------
		// Sort options
		//-----------------------------------------
		
		$prune_value = $std->select_var( array( 
												1 => $ibforums->input['prune_day'],
												2 => $this->forum['prune']        ,
												3 => '100'                        )
									    );

		$sort_key    = $std->select_var( array(
												1 => $ibforums->input['sort_key'],
												2 => $this->forum['sort_key']    ,
												3 => 'last_post'                 )
									   );

		$sort_by     = $std->select_var( array(
												1 => $ibforums->input['sort_by'],
												2 => $this->forum['sort_order'] ,
												3 => 'Z-A'                      )
									   );
									  
		$topicfilter = $ibforums->input['topicfilter'] ? $ibforums->input['topicfilter'] : 'all';
    
		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------
		
		$Prune = $prune_value != 100 ? (time() - ($prune_value * 60 * 60 * 24)) : 0;

		$sort_keys   =  array( 'last_post'         => 'sort_by_date',
							   'last_poster_name'  => 'sort_by_last_poster',
							   'title'             => 'sort_by_topic',
							   'starter_name'      => 'sort_by_poster',
							   'start_date'        => 'sort_by_start',
							   'topic_hasattach'   => 'sort_by_attach',
							   'posts'             => 'sort_by_replies',
							   'views'             => 'sort_by_views',
							   
							 );

		$prune_by_day = array( '1'    => 'show_today',
							   '5'    => 'show_5_days',
							   '7'    => 'show_7_days',
							   '10'   => 'show_10_days',
							   '15'   => 'show_15_days',
							   '20'   => 'show_20_days',
							   '25'   => 'show_25_days',
							   '30'   => 'show_30_days',
							   '60'   => 'show_60_days',
							   '90'   => 'show_90_days',
							   '100'  => 'show_all',
							 );

		$sort_by_keys = array( 'Z-A'  => 'descending_order',
                         	   'A-Z'  => 'ascending_order',
                             );
                             
        $filter_keys  = array( 'all'    => 'topicfilter_all',
        					   'open'   => 'topicfilter_open',
        					   'hot'    => 'topicfilter_hot',
        					   'poll'   => 'topicfilter_poll',
        					   'locked' => 'topicfilter_locked',
        					   'moved'  => 'topicfilter_moved',
        					 );
        					 
        if ( $ibforums->member['id'] )
        {
        	$filter_keys['istarted'] = 'topicfilter_istarted';
        	$filter_keys['ireplied'] = 'topicfilter_ireplied';
        }
                         
        //-----------------------------------------
        // check for any form funny business by wanna-be hackers
		//-----------------------------------------
		
		if ( (!isset($filter_keys[$topicfilter])) or (!isset($sort_keys[$sort_key])) or (!isset($prune_by_day[$prune_value])) or (!isset($sort_by_keys[$sort_by])) )
		{
			   $std->Error( array( LEVEL=> 5, MSG =>'incorrect_use') );
	    }
	    
	    $r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';
	    
	    //-----------------------------------------
	    // Additional queries?
	    //-----------------------------------------
	    
	    $add_query_array = array();
	    $add_query       = "";
	    
	    switch( $topicfilter )
	    {
	    	case 'all':
	    		break;
	    	case 'open':
	    		$add_query_array[] = "state='open'";
	    		break;
	    	case 'hot':
	    		$add_query_array[] = "state='open' AND posts + 1 >= ".intval($ibforums->vars['hot_topic']);
	    		break;
	    	case 'locked':
	    		$add_query_array[] = "state='closed'";
	    		break;
	    	case 'moved':
	    		$add_query_array[] = "state='link'";
	    		break;
	    	case 'poll':
	    		$add_query_array[] = "poll_state='open'";
	    		break;
	    	default:
	    		break;
	    }
	    
	    if ( ! $ibforums->member['g_other_topics'] or $topicfilter == 'istarted' )
		{
            $add_query_array[] = "starter_id='".$ibforums->member['id']."'";
		}
		
		if ( count($add_query_array) )
		{
			$add_query = ' AND '. implode( ' AND ', $add_query_array );
		}
		
		//-----------------------------------------
		// Moderator?
		//-----------------------------------------
		
		if ( ! $ibforums->member['is_mod'] )
		{
			$approved = 'and approved=1';
		}
		else
		{
			$approved = '';
		}
		
		//-----------------------------------------
		// Query the database to see how many topics there are in the forum
		//-----------------------------------------
		
		if ( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			//-----------------------------------------
			
			if ( $Prune )
			{
				$prune_filter = "and (pinned=1 or last_post > $Prune)";
			}
			else
			{
				$prune_filter = "";
			}
			
			$DB->cache_add_query( 'forums_get_replied_topics', array( 'mid'          => $ibforums->member['id'],
																	  'fid'          => $this->forum['id'],
																	  'approved'     => $approved,
																	  'prune_filter' => $prune_filter ) );
			$DB->cache_exec_query();
			
			$total_possible = $DB->fetch_row();
		}
		else if ( ( $add_query or $Prune ) and ! $ibforums->input['modfilter'] )
		{
			$DB->simple_construct( array( 'select' => 'COUNT(*) as max',
										  'from'   => 'topics ',
										  'where'  => "forum_id=".$this->forum['id']." {$approved} and (pinned=1 or last_post > $Prune)" . $add_query
								 )      );

			$DB->simple_exec();
			
			$total_possible = $DB->fetch_row();
		}
		else 
		{
			$total_possible['max'] = $this->forum['topics'];
			$Prune = 0;
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$this->forum['SHOW_PAGES']
			= $std->build_pagelinks( array( 'TOTAL_POSS'  => $total_possible['max'],
											'PER_PAGE'    => $ibforums->vars['display_max_topics'],
											'CUR_ST_VAL'  => $ibforums->input['st'],
											'L_SINGLE'    => $ibforums->lang['single_page_forum'],
											'BASE_URL'    => $ibforums->base_url."showforum=".$this->forum['id']."&amp;prune_day=$prune_value&amp;sort_by=$sort_by&amp;sort_key=$sort_key&amp;topicfilter={$topicfilter}",
										  )
								   );
								   
								   
		//-----------------------------------------
		// Do we have any rules to show?
		//-----------------------------------------
		
		 $this->output .= $std->print_forum_rules($this->forum);
		
		//-----------------------------------------
		// Generate the poll button
		//-----------------------------------------
		   
		$this->forum['POLL_BUTTON'] = $this->forum['allow_poll']
										 ? "<a href='".$ibforums->base_url."act=Post&amp;CODE=10&amp;f=".$this->forum['id']."'><{A_POLL}></a>"
										 : '';
	
		//-----------------------------------------
		// Start printing the page
		//-----------------------------------------
		
		$this->output .= $this->html->PageTop($this->forum);
		
		//-----------------------------------------
		// Do we have any topics to show?
		//-----------------------------------------
		
		if ($total_possible['max'] < 1)
		{
			$this->output .= $this->html->show_no_matches();
		}
		
		$total_topics_printed = 0;
		
		//-----------------------------------------
		// Get main topics
		//-----------------------------------------
		
		$topic_array = array();
		$topic_ids   = array();
		$topic_sort  = "";
        
        //-----------------------------------------
        // Mod filter?
        //-----------------------------------------
        
		if ( $ibforums->input['modfilter'] == 'invisible_topics' and $ibforums->member['is_mod'] )
		{
			$topic_sort = 'approved asc,';
		}
		else if ( $ibforums->input['modfilter'] == 'invisible_posts' and $ibforums->member['is_mod'] )
		{
			$topic_sort = 'topic_queuedposts desc,';
		}
		else if ( $ibforums->input['modfilter'] == 'all' and $ibforums->member['is_mod'] )
		{
			$topic_sort = 'approved asc, topic_queuedposts desc,';
		}
		
		//-----------------------------------------
		// Cut off?
		//-----------------------------------------
		
		$parse_dots = 1;
		
		if ( $Prune )
		{
			$query = "forum_id=".$this->forum['id']." {$approved} and (last_post > $Prune OR pinned=1)";
		}
		else
		{
			$query = "forum_id=".$this->forum['id']." {$approved}";
		}
		
		if ( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			// No point in getting dots again...
			//-----------------------------------------
			
			$parse_dots = 0;
			
			$DB->cache_add_query( 'forums_get_replied_topics_actual', array( 'mid'          => $ibforums->member['id'],
																			 'fid'          => $this->forum['id'],
																			 'query'        => $query,
																			 'topic_sort'   => $topic_sort,
																			 'sort_key'     => $sort_key,
																			 'r_sort_by'    => $r_sort_by,
																			 'limit_a'      => intval($First),
																			 'limit_b'      => intval($ibforums->vars['display_max_topics']) ) );
			$DB->cache_exec_query();
		}
		else
		{
			$DB->simple_construct( array( 'select' => '*',
										  'from'   => 'topics',
										  'where'  => $query . $add_query,
										  'order'  => 'pinned desc, '.$topic_sort.' '.$sort_key .' '. $r_sort_by,
										  'limit'  => array( intval($First), $ibforums->vars['display_max_topics'] )
								 )      );
			$DB->simple_exec();
		}
		
		while ( $t = $DB->fetch_row() )
		{
			$topic_array[ $t['tid'] ] = $t;
			$topic_ids[ $t['tid'] ] = $t['tid'];
		}
		
		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------
		
		if ( ($ibforums->vars['show_user_posted'] == 1) and ($ibforums->member['id']) and ( count($topic_ids) ) and ( $parse_dots ) )
		{
			$DB->simple_construct( array( 'select' => 'topic_id, author_id',
										  'from'   => 'posts',
										  'where'  => "topic_id IN(".implode( ",", $topic_ids ).") AND author_id=".$ibforums->member['id'],
								)      );
									  
			$DB->simple_exec();
			
			while( $p = $DB->fetch_row() )
			{
				if ( is_array( $topic_array[ $p['topic_id'] ] ) )
				{
					$topic_array[ $p['topic_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Get topic trackers table? I guess we could JOIN this but...
		//-----------------------------------------
		
		if ( $ibforums->vars['db_topic_read_cutoff'] and count($topic_ids) )
		{
			$DB->simple_construct( array( 'select' => 'read_tid, read_date',
										  'from'   => 'topics_read',
										  'where'  => "read_tid IN(".implode( ",", $topic_ids ).") AND read_mid=".$ibforums->member['id'],
								)      );
									  
			$DB->simple_exec();
			
			while( $p = $DB->fetch_row() )
			{
				if ( is_array( $topic_array[ $p['read_tid'] ] ) )
				{
					$topic_array[ $p['read_tid'] ]['db_read'] = $p['read_date'];
				}
			}
		} 
		
		//-----------------------------------------
		// Show meh the topics!
		//-----------------------------------------
		
		foreach( $topic_array as $tid => $topic )
		{
			if ( $topic['pinned'] )
			{
				$this->pinned_topic_count++;
			}
			
			$this->output .= $this->render_entry( $topic );
			
			$total_topics_printed++;
		}
		
		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$topicfilter]))
		//-----------------------------------------
		
		foreach ($sort_by_keys as $k => $v)
		{
			$sort_by_html   .= $k == $sort_by     ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $sort_by_keys[ $k ] ] . "</option>\n"
											      : "<option value='$k'>"                     . $ibforums->lang[ $sort_by_keys[ $k ] ] . "</option>\n";
		}
	
		foreach ($sort_keys as  $k => $v)
		{
			$sort_key_html  .= $k == $sort_key    ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $sort_keys[ $k ] ]    . "</option>\n"
											      : "<option value='$k'>"                     . $ibforums->lang[ $sort_keys[ $k ] ]    . "</option>\n";
		}
		
		foreach ($prune_by_day as  $k => $v)
		{
			$prune_day_html .= $k == $prune_value ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $prune_by_day[ $k ] ] . "</option>\n"
												  : "<option value='$k'>"                     . $ibforums->lang[ $prune_by_day[ $k ] ] . "</option>\n";
		}
		
		foreach ($filter_keys as  $k => $v)
		{
			$filter_html    .= $k == $topicfilter ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $filter_keys[ $k ] ]  . "</option>\n"
												  : "<option value='$k'>"                     . $ibforums->lang[ $filter_keys[ $k ] ]  . "</option>\n";
		}
	
		$ibforums->show['sort_by']      = $sort_key_html;
		$ibforums->show['sort_order']   = $sort_by_html;
		$ibforums->show['sort_prune']   = $prune_day_html;
		$ibforums->show['topic_filter'] = $filter_html;
	
		$this->output .= $this->html->TableEnd($this->forum, $this->auth_key);
		
		//-----------------------------------------
		// Multi-moderation?
		//-----------------------------------------
		
		if ( $ibforums->member['is_mod'] )
		{
			$mm_array = $std->get_multimod( $this->forum['id'] );
			
			if ( is_array( $mm_array ) and count( $mm_array ) )
			{
				foreach( $mm_array as $m )
				{
					$mm_html .= $this->html->mm_entry( $m[0], $m[1] );
				}
			}
			
			if ( $mm_html )
			{
				$this->output = str_replace( '<!--IBF.MMOD-->', $this->html->mm_start() . $mm_html . $this->html->mm_end(), $this->output );
			}
		}
		
		//-----------------------------------------
		// If all the new topics have been read in this forum..
		//-----------------------------------------
		
		if ($this->new_posts < 1)
		{
			$ibforums->forum_read[ $this->forum['id'] ] = time();
			
			$std->hdl_forum_read_cookie('set');
		}
		
		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------
		
		if ($ibforums->vars['no_au_forum'] != 1)
		{
			//-----------------------------------------
			// Get the users
			//-----------------------------------------
			
			$cut_off = ($ibforums->vars['au_cutoff'] != "") ? $ibforums->vars['au_cutoff'] * 60 : 900;
			 
			$time = time() - $cut_off;
			
			$DB->cache_add_query('forums_get_active_users',  array( 'fid' => $this->forum['id'], 'time' => $time ) );
			$DB->simple_exec();
			
			//-----------------------------------------
			// ACTIVE USERS
			//-----------------------------------------
			
			$cached = array();
			$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");
			$rows   = array( 0 => array( 'login_type'   => substr($ibforums->member['login_anonymous'],0, 1),
										 'running_time' => time(),
										 'member_id'    => $ibforums->member['id'],
										 'member_name'  => $ibforums->member['name'],
										 'member_group' => $ibforums->member['mgroup'] ) );
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ($r = $DB->fetch_row() )
			{
				$rows[] = $r;
			}
			
			//-----------------------------------------
			// PRINT...
			//-----------------------------------------
			
			foreach( $rows as $i => $result )
			{
				$result['suffix'] = $ibforums->cache['group_cache'][ $result['member_group'] ]['suffix'];
				$result['prefix'] = $ibforums->cache['group_cache'][ $result['member_group'] ]['prefix'];
				
				if ($result['member_id'] == 0)
				{
					$active['guests']++;
				}
				else
				{
					if (empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						
						$p_start = "";
						$p_end   = "";
						$p_title = " title='reading...' ";
						
						if ( strstr( $result['location'], 'Post,' ) )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = " title='posting...' ";
						}
						
						if ($result['login_type'] == 1)
						{
							if ( ($ibforums->member['mgroup'] == $ibforums->vars['admin_group']) and ($ibforums->vars['disable_admin_anon'] != 1) )
							{
								$active['names'] .= "$p_start<a href='{$ibforums->base_url}showuser={$result['member_id']}'$p_title>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>*$p_end, ";
								$active['anon']++;
							}
							else
							{
								$active['anon']++;
							}
						}
						else
						{
							$active['members']++;
							$active['names'] .= "$p_start<a href='{$ibforums->base_url}showuser={$result['member_id']}'$p_title>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>$p_end, ";
						}
					}
				}
			}
			
			$active['names'] = preg_replace( "/,\s+$/", "" , $active['names'] );
			
			$ibforums->lang['active_users_title']   = sprintf( $ibforums->lang['active_users_title']  , ($active['members'] + $active['guests'] + $active['anon'] ) );
			$ibforums->lang['active_users_detail']  = sprintf( $ibforums->lang['active_users_detail'] , $active['guests'],$active['anon'] );
			$ibforums->lang['active_users_members'] = sprintf( $ibforums->lang['active_users_members'], $active['members'] );
			
			$this->output = str_replace( "<!--IBF.FORUM_ACTIVE-->", $this->html->forum_active_users($active), $this->output );
		
		}
		
		if ( ! $this->pinned_topic_count and $this->announce_out )
		{
			$this->announce_out .= $this->html->render_pinned_end();
		}
		
		return TRUE;
    }
    
    /*-------------------------------------------------------------------------*/
	// Parse data
	/*-------------------------------------------------------------------------*/
	
	function parse_data( $topic )
	{
		global $ibforums, $DB, $std;
		
		//-----------------------------------------
		// Get a real ID so that moved
		// topic don't break owt
		//-----------------------------------------
		
		$topic['real_tid'] = $topic['tid'];
		$last_time         = 0;
		
		//-----------------------------------------
		// Using DB?
		//-----------------------------------------
		
		if ( $ibforums->member['id'] and $ibforums->vars['db_topic_read_cutoff'] AND ($topic['last_post'] > intval($ibforums->forum_read[ $topic['forum_id'] ])) )
		{
			$db_topic_read_cutoff = time() - $ibforums->vars['db_topic_read_cutoff'] * 86400;
			
			if ( $topic['last_post'] > $db_topic_read_cutoff )
			{
				//-----------------------------------------
				// Have we read this topic before?
				//-----------------------------------------
				
				if ( $topic['db_read'] )
				{
					$last_time = $topic['db_read'];
				}
				
				//-----------------------------------------
				// No? Must be a new one then
				//-----------------------------------------
				
				else
				{
					$last_time = 1;
				}
			}
		}
		
		//-----------------------------------------
		// Not reading from DB or past out tracking limit
		//-----------------------------------------
		
		if ( ! $last_time )
		{
			$last_time = $this->read_array[$topic['tid']] > $ibforums->input['last_visit'] ? $this->read_array[$topic['tid']] : $ibforums->input['last_visit'];
			
			if ( $ibforums->forum_read[ $topic['forum_id'] ] > $last_time )
			{
				$last_time = $ibforums->forum_read[ $topic['forum_id'] ];
			}
		}
		
		//-----------------------------------------
		// Attachy ment
		//-----------------------------------------
		
		if ( $topic['topic_hasattach'] )
		{
			$topic['attach_img'] = $this->html->topic_attach_icon($topic['tid'], intval($topic['topic_hasattach']));
		}
		
		//-----------------------------------------
		// Yawn
		//-----------------------------------------
		
		$topic['last_text']   = $ibforums->lang['last_post_by'];
		
		$topic['last_poster'] = $topic['last_poster_id'] ? $std->make_profile_link( $topic['last_poster_name'], $topic['last_poster_id']) : "-".$topic['last_poster_name']."-";
								
		$topic['starter']     = $topic['starter_id']     ? $std->make_profile_link( $topic['starter_name'], $topic['starter_id']) : "-".$topic['starter_name']."-";
	 
		if ($topic['poll_state'])
		{
			$topic['prefix']  = $ibforums->vars['pre_polls'].' ';
		}
		
		if ( ($ibforums->member['id']) and ($topic['author_id']) )
		{
			$show_dots = 1;
		}
	
		$topic['folder_img']     = $std->folder_icon( $topic, $show_dots, $last_time );
		
		$topic['topic_icon']     = $topic['icon_id']  ? '<img src="'.$ibforums->vars['img_url'] . '/folder_post_icons/icon' . $topic['icon_id'] . '.gif" border="0" alt="" />'
													  : '&nbsp;';
		
		$topic['start_date'] = $std->get_date( $topic['start_date'], 'LONG' );
	
		//-----------------------------------------
		// Pages 'n' posts
		//-----------------------------------------
		
		$pages = 1;
		
		if ( $ibforums->member['is_mod'] )
		{
			$topic['posts'] += intval($topic['topic_queuedposts']);
		}
		
		if ($topic['posts'])
		{
			if ( (($topic['posts'] + 1) % $ibforums->vars['display_max_posts']) == 0 )
			{
				$pages = ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'];
			}
			else
			{
				$number = ( ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'] );
				$pages = ceil( $number);
			}
		}
		
		if ($pages > 1)
		{
			for ($i = 0 ; $i < $pages ; ++$i )
			{
				$real_no = $i * $ibforums->vars['display_max_posts'];
				$page_no = $i + 1;
				
				if ($page_no == 4 and $pages > 4)
				{
					$topic['PAGES'] .= $this->html->pagination_show_lastpage($topic['tid'], ($pages - 1) * $ibforums->vars['display_max_posts'], $pages);
					break;
				}
				else
				{
					$topic['PAGES'] .= $this->html->pagination_show_page($topic['tid'], $real_no , $page_no);
				}
			}
			
			$topic['PAGES'] = $this->html->pagination_wrap_pages($topic['tid'], $topic['PAGES'], $topic['posts'] + 1, $ibforums->vars['display_max_posts']);
		}
		
		//-----------------------------------------
		// Format some numbers
		//-----------------------------------------
		
		$topic['posts']  = $std->do_number_format( intval($topic['posts']) );
		$topic['views']	 = $std->do_number_format( intval($topic['views']) );
		
		//-----------------------------------------
		// Last time stuff...
		//-----------------------------------------
		
		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			$this->new_posts++;
			$topic['go_new_post']  = "<a href='{$ibforums->base_url}showtopic={$topic['tid']}&amp;view=getnewpost'><{NEW_POST}></a>";
		}
		else
		{
			$topic['go_new_post']  = "";
		}
	
		$topic['last_post']  = $std->get_date( $topic['last_post'], 'SHORT' );
		
		//-----------------------------------------
		// Linky pinky!
		//-----------------------------------------
			
		if ($topic['state'] == 'link')
		{
			$t_array = explode("&", $topic['moved_to']);
			$topic['tid']       = $t_array[0];
			$topic['forum_id']  = $t_array[1];
			$topic['title']     = $topic['title'];
			$topic['views']     = '--';
			$topic['posts']     = '--';
			$topic['prefix']    = $ibforums->vars['pre_moved']." ";
			$topic['go_new_post'] = "";
		}
		else
		{
			$topic['posts'] = $this->html->who_link($topic['tid'], $topic['posts']);
		}
		
		if ( ( $ibforums->member['g_is_supmod'] or $ibforums->member['_moderator'][ $data['id'] ]['post_q'] == 1 ) and ( $topic['topic_queuedposts'] ) )
		{
			$topic['_hasqueued'] = 1;
		}
		
		//-----------------------------------------
		// Already switched on?
		//-----------------------------------------
		
		if ( $ibforums->member['is_mod'] )
		{
			if ( $ibforums->input['selectedtids'] )
			{
				if ( strstr( ','.$ibforums->input['selectedtids'].',', ','.$topic['tid'].',' ) )
				{
					$topic['tidon'] = 1;
				}
				else
				{
					$topic['tidon'] = 0;
				}
			}
		}
		
		return $topic;
	}
	
	/*-------------------------------------------------------------------------*/
	// Crunches the data into pwetty html
	/*-------------------------------------------------------------------------*/

	function render_entry($topic)
	{
		global $DB, $std, $ibforums;
		
		$topic = $this->parse_data( $topic );
		
		$p_start    = "";
		$p_end      = "";
		$class1     = "row2";
		$class2     = "row1";
		$classposts = "row2";
		
		if ( $ibforums->member['is_mod'] )
		{
			if ( ! $topic['approved'] )
			{
				$class1     = 'row4shaded';
				$class2     = 'row2shaded';
				$classposts = 'row4shaded';
			}
			else if ( $topic['_hasqueued'] )
			{
				$classposts = 'row4shaded';
			}
		}
		
		if ($topic['pinned'] == 1)
		{
			$topic['prefix'] = $ibforums->vars['pre_pinned'];
			
			if ($this->pinned_print == 0)
			{
				// we've a pinned topic, but we've not printed the pinned
				// starter row, so..
				
				$show    = $this->announce_out ? 1 : 0;
				$p_start = $this->html->render_pinned_start( $show );
				
				$this->pinned_print = 1;
			}
			
			return $p_start . $this->html->render_forum_row( $topic, $class1, $class2, $classposts, 1 );
		}
		else
		{
			//-----------------------------------------
			// This is not a pinned topic, so lets check to see if we've
			// printed the footer yet.
			//-----------------------------------------
			
			if ($this->pinned_print == 1)
			{
				//-----------------------------------------
				// Nope, so..
				//-----------------------------------------
				
				$p_end = $this->html->render_pinned_end();
				
				$this->pinned_print = 0;
			}
			
			return $p_end . $this->html->render_forum_row( $topic, $class1, $class2, $classposts, 1 );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Returns the last action date
	/*-------------------------------------------------------------------------*/
	    
	function get_last_date($topic)
	{
		global $ibforums, $std;
		
		return $std->get_date( $topic['last_post'], 'SHORT' );
	}

}

?>