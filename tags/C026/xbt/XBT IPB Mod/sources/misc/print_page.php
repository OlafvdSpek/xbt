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
|   > Topic display in printable format module
|   > Module written by Matt Mecham
|   > Date started: 25th March 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Printable;

class Printable {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_groups = array();
    var $mem_titles = array();
    var $mod_action = array();
    var $poll_html  = "";
    var $parser     = "";
    
    /***********************************************************************************/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/***********************************************************************************/
    
    function Printable() {
    
    
        global $ibforums, $DB, $std, $print, $skin_universal;
        
        /***********************************/
		// Compile the language file
		/***********************************/
		
        $ibforums->lang      = $std->load_words($ibforums->lang, 'lang_printpage', $ibforums->lang_id);

        $this->html          = $std->load_template('skin_printpage');
        
        require ROOT_PATH."sources/lib/post_parser.php";
        
        $this->parser = new post_parser();
        
        /***********************************/
        // Check the input
        /***********************************/
        
        $ibforums->input['t'] = intval($ibforums->input['t']);
        $ibforums->input['f'] = intval($ibforums->input['f']);
        
        if ($ibforums->input['t'] < 0 or $ibforums->input['f'] < 0)
        {
            $std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-------------------------------------
        // Get the forum info based on the forum ID, get the category name, ID, and get the topic details
        //-------------------------------------
        
        $DB->query("SELECT t.*, f.name as forum_name, f.id as forum_id, f.read_perms, f.password, f.reply_perms, f.start_perms, f.allow_poll, f.posts as forum_posts, f.topics as forum_topics, f.use_html, c.name as cat_name, c.id as cat_id FROM ibf_topics t, ibf_forums f , ibf_categories c where t.tid='".$ibforums->input[t]."' and f.id = t.forum_id and f.category=c.id");
        
        $this->topic = $DB->fetch_row();
        
        $this->forum = array( 'id'           => $this->topic['forum_id']          ,
        					  'name'         => $this->topic['forum_name']        ,
        					  'posts'        => $this->topic['forum_posts']       ,
        					  'topics'       => $this->topic['forum_topics']      ,
        					  'read_perms'   => $this->topic['read_perms']        ,
        					  'allow_poll'   => $this->topic['allow_poll']        ,
        					  'password'     => $this->topic['password']          ,
        					  'use_html'     => $this->topic['use_html']
        					);
        					
        $this->category = array( 'name'   => $this->topic['cat_name'],
        						 'id'     => $this->topic['cat_id']  ,
        				       );
        
        //-------------------------------------
        // Error out if we can not find the forum
        //-------------------------------------
        
        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        //-------------------------------------
        // Error out if we can not find the topic
        //-------------------------------------
        
        if (!$this->topic['tid'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        
        $this->base_url = "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}?s={$ibforums->session_id}";
        
		
        /***********************************/
        // Check viewing permissions, private forums,
        // password forums, etc
        /***********************************/
        
        if ( (!$this->topic['pin_state']) and (!$ibforums->member['g_other_topics']) )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }
        
        $bad_entry = $this->check_access();
        
        if ($bad_entry == 1)
        {
        	$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }
        
        //------------------------------------------------------------
        //
        // Main logic engine
        //
        //------------------------------------------------------------
        
        if ($ibforums->input['client'] == 'choose')
        {
        	// Show the "choose page"
        	
        	$this->page_title = $this->topic['title'];
		
			$this->nav = array ( "<a href='{$this->base_url}&act=SF&f={$this->forum['id']}'>{$this->forum['name']}</a>",
							 	 "<a href='{$this->base_url}&act=ST&f={$this->forum['id']}&t={$this->topic['tid']}'>{$this->topic['title']}</a>"
						       );
						       
						       
			$this->output = $this->html->choose_form($this->forum['id'], $this->topic['tid'], $this->topic['title']);
						       
			$print->add_output("$this->output");
			
        	$print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        	
        	exit(); // Incase we haven't already done so :p
        }
        else
        {
        	$header = 'text/html';
        	$ext    = '.html';
        	
        	switch ($ibforums->input['client'])
        	{
        		case 'printer':
        			$header = 'text/html';
        			$ext    = '.html';
        			break;
        		case 'html':
        			$header = 'unknown/unknown';
        			$ext    = '.html';
        			break;
        		default:
        			$header = 'application/msword';
        			$ext    = '.doc';
        	}
        }
        
        $title = substr( str_replace( " ", "_" , preg_replace( "/&(lt|gt|quot|#124|#036|#33|#39);/", "", $this->topic['title'] ) ), 0, 12);
        
		//$this->output .= "<br><br><font size='1'><center>Powered by Invision Power Board<br>&copy; 2002 Invision PS</center></font></body></html>";
		
		@flush();
		@header("Content-type: $header");
		
		if ($ibforums->input['client'] != 'printer')
		{
			@header("Content-Disposition: attachment; filename=$title".$ext);
		}
		
		print $this->get_posts();
		
		exit;
		
				        
	}
	
	function get_posts()
	{
		global $ibforums, $DB, $std, $skin_universal;
		
		/***********************************/
		// Render the page top
		/***********************************/
		
		$posts_html = $this->html->pp_header( $this->forum['name'], $this->topic['title'], $this->topic['starter_name'] , $this->forum['id'], $this->topic['tid']);

		$max_posts = 300;

		$DB->query( "SELECT * FROM ibf_posts WHERE topic_id='".$this->topic['tid']."'".
				    " and queued !='1' ORDER BY pid LIMIT 0, ".$max_posts);
				    
		// Loop through to pick out the correct member IDs.
		// and push the post info into an array - maybe in the future
		// we can add page spans, or maybe save to a PDF file?
		
		$the_posts      = array();
		$mem_ids        = "";
		$member_array   = array();
		$cached_members = array();
		
		while ($i = $DB->fetch_row() )
		{
			$the_posts[] = $i;
			if ($i['author_id'] != 0)
			{
				if (preg_match( "/'".$i['author_id']."',/", $mem_ids) )
				{
					continue;
				}
				else
				{
					$mem_ids .= "'".$i['author_id']."',";
				}
			}
		}
		
		// Fix up the member_id string
		$mem_ids = preg_replace( "/,$/", "", $mem_ids);
		
		// Get the member profiles needed for this topic
		
		if ($mem_ids != "")
		{
			$DB->query("SELECT g.*, m.* FROM ibf_members m, ibf_groups g WHERE m.id in ($mem_ids) AND m.mgroup=g.g_id");
	
			while ( $m = $DB->fetch_row() )
			{
				if ($m['id'] and $m['name'])
				{
					if (isset($member_array[ $m['id'] ]))
					{
						continue;
					}
					else
					{
						$member_array[ $m['id'] ] = $m;
					}
				}
			}
		}
		
		/***********************************/
		// Format and print out the topic list
		/***********************************/
		
		$td_col_cnt = 0;
		
		foreach ($the_posts as $row) {
		
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
					// Ok, it's NOT in the cache, is it a member thats
					// not been deleted?
					if ($member_array[ $row['author_id'] ])
					{
						$row['name_css'] = 'normalname';
						$poster = $member_array[ $row['author_id'] ];
						// Add it to the cached list
						$cached_members[ $row['author_id'] ] = $poster;
					}
					else
					{
						// It's probably a deleted member, so treat them as a guest
						$poster = $std->set_up_guest( $row['author_id'] );
						$row['name_css'] = 'unreg';
					}
				}
			}
			else
			{
				// It's definately a guest...
				$poster = $std->set_up_guest( $row['author_name'] );
				$row['name_css'] = 'unreg';
			}
			
			//--------------------------------------------------------------
			
			$row['post_css'] = $td_col_count % 2 ? 'post1' : 'post2';
			
			++$td_col_count;
			
			//--------------------------------------------------------------
			
			$row['post'] = preg_replace( "/<!--EDIT\|(.+?)\|(.+?)-->/", "", $row['post'] );
			
			//--------------------------------------------------------------
		
			$row['post_date']   = $std->get_date( $row['post_date'], 'LONG' );
			
			$row['post'] = $this->parse_message($row['post']);
			
							  
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
			// Parse HTML tag on the fly
			//--------------------------------------------------------------
			
			if ( $this->forum['use_html'] == 1 )
			{
				// So far, so good..
				
				if ( stristr( $row['post'], '[dohtml]' ) )
				{
					// [doHTML] tag found..
					
					$parse = ($this->forum['use_html'] AND $poster['g_dohtml']) ? 1 : 0;
					
					$row['post'] = $this->parser->post_db_parse($row['post'], $parse );
				}
			}
			
			$posts_html .= $this->html->pp_postentry( $poster, $row );
			
		}
		
		/***********************************/
		// Print the footer
		/***********************************/
		
		$posts_html .= $this->html->pp_end();
		
		return $posts_html;
	}
	

