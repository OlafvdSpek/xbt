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
|   > Board index module
|   > Module written by Matt Mecham
|   > Date started: 17th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Boards;

class Boards {

    var $output   = "";
    var $base_url = "";
    var $html     = "";
    var $forums   = array();
    var $mods     = array();
    var $cats     = array();
    var $children = array();
    var $nav;
    
    var $news_topic_id = "";
    var $news_forum_id = "";
    var $news_title    = "";
    var $sep_char      = "";
    
    function Boards()
    {
        global $ibforums, $DB, $std, $print, $skin_universal;
        
        $this->base_url = $ibforums->base_url;

        // Get more words for this invocation!
        
        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_boards', $ibforums->lang_id);
        
        $this->html = $std->load_template('skin_boards');
        
        $this->sep_char = trim( $this->html->active_list_sep() );
        
        if (! $ibforums->member['id'] )
        {
        	$ibforums->input['last_visit'] = time();
        }
        
        $this->output .= $this->html->PageTop( $std->get_date( $ibforums->input['last_visit'], 'LONG' ) );
        
        // Get the forums and category info from the DB
        
        $last_c_id = -1;
        
        $DB->query("SELECT f.*, c.id as cat_id, c.position as cat_position, c.state as cat_state, c.name as cat_name, c.description as cat_desc,
        		   c.image, c.url, m.member_name as mod_name, m.member_id as mod_id, m.is_group, m.group_id, m.group_name, m.mid
        		   FROM ibf_forums f, ibf_categories c
        		     LEFT JOIN ibf_moderators m ON (f.id=m.forum_id)
        		   WHERE c.id=f.category
        		   ORDER BY c.position, f.position");
        		   
        		   
        while ( $r = $DB->fetch_row() )
        {
        	if ($last_c_id != $r['cat_id'])
        	{
        		$this->cats[ $r['cat_id'] ] = array( 'id'          => $r['cat_id'],
        											 'position'    => $r['cat_position'],
        											 'state'       => $r['cat_state'],
        											 'name'        => $r['cat_name'],
        											 'description' => $r['cat_desc'],
        											 'image'       => $r['image'],
        											 'url'         => $r['url'],
        										   );
        										   
        		$last_c_id = $r['cat_id'];
        	}
        	
        	if ($r['parent_id'] > 0)
			{
				$this->children[ $r['parent_id'] ][$r['id']] = $r;
			}
			else
			{
				$this->forums[ $r['id'] ] = $r;
			}
			
			if ($r['mod_id'] != "")
			{
				$this->mods[ $r['id'] ][ $r['mid'] ] = array( 'name' => $r['mod_name'],
															  'id'   => $r['mod_id'],
															  'isg'  => $r['is_group'],
															  'gname'=> $r['group_name'],
															  'gid'  => $r['group_id'],
															);
			}
        }
        
        //-----------------------------------
        // What are we doing?
        //-----------------------------------
        
        if ($ibforums->input['c'] != "")
        {
        	$this->show_single_cat();
        	$this->nav[] = $this->cats[ $ibforums->input['c'] ]['name'];
        }
        else
        {
        	$this->process_all_cats();
        }
        
        //*********************************************/
		// Add in show online users
		//*********************************************/
		
		$active = array( 'TOTAL'   => 0 ,
						 'NAMES'   => "",
						 'GUESTS'  => 0 ,
						 'MEMBERS' => 0 ,
						 'ANON'    => 0 ,
					   );
					   
		$stats_html = "";
		
		if ($ibforums->vars['show_active'])
		{
			
			if ($ibforums->vars['au_cutoff'] == "")
			{
				$ibforums->vars['au_cutoff'] = 15;
			}
			
			// Get the users from the DB
			
			$cut_off = $ibforums->vars['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			
			
			$DB->query("SELECT s.id, s.member_id, s.member_name, s.login_type, g.suffix, g.prefix
			            FROM ibf_sessions s
			              LEFT JOIN ibf_groups g ON (g.g_id=s.member_group)
			            WHERE running_time > $time
			            ORDER BY s.running_time DESC");
			
			// cache all printed members so we don't double print them
			
			$cached = array();
			
			while ($result = $DB->fetch_row() )
			{
				if ( strstr( $result['id'], '_session' ) )
				{
					if ( $ibforums->vars['spider_anon'] )
					{
						if ( $ibforums->member['mgroup'] == $ibforums->vars['admin_group'] )
						{
							$active['NAMES'] .= "{$result['member_name']}*{$this->sep_char} \n";
						}
					}
					else
					{
						$active['NAMES'] .= "{$result['member_name']}{$this->sep_char} \n";
					}
				}
				else if ($result['member_id'] == 0 )
				{
					$active['GUESTS']++;
				}
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						if ($result['login_type'] == 1)
						{
							if ( ($ibforums->member['mgroup'] == $ibforums->vars['admin_group']) and ($ibforums->vars['disable_admin_anon'] != 1) )
							{
								$active['NAMES'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>*{$this->sep_char} \n";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>{$this->sep_char} \n";
						}
					}
				}
			}
			
			$active['NAMES'] = preg_replace( "/".preg_quote($this->sep_char)."$/", "", trim($active['NAMES']) );
			
			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			// Show a link?
			
			if ($ibforums->vars['allow_online_list'])
			{
				$active['links'] = $this->html->active_user_links();
			}
			
			$ibforums->lang['active_users'] = sprintf( $ibforums->lang['active_users'], $ibforums->vars['au_cutoff'] );
			
			$stats_html .= $this->html->ActiveUsers($active, $ibforums->vars['au_cutoff']);
		}
			
		//-----------------------------------------------
		// Are we viewing the calendar?
		//-----------------------------------------------
		
		if ($ibforums->vars['show_birthdays'])
		{
			
			$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $std->get_time_offset() ) );
		
			$day   = $a[2];
			$month = $a[1];
			$year  = $a[0];
			
			$birthstring = "";
			$count       = 0;
			
			$DB->query("SELECT id, name, bday_day as DAY, bday_month as MONTH, bday_year as YEAR 
						FROM ibf_members WHERE bday_day=$day and bday_month=$month");
			
			while ( $user = $DB->fetch_row() )
			{
				$birthstring .= "<a href='{$this->base_url}showuser={$user['id']}'>{$user['name']}</a>";
				
				if ($user['YEAR'])
				{
					$pyear = $year - $user['YEAR'];  // $year = 2002 and $user['YEAR'] = 1976
					$birthstring .= "(<b>$pyear</b>)";
				}
				
				$birthstring .= $this->sep_char."\n";
				
				$count++;
			}
			
			$birthstring = preg_replace( "/".$this->sep_char."$/", "", trim($birthstring) );
			
			$lang = $ibforums->lang['no_birth_users'];
			
			if ($count > 0)
			{
				$lang = ($count > 1) ? $ibforums->lang['birth_users'] : $ibforums->lang['birth_user'];
				$stats_html .= $this->html->birthdays( $birthstring, $count, $lang  );
			}
			else
			{
				$count = "";
				
				if ( ! $ibforums->vars['autohide_bday'] )
				{
					$stats_html .= $this->html->birthdays( $birthstring, $count, $lang  );
				}
			}
		}
		
		
		//-----------------------------------------------
		// Are we viewing the calendar?
		//-----------------------------------------------
		
		if ($ibforums->vars['show_calendar'])
		{
		
			if ($ibforums->vars['calendar_limit'] < 2)
			{
				$ibforums->vars['calendar_limit'] = 2;
			}
			
			$our_unix         = time() + $std->get_time_offset();
			$max_date         = $our_unix + ($ibforums->vars['calendar_limit'] * 86400);
			
			$DB->query("SELECT eventid, title, read_perms, priv_event, userid, unix_stamp
			            FROM ibf_calendar_events WHERE unix_stamp > $our_unix and unix_stamp < $max_date ORDER BY unix_stamp ASC");
			
			$show_events = array();
			
			while ($event = $DB->fetch_row())
			{
				if ($event['priv_event'] == 1 and $ibforums->member['id'] != $event['userid'])
				{
					continue;
				}
				
				//-----------------------------------------
				// Do we have permission to see the event?
				//-----------------------------------------
				
				if ( $event['read_perms'] != '*' )
				{
					if ( ! preg_match( "/(^|,)".$ibforums->member['mgroup']."(,|$)/", $event['read_perms'] ) )
					{
						continue;
					}
				}
				
				$c_time = date( 'j-F-y', $event['unix_stamp']);
				
				$show_events[] = "<a href='{$ibforums->base_url}act=calendar&amp;code=showevent&amp;eventid={$event['eventid']}' title='$c_time'>".$event['title']."</a>";
			}
			
			$ibforums->lang['calender_f_title'] = sprintf( $ibforums->lang['calender_f_title'], $ibforums->vars['calendar_limit'] );
			
			if ( count($show_events) > 0 )
			{
				$event_string = implode( $this->sep_char.' ', $show_events );
				$stats_html .= $this->html->calendar_events( $event_string  );
			}
			else
			{
				if ( ! $ibforums->vars['autohide_calendar'] )
				{
					$event_string = $ibforums->lang['no_calendar_events'];
					$stats_html .= $this->html->calendar_events( $event_string  );
				}
			}
		}
		
		//*********************************************/
		// Add in show stats
		//*********************************************/
		
		
		if ($ibforums->vars['show_totals'])
		{
		
			$DB->query("SELECT * FROM ibf_stats");
			$stats = $DB->fetch_row();
			
			// Update the most active count if needed
			
			if ($active['TOTAL'] > $stats['MOST_COUNT'])
			{
				$DB->query("UPDATE ibf_stats SET MOST_DATE='".time()."', MOST_COUNT='".$active[TOTAL]."'");
				$stats['MOST_COUNT'] = $active[TOTAL];
				$stats['MOST_DATE']  = time();
			}
			
			$most_time = $std->get_date( $stats['MOST_DATE'], 'LONG' );
			
			$ibforums->lang['most_online'] = str_replace( "<#NUM#>" ,   $std->do_number_format($stats['MOST_COUNT'])  , $ibforums->lang['most_online'] );
			$ibforums->lang['most_online'] = str_replace( "<#DATE#>",                   $most_time                    , $ibforums->lang['most_online'] );
			
			$total_posts = $stats['TOTAL_REPLIES'] + $stats['TOTAL_TOPICS'];
			
			$total_posts        = $std->do_number_format($total_posts);
			$stats['MEM_COUNT'] = $std->do_number_format($stats['MEM_COUNT']);
			
			$link = $ibforums->base_url."showuser=".$stats['LAST_MEM_ID'];
			
			$ibforums->lang['total_word_string'] = str_replace( "<#posts#>" , "$total_posts"          , $ibforums->lang['total_word_string'] );
			$ibforums->lang['total_word_string'] = str_replace( "<#reg#>"   , $stats['MEM_COUNT']     , $ibforums->lang['total_word_string'] );
			$ibforums->lang['total_word_string'] = str_replace( "<#mem#>"   , $stats['LAST_MEM_NAME'] , $ibforums->lang['total_word_string'] );
			$ibforums->lang['total_word_string'] = str_replace( "<#link#>"  , $link                   , $ibforums->lang['total_word_string'] );
			
			$stats_html .= $this->html->ShowStats($ibforums->lang['total_word_string']);
			
		}
		
		if ($stats_html != "")
		{
			$this->output .= $this->html->stats_header();
			$this->output .= $stats_html;
			$this->output .= $this->html->stats_footer();
		}
		
		//---------------------------------------
		// Add in board info footer
		//---------------------------------------
		
		$this->output .= $this->html->bottom_links();
		
		//---------------------------------------
		// Check for news forum.
		//---------------------------------------
		
		if ($this->news_title and $this->news_topic_id and $this->news_forum_id)
		{
			$t_html = $this->html->newslink( $this->news_forum_id, stripslashes($this->news_title) , $this->news_topic_id);
			$this->output = str_replace( "<!-- IBF.NEWSLINK -->" , "$t_html" , $this->output );
		}
		
		//---------------------------------------
		// Display quick log in if we're not a member
		//---------------------------------------
		
		if ($ibforums->member['id'] < 1)
		{
			$this->output = str_replace( "<!--IBF.QUICK_LOG_IN-->" , $this->html->quick_log_in() , $this->output );
		}
		
		//---------------------------------------
		// Showing who's chatting?
		//---------------------------------------
		
		if ( $ibforums->vars['chat_account_no'] and $ibforums->vars['chat_who_on'] )
		{
			require_once( ROOT_PATH.'sources/lib/chat_functions.php' );
			
			$chat = new chat_functions();
			
			$chat->register_class( &$this );
			
			$chat_html = $chat->get_online_list();
			
			$this->output = str_replace( "<!--IBF.WHOSCHATTING-->", $chat_html, $this->output );
		}
		
		//---------------------------------------
		// Print as normal
		//---------------------------------------

        $print->add_output("$this->output");
        
        $cp = " (Powered by Invision Power Board)";
        
        if ($ibforums->vars['ips_cp_purchase'])
        {
        	$cp = "";
        }
        
        $print->do_output( array( 'TITLE' => $ibforums->vars['board_name'].$cp, 'JS' => 0, 'NAV' => $this->nav ) );
        
	}

    
	//*********************************************/
	//
	// PROCESS ALL CATEGORIES
	//
	//*********************************************/
	
	function process_all_cats() {
	
		global $std, $DB, $ibforums;    
        
        foreach ($this->cats as $cat_id => $cat_data)
        {
        
        	//----------------------------
        	// Is this category turned on?
        	//----------------------------
        
        	if ( $cat_data['state'] != 1 )
        	{
        		continue;
        	}
        
            foreach ($this->forums as $forum_id => $forum_data)
            {
                if ($forum_data['category'] == $cat_id)
                {
                	//-----------------------------------
                    // We store the HTML in a temp var so
                    // we can make sure we have cats for
                    // this forum, or hidden forums with a 
                    // cat will show the cat strip - we don't
                    // want that, no - we don't.
                    //-----------------------------------
                    
                    $temp_html .= $this->process_forum($forum_id, $forum_data);
                }
            }
            
            if ($temp_html != "")
            {
            	$this->output .= $this->html->CatHeader_Expanded($cat_data);
            	$this->output .= $temp_html;
            	$this->output .= $this->html->end_this_cat();
            }
            
            unset($temp_html);
        }
        
        $this->output .= $this->html->end_all_cats();
        
    }
 
 
	//*********************************************/
	//
	// SHOW A SINGLE CATEGORY
	//
	//*********************************************/   
    
	function show_single_cat() {
	
		global $std, $DB, $ibforums;    
        
        $cat_id = $ibforums->input['c'];
        
        if (!is_array( $this->cats[ $cat_id ] ) )
        {
        	$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
        }
        
        $cat_data = $this->cats[ $cat_id ];
        
		//----------------------------
		// Is this category turned on?
		//----------------------------
        
		if ( $cat_data['state'] == 0 )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}
	
		foreach ($this->forums as $forum_id => $forum_data)
		{
			if ($forum_data['category'] == $cat_id)
			{
				//-----------------------------------
				// We store the HTML in a temp var so
				// we can make sure we have cats for
				// this forum, or hidden forums with a 
				// cat will show the cat strip - we don't
				// want that, no - we don't.
				//-----------------------------------
				
				$temp_html .= $this->process_forum($forum_id, $forum_data);
			}
		}
		
		if ($temp_html != "")
		{
			$this->output .= $this->html->CatHeader_Expanded($cat_data);
			$this->output .= $temp_html;
			$this->output .= $this->html->end_this_cat();
		}
		else
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}
		unset($temp_html);
	
		$this->output .= $this->html->end_all_cats();
    }
    
	//*********************************************/
	//
	// RENDER A FORUM
	//
	//*********************************************/   
    
    function process_forum($forum_id="", $forum_data="")
    {
    	global $std, $ibforums;
    	
    	
    	if ($forum_data['subwrap'] == 1)
    	{
    	
    		$printed_children = 0;
    		$can_see_root = FALSE;
    		
    		//--------------------------------------
    		// This is a sub cat forum...
    		//--------------------------------------
    		
    		// Do we have any sub forums here?
    		
    		if ( (isset($this->children[ $forum_data['id'] ])) and (count($this->children[ $forum_data['id'] ]) > 0 ) )
    		{
    		
    			// Are we allowed to see the postable forum stuff?
    			
    			if ($forum_data['sub_can_post'] == 1 and $forum_data['redirect_on'] != 1)
    			{
    				if ( $std->check_perms($forum_data['read_perms']) )
					{
						$forum_data['fid'] = $forum_data['id'];
						$newest = $forum_data;
						$can_see_root = TRUE;
						
						if (isset($forum_data['last_title']) and $forum_data['last_id'] != "")
						{
							if ( ( $ibforums->vars['index_news_link'] == 1 ) and (! empty($ibforums->vars['news_forum_id']) ) and ($ibforums->vars['news_forum_id'] == $forum_data['id']) )
							{
								
							   $this->news_topic_id = $forum_data['last_id'];
							   $this->news_forum_id = $forum_data['id'];
							   $this->news_title    = $forum_data['last_title'];
								
							}
						}
					}
					else
					{
						$newest = array();
					}
				}
    		
    			foreach($this->children[ $forum_data['id'] ] as $idx => $data)
				{
    				//--------------------------------------
					// Check permissions...
					//--------------------------------------
					
					if ( $std->check_perms($data['read_perms']) != TRUE )
					{
						continue;
					}
					
					// Do the news stuff first
					
					if (isset($data['last_title']) and $data['last_id'] != "")
					{
						if ( ( $ibforums->vars['index_news_link'] == 1 ) and (! empty($ibforums->vars['news_forum_id']) ) and ($ibforums->vars['news_forum_id'] == $data['id']) )
						{
							
						   $this->news_topic_id = $data['last_id'];
						   $this->news_forum_id = $data['id'];
						   $this->news_title    = $data['last_title'];
							
						}
					}
					
					if ($data['last_post'] > $newest['last_post'])
					{
						
						$newest['last_post']        = $data['last_post'];
						$newest['fid']              = $data['id'];
						//$newest['id']               = $data['id'];
						$newest['last_id']          = $data['last_id'];
						$newest['last_title']       = $data['last_title'];
						$newest['password']         = $data['password'];
						$newest['last_poster_id']   = $data['last_poster_id'];
						$newest['last_poster_name'] = $data['last_poster_name'];
						$newest['status']           = $data['status'];
					}
					
					$newest['posts']  += $data['posts'];
					$newest['topics'] += $data['topics'];
					
					$printed_children++;
					
				}
				
				if ( ($printed_children < 1) && ($can_see_root != TRUE) )
				{
					// If we don't have permission to view any forums
					// and we can't post in this root forum
					// then simply return and the row won't be printed
					// 
					
					return "";
					
				}
				
				// Fix up the last of the data
				
				$newest['last_title'] = strip_tags($newest['last_title']);
				$newest['last_title'] = str_replace( "&#33;" , "!" , $newest['last_title'] );
				$newest['last_title'] = str_replace( "&quot;", "\"", $newest['last_title'] );
				
				if (strlen($newest['last_title']) > 30)
				{
					$newest['last_title'] = substr($newest['last_title'],0,27) . "...";
					$newest['last_title'] = preg_replace( '/&(#(\d+;?)?)?(\.\.\.)?$/', '...', $newest['last_title'] );
				}
				if ($newest['password'] != "")
				{
					$newest['last_topic'] = $ibforums->lang['f_protected'];
				}
				else if($newest['last_title'] != "")
				{
					$newest['last_unread'] = $this->html->forumrow_lastunread_link($newest['fid'], $newest['last_id']);
					$newest['last_topic'] = "<a href='{$ibforums->base_url}showtopic={$newest['last_id']}&amp;view=getnewpost' title='{$ibforums->lang['tt_gounread']}'>{$newest['last_title']}</a>";
				}
				else
				{
					$newest['last_topic'] = $ibforums->lang['f_none'];
				}
				
				if ( isset($newest['last_poster_name']))
				{
					$newest['last_poster'] = $newest['last_poster_id'] ? "<a href='{$ibforums->base_url}showuser={$newest['last_poster_id']}'>{$newest['last_poster_name']}</a>"
																       : $newest['last_poster_name'];
				}
				else
				{
					$newest['last_poster'] = $ibforums->lang['f_none'];
				}
				
				$newest['img_new_post'] = $std->forum_new_posts($newest, $printed_children > 0 ? 1: 0);
				
				if ( $newest['img_new_post'] == '<{C_ON_CAT}>' )
				{
					$newest['img_new_post'] = $this->html->subforum_img_with_link($newest['img_new_post'], $forum_data['id']);
				}
			
				$newest['last_post'] = $std->get_date($newest['last_post'], 'LONG');
				
				$newest['posts']  = $std->do_number_format($newest['posts']);
				$newest['topics'] = $std->do_number_format($newest['topics']);
				
				foreach($newest as $k => $v)
				{
					if ($k == 'id')
					{
						continue;
					}
					$forum_data[$k] = $v;
				}
    			
    			$forum_data['moderator'] = $this->get_moderators($forum_id);
    			
    			return $this->html->ForumRow($forum_data);
    			
    		}
    		else
    		{
    			return "";
    		}
    	
    	}
    	else
    	{
    	
			//--------------------------------------
			// Check permissions...
			//--------------------------------------
			
			if ( $std->check_perms($forum_data['read_perms']) != TRUE )
			{
				return "";
			}
			
			//--------------------------------------
			// Redirect only forum?
			//--------------------------------------
			
			if ( $forum_data['redirect_on'] )
			{
				// Simply return with the redirect information
				
				if ( $forum_data['redirect_loc'] != "" )
				{
					$forum_data['redirect_target'] = " target='".$forum_data['redirect_loc']."' ";
				}
				
				$forum_data['redirect_hits'] = $std->do_number_format($forum_data['redirect_hits']);
				
				return $this->html->forum_redirect_row($forum_data);
				
			}
			
			//--------------------------------------
			// No - normal forum..
			//--------------------------------------
			
			$forum_data['img_new_post'] = $std->forum_new_posts($forum_data);
			
			if ( $forum_data['img_new_post'] == '<{C_ON}>' )
			{
				$forum_data['img_new_post'] = $this->html->forum_img_with_link($forum_data['img_new_post'], $forum_data['id']);
			}
			
			$forum_data['last_post'] = $std->get_date($forum_data['last_post'], 'LONG');
						
			$forum_data['last_topic'] = $ibforums->lang['f_none'];
			
			if (isset($forum_data['last_title']) and $forum_data['last_id'])
			{
			
				if ( ( $ibforums->vars['index_news_link'] == 1 ) and (! empty($ibforums->vars['news_forum_id']) ) and ($ibforums->vars['news_forum_id'] == $forum_data['id']) )
				{
					
				   $this->news_topic_id = $forum_data['last_id'];
				   $this->news_forum_id = $forum_data['id'];
				   $this->news_title    = $forum_data['last_title'];
					
				}
				
				$forum_data['last_title'] = strip_tags($forum_data['last_title']);
				$forum_data['last_title'] = str_replace( "&#33;" , "!", $forum_data['last_title'] );
				$forum_data['last_title'] = str_replace( "&quot;", "\"", $forum_data['last_title'] );
					
				if (strlen($forum_data['last_title']) > 30)
				{
					$forum_data['last_title'] = substr($forum_data['last_title'],0,27) . "...";
					$forum_data['last_title'] = preg_replace( "/&(#(\d+;?)?)?\.\.\.$/", '...', $forum_data['last_title'] );
				}
				else
				{
					$forum_data['last_title'] = preg_replace( "/&(#(\d+?)?)?$/", '', $forum_data['last_title'] );
				}
				
				if ($forum_data['password'] != "")
				{
					$forum_data['last_topic'] = $ibforums->lang['f_protected'];
				}
				else
				{
					$forum_data['last_unread'] = $this->html->forumrow_lastunread_link($forum_data['id'], $forum_data['last_id']);
					$forum_data['last_topic']  = "<a href='{$ibforums->base_url}showtopic={$forum_data['last_id']}&amp;view=getnewpost' title='{$ibforums->lang['tt_gounread']}'>{$forum_data['last_title']}</a>";
				}
			}
			
							
			if ( isset($forum_data['last_poster_name']))
			{
				$forum_data['last_poster'] = $forum_data['last_poster_id'] ? "<a href='{$ibforums->base_url}showuser={$forum_data['last_poster_id']}'>{$forum_data['last_poster_name']}</a>"
																		   : $forum_data['last_poster_name'];
			}
			else
			{
				$forum_data['last_poster'] = $ibforums->lang['f_none'];
			}
	
			//---------------------------------
			// Moderators
			//---------------------------------
			
			$forum_data['moderator'] = $this->get_moderators($forum_data['id']);
			
			$forum_data['posts']  = $std->do_number_format($forum_data['posts']);
			$forum_data['topics'] = $std->do_number_format($forum_data['topics']);
			
			$forum_data['description'] = str_replace( "<br>", "<br />", $forum_data['description'] );
			
			return $this->html->ForumRow($forum_data);
		
		}
                    
	}
	
	//-------------------------------------
	//
	// Return mods for this forum in a 
	// HTML formatted string
	//
	//-------------------------------------
	
	function get_moderators($forum_id="")
	{
		global $ibforums, $std, $DB;
			
		$mod_string = "";
		
		if ($forum_id == "")
		{
			return "";
		}
		
		if (isset($this->mods[ $forum_id ] ) )
		{
			$mod_string = $ibforums->lang['forum_leader'].' ';
			
			if (is_array($this->mods[ $forum_id ]) )
			{
				foreach ($this->mods[ $forum_id ] as $moderator)
				{
					if ($moderator['isg'] == 1)
					{
						$mod_string .= "<a href='{$ibforums->base_url}act=Members&amp;max_results=30&amp;filter={$moderator['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0&amp;b=1'>{$moderator['gname']}</a>, ";
					}
					else
					{
						$mod_string .= "<a href='{$ibforums->base_url}showuser={$moderator['id']}'>{$moderator['name']}</a>, ";
					}
				}
				
				$mod_string = preg_replace( "!,\s+$!", "", $mod_string );
				
			}
			else
			{
				if ($moderator['isg'] == 1)
				{
					$mod_string .= "<a href='{$ibforums->base_url}act=Members&amp;max_results=30&amp;filter={$this->mods[$forum_id]['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0&amp;b=1'>{$this->mods[$forum_id]['gname']}</a>, ";
				}
				else
				{
					$mod_string .= "<a href='{$ibforums->base_url}showuser={$this->mods[$forum_id]['id']}'>{$this->mods[$forum_id]['name']}</a>";
				}
			}
		}
		
		return $mod_string;
		
	}
    
        
}

?>
