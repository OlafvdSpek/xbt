<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.2 (Click Site Module)
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
|   > Click site core module
|   > Module written by Matt Mecham
|   > Date started: 1st July 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

class click_site {

    var $output     = "";
    var $html       = "";
    var $template   = "";
    var $site_bits  = array();
    var $parser     = "";
    var $articles   = array();
    var $recent     = array();
    var $bad_forum  = array();
    var $good_forum = array();
    var $raw        = "";
    
    function click_site()
    {
    	global $ibforums, $DB, $std, $print;
    	
		//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
    	if ( ! $ibforums->vars['csite_on'] )
    	{
    		print "IPDynamic Lite has not been enabled. Please check your Invision Power Board Admin Settings";
    		exit();
    	}
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_csite', $ibforums->lang_id );
    	
    	$this->html = $std->load_template('skin_csite');
    	
    	require ROOT_PATH."sources/lib/post_parser.php";
        
        $this->parser = new post_parser();
        
        if ( $ibforums->vars['dynamiclite'] == "" )
        {
        	$ibforums->vars['dynamiclite'] = $ibforums->base_url.'act=home';
        }
        
        //--------------------------------------------
		// Get site nav / favourites
		//--------------------------------------------
		
        $DB->query("SELECT cs_key, cs_value FROM ibf_cache_store WHERE cs_key IN ('csite_nav_contents', 'csite_fav_contents')");
 		
 		while ( $row = $DB->fetch_row() )
 		{
 			$this->raw[ $row['cs_key'] ] = str_replace( '&#39;', "'", str_replace( "\r\n", "\n", $row['cs_value'] ) );
 		}
        
        //--------------------------------------------
		// Get forums we're allowed to read
		//--------------------------------------------
		
		$DB->query("SELECT id, read_perms, password FROM ibf_forums");
		
		while( $f = $DB->fetch_row() )
		{
			if ( ($std->check_perms($f['read_perms']) != TRUE) or ($f['password'] != "" ) )
        	{
        		$this->bad_forum[] = $f['id'];
        	}
        	else
        	{
        		$this->good_forum[] = $f['id'];
        	}
        }
        
        //--------------------------------------------
    	// Grab articles new/recent in 1 bad ass query
    	//--------------------------------------------
    	
    	$limit = $ibforums->vars['csite_article_max'];
    	
    	if ( $ibforums->vars['csite_article_recent_on'] AND $ibforums->vars['csite_article_recent_max'] )
    	{
    		$limit += $ibforums->vars['csite_article_recent_max'];
    	}
    	
    	if ( count($this->bad_forum) > 0 )
    	{
    		$qe = " AND p.forum_id NOT IN(".implode(',', $this->bad_forum ).") ";
    	}
        
        if ( $ibforums->vars['csite_article_forum'] )
        {
        	$ibforums->vars['csite_article_forum'] = ','.$ibforums->vars['csite_article_forum'];
        }
        
        // have we converted from another board?
        
        if ( $ibforums->vars['vb_configured'] )
        {
			$DB->query("SELECT t.*, f.read_perms, f.use_html, p.*, m.avatar, m.view_avs, m.avatar_size, m.id as member_id, m.name as member_name, m.mgroup, g.g_id, g.g_dohtml
						FROM ibf_posts p
						 LEFT JOIN ibf_topics t on (t.tid=p.topic_id and t.approved=1 and t.moved_to IS NULL)
						 LEFT JOIN ibf_forums f on (f.id=p.forum_id)
						 LEFT JOIN ibf_members m on (p.author_id=m.id)
						 LEFT JOIN ibf_groups g on (g.g_id=m.mgroup)
						WHERE p.forum_id IN (-1{$ibforums->vars['csite_article_forum']}) $qe
						GROUP BY p.topic_id
						ORDER BY t.pinned DESC, p.post_date DESC
						LIMIT 0,$limit");
        }
        else
        {
			$DB->query("SELECT t.*, f.read_perms, f.use_html, p.*, m.avatar, m.view_avs, m.avatar_size,
						m.id as member_id, m.name as member_name, m.mgroup, g.g_id, g.g_dohtml
						FROM ibf_topics t
						 LEFT JOIN ibf_members m ON (t.starter_id=m.id)
						 LEFT JOIN ibf_groups g ON (g.g_id=m.mgroup)
						 LEFT JOIN ibf_posts p ON (p.topic_id=t.tid AND p.new_topic=1)
						 LEFT JOIN ibf_forums f on (f.id=p.forum_id)
						WHERE t.forum_id IN (-1{$ibforums->vars['csite_article_forum']}) $qe
						AND t.approved=1 AND (t.moved_to IS NULL or t.moved_to='')
						ORDER BY t.pinned DESC, t.start_date DESC
						LIMIT 0,$limit");
        }			
        
        $i = 0;
        			
        while ( $r = $DB->fetch_row() )
        {
        	
        	if ( $i >= $ibforums->vars['csite_article_max'] )
        	{
        		//------------------------------------
        		// Store recent
        		//------------------------------------
        		
        		$this->recent[ $r['pid'] ] = $r;
        	}
        	else
        	{
        		//------------------------------------
        		// Store new
        		//------------------------------------
        		
        		$this->articles[ $r['pid'] ] = $r;
        	}
        	
        	$i++;
        }
        
    	//--------------------------------------------
    	// Assign skeletal template ma-doo-bob
    	//--------------------------------------------
    	
    	$this->template = $this->html->csite_skeleton_template();
    	
    	//--------------------------------------------
    	// Work on some fancy replacements
    	//--------------------------------------------
    	
    	$this->site_bits['welcomebox']     = $this->_show_welcomebox();
    	$this->site_bits['search']         = $this->_show_search();
    	$this->site_bits['changeskin']     = $this->_show_changeskin();
    	$this->site_bits['sitenav']        = $this->_show_sitenav();
    	$this->site_bits['onlineusers']    = $this->_show_onlineusers();
    	$this->site_bits['poll']           = $this->_show_poll();
    	$this->site_bits['latestposts']    = $this->_show_latestposts();
    	$this->site_bits['recentarticles'] = $this->_show_recentarticles();
    	$this->site_bits['articles']       = $this->_show_articles();
    	$this->site_bits['affiliates']     = $this->_show_affiliates();
    	
    	$this->_do_output();
    		
 	}
 	
 	//---------------------------------------------------
 	// Do OUTPUT
 	//---------------------------------------------------
 	
 	function _do_output()
 	{
 		global $ibforums, $DB, $std, $print, $Debug;
 		
 		if ($DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>mySQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print $ibforums->debug_html;
        	print "</body></html>";
        	exit();
        }
 		
 		//------------------------------------------
        // CSS
        //------------------------------------------
 		
 		if ( $ibforums->skin['css_method'] == 'external' )
        {
        	$css = $this->html->csite_css_external($ibforums->skin['css_id'], $ibforums->skin['img_dir']);
        }
        else
        {
        	$css = $this->html->csite_css_inline( preg_replace( "#(?!".preg_quote($ibforums->vars['board_url'], '/')."/)style_images/<\#IMG_DIR\#>#is", $ibforums->vars['board_url']."/style_images/".$ibforums->skin['img_dir'], $ibforums->skin['css_text'] ) );
        }
        
        //------------------------------------------
        // TEMPLATE REPLACEMENTS
        //------------------------------------------
        
        $this->site_bits['title']      = $ibforums->vars['csite_title'];
        $this->site_bits['css']        = $css;
        $this->site_bits['javascript'] = $this->html->csite_javascript();
        
        //------------------------------------------
        // SITE REPLACEMENTS
        //------------------------------------------
        
        foreach( $this->site_bits as $sbk => $sbv )
        {
        	$this->template = str_replace( "<!--CS.TEMPLATE.".strtoupper($sbk)."-->", $sbv, $this->template );
        }
        
        //------------------------------------------
      	// MACROS
      	//------------------------------------------
      	
      	$DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set={$ibforums->skin['macro_id']}");
        
      	while ( $row = $DB->fetch_row() )
      	{
			if ($row['macro_value'] != "")
			{
				$this->template = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $this->template );
			}
		}
		
		$this->template = preg_replace( "#(?!".preg_quote($ibforums->vars['board_url'], '/')."/)style_images/<\#IMG_DIR\#>#is", $ibforums->vars['board_url']."/style_images/".$ibforums->skin['img_dir'], $this->template );
		
		//------------------------------------------
      	// DEBUG
      	//------------------------------------------
		
		if ( $ibforums->vars['debug_level'] )
		{
			$this->template = str_replace( "<!--CS.TEMPLATE.DEBUG-->", $this->html->tmpl_debug( $DB->get_query_cnt(), sprintf( "%.4f",$Debug->endTimer() ) ), $this->template );
		}
		
		//------------------------------------------
      	// CPYRT
      	//------------------------------------------
		
		$extra = "";
        $ur    = '(U)';
        
        if ( $ibforums->vars['ipb_reg_number'] )
        {
        	$ur = '(R)';
        	
        	if ( $ibforums->vars['ipb_reg_show'] and $ibforums->vars['ipb_reg_name'] )
        	{
        		$extra = "- Registered to: ". $ibforums->vars['ipb_reg_name'];
        	}
        }
		
        $copyright = "\n\n<div align='center' class='copyright'>Powered by <a href=\"http://www.invisionboard.com\" target='_blank'>IPDynamic Lite</a>{$ur} {$ibforums->version} &copy; 2003 &nbsp;<a href='http://www.invisionpower.com' target='_blank'>IPS, Inc.</a>$extra</div>";
        
        if ($ibforums->vars['ips_cp_purchase'])
        {
        	$copyright = "";
        }
		
		$this->template = str_replace( "<!--CS.TEMPLATE.COPYRIGHT-->", $copyright, $this->template );
		
		//---------------------------------------
		// CHAT
		//---------------------------------------
		
		if ($ibforums->vars['chat_account_no'])
		{
			$ibforums->vars['chat_height'] += 50;
			$ibforums->vars['chat_width']  += 50;
			
			$chat_link = ( $ibforums->vars['chat_display'] == 'self' )
					   ? $this->html->show_chat_link_inline()
					   : $this->html->show_chat_link_popup();
			
			$this->template = str_replace( "<!--IBF.CHATLINK-->", $chat_link, $this->template );
		}
		
		//---------------------------------------
		// BOARD RULES
		//---------------------------------------
		
		if ($ibforums->vars['gl_show'] and $ibforums->vars['gl_title'])
        {
        	if ($ibforums->vars['gl_link'] == "")
        	{
        		$ibforums->vars['gl_link'] = $ibforums->base_url."act=boardrules";
        	}
        	
        	$this->template = str_replace( "<!--IBF.RULES-->", $this->html->rules_link($ibforums->vars['gl_link'], $ibforums->vars['gl_title']), $this->template );
        }
        
		//---------------------------------------
		// Close this DB connection
		//---------------------------------------
		
		$DB->close_db();
		
		//---------------------------------------
		// Start GZIP compression
        //---------------------------------------
        
        if ($ibforums->vars['disable_gzip'] != 1)
        {
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	ob_start('ob_gzhandler');
        	print $buffer;
        }
        
        $print->do_headers();
        
		//------------------------------------------
      	// PRINT!
      	//------------------------------------------
      	
		print $this->template;
		
		exit();
 	}
 	
 	//---------------------------------------------------
 	// Format topic entry
 	//---------------------------------------------------
 	
 	function _tmpl_format_topic($entry, $cut)
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$entry['title'] = strip_tags($entry['title']);
		$entry['title'] = str_replace( "&#33;" , "!" , $entry['title'] );
		$entry['title'] = str_replace( "&quot;", "\"", $entry['title'] );
		
		if (strlen($entry['title']) > $cut)
		{
			$entry['title'] = substr( $entry['title'],0,($cut - 3) ) . "...";
			$entry['title'] = preg_replace( '/&(#(\d+;?)?)?(\.\.\.)?$/', '...',$entry['title'] );
		}
		
		$entry['posts'] = $std->do_number_format($entry['posts']);
 		$entry['views'] = $std->do_number_format($entry['views']);
 		
 		$ibforums->vars['csite_article_date'] = $ibforums->vars['csite_article_date'] ? $ibforums->vars['csite_article_date'] : 'm-j-y H:i';
 		
 		$entry['date']  = gmdate( $ibforums->vars['csite_article_date'], $entry['post_date'] + $std->get_time_offset() );
 		
 		return $this->html->tmpl_topic_row($entry['tid'], $entry['title'], $entry['posts'], $entry['views'], $entry['member_id'], $entry['member_name'], $entry['date']);
 	}
 	
 	
 	//---------------------------------------------------
 	// Main articles
 	//---------------------------------------------------
 	
