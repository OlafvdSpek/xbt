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
|   > Forum topic index module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Forums;

class Forums {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $mods      = array();
    var $show_dots = "";
    var $nav_extra = "";
    var $read_array = array();
    var $board_html = "";
    var $sub_output = "";
    var $pinned_print = 0;
    var $new_posts    = 0;

    //+----------------------------------------------------------------
	//
	// Our constructor, load words, load skin, get DB forum/cat data
	//
	//+----------------------------------------------------------------

    function Forums()
    {
        global $ibforums, $DB, $std, $print, $skin_universal;

        //+------------------------------------------
        // Are we doing anything with "site jump?"
        //+------------------------------------------

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

        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_forum', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_forum');

        //+------------------------------------------
        // Get the forum info based on the forum ID,
        // and get the category name, ID, etc.
        //+------------------------------------------

        $DB->query("SELECT f.*, c.id as cat_id, c.name as cat_name
        			FROM ibf_forums f
        			  LEFT JOIN ibf_categories c ON (c.id=f.category)
        			WHERE f.id=".$ibforums->input['f']);

        $this->forum = $DB->fetch_row();

        //----------------------------------------
        // Error out if we can not find the forum
        //----------------------------------------

        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }

        //----------------------------------------
        // Is it a redirect forum?
        //----------------------------------------

        if ( $this->forum['redirect_on'] and $this->forum['redirect_url'] )
        {
        	// Update hits:

        	$DB->query("UPDATE ibf_forums SET redirect_hits=redirect_hits+1 WHERE id=".$this->forum['id']);

        	// Boink!

        	$std->boink_it( $this->forum['redirect_url'] );

        	// Game over man!
        }

        //----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //----------------------------------------

        if ($this->forum['parent_id'] > 0)
        {

        	$DB->query("SELECT f.id as forum_id, f.name as forum_name, c.id, c.name FROM ibf_forums f, ibf_categories c WHERE f.id='".$this->forum['parent_id']."' AND c.id=f.category");

        	$row = $DB->fetch_row();

        	$this->forum['cat_id']   = $row['id'];
        	$this->forum['cat_name'] = $row['name'];

        	$this->nav_extra = "<a href='".$ibforums->base_url."showforum={$row['forum_id']}'>{$row['forum_name']}</a>";
        }

        //--------------------------------------------------------------------------------
        //--------------------------------------------------------------------------------

        $this->base_url = $ibforums->base_url;

		$this->forum['FORUM_JUMP'] = $std->build_forum_jump();

        // Are we viewing the forum, or viewing the forum rules?

        if ($ibforums->input['act'] == 'SR')
        {
        	$this->show_rules();
        }
        else
        {
        	if ($this->forum['subwrap'] == 1)
			{
				$this->show_subforums();

				if ($this->forum['sub_can_post'])
				{
					$this->show_forum();
				}
				else
				{
					// No forum to show, just use the HTML in $this->sub_output
					// or there will be no HTML to use in the str_replace!

					$this->output     = $this->sub_output;
					$this->sub_output = "";
				}
			}
			else
			{
        		$this->show_forum();
        	}
        }

        //+----------------------------------------------------------------
		// Print it
		//+----------------------------------------------------------------

		if ($this->sub_output != "")
		{
			$this->output = str_replace( "<!--IBF.SUBFORUMS-->", $this->sub_output, $this->output );
		}

		if ($ibforums->member['id'] > 0)
		{
			$this->output = str_replace( "<!--IBF.SUB_FORUM_LINK-->", $this->html->show_sub_link($this->forum['id']), $this->output );
		}

		if ( $ibforums->member['g_is_supmod'] OR $ibforums->member['is_mod'] )
		{
			if ( $this->forum['has_mod_posts'] )
			{
				$this->output = str_replace( "<!--IBF.MODLINK-->", $this->html->show_mod_link($this->forum['id']), $this->output );
			}
		}

