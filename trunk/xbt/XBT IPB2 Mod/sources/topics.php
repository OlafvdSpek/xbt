<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v2.0.3
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > Topic display module
|   > Module written by Matt Mecham
|   > Date started: 18th February 2002
|
|	> Module Version Number: 1.1.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class topics {

    var $output         = "";
    var $base_url       = "";
    var $html           = "";
    var $moderator      = array();
    var $forum          = array();
    var $topic          = array();
    var $mem_titles     = array();
    var $mod_action     = array();
    var $poll_html      = "";
    var $parser         = "";
    var $mimetypes      = "";
    var $nav_extra      = "";
    var $read_array     = array();
    var $mod_panel_html = "";
    var $warn_range     = 0;
    var $warn_done      = 0;

    var $md5_check      = "";
    var $post_count     = 0;
    var $cached_members = array();
    var $first_printed  = 0;
    var $pids           = array();
    var $attach_pids    = array();
    var $first          = "";
    var $qpids          = "";
    var $custom_fields  = "";
    var $last_read_tid  = "";

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
		// Process the topic
		//-----------------------------------------

        $this->topic_set_up();

        //-----------------------------------------
		// Which view are we using?
		//-----------------------------------------

		if ( $ibforums->input['mode'] )
		{
			$this->topic_view_mode = $ibforums->input['mode'];
			$std->my_setcookie( 'topicmode', $ibforums->input['mode'], 1 );
		}
		else
		{
			$this->topic_view_mode = $std->my_getcookie('topicmode');
		}

		if ( ! $this->topic_view_mode )
		{
			//-----------------------------------------
			// No cookie and no URL
			//-----------------------------------------

			$this->topic_view_mode = $ibforums->vars['topicmode_default'] ? $ibforums->vars['topicmode_default'] : 'linear';
		}

        //-----------------------------------------
        // VIEWS
        //-----------------------------------------

        if ( isset($ibforums->input['view']) )
        {
        	if ($ibforums->input['view'] == 'new')
        	{
        		//-----------------------------------------
        		// Newer
        		//-----------------------------------------

        		$DB->simple_construct( array( 'select' => 'tid',
											  'from'   => 'topics',
											  'where'  => "forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post > ".$this->topic['last_post'],
											  'order'  => 'last_post',
											  'limit'  => array( 0,1 )
									)      );

				$DB->simple_exec();

        		if ( $DB->get_num_rows() )
        		{
        			$this->topic = $DB->fetch_row();

        			$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']);
        		}
        		else
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'no_newer') );
        		}
        	}
        	else if ($ibforums->input['view'] == 'old')
        	{
        		//-----------------------------------------
        		// Older
        		//-----------------------------------------

				$DB->simple_construct( array( 'select' => 'tid',
											  'from'   => 'topics',
											  'where'  => "forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post < ".$this->topic['last_post'],
											  'order'  => 'last_post DESC',
											  'limit'  => array( 0,1 )
									)      );

				$DB->simple_exec();

				if ( $DB->get_num_rows() )
        		{
        			$this->topic = $DB->fetch_row();

        			$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']);
        		}
        		else
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'no_older') );
        		}
        	}
        	else if ($ibforums->input['view'] == 'getlastpost')
        	{
        		//-----------------------------------------
        		// Last post
        		//-----------------------------------------

        		$this->return_last_post();
			}
			else if ($ibforums->input['view'] == 'getnewpost')
			{
				//-----------------------------------------
				// Newest post
				//-----------------------------------------

				$st  = 0;
				$pid = "";

				if ( $ibforums->vars['db_topic_read_cutoff'] and $ibforums->member['id'] )
				{
					$row = $DB->simple_exec_query( array( 'select' => 'read_date', 'from' => 'topics_read', 'where' => 'read_tid='.$this->topic['tid'].' AND read_mid='.$ibforums->member['id'] ) );

					$last_time = intval( $row['read_date'] );
				}
				else
				{
					$last_time = $this->last_read_tid;
				}

				$last_time = $last_time ? $last_time : $ibforums->input['last_visit'];

				$DB->simple_construct( array( 'select' => 'MIN(pid) as pid',
											  'from'   => 'posts',
											  'where'  => "queued=0 AND topic_id=".$this->topic['tid']." AND post_date > ".intval($last_time),
											  'limit'  => array( 0,1 )
									)      );

				$DB->simple_exec();

				$post = $DB->fetch_row();

				if ( $post['pid'] )
				{
					$pid = "&#entry".$post['pid'];

					$DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
												  'from'   => 'posts',
												  'where'  => "topic_id=".$this->topic['tid']." AND pid <= ".$post['pid'],
										)      );

					$DB->simple_exec();

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
				}
				else
				{
					$this->return_last_post();
				}
			}
			else if ($ibforums->input['view'] == 'findpost')
			{
				//-----------------------------------------
				// Find a post
				//-----------------------------------------

				$pid = intval($ibforums->input['p']);

				if ( $pid > 0 )
				{
					$DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
												  'from'   => 'posts',
												  'where'  => "topic_id=".$this->topic['tid']." AND pid <= ".$pid,
										)      );

					$DB->simple_exec();

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

					$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&st=$st&p=$pid"."&#entry".$pid);
				}
				else
				{
					$this->return_last_post();
				}
			}
		}

		//-----------------------------------------
		// UPDATE TOPIC?
		//-----------------------------------------

		if ( ! $ibforums->input['b'] )
		{
			if ( $this->topic['topic_firstpost'] < 1 )
			{
				//--------------------------------------
				// No first topic set - old topic, update
				//--------------------------------------

				$DB->simple_construct( array (
												'select' => 'pid',
												'from'   => 'posts',
												'where'  => 'topic_id='.$this->topic['tid'].' AND new_topic=1'
									 )       );

				$DB->simple_exec();

				$post = $DB->fetch_row();

				if ( ! $post['pid'] )
				{
					//-----------------------------------------
					// Get first post info
					//-----------------------------------------

					$DB->simple_construct( array( 'select' => 'pid',
												  'from'   => 'posts',
												  'where'  => "topic_id={$this->topic['tid']}",
												  'order'  => 'pid ASC',
												  'limit'  => array(0,1) ) );
					$DB->simple_exec();

					$first_post  = $DB->fetch_row();
					$post['pid'] = $first_post['pid'];
				}

				if ( $post['pid'] )
				{
					$DB->simple_construct( array (
													'update' => 'topics',
													'set'    => 'topic_firstpost='.$post['pid'],
													'where'  => 'tid='.$this->topic['tid']
										 )       );

					$DB->simple_exec();
				}

				//--------------------------------------
				// Reload "fixed" topic
				//--------------------------------------

				$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&b=1&st={$ibforums->input['st']}&p={$ibforums->input['p']}"."&#entry".$ibforums->input['p']);
			}
		}

		$find_pid = $ibforums->input['pid'] == "" ? $ibforums->input['p'] : $ibforums->input['pid'];

		if ( $find_pid )
		{
			$threaded_pid = '&amp;pid='.$find_pid;
			$linear_pid   = '&amp;view=findpost&amp;p='.$find_pid;
		}

		if ( $this->topic_view_mode == 'threaded' )
		{
			$require = 'topic_threaded.php';

			$this->topic['to_button_threaded'] = $this->html->toutline_mode_choice_on( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=threaded".$threaded_pid, $ibforums->lang['tom_outline'] );
			$this->topic['to_button_standard'] = $this->html->toutline_mode_choice_off( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $ibforums->lang['tom_standard'] );
			$this->topic['to_button_linearpl'] = $this->html->toutline_mode_choice_off( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $ibforums->lang['tom_linear'] );

		}
		else
		{
			$require = 'topic_linear.php';

			$this->topic['to_button_threaded'] = $this->html->toutline_mode_choice_off( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=threaded".$threaded_pid, $ibforums->lang['tom_outline'] );

			if ( $this->topic_view_mode == 'linearplus' )
			{
				$this->topic['to_button_standard'] = $this->html->toutline_mode_choice_off( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $ibforums->lang['tom_standard'] );
				$this->topic['to_button_linearpl'] = $this->html->toutline_mode_choice_on( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $ibforums->lang['tom_linear'] );
			}
			else
			{
				$this->topic['to_button_standard'] = $this->html->toutline_mode_choice_on( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $ibforums->lang['tom_standard'] );
				$this->topic['to_button_linearpl'] = $this->html->toutline_mode_choice_off( "{$ibforums->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $ibforums->lang['tom_linear'] );
			}
		}

		//-----------------------------------------
		// Remove potential [attachmentid= tag in title
		//-----------------------------------------

		$this->topic['title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $this->topic['title'] );

		//-----------------------------------------
		// Load and run lib
		//-----------------------------------------

		require_once( ROOT_PATH . 'sources/lib/'.$require );

		$this->func = new topic_display();
		$this->func->register_class( &$this );
		$this->func->display_topic();

		$this->output .= $this->func->output;

		//-----------------------------------------
		// Do we have a poll?
		//-----------------------------------------

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

		//-----------------------------------------
		// ATTACHMENTS!!!
		//-----------------------------------------

		if ( $this->topic['topic_hasattach'] )
		{
			$this->output = $this->parse_attachments( $this->output, $this->attach_pids );
		}

		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------

		if ($ibforums->vars['no_au_topic'] != 1)
		{
			//-----------------------------------------
			// Get the users
			//-----------------------------------------

			$cut_off = ($ibforums->vars['au_cutoff'] != "") ? $ibforums->vars['au_cutoff'] * 60 : 900;

			$DB->cache_add_query( 'topics_get_active_users',
								  array( 'tid'   => $this->topic['tid'],
										 'time'  => time() - $cut_off,
								)      );

			$DB->simple_exec();

			//-----------------------------------------
			// ACTIVE USERS
			//-----------------------------------------

			$ar_time = time();
			$cached = array();
			$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");
			$rows   = array( $ar_time => array( 'login_type'   => substr($ibforums->member['login_anonymous'],0, 1),
												'running_time' => $ar_time,
												'member_id'    => $ibforums->member['id'],
												'member_name'  => $ibforums->member['name'],
												'member_group' => $ibforums->member['mgroup'] ) );

			//-----------------------------------------
			// FETCH...
			//-----------------------------------------

			while ($r = $DB->fetch_row() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}

			krsort( $rows );

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

						if ( strstr( $result['location'], 'Post,' ) and $result['member_id'] != $ibforums->member['id'] )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = " title='replying...' ";
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


			$this->output = str_replace( "<!--IBF.TOPIC_ACTIVE-->", $this->html->topic_active_users($active), $this->output );

		}

		//-----------------------------------------
		// Print it
		//-----------------------------------------

		if ( $ibforums->member['is_mod'] )
		{
			$this->output = str_replace( "<!--IBF.MOD_PANEL-->", $this->moderation_panel(), $this->output );
		}
		else
		{
			$this->output = str_replace( "<!--IBF.MOD_PANEL_NO_MOD-->", $this->moderation_panel(), $this->output );
		}

		//-----------------------------------------
		// Enable quick reply box?
		//-----------------------------------------

		if (   ( $this->forum['quick_reply'] == 1 )
		   and ( $std->check_perms( $this->forum['reply_perms']) == TRUE )
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

		//-----------------------------------------
		// Topic multi-moderation - yay!
		//-----------------------------------------

		$this->output = str_replace( "<!--IBF.MULTIMOD-->", $this->multi_moderation(), $this->output );

		// Pass it to our print routine

		$print->add_output("$this->output");
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> {$this->topic['title']}",
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->nav,
        				 )      );

	}

	/*-------------------------------------------------------------------------*/
	// ATTACHMENTS
	/*-------------------------------------------------------------------------*/

	function parse_attachments( $html, $attach_pids, $type='attach_pid', $from='pid', $method='post' )
	{
		global $DB, $forums, $std, $ibforums;

		$final_attachments = array();

		if ( count( $attach_pids ) )
		{
			$DB->query(sprintf("select * from ibf_attachments left join xbt_files on bt_info_hash = info_hash where %s in (%s)", $type, implode(',', $attach_pids)));

			while ( $a = $DB->fetch_row() )
			{
				$final_attachments[ $a[ $type ] ][ $a['attach_id'] ] = $a;
			}

			foreach ( $final_attachments as $pid => $data )
			{
				$temp_out = "";
				$temp_hold = array();

				foreach( $final_attachments[$pid] as $aid => $row )
				{
					//-----------------------------------------
					// Is it an image, and are we viewing the image in the post?
					//-----------------------------------------

					if ( $ibforums->vars['show_img_upload'] and $row['attach_is_image'] )
					{
						if ( $row['attach_thumb_location'] AND $row['attach_thumb_width'] )
						{
							$tmp = $this->html->Show_attachments_img_thumb( $row['attach_thumb_location'],
																			$row['attach_thumb_width'],
																			$row['attach_thumb_height'],
																			$row['attach_id'],
																			$std->size_format( $row['attach_filesize'] ),
																			$row['attach_hits'],
																			$row['attach_file'],
																			$method
																		  );

							if ( strstr( $html, '[attachmentid='.$row['attach_id'].']' ) )
							{
								$html = str_replace( '[attachmentid='.$row['attach_id'].']', $tmp, $html );
							}
							else
							{
								$temp_hold['thumb'] .= $tmp . ' ';
							}
						}
						else
						{
							//-----------------------------------------
							// Standard size..
							//-----------------------------------------

							$tmp = $this->html->Show_attachments_img( $row['attach_location'] );

							if ( strstr( $html, '[attachmentid='.$row['attach_id'].']' ) )
							{
								$html = str_replace( '[attachmentid='.$row['attach_id'].']', $tmp, $html );
							}
							else
							{
								$temp_hold['image'] .= $tmp . ' ';
							}
						}
					}
					else
					{
						//-----------------------------------------
						// Full attachment thingy
						//-----------------------------------------

						$tmp = $this->html->Show_attachments( array (
																	  'hits'  => $row['attach_hits'],
																	  'image' => $ibforums->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'],
																	  'name'  => $row['attach_file'],
																	  $from   => $row[$type],
																	  'id'    => $row['attach_id'],
																	  'method'=> $method,
																	  'size'  => $std->size_format( $row['attach_filesize'] ),
																	  'completed' => $row['completed'],
																	  'leechers' => $row['leechers'],
																	  'seeders' => $row['seeders'],
															)  	  );

						if ( strstr( $html, '[attachmentid='.$row['attach_id'].']' ) )
						{
							$html = str_replace( '[attachmentid='.$row['attach_id'].']', $tmp, $html );
						}
						else
						{
							$temp_hold['attach'] .= $tmp;
						}
					}
				}

				//-----------------------------------------
				// Anyfink to show?
				//-----------------------------------------

				if ( $temp_hold['thumb'] )
				{
					$temp_out = $this->html->show_attachment_title($ibforums->lang['attach_thumbs']) . $temp_hold['thumb'];
				}

				if ( $temp_hold['image'] )
				{
					$temp_out .= $this->html->show_attachment_title($ibforums->lang['attach_images']) . $temp_hold['image'];
				}

				if ( $temp_hold['attach'] )
				{
					$temp_out .= $this->html->show_attachment_title($ibforums->lang['attach_normal']) . $temp_hold['attach'];
				}

				if ( $temp_out )
				{
					$html = str_replace( "<!--IBF.ATTACHMENT_{$row[$type]}-->", $temp_out, $html );
				}
			}
		}

		return $html;
	}

	/*-------------------------------------------------------------------------*/
	// Parse post
	/*-------------------------------------------------------------------------*/

	function parse_row( $row = array() )
	{
		global $ibforums, $std, $DB, $forums, $skin_universal;

		$poster = array();

		//-----------------------------------------
		// Cache member
		//-----------------------------------------

		if ($row['author_id'] != 0)
		{
			//-----------------------------------------
			// Is it in the hash?
			//-----------------------------------------

			if ( isset($this->cached_members[ $row['author_id'] ]) )
			{
				//-----------------------------------------
				// Ok, it's already cached, read from it
				//-----------------------------------------

				$poster = $this->cached_members[ $row['author_id'] ];
				$row['name_css'] = 'normalname';
			}
			else
			{
				$row['name_css'] = 'normalname';
				$poster = $this->parse_member( $row );

				//-----------------------------------------
				// Add it to the cached list
				//-----------------------------------------

				$this->cached_members[ $row['author_id'] ] = $poster;
			}
		}
		else
		{
			//-----------------------------------------
			// It's definitely a guest...
			//-----------------------------------------

			$poster = $std->set_up_guest( $row['author_name'] );
			$row['name_css'] = 'unreg';
		}

		//-----------------------------------------

		if ( $row['queued'] or ($this->topic['topic_firstpost'] == $row['pid'] and $this->topic['approved'] != 1) )
		{
			$row['post_css'] = $this->post_count % 2 ? 'post1shaded' : 'post2shaded';
			$row['altrow']   = 'row4shaded';
		}
		else
		{
			$row['post_css'] = $this->post_count % 2 ? 'post1' : 'post2';
			$row['altrow']   = 'row4';
		}

		//-----------------------------------------

		if ( ($row['append_edit'] == 1) and ($row['edit_time'] != "") and ($row['edit_name'] != "") )
		{
			$e_time = $std->get_date( $row['edit_time'] , 'LONG' );

			$row['post'] .= "<br /><br /><span class='edit'>".sprintf($ibforums->lang['edited_by'], $row['edit_name'], $e_time)."</span>";
		}

		//-----------------------------------------

		if (!$ibforums->member['view_img'])
		{
			//-----------------------------------------
			// unconvert smilies first, or it looks a bit crap.
			//-----------------------------------------

			$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );

			$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>)", $row['post'] );
		}

		//-----------------------------------------
		// Highlight...
		//-----------------------------------------

		if ($ibforums->input['hl'])
		{
			$ibforums->input['hl'] = urldecode($ibforums->input['hl']);
			$loosematch = strstr( $ibforums->input['hl'], '*' ) ? 1 : 0;
			$keywords   = str_replace( '*', '', str_replace( "+", " ", str_replace( '-', '', trim($ibforums->input['hl']) ) ) );
			$word_array = array();
			$endmatch1  = "";
			$endmatch2  = "(.)";

			if ( preg_match("/,(and|or),/i", $keywords) )
			{
				while ( preg_match("/,(and|or),/i", $keywords, $match) )
				{
					$word_array = explode( ",".$match[1].",", $keywords );
				}
			}
			else
			{
				$word_array[] = $keywords;
			}

			if ( ! $loosematch )
			{
				$endmatch1 = "(\s|,|\.|!|<br|&|$)";
				$endmatch2 = "(\<|\s|,|\.|!|<br|&|$)";
			}

			if (is_array($word_array))
			{
				foreach ($word_array as $keywords)
				{
					while( preg_match( "/(^|\s|;)(".preg_quote($keywords, '/')."){$endmatch1}/i", $row['post'] ) )
				   {
					   $row['post'] = preg_replace( "/(^|\s|;|\>)(".preg_quote($keywords, '/')."){$endmatch2}/is", "\\1<span class='searchlite'>\\2</span>\\3", $row['post'] );
				   }
				}
			}
		}

		//-----------------------------------------
		// Online, offline?
		//-----------------------------------------

		if ( $row['author_id'] )
		{
			$time_limit = time() - $ibforums->vars['au_cutoff'] * 60;

			$poster['online_status_indicator'] = '<{PB_USER_OFFLINE}>';

			list( $be_anon, $loggedin ) = explode( '&', $row['login_anonymous'] );

			if ( ( $row['last_visit'] > $time_limit or $row['last_activity'] > $time_limit ) AND $be_anon != 1 AND $loggedin == 1 )
			{
				$poster['online_status_indicator'] = '<{PB_USER_ONLINE}>';
			}
		}
		else
		{
			$poster['online_status_indicator'] = '';
		}

		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------

		$row['mq_start_image'] = $this->html->mq_image_add($row['pid']);

		if ( $this->qpids )
		{
			if ( strstr( ','.$this->qpids.',', ','.$row['pid'].',' ) )
			{
				$row['mq_start_image'] = $this->html->mq_image_remove($row['pid']);
			}
		}

		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------

		if ( $ibforums->member['is_mod'] )
		{
			$row['pid_start_image'] = $this->html->pid_image_unselected($row['pid']);

			if ( $ibforums->input['selectedpids'] )
			{
				if ( strstr( ','.$ibforums->input['selectedpids'].',', ','.$row['pid'].',' ) )
				{
					$row['pid_start_image'] = $this->html->pid_image_selected($row['pid']);
				}
			}
		}

		//-----------------------------------------
		// Delete button..
		//-----------------------------------------

		if ( $row['pid'] != $this->topic['topic_firstpost'] )
		{
			$row['delete_button'] = $this->delete_button($row['pid'], $poster);
		}


		$row['edit_button']   = $this->edit_button($row['pid'], $poster, $row['post_date']);

		$row['post_date']     = $std->get_date( $row['post_date'], 'LONG' );

		$row['post_icon']     = $row['icon_id']
							  ? $this->html->post_icon( $row['icon_id'] )
							  : "";

		$row['ip_address']    = $this->view_ip($row, $poster);

		$row['report_link']   = (($ibforums->vars['disable_reportpost'] != 1) and ( $ibforums->member['id'] ))
							  ? $this->html->report_link($row)
							  : "";

		//-----------------------------------------
		// Siggie stuff
		//-----------------------------------------

		$row['signature'] = "";

		if ($poster['signature'] and $ibforums->member['view_sigs'])
		{
			if ($row['use_sig'] == 1)
			{
				$this->parser->pp_do_html  = intval($ibforums->vars['sig_allow_html']);
				$this->parser->pp_wordwrap = $ibforums->vars['post_wordwrap'];
				$this->parser->pp_nl2br    = 1;

				$row['signature'] = $ibforums->skin_global->signature_separator( $this->parser->post_db_parse($poster['signature']) );
			}
		}

		//-----------------------------------------
		// Fix up the membername so it links to the members profile
		//-----------------------------------------

		if ($poster['id'])
		{
			$poster['name'] = "<a href='{$this->base_url}showuser={$poster['id']}'>{$poster['name']}</a>";
		}

		//-----------------------------------------
		// Parse HTML tag on the fly
		//-----------------------------------------

		$this->parser->pp_do_html  = ( $this->forum['use_html'] and $ibforums->cache['group_cache'][ $poster['mgroup'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
		$this->parser->pp_wordwrap = $ibforums->vars['post_wordwrap'];
		$this->parser->pp_nl2br    = $row['post_htmlstate'] == 2 ? 1 : 0;

		$row['post'] = $this->parser->post_db_parse( $row['post'] );

		//-----------------------------------------
		// A bit hackish - but there are lots of <br> => <br /> changes to make
		//-----------------------------------------

		//$row['post']      = str_replace( "<br>", "<br />", $row['post'] );
		//$row['signature'] = str_replace( "<br>", "<br />", $row['signature'] );

		//-----------------------------------------
		// Post number
		//-----------------------------------------

		if ( $this->topic_view_mode == 'linearplus' and $this->topic['topic_firstpost'] == $row['pid'])
		{
			$row['post_count'] = 1;

			if ( ! $this->first )
			{
				$this->post_count++;
			}
		}
		else
		{
			$this->post_count++;

			$row['post_count'] = intval($ibforums->input['st']) + $this->post_count;
		}

		return array( 'row' => $row, 'poster' => $poster );
	}

	/*-------------------------------------------------------------------------*/
	// Parse the member info
	/*-------------------------------------------------------------------------*/

	function parse_member( $member=array() )
	{
		global $ibforums, $std, $DB;

		$member['avatar'] = $std->get_avatar( $member['avatar_location'], $ibforums->member['view_avs'], $member['avatar_size'], $member['avatar_type'] );

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

		if ( $ibforums->cache['group_cache'][ $member['mgroup'] ]['g_icon'] )
		{
			$member['member_rank_img'] = $this->html->member_rank_img($ibforums->cache['group_cache'][ $member['mgroup'] ]['g_icon']);
		}
		else if ( $pips )
		{
			if ( is_numeric( $pips ) )
			{
				for ($i = 1; $i <= $pips; ++$i)
				{
					$member['member_rank_img'] .= "<{A_STAR}>";
				}
			}
			else
			{
				$member['member_rank_img'] = $this->html->member_rank_img( 'style_images/<#IMG_DIR#>/folder_team_icons/'.$pips );
			}
		}

		$member['member_joined'] = $this->html->member_joined( $std->get_date( $member['joined'], 'JOINED' ) );

		$member['member_group']  = $this->html->member_group( $ibforums->cache['group_cache'][ $member['mgroup'] ]['g_title'] );

		$member['member_posts']  = $this->html->member_posts( $std->do_number_format($member['posts']) );

		$member['member_number'] = $this->html->member_number( $std->do_number_format($member['id']) );

		$member['profile_icon']  = $this->html->member_icon_profile( $member['id'] );

		$member['message_icon']  = $this->html->member_icon_msg( $member['id'] );

		if ($member['location'])
		{
			$member['member_location']  = $this->html->member_location( $member['location'] );
		}

		if (! $member['hide_email'])
		{
			$member['email_icon'] = $this->html->member_icon_email( $member['id'] );
		}

		if ( $member['id'] )
		{
			$member['addresscard'] = $this->html->member_icon_vcard( $member['id'] );
		}

		//-----------------------------------------
		// Warny porny?
		//-----------------------------------------

		if ( $ibforums->vars['warn_on'] and ( ! strstr( ','.$ibforums->vars['warn_protected'].',', ','.$member['mgroup'].',' ) ) )
		{
			if (   ( $ibforums->vars['warn_mod_ban'] AND $ibforums->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn'] )
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

				if ( ( $ibforums->vars['warn_mod_ban'] AND $ibforums->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn'] ) or ( $ibforums->member['g_is_supmod'] == 1 ) )
				{
					$member['warn_add']   = "<a href='{$ibforums->base_url}act=warn&amp;type=add&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($ibforums->input['st'])."' title='{$ibforums->lang['tt_warn_add']}'><{WARN_ADD}></a>";
					$member['warn_minus'] = "<a href='{$ibforums->base_url}act=warn&amp;type=minus&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($ibforums->input['st'])."' title='{$ibforums->lang['tt_warn_minus']}'><{WARN_MINUS}></a>";
				}
			}
		}

		//-----------------------------------------
		// Profile fields stuff
		//-----------------------------------------

		if ( $ibforums->vars['custom_profile_topic'] == 1 )
		{
			if ( $this->custom_fields )
			{
				$this->custom_fields->member_data = $member;
				$this->custom_fields->init_data();
				$this->custom_fields->parse_to_view( 1 );

				if ( count( $this->custom_fields->out_fields ) )
				{
					foreach( $this->custom_fields->out_fields as $i => $data )
					{
						if ( $data )
						{
							$member['custom_fields'] .= "\n".$this->custom_fields->method_format_field_for_topic_view( $i );
						}
					}
				}
			}
		}

		return $member;
	}

	/*-------------------------------------------------------------------------*/
	// Render the delete button
	/*-------------------------------------------------------------------------*/

	function delete_button($post_id, $poster)
	{
		global $ibforums, $std;

		if ($ibforums->member['id'] == "" or $ibforums->member['id'] == 0)
		{
			return "";
		}

		$button = $this->html->button_delete($this->forum['id'],$this->topic['tid'],$post_id,$this->md5_check );

		if ($ibforums->member['g_is_supmod']) return $button;
		if ($this->moderator['delete_post']) return $button;
		if ($poster['id'] == $ibforums->member['id'] and ($ibforums->member['g_delete_own_posts'])) return $button;
		return "";
	}

	/*-------------------------------------------------------------------------*/
	// Render the edit button
	/*-------------------------------------------------------------------------*/

	function edit_button($post_id, $poster, $post_date)
	{
		global $ibforums;

		if ($ibforums->member['id'] == "" or $ibforums->member['id'] == 0)
		{
			return "";
		}

		$button = $this->html->button_edit( $this->forum['id'],$this->topic['tid'],$post_id );

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

	/*-------------------------------------------------------------------------*/
	// Render the reply button
	/*-------------------------------------------------------------------------*/

	function reply_button()
	{
		global $ibforums;

		if ($this->topic['state'] == 'closed')
		{
			// Do we have the ability to post in
			// closed topics?

			if ($ibforums->member['g_post_closed'] == 1)
			{
				return $this->html->button_posting( "{$ibforums->base_url}act=Post&amp;CODE=02&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'], "<{A_LOCKED_B}>" );
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

		return $this->html->button_posting( "{$ibforums->base_url}act=Post&amp;CODE=02&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'], "<{A_REPLY}>" );

	}

	/*-------------------------------------------------------------------------*/
	// Poll button
	/*-------------------------------------------------------------------------*/

	function poll_button()
	{
		global $ibforums;

		return $this->forum['allow_poll']
			   ? $this->html->button_posting( "{$ibforums->base_url}act=Post&amp;CODE=10&amp;f=".$this->forum['id'],  "<{A_POLL}>" )
			   : '';
	}

	/*-------------------------------------------------------------------------*/
	// Render the IP address
	/*-------------------------------------------------------------------------*/

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
						  ? $this->html->ip_admin_hide()
						  : $this->html->ip_admin_show( $row['ip_address'] );
			return $this->html->ip_show($row['ip_address']);
		}
	}

	/*-------------------------------------------------------------------------*/
	// Render the topic multi-moderation
	/*-------------------------------------------------------------------------*/

	function multi_moderation()
	{
		global $ibforums, $std, $DB;

		$mm_html = "";

		$mm_array = $std->get_multimod( $this->forum['id'] );

		//-----------------------------------------
		// Print and show
		//-----------------------------------------

		if ( is_array( $mm_array ) and count( $mm_array ) )
		{
			foreach( $mm_array as $m )
			{
				$mm_html .= $this->html->mm_entry( $m[0], $m[1] );
			}
		}

		if ( $mm_html )
		{
			$mm_html = $this->html->mm_start($this->topic['tid']) . $mm_html . $this->html->mm_end();
		}

		return $mm_html;
	}

	/*-------------------------------------------------------------------------*/
	// Render the moderator links
	/*-------------------------------------------------------------------------*/

	function moderation_panel()
	{
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

		//-----------------------------------------
		// Add on approve/unapprove topic fing
		//-----------------------------------------

		if ( $std->can_queue_posts( $this->forum['id'] ) )
		{
			if ( $this->topic['approved'] != 1 )
			{
				$mod_links .= $this->html->mod_wrapper('topic_approve', $ibforums->lang[ 'cpt_approvet' ]);
			}
			else
			{
				$mod_links .= $this->html->mod_wrapper('topic_unapprove', $ibforums->lang[ 'cpt_unapprovet' ]);
			}
		}

		$actions = array( 'MOVE_TOPIC', 'CLOSE_TOPIC', 'OPEN_TOPIC', 'DELETE_TOPIC', 'EDIT_TOPIC', 'PIN_TOPIC', 'UNPIN_TOPIC', 'MERGE_TOPIC', 'UNSUBBIT' );

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

	/*-------------------------------------------------------------------------*/
	// Append mod links
	/*-------------------------------------------------------------------------*/

	function append_link( $key="" )
	{
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

	/*-------------------------------------------------------------------------*/
	// Process and parse the poll
	/*-------------------------------------------------------------------------*/

	function parse_poll()
	{
		global $ibforums, $DB, $std;

	    $html        = "";
	    $check       = 0;
	    $poll_footer = "";

	    $ibforums->lang  = $std->load_words($ibforums->lang, 'lang_post', $ibforums->lang_id);

        $this->poll_html = $std->load_template('skin_poll');

        //-----------------------------------------
        // Get the poll information...
        //-----------------------------------------

        $DB->simple_construct( array( 'select' => '*',
        							  'from'   => 'polls',
        							  'where'  => "tid=".$this->topic['tid']
        					 )      );

        $DB->simple_exec();

        $poll_data = $DB->fetch_row();

        if (! $poll_data['pid'])
        {
        	return;
        }

        if ( ! $poll_data['poll_question'] )
        {
        	$poll_data['poll_question'] = $this->topic['title'];
        }

        //-----------------------------------------

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

        //-----------------------------------------

        $voter = array( 'id' => 0 );

        //-----------------------------------------
        // Have we voted in this poll?
        //-----------------------------------------

        $DB->simple_construct( array( 'select' => 'member_id',
        							  'from'   => 'voters',
        							  'where'  => "member_id=".$ibforums->member['id']." and tid=".$this->topic['tid']
        					 )      );

        $DB->simple_exec();

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

        //-----------------------------------------
        // is the topic locked?
        //-----------------------------------------

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

        //-----------------------------------------
        // Stop the parser killing images
        // 'cos there are too many
        //-----------------------------------------

        $tmp_max_images               = $ibforums->vars['max_images'];
        $ibforums->vars['max_images'] = 0;

        if ($check == 1)
        {
        	//-----------------------------------------
        	// Show the results
        	//-----------------------------------------

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

        	//-----------------------------------------
        	// Show poll form
        	//-----------------------------------------

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
        	//-----------------------------------------
        	// Already defined..
        	//-----------------------------------------

        	$html = str_replace( "<!--IBF.VOTE-->", $poll_footer, $html );
        }
        else
        {
        	//-----------------------------------------
        	// Not defined..
        	//-----------------------------------------

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
        		//-----------------------------------------
        		// Do not allow result viewing
        		//-----------------------------------------

        		$html = str_replace( "<!--IBF.VOTE-->", $this->poll_html->button_vote(), $html );
        		$html = str_replace( "<!--IBF.SHOW-->", $this->poll_html->button_null_vote(), $html );
        	}
        }

        $html = str_replace( "<!--IBF.POLL_JS-->", $this->poll_html->poll_javascript($this->topic['tid'], $this->forum['id']), $html );

        $ibforums->vars['max_images'] = $tmp_max_images;

        return $html;
	}

	/*-------------------------------------------------------------------------*/
	// Return last post
	/*-------------------------------------------------------------------------*/

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

		$DB->simple_construct( array( 'select' => 'MAX(pid) as pid',
        							  'from'   => 'posts',
        							  'where'  => "queued=0 AND topic_id=".$this->topic['tid'],
        							  'limit'  => array( 0,1 )
        					 )      );

        $DB->simple_exec();

		$post = $DB->fetch_row();

		$std->boink_it($ibforums->base_url."showtopic=".$this->topic['tid']."&pid={$post['pid']}&st=$st&"."#entry".$post['pid']);
	}

	/*-------------------------------------------------------------------------*/
	// INIT, innit? IS IT?
	/*-------------------------------------------------------------------------*/

	function topic_init( $load_modules=0 )
	{
		global $ibforums, $forums, $DB, $std;

		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------

        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_topic', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_topic');

        //-----------------------------------------
        // Parser
        //-----------------------------------------

        require_once( ROOT_PATH."sources/lib/post_parser.php" );

        $this->parser = new post_parser();

        //-----------------------------------------
        // Custom Profile fields
        //-----------------------------------------

        if ( $ibforums->vars['custom_profile_topic'] == 1 or $load_modules )
        {
			require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
			$this->custom_fields = new custom_fields( $DB );

			$this->custom_fields->member_id  = $ibforums->member['id'];
			$this->custom_fields->cache_data = $ibforums->cache['profilefields'];
			$this->custom_fields->admin      = intval($ibforums->member['g_access_cp']);
			$this->custom_fields->supmod     = intval($ibforums->member['g_is_supmod']);
        }

        //-----------------------------------------
 		// Get all the member groups and
 		// member title info
 		//-----------------------------------------

        if ( ! is_array( $ibforums->cache['ranks'] ) )
        {
        	$ibforums->cache['ranks'] = array();

			$DB->simple_construct( array( 'select' => 'id, title, pips, posts',
										  'from'   => 'titles',
										  'order'  => "posts DESC",
								)      );

			$DB->simple_exec();

			while ($i = $DB->fetch_row())
			{
				$ibforums->cache['ranks'][ $i['id'] ] = array(
															  'TITLE' => $i['title'],
															  'PIPS'  => $i['pips'],
															  'POSTS' => $i['posts'],
															);
			}

			$std->update_cache( array( 'name' => 'ranks', 'array' => 1, 'deletefirst' => 1 ) );
        }

        $this->mem_titles = $ibforums->cache['ranks'];
	}

	/*-------------------------------------------------------------------------*/
	// MAIN init
	/*-------------------------------------------------------------------------*/

	function init($topic="")
	{
		global $ibforums, $forums, $DB, $std, $print;

		 $this->md5_check = $std->return_md5_check();

		 $this->topic_init();

        if ( ! is_array($topic) )
        {
			//-----------------------------------------
			// Check the input
			//-----------------------------------------

			$ibforums->input['t'] = intval($ibforums->input['t']);

			if ( $ibforums->input['t'] < 0  )
			{
				$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
			}


			//-----------------------------------------
			// Get the forum info based on the forum ID,
			// get the category name, ID, and get the topic details
			//-----------------------------------------

			if ( ! $ibforums->topic_cache['tid'] )
			{
				$DB->simple_construct( array( 'select' => '*',
											  'from'   => 'topics',
											  'where'  => "tid=".$ibforums->input['t'],
									)      );

				$DB->simple_exec();

				$this->topic = $DB->fetch_row();
			}
			else
			{
				$this->topic = $ibforums->topic_cache;
			}
		}
		else
		{
			$this->topic = $topic;
		}

        $this->forum = $forums->forum_by_id[ $this->topic['forum_id'] ];

        $ibforums->input['f'] = $this->forum['id'];

        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------

        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }

        //-----------------------------------------
        // Error out if we can not find the topic
        //-----------------------------------------

        if (!$this->topic['tid'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'is_broken_link') );
        }

        //-----------------------------------------
        // Error out if the topic is not approved
        //-----------------------------------------

        if ( ! $std->can_queue_posts($this->forum['id']) )
        {
			if ($this->topic['approved'] != 1)
			{
				$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
			}
        }

        //-----------------------------------------
        // If this forum is a link, then
        // redirect them to the new location
        //-----------------------------------------

        if ($this->topic['state'] == 'link')
        {
        	$f_stuff = explode("&", $this->topic['moved_to']);
        	$print->redirect_screen( $ibforums->lang['topic_moved'], "showtopic={$f_stuff[0]}" );
        }

        $forums->forums_check_access( $this->forum['id'], 1, 'topic' );

        //-----------------------------------------
        // Unserialize the read array and parse into
        // array
        //-----------------------------------------

        if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));

        	if (! is_array($this->read_array) )
        	{
        		$this->read_array = array();
        	}
        }

        $this->last_read_tid = $this->read_array[$this->topic['tid']];

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
	}

	/*-------------------------------------------------------------------------*/
	// Topic set up ya'll
	/*-------------------------------------------------------------------------*/

	function topic_set_up()
	{
		global $DB, $ibforums, $std, $forums, $skin_universal;

		$this->base_url = $ibforums->base_url;

		$this->forum['JUMP'] = $std->build_forum_jump();

		$this->first = intval($ibforums->input['st']);

        //-----------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-----------------------------------------

        if ( (!$this->topic['pinned']) and ( ( ! $ibforums->member['g_other_topics'] ) AND ( $this->topic['starter_id'] != $ibforums->member['id'] ) ) )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }

        //-----------------------------------------
        // Update the topic views counter
        //-----------------------------------------

        $DB->simple_construct( array( 'update' => 'topics',
									  'set'    => 'views=views+1',
									  'where'  => "tid=".$this->topic['tid'],
									  'lowpro' => 1,
							)      );

		$DB->simple_shutdown_exec();

        //-----------------------------------------
        // Update the topic read cookie / counters
        //-----------------------------------------

        if ( ( $ibforums->member['id'] ) and ( $ibforums->input['view'] == "" ) )
        {
			$this->read_array[$this->topic['tid']] = time();

			$std->my_setcookie('topicsread', serialize($this->read_array), -1 );

			if ( $ibforums->vars['db_topic_read_cutoff'] and $ibforums->input['view'] != 'getnewpost' )
			{
				$DB->cache_add_query( 'topics_replace_topic_read', array( 'tid' => $this->topic['tid'], 'mid' => $ibforums->member['id'], 'date' => time() ) );
				$DB->cache_shutdown_exec();
			}
        }

        //-----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //-----------------------------------------

       	$this->nav = $forums->forums_breadcrumb_nav( $this->forum['id'] );

        //-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------

		if ( ($ibforums->member['id']) and ($ibforums->member['g_is_supmod'] != 1) )
		{
			$DB->cache_add_query('topics_check_for_mod',  array( 'fid' => $this->forum['id'], 'mid' => $ibforums->member['id'], 'gid' => $ibforums->member['mgroup'] ) );
			$DB->simple_exec();

			$this->moderator = $DB->fetch_row();
		}

		$this->mod_action = array( 'CLOSE_TOPIC'   => '00',
								   'OPEN_TOPIC'    => '01',
								   'MOVE_TOPIC'    => '02',
								   'DELETE_TOPIC'  => '03',
								   'EDIT_TOPIC'    => '05',
								   'PIN_TOPIC'     => '15',
								   'UNPIN_TOPIC'   => '16',
								   'UNSUBBIT'      => '30',
								   'MERGE_TOPIC'   => '60',
								   'TOPIC_HISTORY' => '90',
								 );


		//-----------------------------------------
        // Get the reply, and posting buttons
        //-----------------------------------------

        $this->topic['POLL_BUTTON']   = $this->poll_button();

		$this->topic['REPLY_BUTTON']  = $this->reply_button();


		//-----------------------------------------
		// Hi! Light?
		//-----------------------------------------

		if ($ibforums->input['hl'])
		{
			$hl = '&amp;hl='.$ibforums->input['hl'];
		}

		//-----------------------------------------
		// If we can see queued topics, add count
		//-----------------------------------------

		if ( $std->can_queue_posts($this->forum['id']) )
		{
			$this->topic['posts'] += intval( $this->topic['topic_queuedposts'] );
		}

		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------

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


		//-----------------------------------------
		// Fix up some of the words
		//-----------------------------------------

		$this->topic['TOPIC_START_DATE'] = $std->get_date( $this->topic['start_date'], 'LONG' );

		$ibforums->lang['topic_stats'] = preg_replace( "/<#START#>/", $this->topic['TOPIC_START_DATE'], $ibforums->lang['topic_stats']);
		$ibforums->lang['topic_stats'] = preg_replace( "/<#POSTS#>/", $this->topic['posts']           , $ibforums->lang['topic_stats']);

		if ($this->topic['description'])
		{
			$this->topic['description'] = ', '.$this->topic['description'];
		}

		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------

		$this->qpids = $std->my_getcookie('mqtids');

		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------

		$ibforums->input['selectedpids'] = $std->my_getcookie('modpids');

		$ibforums->input['selectedpidcount'] = intval( count( preg_split( "/,/", $ibforums->input['selectedpids'], -1, PREG_SPLIT_NO_EMPTY ) ) );

		$std->my_setcookie('modpids', '', 0);
	}

}

?>