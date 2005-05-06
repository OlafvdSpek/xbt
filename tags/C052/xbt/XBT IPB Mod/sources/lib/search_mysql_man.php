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
|   > MySQL Manual Search Library
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
	// Main Board Search-e-me-doo-daa
	//--------------------------------------------
 

	function do_main_search()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		//------------------------------------
		// Do we have any input?
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
		
		$check_keywords = trim($keywords);
		
		$check_keywords = str_replace( "%", "", $check_keywords );
		
		if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
		{
			if ($type != 'nameonly')
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
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
			
			if (preg_match( "/ and|or /", $keywords) )
			{
				preg_match_all( "/(^|and|or)\s{1,}(\S+?)\s{1,}/", $keywords, $matches );
				
				$title_like = "(";
				$post_like  = "(";
				
				for ($i = 0 ; $i < count($matches[0]) ; $i++ )
				{
					$boolean = $matches[1][$i];
					$word    = trim($matches[2][$i]);
					
					if (strlen($word) < $ibforums->vars['min_search_word'])
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $ibforums->vars['min_search_word']) );
					}
					
					if ($boolean)
					{
						$boolean = " $boolean";
					}
					
					$title_like .= "$boolean LOWER(t.title) LIKE '%$word%' ";
					$post_like  .= "$boolean LOWER(p.post) LIKE '%$word%' ";
				}
				
				$title_like .= ")";
				$post_like  .= ")";
			
			}
			else
			{
			
				if (strlen(trim($keywords)) < $ibforums->vars['min_search_word'])
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $ibforums->vars['min_search_word']) );
				}
				
				$title_like = " LOWER(t.title) LIKE '%".trim($keywords)."%' ";
				$post_like  = " LOWER(p.post) LIKE '%".trim($keywords)."%' ";
			}
		}
			
		//$posts_datecut $topics_datecut $post_like $title_like $posts_name $topics_name
		
		$unique_id = md5(uniqid(microtime(),1));
		
		if ($type != 'nameonly')
		{
			$topics_query = "SELECT t.tid
							FROM ibf_topics t
							WHERE $topics_datecut t.forum_id IN ($forums)
							$topics_name AND t.approved=1 AND ($title_like)";
		
		
			$posts_query = "SELECT p.pid ".
						   "FROM ibf_posts p ".
						   "WHERE $posts_datecut  p.forum_id IN ($forums)".
						   " AND p.queued <> 1".
						   " $posts_name AND ($post_like)";
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
