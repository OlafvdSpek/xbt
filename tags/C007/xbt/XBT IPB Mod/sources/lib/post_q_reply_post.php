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
|   > Reply with quote library
|   > Module written by Matt Mecham
|
+--------------------------------------------------------------------------
*/



class post_functions extends Post {

	var $nav               = array();
	var $title             = "";
	var $post              = array();
	var $topic             = array();
	var $upload            = array();
	var $mod_topic         = array();
	var $quoted_post        = array();
	var $quoted_post_author = array();
	
	var $m_group = "";

	function post_functions($class) {
	
		global $ibforums, $std, $DB;
		
		// Lets load the topic from the database before we do anything else.
		
		$DB->query("SELECT * FROM ibf_topics WHERE forum_id='".$class->forum['id']."' AND tid='".$ibforums->input['t']."'");
		$this->topic = $DB->fetch_row();
		
		// Is it legitimate?
		
		if (! $this->topic['tid'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-------------------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to reply to this topic
		//-------------------------------------------------
		
		if ($this->topic['poll_state'] == 'closed' and $ibforums->member['g_is_supadmin'] != 1)
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_replies') );
		}
		
		if ($this->topic['starter_id'] == $ibforums->member['id'])
		{
			if (! $ibforums->member['g_reply_own_topics'])
			{
				$std->Error( array( LEVEL => 1, MSG => 'no_replies') );
			}
		}
		
		if ($this->topic['starter_id'] != $ibforums->member['id'])
		{
			if (! $ibforums->member['g_reply_other_topics'])
			{
				$std->Error( array( LEVEL => 1, MSG => 'no_replies') );
			}
		}
		
		if ( $std->check_perms($class->forum['reply_perms']) == FALSE )
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_replies') );
		}
		
		// Is the topic locked?
		
		if ($this->topic['state'] != 'open')
		{
			if ($ibforums->member['g_post_closed'] != 1)
			{
				$std->Error( array( LEVEL => 1, MSG => 'locked_topic') );
			}
		}

	}
	
