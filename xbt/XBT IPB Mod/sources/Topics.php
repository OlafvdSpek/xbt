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
|   > Topic display module
|   > Module written by Matt Mecham
|   > Date started: 18th February 2002
|
|	> Module Version Number: 1.1.0
+--------------------------------------------------------------------------
*/


$idx = new Topics;

class Topics {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_titles = array();
    var $mod_action = array();
    var $poll_html  = "";
    var $colspan    = 0;
    var $parser     = "";
    var $mimetypes  = "";
    var $nav_extra  = "";
    var $read_array = array();
    var $mod_panel_html = "";
    var $warn_range = 0;
    var $warn_done  = 0;
    var $pfields    = array();
    var $pfields_dd = array();
    var $md5_check  = "";

    /***********************************************************************************/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/***********************************************************************************/

    function Topics()
    {

        global $ibforums, $DB, $std, $print, $skin_universal;

        $this->md5_check = $std->return_md5_check();

        //-------------------------------------
		// Compile the language file
		//-------------------------------------

        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_topic', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_topic');

        require ROOT_PATH."sources/lib/post_parser.php";

        $this->parser = new post_parser();

        //-------------------------------------
        // Check the input
        //-------------------------------------

        $ibforums->input['t'] = intval($ibforums->input['t']);

		if ( $ibforums->input['t'] < 0  )
		{
			$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}


        //-------------------------------------
        // Get the forum info based on the forum ID,
        // get the category name, ID, and get the topic details
        //-------------------------------------

        if ( ! $ibforums->topic_cache['tid'] )
        {
			$DB->query("SELECT t.*, f.topic_mm_id, f.name as forum_name, f.quick_reply, f.id as forum_id, f.read_perms, f.reply_perms, f.parent_id, f.use_html,
							   f.start_perms, f.allow_poll, f.password, f.posts as forum_posts, f.topics as forum_topics, f.upload_perms,
							   f.show_rules, f.rules_text, f.rules_title,
							   c.name as cat_name, c.id as cat_id
						FROM ibf_topics t, ibf_forums f , ibf_categories c where t.tid=".$ibforums->input['t']." and f.id = t.forum_id and f.category=c.id");

			$this->topic = $DB->fetch_row();
        }
        else
        {
        	$this->topic = $ibforums->topic_cache;
        }

        $this->forum = array( 'id'           => $this->topic['forum_id']          ,
        					  'name'         => $this->topic['forum_name']        ,
        					  'posts'        => $this->topic['forum_posts']       ,
        					  'topics'       => $this->topic['forum_topics']      ,
        					  'read_perms'   => $this->topic['read_perms']        ,
        					  'reply_perms'  => $this->topic['reply_perms']       ,
        					  'allow_poll'   => $this->topic['allow_poll']        ,
        					  'upload_perms' => $this->topic['upload_perms']      ,
        					  'parent_id'    => $this->topic['parent_id']         ,
        					  'password'     => $this->topic['password']          ,
        					  'quick_reply'  => $this->topic['quick_reply']       ,
        					  'use_html'     => $this->topic['use_html']          ,
        					  'topic_mm_id'  => $this->topic['topic_mm_id']
        					);

        $this->category = array( 'name'   => $this->topic['cat_name'],
        						 'id'     => $this->topic['cat_id']  ,
        				       );

        $ibforums->input['f'] = $this->forum['id'];

        //-------------------------------------
        // Error out if we can not find the forum
        //-------------------------------------

        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }

        //-------------------------------------
        // Error out if we can not find the topic
        //-------------------------------------

        if (!$this->topic['tid'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }

        //-------------------------------------
        // Error out if the topic is not approved
        //-------------------------------------

        if ($this->topic['approved'] != 1)
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }

        //-------------------------------------
        // If this forum is a link, then
        // redirect them to the new location
        //-------------------------------------

        if ($this->topic['state'] == 'link')
        {
        	$f_stuff = explode("&", $this->topic['moved_to']);
        	$print->redirect_screen( $ibforums->lang['topic_moved'], "showtopic={$f_stuff[0]}" );
        }

        //-------------------------------------
        // Unserialize the read array and parse into
        // array
        //-------------------------------------