	function parse_message($message="") {
	
		//$message = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $message );
		
		//$message = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", "\n\n------------ CODE SAMPLE ----------\n"  , $message );
		//$message = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", "\n-----------------------------------\n\n"  , $message );
		
		//$message = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                       , "\n\n------------ QUOTE ----------\n" , $message );
		//$message = preg_replace( "#<!--QuoteBegin--(.+?)\+(.+?)-->(.+?)<!--QuoteEBegin-->#"         , "\n\n------------ QUOTE ----------\n" , $message );
		//$message = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                           , "\n-----------------------------\n\n" , $message );
		
		$message = preg_replace( "#<!--Flash (.+?)-->.+?<!--End Flash-->#e"                         , "(FLASH MOVIE)" , $message );
		//$message = preg_replace( "#<img src=[\"'](\S+?)['\"].+"."?".">#"                            , "(IMAGE: \\1)"   , $message );
		$message = preg_replace( "#<a href=[\"'](http|https|ftp|news)://(\S+?)['\"].+?".">(.+?)</a>#"  , "\\1://\\2"     , $message );
		//$message = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#"                       , "(EMAIL: \\2)"   , $message );
		
		//$message = preg_replace( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#e"    , "\n\n--------------- SQL -----------\n\\2\n----------------\n\n", $message);
		//$message = preg_replace( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#e", "\n\n-------------- HTML -----------\n\\2\n----------------\n\n", $message);
		
		return $message;
		
	}
	
	function check_access() {
		global $ibforums, $std, $HTTP_COOKIE_VARS;
		
		$return = 1;
		
		$this->m_group = $ibforums->member['mgroup'];
		
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
}

?>