 	function _show_articles()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$html = "";
 		
 		foreach( $this->articles as $pid => $entry )
 		{
 			$bottom_string = "";
 			$read_more     = "";
 			$top_string    = "";
 			
 			$real_posts = $entry['posts'];
 			
 			$entry['title'] = strip_tags($entry['title']);
 			
 			$entry['posts'] = $std->do_number_format(intval($entry['posts']));
 			$entry['views'] = $std->do_number_format($entry['views']);
 			
 			$comment_link  = $this->html->tmpl_comment_link($entry['tid']);
 			$profile_link  = $std->make_profile_link( $entry['last_poster_name'], $entry['last_poster_id'] );
 			
 			if ( $real_posts > 0 )
 			{
 				$bottom_string = sprintf( $ibforums->lang['article_reply'], $entry['views'], $comment_link, $profile_link );
 			}
 			else
 			{
 				$bottom_string = sprintf( $ibforums->lang['article_noreply'], $entry['views'], $comment_link );
 			}
 			
 			$ibforums->vars['csite_article_date'] = $ibforums->vars['csite_article_date'] ? $ibforums->vars['csite_article_date'] : 'm-j-y H:i';
 		
 			$entry['date'] = gmdate( $ibforums->vars['csite_article_date'], $entry['post_date'] + $std->get_time_offset() );
 			
 			$top_string = sprintf(
 								   $ibforums->lang['article_postedby'],
 								   $std->make_profile_link( $entry['member_name'], $entry['member_id'] ),
 								   $entry['date'],
 								   $entry['posts']
 								 );
 			
 			$entry['post'] = str_replace( '<br>', '<br />', $entry['post'] );
 			
 			$entry['post'] = $this->parser->post_db_parse($entry['post'], ($entry['use_html'] AND $entry['g_dohtml']) );
 			
 			if ( $ibforums->vars['csite_article_chars'] > 0 )
 			{
 				if ( strlen($entry['post']) > $ibforums->vars['csite_article_chars'] )
 				{
 					$entry['post'] = substr( $entry['post'], 0, $ibforums->vars['csite_article_chars'] );
 					
 					$read_more = $this->html->tmpl_readmore_link($entry['tid']);
 				}
 			}
 			
 			//-------------------------------------
 			// Avatar
 			//-------------------------------------
 			
 			$entry['avatar'] = $std->get_avatar( $entry['avatar'], 1, $entry['avatar_size'] );
 			
 			if ( $entry['avatar'] )
 			{
 				$entry['avatar'] = $this->html->tmpl_wrap_avatar( $entry['avatar'] );
 			}
 			
 			$html .= $this->html->tmpl_articles_row($entry, $bottom_string, $read_more, $top_string);
 		
 		}
 		