	function process($class) {
	
		global $ibforums, $std, $DB, $print;
		
		//-------------------------------------------------
		// Parse the post, and check for any errors.
		//-------------------------------------------------
		
		$this->post   = $class->compile_post();
		
		//-------------------------------------------------
		// If we have a post to quote, lets sort that now
		//-------------------------------------------------
		
		if ($ibforums->input['QPost'])
		{
		
			$this->quoted_post['AUTHOR']['name'] = $ibforums->input['QAuthorN'];
			
			$this->quoted_post['DATE'] = $std->get_date( $ibforums->input['QDate'], 'LONG' );
			
			$this->quoted_post['post'] = $class->parser->convert( array( 'TEXT'    => $ibforums->input['QPost'],
																		 'SMILIES' => $ibforums->input['enableemo'],
																		 'CODE'    => 1,
																		 'HTML'    => 0,
																	   ) );
																	   
			if ( $class->parser->error != "" )
			{
				$class->obj['post_errors'] = $class->parser->error;
				
				$class->dump_quote = '[QUOTE='.$this->quoted_post['AUTHOR']['name'].','.$this->quoted_post['DATE'].']'.$ibforums->input['QPost'].'[/QUOTE]'."\n";
				
				$this->show_form($class);
			}
			
			// add it to the post
			
			$html = $class->parser->wrap_style( array( 'STYLE' => 'QUOTE',
													   'EXTRA' => '(' . $this->quoted_post['AUTHOR']['name'] . ' @ ' . $this->quoted_post['DATE'] . ')'
													 ) );
			if ( $class->obj['preview_post'] == "" )
			{
				$this->quoted_post['AUTHOR']['name'] = str_replace( "-", "&#045;", $this->quoted_post['AUTHOR']['name'] );
				
				$this->post['post'] = "<!--QuoteBegin-{$this->quoted_post['AUTHOR']['name']}+{$this->quoted_post['DATE']}-->"
									. "{$html['START']}<!--QuoteEBegin--> ".trim($this->quoted_post['post'])." <!--QuoteEnd--> "
									. "{$html['END']} <!--QuoteEEnd--><br>"
									. $this->post['post'];
			}
			else
			{
				$class->dump_quote = '[QUOTE='.$this->quoted_post['AUTHOR']['name'].','.$this->quoted_post['DATE'].']'.$ibforums->input['QPost'].'[/QUOTE]'."\n";
			}
		
		}
		
		if ($class->obj['post_errors'] == "")
		{
			$this->upload = $class->process_upload();
		}
		
		if ( ($class->obj['post_errors'] != "") or ($class->obj['preview_post'] != "") )
		{
			// Show the form again
			
			$this->show_form($class);
		}
		else
		{
			$this->add_reply($class);
		}
	}
	
	
	
	
	
function add_reply($class) {
		
		global $ibforums, $std, $DB, $print;
		
		//-------------------------------------------------
		// Update the post info with the upload array info
		//-------------------------------------------------
		
		$this->post['attach_id']   = $this->upload['attach_id'];
		$this->post['attach_type'] = $this->upload['attach_type'];
		$this->post['attach_hits'] = $this->upload['attach_hits'];
		$this->post['attach_file'] = $this->upload['attach_file'];
		
		//-------------------------------------------------
		// Insert the post into the database to get the
		// last inserted value of the auto_increment field
		//-------------------------------------------------
		
		$this->post['topic_id'] = $this->topic['tid'];
		
		$return_to_move = 0;
		
		if ( ($ibforums->input['mod_options'] != "") or ($ibforums->input['mod_options'] != 'nowt') )
		{
			if ($ibforums->input['mod_options'] == 'pin')
			{
				if ($ibforums->member['g_is_supmod'] == 1 or $class->moderator['pin_topic'] == 1)
				{
					$this->topic['pinned'] = 1;
					
					$class->moderate_log('Pinned topic from post form', $this->topic['title']);
				}
			}
			else if ($ibforums->input['mod_options'] == 'close')
			{
				if ($ibforums->member['g_is_supmod'] == 1 or $class->moderator['close_topic'] == 1)
				{
					$this->topic['state'] = 'closed';
					
					$class->moderate_log('Closed topic from post form', $this->topic['title']);
				}
			}
			else if ($ibforums->input['mod_options'] == 'move')
			{
				if ($ibforums->member['g_is_supmod'] == 1 or $class->moderator['move_topic'] == 1)
				{
					$return_to_move = 1;
				}
			}
			else if ($ibforums->input['mod_options'] == 'pinclose')
			{
				if ($ibforums->member['g_is_supmod'] == 1 or ( $class->moderator['pin_topic'] == 1 AND $class->moderator['close_topic'] == 1 ) )
				{
					$this->topic['pinned'] = 1;
					$this->topic['state']  = 'closed';
					
					$class->moderate_log('Pinned & closed topic from post form', $this->topic['title']);
				}
			}
		}
		
		$db_string = $std->compile_db_string( $this->post );
		
		$DB->query("INSERT INTO ibf_posts (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
		$this->post['pid'] = $DB->get_insert_id();
		
		if ( $class->obj['moderate'] == 1 or $class->obj['moderate'] == 3 )
		{
			$DB->query("UPDATE ibf_forums SET has_mod_posts=1 WHERE id=".$class->forum['id']);
			
			$page = floor( ($this->topic['posts'] + 1) / $ibforums->vars['display_max_posts']);
			$page = $page * $ibforums->vars['display_max_posts'];
			
			$print->redirect_screen( $ibforums->lang['moderate_post'], "showtopic={$this->topic['tid']}&st=$page" );
		}
		
		//-------------------------------------------------
		// If we are still here, lets update the
		// board/forum/topic stats
		//-------------------------------------------------
		
		$class->forum['last_title']       = str_replace( "'", "&#39;", $this->topic['title'] );;
		$class->forum['last_id']          = $this->topic['tid'];
		$class->forum['last_post']        = time();
		$class->forum['last_poster_name'] = $ibforums->member['id'] ?  $ibforums->member['name'] : $ibforums->input['UserName'];
		$class->forum['last_poster_id']   = $ibforums->member['id'];
		$class->forum['posts']++;
		
		// Update the database
		
		$DB->query("UPDATE ibf_forums    SET last_title='"      .$class->forum['last_title']       ."', ".
											"last_id='"         .$class->forum['last_id']          ."', ".
											"last_post='"       .$class->forum['last_post']        ."', ".
											"last_poster_name='".$class->forum['last_poster_name'] ."', ".
											"last_poster_id='"  .$class->forum['last_poster_id']   ."', ".
											"posts='"           .$class->forum['posts']            ."' ".
											"WHERE id='"        .$class->forum['id']               ."'");
		
		
		
		
		//-------------------------------------------------
		// Get the correct number of replies the topic has
		//-------------------------------------------------
		
		$DB->query("SELECT COUNT(pid) as posts FROM ibf_posts WHERE topic_id={$this->topic['tid']} and queued != 1");
		
		$posts = $DB->fetch_row();
		
		$pcount = intval( $posts['posts'] - 1 );
		
		//+------------------------------------------------------------------------------------------------------
		
		$DB->query("UPDATE ibf_topics     SET last_poster_id='"       .$class->forum['last_poster_id']    ."', ".
											  "last_poster_name='"    .$class->forum['last_poster_name']  ."', ".
											  "last_post='"           .$class->forum['last_post']         ."', ".
											  "pinned='"              .$this->topic['pinned']             ."', ".
											  "state='"               .$this->topic['state']              ."', ".
											  "posts=$pcount ".
											  "WHERE tid='"           .$this->topic['tid']             ."'");
		
		//+------------------------------------------------------------------------------------------------------
		
		$DB->query("UPDATE ibf_stats SET TOTAL_REPLIES=TOTAL_REPLIES+1");
		
		//-------------------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-------------------------------------------------
		
		$pcount = "";
		
		if ($ibforums->member['id'])
		{
			if ($class->forum['inc_postcount'])
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
							$class->modules->register_class(&$class);
							$class->modules->on_group_change($ibforums->member['id'], $gid);
						}
					}
				}
			}
			
			$ibforums->member['last_post'] = time();
			
			$DB->query("UPDATE ibf_members SET ".$pcount.$mgroup.
											  "last_post='"    .$ibforums->member['last_post']   ."'".
											  "WHERE id='"     .$ibforums->member['id']."'");
		}
		
		//-------------------------------------------------
		// Are we tracking topics we reply in 'auto_track'?
		//-------------------------------------------------
		
		if ( $ibforums->member['id'] AND $ibforums->input['enabletrack'] == 1 )
		{
			$DB->query("SELECT trid FROM ibf_tracker WHERE topic_id='".$this->topic['tid']."' AND member_id='".$ibforums->member['id']."'");
			
			if ( ! $DB->get_num_rows() )
			{
				$db_string = $DB->compile_db_insert_string( array (
																	'member_id'  => $ibforums->member['id'],
																	'topic_id'   => $this->topic['tid'],
																	'start_date' => time(),
														  )       );
				
				$DB->query("INSERT INTO ibf_tracker ({$db_string['FIELD_NAMES']}) VALUES ({$db_string['FIELD_VALUES']})");
			}
		}
		
		//-------------------------------------------------
		// Check for subscribed topics
		// Pass on the previous last post time of the topic
		// to see if we need to send emails out
		//-------------------------------------------------
		
		$class->topic_tracker( $this->topic['tid'], $this->post['post'], $class->forum['last_poster_name'], $this->topic['last_post'] );
		
		//-------------------------------------------------
		// Redirect them back to the topic
		//-------------------------------------------------
		
		if ($return_to_move == 1)
		{
			$std->boink_it($class->base_url."act=Mod&CODE=02&f={$class->forum['id']}&t={$this->topic['tid']}");
		}
		else
		{
			$page = floor( ($this->topic['posts'] + 1) / $ibforums->vars['display_max_posts']);
			$page = $page * $ibforums->vars['display_max_posts'];
			$std->boink_it($class->base_url."showtopic={$this->topic['tid']}&st=$page&#entry{$this->post['pid']}");
		}
		
	}







	function show_form($class) {
	
		global $ibforums, $std, $DB, $print, $HTTP_POST_VARS;
		
		//-------------------------------------------------
		// Sort out the "raw" textarea input and make it safe incase
		// we have a <textarea> tag in the raw post var.
		//-------------------------------------------------
		
		$quote_post  = $this->post['post'];
		
		$raw_post    = isset($HTTP_POST_VARS['Post']) ? $std->txt_htmlspecialchars($HTTP_POST_VARS['Post']) : "";

		if (isset($raw_post))
		{
			$raw_post = $std->txt_raw2form($raw_post);
			
			if ($class->dump_quote != "")
			{
				$raw_post   = str_replace( "<br>", "\n", $class->dump_quote ) . $raw_post;
				$quote_post = str_replace( "<br>", "\n", $class->dump_quote ) . $quote_post;
			}
		}
		
		//-------------------------------------------------
		// Get the old post
		//-------------------------------------------------
		
		$DB->query("SELECT post, author_id, author_name, post_date, pid FROM ibf_posts WHERE pid='".$ibforums->input['p']."'");
		$this->quoted_post = $DB->fetch_row();
		
		//-------------------------------------------------
		// Unconvert the smilies, HTML and iB_Code
		//-------------------------------------------------
		
		$this->quoted_post['post'] = trim($class->parser->unconvert( $this->quoted_post['post'] ));
		
		//-------------------------------------------------
		// Are we stripping quotes?
		//-------------------------------------------------
		
		if ($ibforums->vars['strip_quotes'])
		{
			$this->quoted_post['post'] = preg_replace( "#\[QUOTE(=.+?,.+?)?\].+?\[/QUOTE\]#is", "", $this->quoted_post['post'] );
		}
		
		//-------------------------------------------------
		// Do we have any posting errors?
		//-------------------------------------------------
		
		if ($class->obj['post_errors'])
		{
			$class->output .= $class->html->errors( $ibforums->lang[ $class->obj['post_errors'] ]);
		}
		
		if ($class->obj['preview_post'])
		{
			$this->post['post'] = $class->parser->post_db_parse(
															     $class->parser->convert( array(
															     								 'TEXT'    => $quote_post,
															     								 'CODE'    => $class->forum['use_ibc'],
															     								 'SMILIES' => $ibforums->input['enableemo'],
															     								 'HTML'    => $class->forum['use_html']
															     						)      ) ,
															     $class->forum['use_html'] AND $ibforums->member['g_dohtml'] ? 1 : 0);
			$class->output .= $class->html->preview( $this->post['post'] );
		}
		
		$class->check_upload_ability();
		
		$class->output .= $class->html_start_form( array( 1 => array( 'CODE', '07' ),
														  2 => array( 't'   , $this->topic['tid']),
														  3 => array( 'p'   , $ibforums->input['p'] )
														) );
		//---------------------------------------
		// START TABLE
		//---------------------------------------
		
		$class->output .= $class->html->table_structure();
		
		//---------------------------------------
		
		$start_table = $class->html->table_top( "{$ibforums->lang['top_txt_reply']} {$this->topic['title']}");
		
		$name_fields = $class->html_name_field();
		
		if (! $class->obj['preview_post'])
		{
			$quote_box   = $class->html->quote_box( $this->quoted_post );
		}
		
		$post_box    = $class->html_post_body( $raw_post );
		
		$mod_options = $class->mod_options(1);
		
		$end_form    = $class->html->EndForm( $ibforums->lang['submit_reply'] );
		
		$post_icons  = $class->html_post_icons();
		
		if ($class->obj['can_upload'])
		{
			$upload_field = $class->html->Upload_field( $std->size_format( $ibforums->member['g_attach_max'] * 1024 ) );
		}
		
		//---------------------------------------
		
		$class->output = preg_replace( "/<!--START TABLE-->/" , "$start_table"  , $class->output );
		$class->output = preg_replace( "/<!--NAME FIELDS-->/" , "$name_fields"  , $class->output );
		$class->output = preg_replace( "/<!--POST BOX-->/"    , "$post_box"     , $class->output );
		$class->output = preg_replace( "/<!--POST ICONS-->/"  , "$post_icons"   , $class->output );
		$class->output = preg_replace( "/<!--UPLOAD FIELD-->/", "$upload_field" , $class->output );
		$class->output = preg_replace( "/<!--MOD OPTIONS-->/" , "$mod_options"  , $class->output );
		$class->output = preg_replace( "/<!--END TABLE-->/"   , "$end_form"     , $class->output );
		$class->output = preg_replace( "/<!--QUOTE BOX-->/"   , "$quote_box"    , $class->output );
		$class->output = str_replace("<!--FORUM RULES-->", $std->print_forum_rules($class->forum), $class->output );
		
		//---------------------------------------
		
		$class->html_add_smilie_box();
		
		//---------------------------------------
		// Add in siggy buttons and such
		//---------------------------------------
		
		$class->html_checkboxes('reply', $this->topic['tid']);
		
		//---------------------------------------
		
		$class->html_topic_summary($this->topic['tid']);
		
		$this->nav = array( "<a href='{$class->base_url}act=SC&c={$class->forum[cat_id]}'>{$class->forum['cat_name']}</a>",
							"<a href='{$class->base_url}showforum={$class->forum['id']}'>{$class->forum['name']}</a>",
							"<a href='{$class->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>",
						  );
						  
		$this->title = $ibforums->lang['quoting_post'].' '.$this->topic['title'];
		
		$print->add_output("$class->output");
		
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->title,
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->nav,
        					  ) );
		
	}
	

}

?>