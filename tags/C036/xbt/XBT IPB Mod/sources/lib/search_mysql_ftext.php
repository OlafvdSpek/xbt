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
|   > MySQL FULL TEXT Search Library
|   > Module written by Matt Mecham
|   > Date started: 31st March 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


class search_lib extends Search
{

    var $parser      = "";
    var $is          = "";
    
    //--------------------------------------------
	// Constructor
	//--------------------------------------------
    	
    function search_lib($that)
    {
    	global $ibforums, $DB, $std, $print;
    	
    	$this->is = &$that; // hahaha!
 	}
 	
 	
 	//--------------------------------------------
	// Simple search
	//--------------------------------------------
 

	function do_simple_search()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		if ( ! $ibforums->input['sid'] )
		{
			$boolean = "";
			
			//--------------------------------------------
			// NEW SEARCH.. Check Keywords..
			//--------------------------------------------
			
			if ( $this->is->mysql_version >= 40010 )
			{
				$boolean  = 'IN BOOLEAN MODE';
				$keywords = $this->is->filter_ftext_keywords($ibforums->input['keywords']);
			}
			else
			{
				$keywords = $this->is->filter_keywords($ibforums->input['keywords']);
			}
			
			$check_keywords = trim($keywords);
			
			if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
			}
			
			if (strlen(trim($keywords)) < 4)
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => 4) );
			}
			
			//--------------------------------------------
			// Get forums...
			//--------------------------------------------
			
			$forums = $this->is->get_searchable_forums();
			
			if ($forums == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
			}
			
			//--------------------------------------------
			// How many results?
			//--------------------------------------------
			
			$DB->query("SELECT COUNT(*) as dracula FROM ibf_posts p WHERE p.forum_id IN ($forums) AND MATCH(post) AGAINST ('$check_keywords' $boolean)");
			
			$count = $DB->fetch_row();
			
			if ( $count['dracula'] < 1 ) // Tee-hee!
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
			
			//--------------------------------------------
			// Store it daddy-o!
			//--------------------------------------------
			
			$cache = "SELECT MATCH(post) AGAINST ('$check_keywords' $boolean) as score, t.tid, t.title, t.posts, t.views,
			                 f.category, f.id, f.name, p.post, p.author_id, p.author_name, p.post_date, p.pid
					  FROM ibf_posts p
					   LEFT JOIN ibf_forums f ON (p.forum_id=f.id)
					   LEFT JOIN ibf_topics t on (p.topic_id=t.tid)
					  WHERE p.forum_id IN ($forums) AND t.title IS NOT NULL
					  AND MATCH(post) AGAINST ('$check_keywords' $boolean)";
			
			if ( $ibforums->input['sortby'] != "relevant" )
			{
				$cache .= " ORDER BY p.post_date DESC";
			}
					  
			$unique_id = md5(uniqid(microtime(),1));
		
			$str = $DB->compile_db_insert_string( array (
															'id'         => $unique_id,
															'search_date'=> time(),
															'topic_id'   => '00',
															'topic_max'  => $count['dracula'],
															'member_id'  => $ibforums->member['id'],
															'ip_address' => $ibforums->input['IP_ADDRESS'],
															'post_id'    => '00',
															'query_cache'=> $cache,

												)        );
			
			$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
			
			$print->redirect_screen( $ibforums->lang['search_redirect'] , "act=Search&CODE=simpleresults&sid=$unique_id&highlite=".urlencode(trim(str_replace( '&amp;', '&', $ibforums->input['keywords']))) );
		
		}
		else
		{
			//--------------------------------------------
			// Get SQL schtuff
			//--------------------------------------------
			
			$this->unique_id = $ibforums->input['sid'];
		
			if ($this->unique_id == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
			
			$DB->query("SELECT * FROM ibf_search_results WHERE id='{$this->unique_id}'");
			
			if ( ! $sr = $DB->fetch_row() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
		
			$query = stripslashes($sr['query_cache']);
		
			$check_keywords = preg_replace( '/&amp;(lt|gt|quot);/', "&\\1;", trim(urldecode($ibforums->input['highlite'])) );
		
			//--------------------------------------------
			// Display
			//--------------------------------------------
			
			$this->links = $std->build_pagelinks(
						     array(
						      		'TOTAL_POSS'  => $sr['topic_max'],
						      		'leave_out'   => 10,
									'PER_PAGE'    => 25,
									'CUR_ST_VAL'  => $this->is->first,
									'L_SINGLE'    => $ibforums->lang['sp_single'],
									'L_MULTI'     => "",
									'BASE_URL'    => $ibforums->base_url."&amp;act=Search&amp;CODE=simpleresults&amp;sid=".$this->unique_id."&amp;highlite=".urlencode($check_keywords),
								  )
						   					    );
		
			require "./sources/lib/post_parser.php";
			$this->parser = new post_parser();
			
			//--------------------------------------------
			// Get categories
			//--------------------------------------------
			
			$DB->query("SELECT id, name FROM ibf_categories");
			
			while ( $cat = $DB->fetch_row() )
			{
				$cat_array[ $cat['id'] ] = $cat['name'];
			}
			
			
			//--------------------------------------------
			// oh look, a query!
			//--------------------------------------------
			
			$last_tid = 0;
			
			$SQLtime = new Debug();
			
			$SQLtime->startTimer();
			
			$DB->query($query." LIMIT {$this->is->first},25");
			
			$ex_time = sprintf( "%.4f",$SQLtime->endTimer() );
			
			$show_end = 25;
			
			if ( $sr['topic_max'] < 25 )
			{
				$show_end = $sr['topic_max'];
			}
			
			$this->output .= $this->is->html->result_simple_header(array(
																		 'links'   => $this->links,
																		 'start'   => $this->is->first,
																		 'end'     => $show_end + $this->is->first,
																		 'matches' => $sr['topic_max'],
																		 'ex_time' => $ex_time,
																		 'keyword' => $check_keywords,
																  )     );
						
			while ( $row = $DB->fetch_row() )
			{
				//---------------------------------------------------------------------
				// Listen up, this is relevant.
				// MySQL's relevance is a bit of a mystery. It's
				// based on many hazy variables such as placing, occurance
				// and such. The result is a floating point number, like 1.239848556
				// No one can really disect what this means in human terms, so I'm
				// going to simply assume that anything over 1.0 is 100%, and *100 any
				// other relevance result.
				//---------------------------------------------------------------------
				
				$row['relevance'] = sprintf( "%3d", ( $row['score'] > 1.0 ) ? 100 : $row['score'] * 100 );
				
				//-----------------------------------------
				// Fix up da post
				//-----------------------------------------
				
				$row['post']      = $this->parser->unconvert($row['post']);
				
				// Strip out BB tags
				
				$row['post']      = preg_replace( "#\[.+?/?\]#", "", $row['post'] );
				
				// Try and locate keyword in the post...
				
				$start     = 0;
				$end       = 250;
				$tmp_post  = strtolower($row['post']);
				$tmp_words = explode(" ", trim(strtolower($check_keywords)) );
				
				if ( $st = strpos( $tmp_post, $tmp_words[0] ) )
				{
					$start  = $st - 100;
					$start  = $start < 0 ? 0 : $start;
					$end   += $start;
				}
				
				$row['post'] = substr( $row['post'], $start, $end );
				
				
				// Finish tidy up..
				
				if ( $start > 0 )
				{
					// We're cutting at the start, so..
					// If it's not a capital letter, it's probably
					// cut a word in half / it's a lower case word so it doesnt matter
					
					if ( ! preg_match( "/^[A-Z]/", $row['post'] ) )
					{
						// find the first space..
						
						if ( $new_start = strpos( $row['post'], " " ) )
						{
							// Cut to space..
							
							$row['post'] = substr( $row['post'], $new_start );
							
							$row['post'] = '...'.$row['post'];
						}
					}
				}
				
				// Tidy up the end..
				// Find the last space..
				
				if ( strlen( $row['post'] ) > 250  )
				{
					if ( $new_end = strrpos( $row['post'], " " ) )
					{
						$row['post'] = substr( $row['post'], 0, $new_end );
						
						$row['post'] .= '...';
					}
				}	
					
				// Basic highlighting
				
				$row['post'] = preg_replace( "/".preg_quote($tmp_words[0], '/')."/i", "<strong>".$tmp_words[0]."</strong>", $row['post'] );
				
				$row['css_class'] = 'googleroot';
				
				if ( $row['tid'] == $last_tid )
				{
					$row['css_class'] = 'googlechild';
				}
				
				$last_tid = $row['tid'];
				
				$row['cat_id']   = $row['category'];
				$row['cat_name'] = $cat_array[ $row['category'] ];
				
				$row['post_date'] = $std->get_date( $row['post_date'], 'LONG' );
				
				// Link member's name
				
				if ($row['author_id'])
				{
					$row['author_name'] = "<a href='{$ibforums->base_url}act=Profile&amp;MID={$row['author_id']}'>{$row['author_name']}</a>";
				}
				
				$this->output .= $this->is->html->result_simple_entry($row);
			}			
						
			$this->output .= $this->is->html->result_simple_footer(array(
																		 'links'   => $this->links,
																  )     );
																  			
			$print->add_output("$this->output");
			$print->do_output( array( 'TITLE' => $ibforums->lang['g_simple_title'], 'JS' => 0, NAV => array( $ibforums->lang['g_simple_title'] ) ) );
    	
    	}
 	
 	}
 	
 	
 	
 	
 	//--------------------------------------------
	// Main Board Search-e-me-doo-daa
	//--------------------------------------------
 

	function do_main_search()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		//------------------------------------
		// Do we have any input?
		//------------------------------------
		
		//------------------------------------
		// USING FULL TEXT - Wooohoo!!
		//------------------------------------
		
		
		if ($ibforums->input['namesearch'] != "")
		{
			$name_filter = $this->is->filter_keywords($ibforums->input['namesearch'], 1);
		}
			
		if ($ibforums->input['useridsearch'] != "")
		{
			$keywords = $this->is->filter_keywords($ibforums->input['useridsearch']);
			$this->is->search_type = 'userid';
		}
		else
		{
			$keywords = $this->is->filter_keywords($ibforums->input['keywords']);
			$this->is->search_type = 'posts';
		}
		
		if ( $name_filter != "" AND $ibforums->input['keywords'] != "" )
		{
			$type = 'joined';
		}
		else if ( $name_filter == "" AND $ibforums->input['keywords'] != "" )
		{
			$type= 'postonly';
		}
		else if ( $name_filter != "" AND $ibforums->input['keywords'] == "" )
		{
			$type='nameonly';
		}
		
		//------------------------------------
		
		if ( $this->is->mysql_version >= 40010 )
		{
			$boolean  = 'IN BOOLEAN MODE';
			$keywords = $this->is->filter_ftext_keywords($ibforums->input['keywords']);
		}
		else
		{
			$keywords = $this->is->filter_keywords($ibforums->input['keywords']);
		}
		
		$check_keywords = trim($keywords);
		
		if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
		{
			if ($ibforums->input['namesearch'] == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
			}
		}
		else
		{
			if (strlen(trim($keywords)) < 4)
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => 4) );
			}
		}
		
		//------------------------------------
		
		if ($ibforums->input['search_in'] == 'titles')
		{
			$this->is->search_in = 'titles';
		}
		
		//------------------------------------
		
		$forums = $this->is->get_searchable_forums();
		
		//------------------------------------
		// Do we have any forums to search in?
		//------------------------------------
		
		if ($forums == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
	
		//------------------------------------
		
		foreach( array( 'last_post', 'posts', 'starter_name', 'forum_id' ) as $v )
		{
			if ($ibforums->input['sort_key'] == $v)
			{
				$this->is->sort_key = $v;
			}
		}
		
		//------------------------------------
		
		foreach ( array( 1, 7, 30, 60, 90, 180, 365, 0 ) as $v )
		{
			if ($ibforums->input['prune'] == $v)
			{
				$this->is->prune = $v;
			}
		}
		
		//------------------------------------
		
		if ($ibforums->input['sort_order'] == 'asc')
		{
			$this->is->sort_order = 'asc';
		}
		
		//------------------------------------
		
		if ($ibforums->input['result_type'] == 'posts')
		{
			$this->is->result_type = 'posts';
		}
		
		if ( $ibforums->vars['min_search_word'] < 1 )
		{
			$ibforums->vars['min_search_word'] = 4;
		}
		
		//------------------------------------
		// Add on the prune days
		//------------------------------------
		
		if ($this->is->prune > 0)
		{
			$gt_lt = $ibforums->input['prune_type'] == 'older' ? "<" : ">";
			$time = time() - ($ibforums->input['prune'] * 86400);
			
			$topics_datecut = "t.last_post $gt_lt $time AND";
			$posts_datecut  = "p.post_date $gt_lt $time AND";
		}
		
		 // Is this a membername search?
		 
		 $name_filter = trim( $name_filter );
		 $member_string = "";
		 
		 if ( $name_filter != "" )
		 {
			//------------------------------------------------------------------
			// Get all the possible matches for the supplied name from the DB
			//------------------------------------------------------------------
			
			$name_filter = str_replace( '|', "&#124;", $name_filter );
			
			if ($ibforums->input['exactname'] == 1)
			{
				$sql_query = "SELECT id from ibf_members WHERE lower(name)='".$name_filter."'";
			}
			else
			{
				$sql_query = "SELECT id from ibf_members WHERE name like '%".$name_filter."%'";
			}
			
			
			$DB->query( $sql_query );
			
			
			while ($row = $DB->fetch_row())
			{
				$member_string .= "'".$row['id']."',";
			}
			
			$member_string = preg_replace( "/,$/", "", $member_string );
			
			// Error out of we matched no members
			
			if ($member_string == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_name_search_results') );
			}
			
			$posts_name  = " AND p.author_id IN ($member_string)";
			$topics_name = " AND t.starter_id IN ($member_string)";
			
		}
		
		if ( $type != 'nameonly' )
		{
			if (strlen(trim($keywords)) < $ibforums->vars['min_search_word'])
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $ibforums->vars['min_search_word']) );
			}
			
		}
			
		$unique_id = md5(uniqid(microtime(),1));
		
		if ($type != 'nameonly')
		{
			$topics_query = "SELECT t.tid
							FROM ibf_topics t
							WHERE $topics_datecut t.forum_id IN ($forums)
							$topics_name AND t.approved=1 AND MATCH(title) AGAINST ('".trim($keywords)."' $boolean)";
		
		
			$posts_query = "SELECT p.pid ".
						   "FROM ibf_posts p ".
						   "WHERE $posts_datecut  p.forum_id IN ($forums)".
						   " AND p.queued <> 1".
						   " $posts_name AND MATCH(post) AGAINST ('".trim($keywords)."' $boolean)";
		}
		else
		{
			$topics_query = "SELECT t.tid
							FROM ibf_topics t
							WHERE $topics_datecut t.forum_id IN ($forums)
							$topics_name";
		
		
			$posts_query = "SELECT p.pid ".
						   "FROM ibf_posts p ".
						   "WHERE $posts_datecut  p.forum_id IN ($forums)".
						   " AND p.queued <> 1".
						   " $posts_name";
		}
					   
		//------------------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//------------------------------------------------
		
		$topics = "";
		$posts  = "";
		
		//------------------------------------
		
		$DB->query($topics_query);
	
		$topic_max_hits = $DB->get_num_rows();
		
		while ($row = $DB->fetch_row() )
		{
			$topics .= $row['tid'].",";
		}
		
		$DB->free_result();
		
		//------------------------------------
		
		$DB->query($posts_query);
	
		$post_max_hits = $DB->get_num_rows();
		
		while ($row = $DB->fetch_row() )
		{
			$posts .= $row['pid'].",";
		}
		
		$DB->free_result();
		
		//------------------------------------
		
		$topics = preg_replace( "/,$/", "", $topics );
		$posts  = preg_replace( "/,$/", "", $posts );
		
		//------------------------------------------------
		// Do we have any results?
		//------------------------------------------------
		
		if ($topics == "" and $posts == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------------------
		// If we are still here, return data like a good
		// boy (or girl). Yes Reg; or girl.
		// What have the Romans ever done for us?
		//------------------------------------------------
		
		return array(
					  'topic_id'  => $topics,
					  'post_id'   => $posts,
					  'topic_max' => $topic_max_hits,
					  'post_max'  => $post_max_hits,
					  'keywords'  => $keywords,
					);
		
	}
	

}

?>