 		return $this->html->tmpl_articles($html);
 	}
 	
 	//---------------------------------------------------
 	// Recent articles
 	//---------------------------------------------------
 	
 	function _show_recentarticles()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_article_recent_on'] )
 		{
 			return;
 		}
 		
 		if ( count( $this->recent ) < 1 )
 		{
 			return;
 		}
 		
 		$html = "";
 		
 		foreach( $this->recent as $pid => $entry )
 		{
 			$html .= $this->_tmpl_format_topic($entry, $ibforums->vars['csite_article_len']);
 		}
 		
 		return $this->html->tmpl_recentarticles($html);
 	}
 	
 	//---------------------------------------------------
 	// Latest Posts
 	//---------------------------------------------------
 	
 	function _show_latestposts()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_discuss_on'] )
 		{
 			return;
 		}
 		
 		$html  = "";
 		$limit = $ibforums->vars['csite_discuss_max'] ? $ibforums->vars['csite_discuss_max'] : 5;
 		
 		if ( count($this->bad_forum) > 0 )
    	{
    		$qe = " AND forum_id NOT IN(".implode(',', $this->bad_forum ).") ";
    	}
 		
 		$DB->query("SELECT tid, title, posts, starter_id as member_id, starter_name as member_name, start_date as post_date, views
 		            FROM ibf_topics
 		            WHERE state!='closed' AND approved=1 AND (moved_to IS NULL or moved_to='') $qe
 		            ORDER BY start_date DESC LIMIT 0,$limit");
 		            
 		while ( $row = $DB->fetch_row() )
 		{
 			$html .= $this->_tmpl_format_topic($row, $ibforums->vars['csite_discuss_len']);
 		}
 		
 		return $this->html->tmpl_latestposts($html);
 	}
 	
 	//---------------------------------------------------
 	// Poll
 	//---------------------------------------------------
 	
 	function _show_poll()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$extra = "";
 		$sql   = "";
 		$check = 0;
 		
 		if ( ! $ibforums->vars['csite_poll_show'] )
 		{
 			return;
 		}
 		
 		if ( ! $ibforums->vars['csite_poll_url'] )
 		{
 			return;
 		}
 		
 		//------------------------------------------
		// Get the topic ID of the entered URL
		//------------------------------------------
		
		preg_match( "/(\?|&amp;)?(t|showtopic)=(\d+)($|&amp;)/", $ibforums->vars['csite_poll_url'], $match );
		
		$tid = intval(trim($match[3]));
		
		if ($tid == "")
		{
			return;
		}
		
		if ( $ibforums->member['id'] )
		{
			$extra = "LEFT JOIN ibf_voters v ON (v.member_id={$ibforums->member['id']} and v.tid=t.tid)";
			$sql   = ", v.member_id as member_voted";
		}
		
		//------------------------------------------
		// Get the stuff from the DB
		//------------------------------------------
		
		$DB->query("SELECT t.tid, t.title, t.state, t.last_vote, p.* $sql
					 FROM ibf_topics t, ibf_polls p
					 $extra
					 WHERE t.tid=$tid AND p.tid=t.tid");
					 
		$poll = $DB->fetch_row();
		
		if ( ! $poll['pid'] )
		{
			return;
		}
		
		$poll['poll_question'] = $poll['poll_question'] ? $poll['poll_question'] : $poll['title'];
		
		//------------------------------------------
		// Can we vote?
		//------------------------------------------
		
		if ( $poll['state'] == 'closed' )
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_finished'];
        }
		else if (! $ibforums->member['id'] )
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_noguest'];
        }
		else if ( $poll['member_voted'] )
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_voted'];
        }
        else if ( ($poll['starter_id'] == $ibforums->member['id']) and ($ibforums->vars['allow_creator_vote'] != 1) )
        {
        	$check = 1;
        	$poll_footer = $ibforums->lang['poll_novote'];
        }
        else
        {
        	$check = 0;
        	$poll_footer = $this->html->tmpl_poll_vote();
        }
        	
		//------------------------------------------
		// Show it
		//------------------------------------------
		
        if ($check == 1)
        {
        	//----------------------------------
        	// Show the results
        	//----------------------------------
        	
        	$total_votes = 0;
        	
        	$html = $this->html->tmpl_poll_header($poll['poll_question'], $poll['tid']);
        	
        	$poll_answers = unserialize(stripslashes($poll['choices']));
        	
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
        		
        		$percent = $votes == 0 ? 0 : $votes / $poll['votes'] * 100;
        		$percent = sprintf( '%.2f' , $percent );
        		$width   = $percent > 0 ? floor( round( $percent ) * ( 150 / 100 ) ) : 0;
        		
        		$html   .= $this->html->tmpl_poll_result_row($votes, $id, $choice, $percent, $width);
        	}
        }
        else
        {
        	$poll_answers = unserialize(stripslashes($poll['choices']));
        	reset($poll_answers);
        	
        	//----------------------------------
        	// Show poll form
        	//----------------------------------
        	
        	$html = $this->html->tmpl_poll_header($poll['poll_question'], $poll['tid']);
        	
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
        		
        		$html   .= $this->html->tmpl_poll_choice_row($id, $choice);
        	}
        	
        }
        
        $html .= $this->html->tmpl_poll_footer($poll_footer, sprintf( $ibforums->lang['poll_total_votes'], $total_votes ), $poll['tid'] );
 		
 		return $html;
 	}
 	
 	//---------------------------------------------------
 	// Online users
 	//---------------------------------------------------
 	
 	function _show_onlineusers()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_online_show'] )
 		{
 			return;
 		}
 		
 		$this->sep_char = $this->html->csite_sep_char();
 		
 		//------------------------------------------
		// Get the users from the DB
		//------------------------------------------
		
		$time = time() - ( ($ibforums->vars['au_cutoff'] ? $ibforums->vars['au_cutoff'] : 15) * 60 );
		
		$DB->query("SELECT s.id, s.member_id, s.member_name, s.login_type, g.suffix, g.prefix
					FROM ibf_sessions s
					  LEFT JOIN ibf_groups g ON (g.g_id=s.member_group)
					WHERE running_time > $time
					ORDER BY s.running_time DESC");
		
		//------------------------------------------
		// Cache all printed members
		//------------------------------------------
		
		$cached = array();
		$active = array();
		
		while ($result = $DB->fetch_row() )
		{
			if ( strstr( $result['id'], '_session' ) )
			{
				if ( $ibforums->vars['spider_anon'] )
				{
					if ( $ibforums->member['mgroup'] == $ibforums->vars['admin_group'] )
					{
						$active['names'] .= "{$result['member_name']}*{$this->sep_char} \n";
					}
				}
				else
				{
					$active['names'] .= "{$result['member_name']}{$this->sep_char} \n";
				}
			}
			else if ($result['member_id'] == 0 )
			{
				$active['guests']++;
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
							$active['names'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>*{$this->sep_char} \n";
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
						$active['names'] .= "<a href='{$ibforums->base_url}showuser={$result['member_id']}'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>{$this->sep_char} \n";
					}
				}
			}
		}
		
		$active['names'] = preg_replace( "/".preg_quote($this->sep_char)."$/", "", trim($active['names']) );
		
		$active['total']    = $active['members'] + $active['guests'] + $active['anon'];
		$active['visitors'] = $active['guests']  + $active['anon'];
		
		//------------------------------------------
		// Parse language
		//------------------------------------------
		
		$breakdown = sprintf( $ibforums->lang['online_breakdown'], intval($active['total']) );
		$split     = sprintf( $ibforums->lang['online_split']    , intval($active['members']), intval($active['visitors']) );
		
 		
 		return $this->html->tmpl_onlineusers($breakdown, $split, $active['names']);
 	}
 	
 	//---------------------------------------------------
 	// Navigation Stuff
 	//---------------------------------------------------
 	
 	function _show_sitenav()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_nav_show'] )
 		{
 			return;
 		}
 		
 		$links = "";
 		
 		$raw_nav = $this->raw['csite_nav_contents'];
 		
 		foreach( explode( "\n", $raw_nav ) as $l )
 		{
 			preg_match( "#^(.+?)\[(.+?)\]$#is", trim($l), $matches );
 			
 			$matches[1] = trim($matches[1]);
 			$matches[2] = trim($matches[2]);
 			
 			if ( $matches[1] and $matches[2] )
 			{
 				$links .= $this->html->tmpl_links_wrap( str_replace( '{board_url}', $ibforums->base_url, $matches[1] ), $matches[2] );
 			}
 		}
 		
 		return $this->html->tmpl_sitenav($links);
 	}
 	
 	//---------------------------------------------------
 	// Affiliates
 	//---------------------------------------------------
 	
 	function _show_affiliates()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_fav_show'] )
 		{
 			return;
 		}
 		
 		return $this->html->tmpl_affiliates($this->raw['csite_fav_contents']);
 	}
 	
 	//---------------------------------------------------
 	// Change skin
 	//---------------------------------------------------
 	
 	function _show_changeskin()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_skinchange_show'] )
 		{
 			return;
 		}
 		
 		$select = $this->html->tmpl_skin_select_top();
 		
 		//---------------------------------------
 		// Query DB for skins
 		//---------------------------------------
 		
 		$DB->query("SELECT sname, sid, default_set FROM ibf_skins where hidden=0");
 		
 		while( $s = $DB->fetch_row() )
 		{
 			$used = "";
 			
 			if ( $ibforums->member['skin'] == "" )
 			{
 				if ( $s['default_set'] == 1 )
 				{
 					$used = 'selected="selected"';
 				}
 			}
 			else
 			{
 				if ( $ibforums->member['skin'] == $s['sid'] )
 				{
 					$used = 'selected="selected"';
 				}
 			}
 			
 			$select .= $this->html->tmpl_skin_select_row($s['sid'], $s['sname'], $used);
 		}
 		
 		$select .= $this->html->tmpl_skin_select_bottom();
 		
 		return $this->html->tmpl_changeskin($select);
 	}
 	
	//---------------------------------------------------
 	// Search box
 	//---------------------------------------------------
 	
 	function _show_search()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_search_show'] )
 		{
 			return;
 		}
 		
 		return $this->html->tmpl_search();
 	}
 	
 	
 	//---------------------------------------------------
 	// Welcome Box
 	//---------------------------------------------------
 	
 	function _show_welcomebox()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		if ( ! $ibforums->vars['csite_pm_show'] )
 		{
 			return;
 		}
 		
 		$html = "";
 		
 		$return = $_SERVER["HTTP_REFERER"];
 		
 		if ( $return == "" )
 		{
 			$return = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
 		}
 		
 		$return = urlencode($return);
 		
 		if ( $ibforums->member['id'] )
 		{
 			//------------------------------
 			// Work member info
 			//------------------------------
 			
		    $pm_string  = sprintf( $ibforums->lang['wbox_pm_string'] , "<a href='{$ibforums->base_url}act=Msg'>".intval($ibforums->member['new_msg'])."</a>" );
		    $last_visit = sprintf( $ibforums->lang['wbox_last_visit'], $std->get_date( $ibforums->member['last_visit'], 'LONG' ) );
		    
		    $html = $this->html->tmpl_welcomebox_member($pm_string, $last_visit, $ibforums->member['name'], $ibforums->base_url.'act=home');
		    
 		}
 		else
 		{
 			$top_string = sprintf( $ibforums->lang['wbox_guest_reg'], "<a href='{$ibforums->base_url}act=Reg'>{$ibforums->lang['wbox_register']}</a>" );
 			
 			$html = $this->html->tmpl_welcomebox_guest($top_string, $return);
 		}
 		
 		return $html;
 		
	}
 	

        
}

?>