		$print->add_output($this->output);
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->forum['name'],
        					 	  'JS'       => 0,
        					 	  'NAV'      => array(
        					 	  					   "<a href='".$this->base_url."act=SC&amp;c={$this->forum['cat_id']}'>{$this->forum['cat_name']}</a>",
        					 	  					   $this->nav_extra,
        					 	  					   "<a href='".$this->base_url."showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
        					 	  					 ),
        					  ) );

     }

     //+----------------------------------------------------------------
	 // Display any sub forums
	 //+----------------------------------------------------------------


     function show_subforums() {

		global $std, $DB, $ibforums;

		$ibforums->lang   = $std->load_words($ibforums->lang, 'lang_boards', $ibforums->lang_id);

        $this->board_html = $std->load_template('skin_boards');

        $fid = $ibforums->input['f'];

        $DB->query("SELECT f.*, m.member_name as mod_name, m.member_id as mod_id, m.is_group, m.group_id, m.group_name, m.mid
        			FROM ibf_forums f
        			 LEFT JOIN ibf_moderators m ON (f.id=m.forum_id)
        			WHERE parent_id='$fid'
        			ORDER BY position");

        if ( ! $DB->get_num_rows() )
        {
        	return "";
        }

        while ( $r = $DB->fetch_row() )
        {

			$this->forums[ $r['id'] ] = $r;

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

		foreach( $this->forums as $data )
		{
			$temp_html .= $this->process_forum($data['id'], $data);
		}

		if ($temp_html != "")
		{
			$this->sub_output .= $this->board_html->subheader();
			$this->sub_output .= $temp_html;
			$this->sub_output .= $this->board_html->end_this_cat();
		}
		else
		{
			return $this->sub_output;
		}
		unset($temp_html);

		$this->sub_output .= $this->board_html->end_all_cats();
    }



	function process_forum($forum_id="", $forum_data="")
    {
    	global $std, $ibforums;

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

			return $this->board_html->forum_redirect_row($forum_data);

		}

		$forum_data['img_new_post'] = $std->forum_new_posts($forum_data);

		if ( $forum_data['img_new_post'] == '<{C_ON}>' )
		{
			$forum_data['img_new_post'] = $this->board_html->forum_img_with_link($forum_data['img_new_post'], $forum_data['id']);
		}

		$forum_data['last_post'] = $std->get_date($forum_data['last_post'], 'LONG');

		$forum_data['last_topic'] = $ibforums->lang['f_none'];

		if (isset($forum_data['last_title']) and $forum_data['last_id'])
		{

			$forum_data['last_title'] = strip_tags($forum_data['last_title']);
			$forum_data['last_title'] = str_replace( "&#33;" , "!", $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&quot;", "\"", $forum_data['last_title'] );

			if (strlen($forum_data['last_title']) > 30)
			{
				$forum_data['last_title'] = substr($forum_data['last_title'],0,27) . "...";
				$forum_data['last_title'] = preg_replace( '/&(#(\d+;?)?)?\.\.\.$/', '...', $forum_data['last_title'] );
			}
			else
			{
				$forum_data['last_title'] = preg_replace( '/&(#(\d+?)?)?$/', '', $forum_data['last_title'] );
			}

			if ($forum_data['password'] != "")
			{
				$forum_data['last_topic'] = $ibforums->lang['f_protected'];
			}
			else
			{
			    $forum_data['last_unread'] = $this->board_html->forumrow_lastunread_link($forum_data['id'], $forum_data['last_id']);
				$forum_data['last_topic']  = "<a href='{$ibforums->base_url}showtopic={$forum_data['last_id']}&amp;view=getlastpost' title='{$ibforums->lang['tt_gounread']}'>{$forum_data['last_title']}</a>";
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

		$forum_data['moderator'] = "";

		if (isset($this->mods[ $forum_data['id'] ] ) )
		{
			$forum_data['moderator'] = $ibforums->lang['forum_leader'].' ';

			if (is_array($this->mods[ $forum_data['id'] ]) )
			{
				foreach ($this->mods[ $forum_data['id'] ] as $moderator)
				{
					if ($moderator['isg'] == 1)
					{
						$forum_data['moderator'] .= "<a href='{$ibforums->base_url}act=Members&amp;max_results=30&amp;filter={$moderator['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0'>{$moderator['gname']}</a>, ";
					}
					else
					{
						$forum_data['moderator'] .= "<a href='{$ibforums->base_url}showuser={$moderator['id']}'>{$moderator['name']}</a>, ";
					}
				}

				$forum_data['moderator'] = preg_replace( "!,\s+$!", "", $forum_data['moderator'] );

			}
			else
			{
				if ($moderator['isg'] == 1)
				{
					$forum_data['moderator'] .= "<a href='{$ibforums->base_url}act=Members&amp;max_results=30&amp;filter={$this->mods[$forum_data['id']]['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0'>{$this->mods[$forum_data['id']]['gname']}</a>, ";
				}
				else
				{
					$forum_data['moderator'] .= "<a href='{$ibforums->base_url}showuser={$this->mods[$forum_data['id']]['id']}'>{$this->mods[$forum_data['id']]['name']}</a>";
				}
			}
		}

		$forum_data['posts']  = $std->do_number_format($forum_data['posts']);
		$forum_data['topics'] = $std->do_number_format($forum_data['topics']);

		return $this->board_html->ForumRow($forum_data);
	}

    //+----------------------------------------------------------------
	//
	// Show the forum rules on a separate page
	//
	//+----------------------------------------------------------------

	function show_rules()
	{
		global $DB, $ibforums, $std, $print;

		//+--------------------------------------------
		// Do we have permission to view these rules?
		//+--------------------------------------------

		$bad_entry = $this->check_access();

        if ($bad_entry == 1)
        {
        	$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
        }


        if ( $this->forum['rules_title'] )
        {
        	$rules['title'] = $this->forum['rules_title'];
        	$rules['body']  = $this->forum['rules_text'];
        	$rules['fid']   = $this->forum['id'];

        	$this->output .= $this->html->show_rules($rules);

			$print->add_output("$this->output");
			$print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -&gt; ".$this->forum['name'],
									  'JS'       => 0,
									  'NAV'      => array(
														   "<a href='".$this->base_url."act=SC&amp;c={$this->forum['cat_id']}'>{$this->forum['cat_name']}</a>",
														   $this->forum['name']
														 ),
								  ) );
		}
		else
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
		}
	}

	//+----------------------------------------------------------------
	//
	// Forum view check for authentication
	//
	//+----------------------------------------------------------------

	function show_forum()
	{
   		global $ibforums;
		// are we checking for user authentication via the log in form
		// for a private forum w/password protection?

		$ibforums->input['L'] == 1 ? $this->authenticate_user() : $this->render_forum();
	}

	//+----------------------------------------------------------------
	//
	// Authenicate the log in for a password protected forum
	//
	//+----------------------------------------------------------------

	function authenticate_user() {
		global $std, $ibforums, $print;

		if ($ibforums->input['f_password'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_blank' ) );
		}

		if ($ibforums->input['f_password'] != $this->forum['password'])
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
		}

		$std->my_setcookie( "iBForum".$this->forum['id'], $ibforums->input['f_password'] );

		$print->redirect_screen( $ibforums->lang['logged_in'] , "showforum=".$this->forum['id'] );

	}

	//+----------------------------------------------------------------------------------

	function check_access() {
		global $ibforums, $std, $HTTP_COOKIE_VARS;

		$return = 1;

		if ( $std->check_perms($this->forum['read_perms']) == TRUE )
		{
			$return = 0;
		}

		// Do we have permission to even see the password page?

		if ($return == 0)
		{
			if ($this->forum['password'])
			{
				if ($HTTP_COOKIE_VARS[ $ibforums->vars['cookie_id'].'iBForum'.$this->forum['id'] ] == $this->forum['password'])
				{
					$return = 0;
				}
				else
				{
					$this->forum_login();
				}
			}
		}

		return $return;

	}

	//+----------------------------------------------------------------------------------

	function forum_login() {
		global $ibforums, $std, $DB, $HTTP_COOKIE_VARS, $print;

		if (empty($ibforums->member['id']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}

		$this->output = $this->html->Forum_log_in( $this->forum['id'] );

		$print->add_output("$this->output");

        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->forum['name'],
        					 	  'JS'       => 0,
        					 	  'NAV'      => array(
        					 	  					   "<a href='".$this->base_url."act=SC&amp;c={$this->forum['cat_id']}'>{$this->forum['cat_name']}</a>",
        					 	  					   "<a href='".$this->base_url."showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
        					 	  					 ),
        					  ) );

	}

	//+----------------------------------------------------------------
	//
	// Main render forum engine
	//
	//+----------------------------------------------------------------

	function render_forum()
	{
		global $ibforums, $DB, $std, $print, $skin_universal,$HTTP_COOKIE_VARS;

		$bad_entry = $this->check_access();

        if ($bad_entry == 1)
        {
        	if ($this->forum['subwrap'] == 1)
        	{
        		// Dont' show an error as we may have sub forums up top
        		// Instead, copy the sub forum ouput to the main output
        		// and return gracefully

        		$this->output     = $this->sub_output;
				$this->sub_output = "";

				return TRUE;
        	}
        	else
        	{
        		$std->Error( array( LEVEL => 1, MSG => 'no_permission') );
        	}
        }

		if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));
        }

        $ibforums->input['last_visit'] = $ibforums->forum_read[ $ibforums->input['f'] ] > $ibforums->input['last_visit']
        						       ? $ibforums->forum_read[ $ibforums->input['f'] ] : $ibforums->input['last_visit'];

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

		$First       = $std->select_var( array(
												1 => intval($ibforums->input['st']),
												2 => 0                    )
									   );

		// Figure out sort order, day cut off, etc

		$Prune = $prune_value != 100 ? (time() - ($prune_value * 60 * 60 * 24)) : 0;

		$sort_keys   =  array( 'last_post'         => 'sort_by_date',
							   'title'             => 'sort_by_topic',
							   'starter_name'      => 'sort_by_poster',
							   'posts'             => 'sort_by_replies',
							   'views'             => 'sort_by_views',
							   'start_date'        => 'sort_by_start',
							   'last_poster_name'  => 'sort_by_last_poster',
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

        //+----------------------------------------------------------------
        // check for any form funny business by wanna-be hackers
		//+----------------------------------------------------------------

		if ( (!isset($sort_keys[$sort_key])) or (!isset($prune_by_day[$prune_value])) or (!isset($sort_by_keys[$sort_by])) )
		{
			   $std->Error( array( LEVEL=> 5, MSG =>'incorrect_use') );
	    }

	    $r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';

		//+----------------------------------------------------------------
		// Query the database to see how many topics there are in the forum
		//+----------------------------------------------------------------

		$DB->query("SELECT COUNT(tid) as max FROM ibf_topics WHERE forum_id=".$this->forum['id']." and approved=1 and (pinned=1 or last_post > $Prune)");
		$total_possible = $DB->fetch_row();

		//+----------------------------------------------------------------
		// Generate the forum page span links
		//+----------------------------------------------------------------

		$this->forum['SHOW_PAGES']
			= $std->build_pagelinks( array( 'TOTAL_POSS'  => $total_possible['max'],
											'PER_PAGE'    => $ibforums->vars['display_max_topics'],
											'CUR_ST_VAL'  => $ibforums->input['st'],
											'L_SINGLE'    => $ibforums->lang['single_page_forum'],
											'BASE_URL'    => $this->base_url."showforum=".$this->forum['id']."&amp;prune_day=$prune_value&amp;sort_by=$sort_by&amp;sort_key=$sort_key",
										  )
								   );


		//+----------------------------------------------------------------
		// Do we have any rules to show?
		//+----------------------------------------------------------------

		 $this->output .= $std->print_forum_rules($this->forum);

		//+----------------------------------------------------------------
		// Generate the poll button
		//+----------------------------------------------------------------

		$this->forum['POLL_BUTTON'] = $this->forum['allow_poll']
										 ? "<a href='".$this->base_url."act=Post&amp;CODE=10&amp;f=".$this->forum['id']."'><{A_POLL}></a>"
										 : '';

		//+----------------------------------------------------------------
		// Start printing the page
		//+----------------------------------------------------------------

		$this->output .= $this->html->PageTop($this->forum);

		//+----------------------------------------------------------------
		// Do we have any topics to show?
		//+----------------------------------------------------------------

		if ($total_possible['max'] < 1)
		{
			$this->output .= $this->html->show_no_matches();
		}

		$total_topics_printed = 0;

		if ( ($ibforums->vars['show_user_posted'] == 1) and ($ibforums->member['id']) )
		{
			$query = "SELECT DISTINCT(ibf_posts.author_id), ibf_topics.*, f.leechers, f.seeders FROM ibf_topics
			           LEFT JOIN ibf_posts ON
			           (ibf_topics.tid=ibf_posts.topic_id AND ibf_posts.author_id=".$ibforums->member['id'].")
			           LEFT JOIN xbt_files f ON
						ibf_topics.bt_info_hash=f.info_hash
			     	   WHERE ibf_topics.forum_id=".$this->forum['id']."
			           and ibf_topics.approved=1
			           and (ibf_topics.pinned=1 or ibf_topics.last_post > $Prune)";
		}
		else
		{
			$query = "SELECT *, f.leechers, f.seeders from ibf_topics LEFT JOIN xbt_files f ON bt_info_hash=f.info_hash WHERE forum_id=".$this->forum['id']." and approved=1 and (last_post > $Prune OR pinned=1)";
		}

		//+----------------------------------------------------------------
		// Do we have permission to view other posters topics?
		//+----------------------------------------------------------------

		if (!$ibforums->member['g_other_topics'])
		{
            $query .= " and starter_id='".$ibforums->member['id']."'";
		}

		//+----------------------------------------------------------------
		// Finish off the query
		//+----------------------------------------------------------------

		$First = $First ? $First : 0;

		$query .= " ORDER BY pinned DESC, $sort_key $r_sort_by LIMIT $First,".$ibforums->vars['display_max_topics'];

		$DB->query($query);

		//+----------------------------------------------------------------
		// Grab the rest of the topics and print them
		//+----------------------------------------------------------------

		while ( $topic = $DB->fetch_row() )
		{
			$this->output .= $this->render_entry( $topic );
			$total_topics_printed++;
		}

		//+----------------------------------------------------------------
		// Finish off the rest of the page
		//+----------------------------------------------------------------

		$ibforums->lang['showing_text'] = preg_replace( "/<#MATCHED_TOPICS#>/", $total_topics_printed  , $ibforums->lang['showing_text'] );
		$ibforums->lang['showing_text'] = preg_replace( "/<#TOTAL_TOPICS#>/"  , $total_possible['max'] , $ibforums->lang['showing_text'] );

		$sort_key_html  = "<select name='sort_key'  class='forminput'>\n";
		$prune_day_html = "<select name='prune_day' class='forminput'>\n";
		$sort_by_html   = "<select name='sort_by'   class='forminput'>\n";


		foreach ($sort_by_keys as $k => $v)
		{
			$sort_by_html   .= $k == $sort_by     ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $sort_by_keys[ $k ] ] . "</option>\n"
											      : "<option value='$k'>"          . $ibforums->lang[ $sort_by_keys[ $k ] ] . "</option>\n";
		}

		foreach ($sort_keys as  $k => $v)
		{
			$sort_key_html  .= $k == $sort_key    ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $sort_keys[ $k ] ]    . "</option>\n"
											      : "<option value='$k'>"          . $ibforums->lang[ $sort_keys[ $k ] ]    . "</option>\n";
		}
		foreach ($prune_by_day as  $k => $v)
		{
			$prune_day_html .= $k == $prune_value ? "<option value='$k' selected='selected'>" . $ibforums->lang[ $prune_by_day[ $k ] ] . "</option>\n"
												  : "<option value='$k'>"          . $ibforums->lang[ $prune_by_day[ $k ] ] . "</option>\n";
		}

		$ibforums->lang['sort_text'] = preg_replace( "!<#SORT_KEY_HTML#>!", "$sort_key_html</select>"  , $ibforums->lang['sort_text'] );
		$ibforums->lang['sort_text'] = preg_replace( "!<#ORDER_HTML#>!"   , "$sort_by_html</select>"   , $ibforums->lang['sort_text'] );
		$ibforums->lang['sort_text'] = preg_replace( "!<#PRUNE_HTML#>!"   , "$prune_day_html</select>" , $ibforums->lang['sort_text'] );

		$this->output .= $this->html->TableEnd($this->forum);

		//+----------------------------------------------------------------
		// If all the new topics have been read in this forum..
		//+----------------------------------------------------------------

		if ($this->new_posts < 1)
		{
			$ibforums->forum_read[ $this->forum['id'] ] = time();

			$std->hdl_forum_read_cookie('set');
		}

		//+----------------------------------------------------------------
		// Process users active in this forum
		//+----------------------------------------------------------------

		if ($ibforums->vars['no_au_forum'] != 1)
		{
			//+-----------------------------------------
			// Get the users
			//+-----------------------------------------

			$cut_off = ($ibforums->vars['au_cutoff'] != "") ? $ibforums->vars['au_cutoff'] * 60 : 900;

			$time = time() - $cut_off;

			$DB->query("SELECT s.member_id, s.member_name, s.login_type, s.location, g.suffix, g.prefix, g.g_perm_id, t.forum_id, m.org_perm_id
					    FROM ibf_sessions s
					     LEFT JOIN ibf_groups g ON (g.g_id=s.member_group)
					     LEFT JOIN ibf_topics t ON (t.tid=s.in_topic)
					     LEFT JOIN ibf_members m on (s.member_id=m.id)
					    WHERE (s.in_forum={$this->forum['id']} OR t.forum_id={$this->forum['id']})
					    AND s.running_time > $time
					    ORDER BY s.running_time DESC");


			//+-----------------------------------------
			// Cache all printed members so we don't double print them
			//+-----------------------------------------

			$cached = array();
			$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");

			while ($result = $DB->fetch_row() )
			{
				$result['g_perm_id'] = $result['org_perm_id'] ? $result['org_perm_id'] : $result['g_perm_id'];

				if ( $this->forum['read_perms'] != '*' )
				{
					if ( $result['g_perm_id'] )
					{
						if ( ! preg_match("/(^|,)(".str_replace( ",", '|', $result['g_perm_id'] ).")(,|$)/", $this->forum['read_perms'] ) )
						{
							continue;
						}
					}
					else
					{
						continue;
					}
				}

				if ($result['member_id'] == 0)
				{
					$active['guests']++;
				}
				else
				{
					if (empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						if ($result['login_type'] == 1)
						{
							if ( ($ibforums->member['mgroup'] == $ibforums->vars['admin_group']) and ($ibforums->vars['disable_admin_anon'] != 1) )
							{
								$active['names'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>*, ";
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
							$active['names'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>, ";
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

		return TRUE;

    }

	//+----------------------------------------------------------------
	//
	// Crunches the data into pwetty html
	//
	//+----------------------------------------------------------------

	function render_entry($topic) {
		global $DB, $std, $ibforums;

		$topic['last_text']   = $ibforums->lang['last_post_by'];

		$topic['last_poster'] = ($topic['last_poster_id'] != 0)
								? "<a href='{$this->base_url}showuser={$topic['last_poster_id']}'>{$topic['last_poster_name']}</a>"
								: "-".$topic['last_poster_name']."-";

		$topic['starter']     = ($topic['starter_id']     != 0)
								? "<a href='{$this->base_url}showuser={$topic['starter_id']}'>{$topic['starter_name']}</a>"
								: "-".$topic['starter_name']."-";

		if ($topic['poll_state'])
		{
			$topic['prefix']     = $ibforums->vars['pre_polls'].' ';
		}

		if ( ($ibforums->member['id']) and ($topic['author_id']) )
		{
			$show_dots = 1;
		}

		$topic['folder_img']     = $std->folder_icon($topic, $show_dots, $this->read_array[$topic['tid']]);

		$topic['topic_icon']     = $topic['icon_id']  ? '<img src="'.$ibforums->vars['img_url'] . '/icon' . $topic['icon_id'] . '.gif" border="0" alt="" />'
													  : '&nbsp;';

		$topic['start_date'] = $std->get_date( $topic['start_date'], 'LONG' );


		$pages = 1;

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
			$topic['PAGES'] = "<span class='small'>({$ibforums->lang['topic_sp_pages']} ";
			for ($i = 0 ; $i < $pages ; ++$i ) {
				$real_no = $i * $ibforums->vars['display_max_posts'];
				$page_no = $i + 1;
				if ($page_no == 4) {
					$topic['PAGES'] .= "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;st=" . ($pages - 1) * $ibforums->vars['display_max_posts'] . "'>...$pages </a>";
					break;
				} else {
					$topic['PAGES'] .= "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;st=$real_no'>$page_no </a>";
				}
			}
			$topic['PAGES'] .= ")</span>";
		}

		//------------------------------------------------
		// Format some numbers
		//------------------------------------------------

		if ($topic['posts'] < 0) $topic['posts'] = 0;

		$topic['posts']  = $std->do_number_format($topic['posts']);
		$topic['views']	 = $std->do_number_format($topic['views']);

		//------------------------------------------------
		// Last time stuff...
		//------------------------------------------------

		$last_time = $this->read_array[ $topic['tid'] ] > $ibforums->input['last_visit'] ? $this->read_array[ $topic['tid'] ] : $ibforums->input['last_visit'];

		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			$this->new_posts++;
			$topic['go_new_post']  = "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;view=getnewpost'><{NEW_POST}></a>";
		}
		else
		{
			$topic['go_new_post']  = "";
		}

		$topic['last_post']  = $std->get_date( $topic['last_post'], 'SHORT' );

		//+----------------------------------------------------------------

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

		$p_start = "";
		$p_end   = "";

		if ($topic['pinned'] == 1)
		{
			$topic['prefix']     = $ibforums->vars['pre_pinned'];
			$topic['topic_icon'] = "<{B_PIN}>";

			if ($this->pinned_print == 0)
			{
				// we've a pinned topic, but we've not printed the pinned
				// starter row, so..

				$p_start = $this->html->render_pinned_start();

				$this->pinned_print = 1;
			}

			return $p_start . $this->html->render_pinned_row( $topic );
		}
		else
		{
			// This is not a pinned topic, so lets check to see if we've
			// printed the footer yet.

			if ($this->pinned_print == 1)
			{
				// Nope, so..
				$p_end = $this->html->render_pinned_end();

				$this->pinned_print = 0;
			}

			return $p_end . $this->html->RenderRow( $topic );
		}
	}


	//+----------------------------------------------------------------
	//
	// Returns the last action date
	//
	//+----------------------------------------------------------------

	function get_last_date($topic) {
		global $ibforums, $std;

		return $std->get_date( $topic['last_post'], 'SHORT' );

	}


}

?>