        if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));

        	if (! is_array($this->read_array) )
        	{
        		$this->read_array = array();
        	}
        }

        //--------------------------------------------------------------------
        // Are we looking for an older / newer topic?
        //--------------------------------------------------------------------

        if ( isset($ibforums->input['view']) )
        {
        	if ($ibforums->input['view'] == 'new')
        	{
        		$DB->query("SELECT * from ibf_topics WHERE forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post > ".$this->topic['last_post']." "
        		          ."ORDER BY last_post ASC LIMIT 0,1");

        		if ( $DB->get_num_rows() )
        		{
        			$this->topic = $DB->fetch_row();
        			$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']);
        			//$ibforums->input['t'] = $this->topic['tid'];
        		}
        		else
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'no_newer') );
        		}
        	}
        	else if ($ibforums->input['view'] == 'old')
        	{
        		$DB->query("SELECT * from ibf_topics WHERE forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post < ".$this->topic['last_post']." "
        		          ."ORDER BY last_post DESC LIMIT 0,1");

        		if ( $DB->get_num_rows() )
        		{
        			$this->topic = $DB->fetch_row();
        			$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']);
        			//$ibforums->input['t'] = $this->topic['tid'];
        		}
        		else
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'no_older') );
        		}
        	}
        	else if ($ibforums->input['view'] == 'getlastpost')
        	{

        		$this->return_last_post();

			}
			else if ($ibforums->input['view'] == 'getnewpost')
			{

				$st  = 0;
				$pid = "";

				$last_time = isset($this->read_array[ $this->topic['tid'] ]) ? $this->read_array[ $this->topic['tid'] ] : $ibforums->input['last_visit'];

				$DB->query("SELECT pid, post_date FROM ibf_posts WHERE queued <> 1 AND topic_id='".$this->topic['tid']."' AND post_date > '".$last_time."' ORDER BY post_date LIMIT 1");

				if ( $post = $DB->fetch_row() )
				{

					$pid = "&#entry".$post['pid'];

					$DB->query("SELECT COUNT(pid) as posts FROM ibf_posts WHERE topic_id='".$this->topic['tid']."' AND pid <= '".$post['pid']."'");

					$cposts = $DB->fetch_row();

					if ( (($cposts['posts']) % $ibforums->vars['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $ibforums->vars['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $ibforums->vars['display_max_posts'] );
						$pages = ceil( $number);
					}

					$st = ($pages - 1) * $ibforums->vars['display_max_posts'];

					$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&st=$st".$pid);
					exit();
				}
				else
				{
					$this->return_last_post();
				}
			}
			else if ($ibforums->input['view'] == 'findpost')
			{
				$pid = intval($ibforums->input['p']);

				if ( $pid > 0 )
				{
					$DB->query("SELECT COUNT(pid) as posts FROM ibf_posts WHERE topic_id='".$this->topic['tid']."' AND pid <= '".$pid."'");

					$cposts = $DB->fetch_row();

					if ( (($cposts['posts']) % $ibforums->vars['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $ibforums->vars['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $ibforums->vars['display_max_posts'] );
						$pages = ceil( $number);
					}

					$st = ($pages - 1) * $ibforums->vars['display_max_posts'];

					$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&st=$st"."&#entry".$pid);
					exit();
				}
				else
				{
					$this->return_last_post();
				}
			}
		}

        $this->base_url = $ibforums->base_url;

		$this->forum['JUMP'] = $std->build_forum_jump();

        //-------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-------------------------------------

        if ( (!$this->topic['pinned']) and ( ( ! $ibforums->member['g_other_topics'] ) AND ( $this->topic['starter_id'] != $ibforums->member['id'] ) ) )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }

        $bad_entry = $this->check_access();

        if ($bad_entry == 1)
        {
        	$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }

        //-------------------------------------
        // Update the topic views counter
        //-------------------------------------

        $DB->query("UPDATE ibf_topics SET views=views+1 WHERE tid='".$this->topic['tid']."'");

        //-------------------------------------
        // Update the topic read cookie
        //-------------------------------------

        if ($ibforums->member['id'])
        {
			$this->read_array[$this->topic['tid']] = time();

			$std->my_setcookie('topicsread', serialize($this->read_array), -1 );
        }

        //----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //----------------------------------------

        if ($this->forum['parent_id'] > 0)
        {

        	$DB->query("SELECT f.id as forum_id, f.name as forum_name, c.id, c.name FROM ibf_forums f, ibf_categories c WHERE f.id=".$this->forum['parent_id']." AND c.id=f.category");

        	$row = $DB->fetch_row();

        	$this->category['id']   = $row['id'];
        	$this->category['name'] = $row['name'];

        	$this->nav_extra = "<a href='".$this->base_url."showforum={$row['forum_id']}'>{$row['forum_name']}</a>";
        }


 		//-------------------------------------
 		// Get all the member groups and
 		// member title info
 		//-------------------------------------

        $DB->query("SELECT id, title, pips, posts from ibf_titles ORDER BY posts DESC");
        while ($i = $DB->fetch_row())
        {
         	$this->mem_titles[ $i['id'] ] = array(
												 'TITLE' => $i['title'],
												 'PIPS'  => $i['pips'],
												 'POSTS' => $i['posts'],
											   );
        }

        //-------------------------------------
        // Are we a moderator?
        //-------------------------------------

		if ( ($ibforums->member['id']) and ($ibforums->member['g_is_supmod'] != 1) )
		{
			$DB->query("SELECT * FROM ibf_moderators WHERE forum_id=".$this->forum['id']." AND (member_id=".$ibforums->member['id']." OR (is_group=1 AND group_id='".$ibforums->member['mgroup']."'))");
			$this->moderator = $DB->fetch_row();
		}

		$this->mod_action = array( 'CLOSE_TOPIC'  => '00',
								   'OPEN_TOPIC'   => '01',
								   'MOVE_TOPIC'   => '02',
								   'DELETE_TOPIC' => '03',
								   'EDIT_TOPIC'   => '05',
								   'PIN_TOPIC'    => '15',
								   'UNPIN_TOPIC'  => '16',
								   'UNSUBBIT'     => '30',
								   'SPLIT_TOPIC'  => '50',
								   'MERGE_TOPIC'  => '60',
								   'TOPIC_HISTORY' => '90',
								 );


		//-------------------------------------
        // Get the reply, and posting buttons
        //-------------------------------------

        $this->topic['POLL_BUTTON'] = $this->forum['allow_poll']
										 ? "<a href='".$this->base_url."act=Post&amp;CODE=10&amp;f=".$this->forum['id']."'><{A_POLL}></a>"
										 : '';

		$this->topic['REPLY_BUTTON']  = $this->reply_button();


		//-------------------------------------
		// Generate the forum page span links
		//-------------------------------------

		if ($ibforums->input['hl'])
		{
			$hl = '&amp;hl='.$ibforums->input['hl'];
		}

		$this->topic['SHOW_PAGES']
			= $std->build_pagelinks( array( 'TOTAL_POSS'  => ($this->topic['posts']+1),
											'PER_PAGE'    => $ibforums->vars['display_max_posts'],
											'CUR_ST_VAL'  => $ibforums->input['st'],
											'L_SINGLE'    => "",
											'BASE_URL'    => $this->base_url."showtopic=".$this->topic['tid'].$hl,
										  )
								   );

		if ( ($this->topic['posts'] + 1) > $ibforums->vars['display_max_posts'])
		{
			$this->topic['go_new'] = $this->html->golastpost_link($this->forum['id'], $this->topic['tid'] );
		}


		//-------------------------------------
		// Fix up some of the words
		//-------------------------------------

		$this->topic['TOPIC_START_DATE'] = $std->get_date( $this->topic['start_date'], 'LONG' );

		$ibforums->lang['topic_stats'] = preg_replace( "/<#START#>/", $this->topic['TOPIC_START_DATE'], $ibforums->lang['topic_stats']);
		$ibforums->lang['topic_stats'] = preg_replace( "/<#POSTS#>/", $this->topic['posts']           , $ibforums->lang['topic_stats']);

		if ($this->topic['description'])
		{
			$this->topic['description'] = ', '.$this->topic['description'];
		}


		//-------------------------------------
		// Render the page top
		//-------------------------------------

		$this->output .= $this->html->PageTop( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ) );

		//-------------------------------------
		// Do we have a poll?
		//-------------------------------------

		if ($this->topic['poll_state'])
		{
			$this->output = str_replace( "<!--{IBF.POLL}-->", $this->parse_poll(), $this->output );
		}
		else
		{
			// Can we start a poll? Is this our topic and is it still open?

			if ( $this->topic['state'] != "closed" AND $ibforums->member['id'] AND $ibforums->member['g_post_polls'] AND $this->forum['allow_poll'] )
			{
				if (
					 ( ($this->topic['starter_id'] == $ibforums->member['id']) AND ($ibforums->vars['startpoll_cutoff'] > 0) AND ( $this->topic['start_date'] + ($ibforums->vars['startpoll_cutoff'] * 3600) > time() ) )
					 OR ( $ibforums->member['g_is_supmod'] == 1 )
				   )
				{
					$this->output = str_replace( "<!--{IBF.START_NEW_POLL}-->", $this->html->start_poll_link($this->forum['id'], $this->topic['tid']), $this->output );
				}
			}
		}

		//--------------------------------------------
		// Extra queries?
		//--------------------------------------------

		$join_profile_query = "";
		$join_get_fields    = "";

		if ( $ibforums->vars['custom_profile_topic'] == 1 )
		{
			//--------------------------------------------
			// Get the data for the profile fields
			//--------------------------------------------

			$DB->query("SELECT fid, ftype, fhide, fcontent FROM ibf_pfields_data");

			while ( $r = $DB->fetch_row() )
			{
				$this->pfields['field_'.$r['fid']] = $r;

				if ( $r['ftype'] == 'drop' )
				{
					foreach( explode( '|', $r['fcontent'] ) as $i )
					{
						list($k, $v) = explode( '=', $i, 2 );

						$this->pfields_dd['field_'.$r['fid']][$k] = $v;
					}
				}
			}

			$join_profile_query = "LEFT JOIN ibf_pfields_content pc ON (pc.member_id=p.author_id)";
			$join_get_fields    = ", pc.*";
		}

		//--------------------------------------------
		// Grab the posts we'll need
		//--------------------------------------------

		$first = intval($ibforums->input['st']);

		if ( $ibforums->vars['post_order_column'] != 'post_date' )
		{
			$ibforums->vars['post_order_column'] = 'pid';
		}

		if ( $ibforums->vars['post_order_sort'] != 'desc' )
		{
			$ibforums->vars['post_order_sort'] = 'asc';
		}

		//--------------------------------------------
		// Optimized query?
		// MySQL.com insists that forcing LEFT JOIN or
		// STRAIGHT JOIN helps the query optimizer, so..
		//--------------------------------------------

		$DB->query( "SELECT p.*,
				    m.id,m.name,m.mgroup,m.email,m.joined,m.avatar,m.avatar_size,m.posts,m.aim_name,m.icq_number,
				    m.signature, m.website,m.yahoo,m.integ_msg,m.title,m.hide_email,m.msnname, m.warn_level, m.warn_lastwarn,
				    g.g_id, g.g_title, g.g_icon, g.g_dohtml, f.leechers, f.seeders $join_get_fields
				    FROM ibf_posts p
				      LEFT JOIN ibf_members m ON (p.author_id=m.id)
				      LEFT JOIN ibf_groups g ON (g.g_id=m.mgroup)
				      LEFT JOIN xbt_files f ON (p.bt_info_hash=f.info_hash)
				      $join_profile_query
				    WHERE p.topic_id=".$this->topic['tid']." and p.queued != 1
				    ORDER BY p.{$ibforums->vars['post_order_column']} {$ibforums->vars['post_order_sort']} LIMIT $first, ".$ibforums->vars['display_max_posts']);

		if ( ! $DB->get_num_rows() )
		{
			if ($first >= $ibforums->vars['display_max_posts'])
			{
				// Get the correct number of replies...

				$newq = $DB->query("SELECT COUNT(pid) as pcount FROM ibf_posts p, ibf_members m WHERE p.topic_id=".$this->topic['tid']." and p.queued !=1 AND p.author_id=m.id");
				$pcount = $DB->fetch_row($newq);

				$pcount['pcount'] = $pcount['pcount'] > 0 ? $pcount['pcount'] - 1 : 0;

				// Update the post table...

				if ($pcount['pcount'] > 1)
				{
					$DB->query("UPDATE ibf_topics SET posts=".$pcount['pcount']." WHERE tid='".$this->topic['tid']."'");
				}

				$std->boink_it($ibforums->base_url."act=ST&f={$this->forum['id']}&t={$this->topic['tid']}&view=getlastpost");
				exit();
			}
		}


		$cached_members = array();

		//-------------------------------------
		// Format and print out the topic list
		//-------------------------------------

		$post_count = 0;  // Use this as our master bater, er... I mean counter.

		while ( $row = $DB->fetch_row() )
		{

			$poster = array();

			// Get the member info. We parse the data and cache it.
			// It's likely that the same member posts several times in
			// one page, so it's not efficient to keep parsing the same
			// data

			if ($row['author_id'] != 0)
			{
				// Is it in the hash?
				if ( isset($cached_members[ $row['author_id'] ]) )
				{
					// Ok, it's already cached, read from it
					$poster = $cached_members[ $row['author_id'] ];
					$row['name_css'] = 'normalname';
				}
				else
				{
					$row['name_css'] = 'normalname';
					$poster = $this->parse_member( &$row );
					// Add it to the cached list
					$cached_members[ $row['author_id'] ] = $poster;
				}
			}
			else
			{
				// It's definately a guest...
				$poster = $std->set_up_guest( $row['author_name'] );
				$row['name_css'] = 'unreg';
			}

			//--------------------------------------------------------------

			$row['post_css'] = $post_count % 2 ? 'post1' : 'post2';


			//--------------------------------------------------------------

			if ( ($row['append_edit'] == 1) and ($row['edit_time'] != "") and ($row['edit_name'] != "") )
			{
				$e_time = $std->get_date( $row['edit_time'] , 'LONG' );

				$row['post'] .= "<br /><br /><span class='edit'>".sprintf($ibforums->lang['edited_by'], $row['edit_name'], $e_time)."</span>";
			}

			//--------------------------------------------------------------

			if (!$ibforums->member['view_img'])
			{
				// unconvert smilies first, or it looks a bit crap.

				$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );

				$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>)", $row['post'] );
			}

			//--------------------------------------------------------------

			if ($ibforums->input['hl'])
			{
				$keywords = str_replace( "+", " ", $ibforums->input['hl'] );

				if ( preg_match("/,(and|or),/i", $keywords) )
				{
					while ( preg_match("/,(and|or),/i", $keywords, $match) )
					{
						$word_array = explode( ",".$match[1].",", $keywords );

						if (is_array($word_array))
						{
							foreach ($word_array as $keywords)
							{
								$row['post'] = preg_replace( "/(^|\s)(".preg_quote($keywords, '/').")(\s|,|\.|!|<br|$)/is", "\\1<span class='searchlite'>\\2</span>\\3", $row['post'] );
							}
						}
					}
				}
				else
				{
					while( preg_match( "/(^|\s)(".preg_quote($keywords, '/').")(\s|,|\.|!|<br|$)/i", $row['post'] ) )
					{
						$row['post'] = preg_replace( "/(^|\s)(".preg_quote($keywords, '/').")(\s|,|\.|!|<br|$)/is", "\\1<span class='searchlite'>\\2</span>\\3", $row['post'] );
					}
				}
			}

			//--------------------------------------------------------------

			if ( ($post_count != 0 and $first == 0) or ($first > 0) )
			{
				$row['delete_button'] = $this->delete_button($row['pid'], $poster);
			}


			$row['edit_button']   = $this->edit_button($row['pid'], $poster, $row['post_date']);
			$row['post_date']     = $std->get_date( $row['post_date'], 'LONG' );
			$row['post_icon']     = $row['icon_id']
							  ? "<img src='".$ibforums->vars['img_url']."/icon{$row['icon_id']}.gif' alt='' />&nbsp;&nbsp;"
							  : "";

			$row['ip_address']  = $this->view_ip($row, $poster);

			$row['report_link'] = (($ibforums->vars['disable_reportpost'] != 1) and ( $ibforums->member['id'] ))
							    ? $this->html->report_link($row)
							    : "";

			//--------------------------------------------------------------

			if ($row['attach_id'])
			{
				//----------------------------------------------------
				// If we've not already done so, lets grab our mime-types
				//----------------------------------------------------

				if ( !is_array($this->mimetypes) )
				{
					require "./conf_mime_types.php";
					$this->mimetypes = $mime_types;
					unset($mime_types);
				}

				//----------------------------------------------------
				// Is it an image, and are we viewing the image in the post?
				//----------------------------------------------------

				if (
					 ($ibforums->vars['show_img_upload'])
					   and
					 (
					 	   $row['attach_type'] == 'image/gif'
					 	or $row['attach_type'] == 'image/jpeg'
					 	or $row['attach_type'] == 'image/pjpeg'
					 	or $row['attach_type'] == 'image/x-png'
					 	or $row['attach_type'] == 'image/png'
					 )
					)
				{
					if ( $ibforums->vars['siu_thumb'] AND $ibforums->vars['siu_width'] AND $ibforums->vars['siu_height'] )
					{
						//----------------------------------------------------
						// Tom Thumb!
						//----------------------------------------------------

						$img_size = array();

						$img_size = @GetImageSize( $ibforums->vars['upload_url']."/".$row['attach_id'] );

						if ( $img_size[0] < 1 )
						{
							$img_size    = array();
							$img_size[0] = $ibforums->vars['siu_width'] + 1;
							$img_size[1] = $ibforums->vars['siu_height']+ 1;
						}

						//----------------------------------------------------
						// Do we need to scale?
						//----------------------------------------------------

						if ( ( $img_size[0] <= $ibforums->vars['siu_width'] ) AND ( $img_size[1] <= $ibforums->vars['siu_height'] ) )
						{
							$row['attachment'] = $this->html->Show_attachments_img( $row['attach_id'] );
						}
						else
						{
							$im = $std->scale_image( array(
															'max_width'  => $ibforums->vars['siu_width'],
															'max_height' => $ibforums->vars['siu_height'],
															'cur_width'  => $img_size[0],
															'cur_height' => $img_size[1]
												   )      );

							$row['attachment'] = $this->html->Show_attachments_img_thumb( $row['attach_id'], $im['img_width'], $im['img_height'], $row['pid'] );
						}
					}
					else
					{
						//----------------------------------------------------
						// Standard size..
						//----------------------------------------------------

						$row['attachment'] = $this->html->Show_attachments_img( $row['attach_id'] );
					}
				}
				else
				{
					//----------------------------------------------------
					// Full attachment thingy
					//----------------------------------------------------

					$row['attachment'] = $this->html->Show_attachments( array (
																				'hits'  => $row['attach_hits'],
																				'bt_size'  => $row['bt_size'],
																				'leechers' => $row['leechers'],
																				'seeders' => $row['seeders'],
																				'image' => $this->mimetypes[ $row['attach_type'] ][1],
																				'name'  => $row['attach_file'],
																				'pid'   => $row['pid'],
																	  )  	  );
				}
			}

			//--------------------------------------------------------------
			// Siggie stuff
			//--------------------------------------------------------------

			$row['signature'] = "";

			if ($poster['signature'] and $ibforums->member['view_sigs'])
			{
				if ($row['use_sig'] == 1)
				{
					if ( $ibforums->vars['sig_allow_html'] == 1 )
					{
						$poster['signature'] = $this->parser->parse_html($poster['signature'], 0);
					}

					if ( $ibforums->vars['post_wordwrap'] > 0 )
					{
						$poster['signature'] = $this->parser->my_wordwrap( $poster['signature'], $ibforums->vars['post_wordwrap']) ;
					}

					$row['signature'] = $skin_universal->signature_separator($poster['signature']);
				}
			}

			//--------------------------------------------------------------
			// Fix up the membername so it links to the members profile
			//--------------------------------------------------------------

			if ($poster['id'])
			{
				$poster['name'] = "<a href='{$this->base_url}showuser={$poster['id']}'>{$poster['name']}</a>";
			}

			//--------------------------------------------------------------
			// Parse HTML tag on the fly
			//--------------------------------------------------------------

			if ( $this->forum['use_html'] == 1 )
			{
				// So far, so good..

				if ( stristr( $row['post'], '[dohtml]' ) )
				{
					// [doHTML] tag found..

					$parse = ($this->forum['use_html'] AND $row['g_dohtml']) ? 1 : 0;

					$row['post'] = $this->parser->post_db_parse($row['post'], $parse );
				}
			}

			//--------------------------------------------------------------
			// Do word wrap?
			//--------------------------------------------------------------

			if ( $ibforums->vars['post_wordwrap'] > 0 )
			{
				$row['post'] = $this->parser->my_wordwrap( $row['post'], $ibforums->vars['post_wordwrap']) ;
			}

			//--------------------------------------------------------------
			// A bit hackish - but there are lots of <br> => <br /> changes to make
			//--------------------------------------------------------------

			$row['post']      = str_replace( "<br>", "<br />", $row['post'] );
			$row['signature'] = str_replace( "<br>", "<br />", $row['signature'] );

			$this->output .= $this->html->RenderRow( $row, $poster );

			$post_count++;

		}

		//-------------------------------------
		// Print the footer
		//-------------------------------------

		$this->output .= $this->html->TableFooter( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ) );

		//+----------------------------------------------------------------
		// Process users active in this forum
		//+----------------------------------------------------------------

		if ($ibforums->vars['no_au_topic'] != 1)
		{
			//+-----------------------------------------
			// Get the users
			//+-----------------------------------------

			$cut_off = ($ibforums->vars['au_cutoff'] != "") ? $ibforums->vars['au_cutoff'] * 60 : 900;

			$time = time() - $cut_off;

			$DB->query("SELECT s.member_id, s.member_name, s.login_type, s.location, g.suffix, g.prefix, g.g_perm_id, m.org_perm_id
					    FROM ibf_sessions s
					     LEFT JOIN ibf_groups g ON (g.g_id=s.member_group)
					     LEFT JOIN ibf_members m on (s.member_id=m.id)
					    WHERE s.in_topic={$this->topic['tid']}
					    AND s.running_time > $time
					     ORDER BY s.running_time DESC");

			//+-----------------------------------------
			// Cache all printed members so we don't double print them
			//+-----------------------------------------

			$cached = array();
			$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");

			while ($result = $DB->fetch_row() )
			{
				// Quick check

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


			$this->output = str_replace( "<!--IBF.TOPIC_ACTIVE-->", $this->html->topic_active_users($active), $this->output );

		}

		//+----------------------------------------------------------------
		// Print it
		//+----------------------------------------------------------------

		$this->output = str_replace( "<!--IBF.MOD_PANEL-->", $this->moderation_panel(), $this->output );

		// Enable quick reply box?

		if (   ( $this->topic['quick_reply'] == 1 )
		   and ( $std->check_perms( $this->topic['reply_perms']) == TRUE )
		   and ( $this->topic['state'] != 'closed' ) )
		{
			$show = "none";

			$sqr = $std->my_getcookie("open_qr");

			if ( $sqr == 1 )
			{
				$show = "show";
			}
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_CLOSED-->", $this->html->quick_reply_box_closed(), $this->output );
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_OPEN-->"  , $this->html->quick_reply_box_open($this->topic['forum_id'], $this->topic['tid'], $show, $this->md5_check), $this->output );
		}

		$this->output = str_replace( "<!--IBF.TOPIC_OPTIONS_CLOSED-->", $this->html->topic_opts_closed(), $this->output );
		$this->output = str_replace( "<!--IBF.TOPIC_OPTIONS_OPEN-->"  , $this->html->topic_opts_open($this->topic['forum_id'], $this->topic['tid']), $this->output );

		$this->topic['id'] = $this->topic['forum_id'];

		$this->output = str_replace( "<!--IBF.FORUM_RULES-->", $std->print_forum_rules($this->topic), $this->output );

		//+----------------------------------------------------------------
		// Topic multi-moderation - yay!
		//+----------------------------------------------------------------

		$this->output = str_replace( "<!--IBF.MULTIMOD-->", $this->multi_moderation(), $this->output );

		// Pass it to our print routine

		$print->add_output("$this->output");
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> {$this->topic['title']}",
        					 	  'JS'       => 1,
        					 	  'NAV'      => array(
        					 	  					   "<a href='".$this->base_url."act=SC&amp;c={$this->category['id']}'>{$this->category['name']}</a>",
        					 	  					   $this->nav_extra,
        					 	  					   "<a href='".$this->base_url."showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
        					 	  					 ),
        					  ) );

	}

	//--------------------------------------------------------------
	// Parse the member info
	//--------------------------------------------------------------

	function parse_member($member=array()) {
		global $ibforums, $std, $DB;

		$member['avatar'] = $std->get_avatar( $member['avatar'], $ibforums->member['view_avs'], $member['avatar_size'] );

		$pips = 0;

		foreach($this->mem_titles as $k => $v)
		{
			if ($member['posts'] >= $v['POSTS'])
			{
				if (!$member['title'])
				{
					$member['title'] = $this->mem_titles[ $k ]['TITLE'];
				}
				$pips = $v['PIPS'];
				break;
			}
		}


		if ($member['g_icon'])
		{
			$member['member_rank_img'] = "<img src='{$ibforums->vars[TEAM_ICON_URL]}/{$member['g_icon']}' border='0' alt='Group Icon' />";
		}
		else
		{
			if ($pips)
			{
				if ( preg_match( "/^\d+$/", $pips ) )
				{
					for ($i = 1; $i <= $pips; ++$i)
					{
						$member['member_rank_img'] .= "<{A_STAR}>";
					}
				}
				else
				{
					$member['member_rank_img'] = "<img src='{$ibforums->vars['TEAM_ICON_URL']}/$pips' border='0' alt='*' />";
				}
			}
		}

		$member['member_joined'] = $ibforums->lang['m_joined'].' '.$std->get_date( $member['joined'], 'JOINED' );

		$member['member_group'] = $ibforums->lang['m_group'].' '.$member['g_title'];

		$member['member_posts'] = $ibforums->lang['m_posts'].' '.$std->do_number_format($member['posts']);

		$member['member_number'] = $ibforums->lang['member_no'].' '.$std->do_number_format($member['id']);

		$member['profile_icon'] = "<a href='{$this->base_url}showuser={$member['id']}'><{P_PROFILE}></a>";

		$member['message_icon'] = "<a href='{$this->base_url}act=Msg&amp;CODE=04&amp;MID={$member['id']}'><{P_MSG}></a>";

		if (!$member['hide_email'])
		{
			$member['email_icon'] = "<a href='{$this->base_url}act=Mail&amp;CODE=00&amp;MID={$member['id']}'><{P_EMAIL}></a>";
		}

		if ( $member['website'] and preg_match( "/^http:\/\/\S+$/", $member['website'] ) )
		{
			$member['website_icon'] = "<a href='{$member['website']}' target='_blank'><{P_WEBSITE}></a>";
		}

		if ($member['icq_number'])
		{
			$member['icq_icon'] = "<a href=\"javascript:PopUp('{$this->base_url}act=ICQ&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_ICQ}></a>";
		}

		if ($member['aim_name'])
		{
			$member['aol_icon'] = "<a href=\"javascript:PopUp('{$this->base_url}act=AOL&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_AOL}></a>";
		}

		if ($member['yahoo'])
		{
			$member['yahoo_icon'] = "<a href=\"javascript:PopUp('{$this->base_url}act=YAHOO&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_YIM}></a>";
		}

		if ($member['msnname'])
		{
			$member['msn_icon'] = "<a href=\"javascript:PopUp('{$this->base_url}act=MSN&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_MSN}></a>";
		}

		if ($member['integ_msg'])
		{
			$member['integ_icon'] = "<a href=\"javascript:PopUp('{$this->base_url}act=integ&amp;MID={$member['id']}','Pager','750','450','0','1','1','1')\"><{INTEGRITY_MSGR}></a>";
		}

		if ($ibforums->member['id'])
		{
			$member['addresscard'] = "<a href=\"javascript:PopUp('{$this->base_url}act=Profile&amp;CODE=showcard&amp;MID={$member['id']}','AddressCard','470','300','0','1','1','1')\" title='{$ibforums->lang['ac_title']}'><{ADDRESS_CARD}></a>";
		}

		//--------------------------------------------------------------
		// Warny porny?
		//--------------------------------------------------------------

		if ( $ibforums->vars['warn_on'] and ( ! stristr( $ibforums->vars['warn_protected'], ','.$member['mgroup'].',' ) ) )
		{
			if (
			    ( $ibforums->member['is_mod'] AND $ibforums->member['allow_warn'] )
				or ( $ibforums->member['g_is_supmod'] == 1 )
				or ( $ibforums->vars['warn_show_own'] and ( $ibforums->member['id'] == $member['id'] ) )
			   )
			{
				// Work out which image to show.

				if ( ! $ibforums->vars['warn_show_rating'] )
				{
					if ( $member['warn_level'] <= $ibforums->vars['warn_min'] )
					{
						$member['warn_img']     = '<{WARN_0}>';
						$member['warn_percent'] = 0;
					}
					else if ( $member['warn_level'] >= $ibforums->vars['warn_max'] )
					{
						$member['warn_img']     = '<{WARN_5}>';
						$member['warn_percent'] = 100;
					}
					else
					{

						$member['warn_percent'] = $member['warn_level'] ? sprintf( "%.0f", ( ($member['warn_level'] / $ibforums->vars['warn_max']) * 100) ) : 0;

						if ( $member['warn_percent'] > 100 )
						{
							$member['warn_percent'] = 100;
						}

						if ( $member['warn_percent'] >= 81 )
						{
							$member['warn_img'] = '<{WARN_5}>';
						}
						else if ( $member['warn_percent'] >= 61 )
						{
							$member['warn_img'] = '<{WARN_4}>';
						}
						else if ( $member['warn_percent'] >= 41 )
						{
							$member['warn_img'] = '<{WARN_3}>';
						}
						else if ( $member['warn_percent'] >= 21 )
						{
							$member['warn_img'] = '<{WARN_2}>';
						}
						else if ( $member['warn_percent'] >= 1 )
						{
							$member['warn_img'] = '<{WARN_1}>';
						}
						else
						{
							$member['warn_img'] = '<{WARN_0}>';
						}
					}

					if ( $member['warn_percent'] < 1 )
					{
						$member['warn_percent'] = 0;
					}

					$member['warn_text']  = $this->html->warn_level_warn($member['id'], $member['warn_percent'] );

				}
				else
				{
					// Ratings mode..

					$member['warn_text']  = $ibforums->lang['tt_rating'];
					$member['warn_img']   = $this->html->warn_level_rating($member['id'], $member['warn_level'], $ibforums->vars['warn_min'], $ibforums->vars['warn_max']);
				}

				if ( ( $ibforums->member['is_mod'] AND $ibforums->member['allow_warn'] ) or ( $ibforums->member['g_is_supmod'] == 1 ) )
				{
					$member['warn_add']   = "<a href='{$ibforums->base_url}act=warn&amp;type=add&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($ibforums->input['st'])."' title='{$ibforums->lang['tt_warn_add']}'><{WARN_ADD}></a>";
					$member['warn_minus'] = "<a href='{$ibforums->base_url}act=warn&amp;type=minus&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($ibforums->input['st'])."' title='{$ibforums->lang['tt_warn_minus']}'><{WARN_MINUS}></a>";
				}
			}
		}

		//--------------------------------------------------------------
		// Profile fields stuff
		//--------------------------------------------------------------

		if ( $ibforums->vars['custom_profile_topic'] == 1 )
		{
			foreach( $this->pfields as $id => $pf )
			{
				if ( $member[ $id ] != "" )
				{
					if ( $pf['fhide'] == 1 and $ibforums->member['g_is_supmod'] != 1 )
					{
						$member[ $id ] = "";
					}
					else if ( $pf['ftype'] == 'drop' )
					{
						$member[ $id ] = $this->pfields_dd[$id][$member[ $id ]]; // You just know that's going to make no sense tomorrow.
					}
				}
			}
		}

		return $member;

	}

	//--------------------------------------------------------------
	// Render the delete button
	//--------------------------------------------------------------

	function delete_button($post_id, $poster)
	{
		global $ibforums, $std;

		if ($ibforums->member['id'] == "" or $ibforums->member['id'] == 0)
		{
			return "";
		}

		$button = "<a href=\"javascript:delete_post('{$this->base_url}act=Mod&amp;CODE=04&amp;f={$this->forum['id']}&amp;t={$this->topic['tid']}&amp;p={$post_id}&amp;st={$ibforums->input[st]}&amp;auth_key=".$this->md5_check."')\"><{P_DELETE}></a>";

		if ($ibforums->member['g_is_supmod']) return $button;
		if ($this->moderator['delete_post']) return $button;
		if ($poster['id'] == $ibforums->member['id'] and ($ibforums->member['g_delete_own_posts'])) return $button;
		return "";
	}

	//--------------------------------------------------------------
	// Render the edit button
	//--------------------------------------------------------------

	function edit_button($post_id, $poster, $post_date)
	{
		global $ibforums;

		if ($ibforums->member['id'] == "" or $ibforums->member['id'] == 0)
		{
			return "";
		}

		$button = "<a href=\"{$this->base_url}act=Post&amp;CODE=08&amp;f={$this->forum['id']}&amp;t={$this->topic['tid']}&amp;p={$post_id}&amp;st={$ibforums->input[st]}\"><{P_EDIT}></a>";

		if ($ibforums->member['g_is_supmod']) return $button;

		if ($this->moderator['edit_post']) return $button;

		if ($poster['id'] == $ibforums->member['id'] and ($ibforums->member['g_edit_posts']))
		{

			// Have we set a time limit?

			if ($ibforums->member['g_edit_cutoff'] > 0)
			{
				if ( $post_date > ( time() - ( intval($ibforums->member['g_edit_cutoff']) * 60 ) ) )
				{
					return $button;
				}
				else
				{
					return "";
				}
			}
			else
			{
				return $button;
			}
		}

		return "";
	}


	//--------------------------------------------------------------
	// Render the IP address
	//--------------------------------------------------------------

	function view_ip($row, $poster)
	{
		global $ibforums;

		if ($ibforums->member['g_is_supmod'] != 1 && $this->moderator['view_ip'] != 1)
		{
			return "";
		}
		else
		{
			$row['ip_address'] = $poster['mgroup'] == $ibforums->vars['admin_group']
						  ? "[ ---------- ]"
						  : "[ <a href='{$ibforums->base_url}act=modcp&amp;CODE=ip&amp;incoming={$row['ip_address']}' target='_blank'>{$row['ip_address']}</a> ]";
			return $this->html->ip_show($row['ip_address']);
		}

	}

	//--------------------------------------------------------------
	// Render the topic multi-moderation
	//--------------------------------------------------------------

	function multi_moderation()
	{
		global $ibforums, $std, $DB;

		$mm_html = "";

		$pass_go = FALSE;

		if ( $ibforums->member['id'] )
		{
			if ( $ibforums->member['g_is_supmod'] )
			{
				$pass_go = TRUE;
			}
			else if ( $this->moderator['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}

		if ( $pass_go != TRUE )
		{
			return "";
		}

		$this->forum['topic_mm_id'] = $std->clean_perm_string($this->forum['topic_mm_id']);

		if ( $this->forum['topic_mm_id'] == "" )
		{
			return "";
		}

		//----------------------------------------
		// Get the topic mod thingies
		//----------------------------------------

		$DB->query("SELECT mm_id, mm_title FROM ibf_topic_mmod WHERE mm_id IN(".implode( ",", explode( ",", $this->forum['topic_mm_id'] ) ).") ORDER BY mm_title");

		if ( $DB->get_num_rows() )
		{
			$mm_html = $this->html->mm_start($this->topic['tid']);

			while ( $r = $DB->fetch_row() )
			{
				$mm_html .= $this->html->mm_entry( $r['mm_id'], $r['mm_title'] );
			}

			$mm_html .= $this->html->mm_end();
		}

		return $mm_html;
	}

	//--------------------------------------------------------------
	// Render the moderator links
	//--------------------------------------------------------------

	function moderation_panel() {
		global $ibforums, $std;

		$mod_links = "";

		if (!isset($ibforums->member['id'])) return "";

		$skcusgej = 0;

		if ($ibforums->member['id'] == $this->topic['starter_id'])
		{
			$skcusgej = 1;
		}

		if ($ibforums->member['g_is_supmod'] == 1)
		{
			$skcusgej = 1;
		}

		if ($this->moderator['mid'] != "")
		{
			$skcusgej = 1;
		}

		if ($skcusgej == 0)
		{
		   		return "";
		}

		$actions = array( 'MOVE_TOPIC', 'CLOSE_TOPIC', 'OPEN_TOPIC', 'DELETE_TOPIC', 'EDIT_TOPIC', 'PIN_TOPIC', 'UNPIN_TOPIC', 'UNSUBBIT', 'MERGE_TOPIC', 'SPLIT_TOPIC' );

		foreach( $actions as $key )
		{
			if ($ibforums->member['g_is_supmod'])
			{
				$mod_links .= $this->append_link($key);
			}
			elseif ($this->moderator['mid'])
			{
				if ($key == 'MERGE_TOPIC' or $key == 'SPLIT_TOPIC')
				{
					if ($this->moderator['split_merge'] == 1)
					{
						$mod_links .= $this->append_link($key);
					}
				}
				else
				{
					if ($this->moderator[ strtolower($key) ])
					{
						$mod_links .= $this->append_link($key);
					}
				}
			}
			elseif ($key == 'OPEN_TOPIC' or $key == 'CLOSE_TOPIC')
			{
				if ($ibforums->member['g_open_close_posts'])
				{
					$mod_links .= $this->append_link($key);
				}
			}
			elseif ($key == 'DELETE_TOPIC')
			{
				if ($ibforums->member['g_delete_own_topics'])
				{
					$mod_links .= $this->append_link($key);
				}
			}
		}

		if ($ibforums->member['g_access_cp'] == 1)
		{
			$mod_links .= $this->append_link('TOPIC_HISTORY');
		}

		if ($mod_links != "")
		{
			return $this->html->Mod_Panel($mod_links, $this->forum['id'], $this->topic['tid'], $this->md5_check);

		}

	}

	function append_link( $key="" ) {
		global $ibforums;

		if ($key == "") return "";

		if ($this->topic['state'] == 'open'   and $key == 'OPEN_TOPIC') return "";
		if ($this->topic['state'] == 'closed' and $key == 'CLOSE_TOPIC') return "";
		if ($this->topic['state'] == 'moved'  and ($key == 'CLOSE_TOPIC' or $key == 'MOVE_TOPIC')) return "";
		if ($this->topic['pinned'] == 1 and $key == 'PIN_TOPIC')   return "";
		if ($this->topic['pinned'] == 0 and $key == 'UNPIN_TOPIC') return "";

		++$this->colspan;

		return $this->html->mod_wrapper($this->mod_action[$key], $ibforums->lang[ $key ]);
	}

	//--------------------------------------------------------------
	// Render the reply button
	//--------------------------------------------------------------

	function reply_button()
	{
		global $ibforums;

		if ($this->topic['state'] == 'closed')
		{
			// Do we have the ability to post in
			// closed topics?

			if ($ibforums->member['g_post_closed'] == 1)
			{
				return "<a href='{$this->base_url}act=Post&amp;CODE=02&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid']."'><{A_LOCKED_B}></a>";
			}
			else
			{
				return "<{A_LOCKED_B}>";
			}
		}

		if ($this->topic['state'] == 'moved')
		{
			return "<{A_MOVED_B}>";
		}

		if ($this->topic['poll_state'] == 'closed')
		{
			return "<{A_POLLONLY_B}>";
		}

		return "<a href='{$this->base_url}act=Post&amp;CODE=02&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid']."'><{A_REPLY}></a>";

	}

	function check_access()
	{
		global $ibforums, $std, $HTTP_COOKIE_VARS;

		$return = 1;

		if ( $std->check_perms($this->forum['read_perms']) == TRUE )
		{
			$return = 0;
		}

		if ($this->forum['password'] != "")
		{

			if ( ! $c_pass = $std->my_getcookie('iBForum'.$this->forum['id']) )
			{
				return 1;
			}

			if ( $c_pass == $this->forum['password'] )
			{
				return 0;
			}
			else
			{
			    return 1;
			}
		}

		return $return;

	}

	//--------------------------------------------------------------
	// Process and parse the poll
	//--------------------------------------------------------------

	function parse_poll()
	{
	    global $ibforums, $DB, $std;

	    $html        = "";
	    $check       = 0;
	    $poll_footer = "";

	    $ibforums->lang  = $std->load_words($ibforums->lang, 'lang_post', $ibforums->lang_id);

        $this->poll_html = $std->load_template('skin_poll');

        //----------------------------------
        // Get the poll information...
        //----------------------------------

        $DB->query("SELECT * FROM ibf_polls WHERE tid=".$this->topic['tid']);
        $poll_data = $DB->fetch_row();

        if (! $poll_data['pid']) {
        	return;
        }

        if ( ! $poll_data['poll_question'] )
        {
        	$poll_data['poll_question'] = $this->topic['title'];
        }

        //----------------------------------

        $delete_link = "";
        $edit_link   = "";
        $can_edit    = 0;
        $can_delete  = 0;

        if ($this->moderator['edit_post'])
        {
        	$can_edit = 1;
        }
        if ($this->moderator['delete_post'])
        {
        	$can_delete = 1;
        }

        if ($ibforums->member['g_is_supmod'] == 1)
        {
        	$can_edit   = 1;
        	$can_delete = 1;
        }

        if ($can_edit == 1)
        {
        	$edit_link   = $this->poll_html->edit_link($this->topic['tid'], $this->forum['id'], $this->md5_check );
        }

        if ($can_delete == 1)
        {
        	$delete_link = $this->poll_html->delete_link($this->topic['tid'], $this->forum['id'], $this->md5_check );
        }

        //----------------------------------

        $voter = array( 'id' => 0 );

        //----------------------------------
        // Have we voted in this poll?
        //----------------------------------

        $DB->query("SELECT member_id from ibf_voters WHERE member_id=".$ibforums->member['id']." and tid=".$this->topic['tid']);
        $voter = $DB->fetch_row();

        if ($voter['member_id'] != 0)
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_you_voted'];
        }

        if ( ($poll_data['starter_id'] == $ibforums->member['id']) and ($ibforums->vars['allow_creator_vote'] != 1) )
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_you_created'];
        }

        if (! $ibforums->member['id'] ) {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_no_guests'];
        }

        //----------------------------------
        // is the topic locked?
        //----------------------------------

        if ( $this->topic['state'] == 'closed' )
        {
        	$check = 1;
        	$poll_footer = '&nbsp;';
        }

        if ( $ibforums->vars['allow_result_view'] == 1 )
        {
        	if ( $ibforums->input['mode'] == 'show' )
        	{
        		$check       = 1;
        		$poll_footer = "";
        	}
        }

        if ($check == 1)
        {
        	//---------------------
        	// Show the results
        	//---------------------

        	$total_votes = 0;

        	$html = $this->poll_html->poll_header($this->topic['tid'], $poll_data['poll_question'], $edit_link, $delete_link);

        	$poll_answers = unserialize(stripslashes($poll_data['choices']));

        	reset($poll_answers);
        	foreach ($poll_answers as $entry)
        	{
        		$id     = $entry[0];
        		$choice = $entry[1];
        		$votes  = $entry[2];

        		$total_votes += $votes;

        		if ( strlen($choice) < 1 )
        		{
        			continue;
        		}

        		if ($ibforums->vars['poll_tags'])
        		{
        			$choice = $this->parser->parse_poll_tags($choice);
        		}
        		if ( $ibforums->vars['post_wordwrap'] > 0 )
				{
					$choice = $this->parser->my_wordwrap( $choice, $ibforums->vars['post_wordwrap']) ;
				}

        		$percent = $votes == 0 ? 0 : $votes / $poll_data['votes'] * 100;
        		$percent = sprintf( '%.2f' , $percent );
        		$width   = $percent > 0 ? (int) $percent * 2 : 0;
        		$html   .= $this->poll_html->Render_row_results($votes, $id, $choice, $percent, $width);
        	}

        	$html   .= $this->poll_html->show_total_votes($total_votes);

        }
        else
        {
        	$poll_answers = unserialize(stripslashes($poll_data['choices']));
        	reset($poll_answers);

        	//---------------------
        	// Show poll form
        	//---------------------

        	$html = $this->poll_html->poll_header($this->topic['tid'], $poll_data['poll_question'], $edit_link, $delete_link);

        	foreach ($poll_answers as $entry)
        	{
        		$id     = $entry[0];
        		$choice = $entry[1];
        		$votes  = $entry[2];

        		if ( strlen($choice) < 1 )
        		{
        			continue;
        		}

        		if ($ibforums->vars['poll_tags'])
        		{
        			$choice = $this->parser->parse_poll_tags($choice);
        		}
        		if ( $ibforums->vars['post_wordwrap'] > 0 )
				{
					$choice = $this->parser->my_wordwrap( $choice, $ibforums->vars['post_wordwrap']) ;
				}

        		$html   .= $this->poll_html->Render_row_form($votes, $id, $choice);
        	}

        }

        $html .= $this->poll_html->ShowPoll_footer();

        if ( $poll_footer != "" )
        {
        	//-----------------------------
        	// Already defined..
        	//-----------------------------

        	$html = str_replace( "<!--IBF.VOTE-->", $poll_footer, $html );
        }
        else
        {
        	//-----------------------------
        	// Not defined..
        	//-----------------------------

        	if ( $ibforums->vars['allow_result_view'] == 1 )
        	{
        		if ( $ibforums->input['mode'] == 'show' )
        		{
        			// We are looking at results..

        			$html = str_replace( "<!--IBF.SHOW-->", $this->poll_html->button_show_voteable(), $html );
        		}
        		else
        		{
        			$html = str_replace( "<!--IBF.SHOW-->", $this->poll_html->button_show_results(), $html );
        			$html = str_replace( "<!--IBF.VOTE-->", $this->poll_html->button_vote(), $html );
        		}
        	}
        	else
        	{
        		//-----------------------------
        		// Do not allow result viewing
        		//-----------------------------

        		$html = str_replace( "<!--IBF.VOTE-->", $this->poll_html->button_vote(), $html );
        		$html = str_replace( "<!--IBF.SHOW-->", $this->poll_html->button_null_vote(), $html );
        	}

        }

        $html = str_replace( "<!--IBF.POLL_JS-->", $this->poll_html->poll_javascript($this->topic['tid'], $this->forum['id']), $html );

        return $html;
	}


	function return_last_post()
	{
		global $ibforums, $DB, $std;

		$st = 0;

		if ($this->topic['posts'])
		{
			if ( (($this->topic['posts'] + 1) % $ibforums->vars['display_max_posts']) == 0 )
			{
				$pages = ($this->topic['posts'] + 1) / $ibforums->vars['display_max_posts'];
			}
			else
			{
				$number = ( ($this->topic['posts'] + 1) / $ibforums->vars['display_max_posts'] );
				$pages = ceil( $number);
			}

			$st = ($pages - 1) * $ibforums->vars['display_max_posts'];
		}

		$DB->query("SELECT pid FROM ibf_posts WHERE queued <> 1 AND topic_id='".$this->topic['tid']."' ORDER BY pid DESC LIMIT 1");
		$post = $DB->fetch_row();

		$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&st=$st&"."#entry".$post['pid']);
		exit();

	}
}

?>