<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.3.1 Final
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.ibforums.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Time: Wed, 05 May 2004 18:09:25 GMT
|   Release: faf4a7c2b8220416837424452a6044e1
|   Email: matt@ibforums.com
|   Licence Info: http://www.invisionpower.com
+---------------------------------------------------------------------------
|
|   > Moderator Core Functions
|   > Module written by Matt Mecham
|
+--------------------------------------------------------------------------
| NOTE:
| This module does not do any access/permission checks, it merely
| does what is asked and returns - see function for more info
+--------------------------------------------------------------------------
*/

class modfunctions
{
	//------------------------------------------------------
	// @modfunctions: constructor
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE)
	//------------------------------------------------------
	
	var $topic = "";
	var $forum = "";
	var $error = "";
	
	var $auto_update = FALSE;
	
	var $stm   = "";
	var $upload_dir = "";
	
	var $moderator  = "";
	
	function modfunctions()
	{
		global $ibforums;
		
		$this->error = "";
		
		$this->upload_dir = $ibforums->vars['upload_dir'];
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @init: initialize module (allows us to create new obj)
	// -----------
	// Accepts: References to @$forum [ @$topic , @$moderator ]
	// Returns: NOTHING (TRUE)
	//------------------------------------------------------
	
	function init($forum, $topic="", $moderator="")
	{
		$this->forum = $forum;
		
		if ( is_array($topic) )
		{
			$this->topic = $topic;
		}
		
		if ( is_array($moderator) )
		{
			$this->moderator = $moderator;
		}
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @topic_add_reply: Appends topic with reply
	// -----------
	// Accepts: $post, $tids = array( 'tid', 'forumid' );
	//         
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_add_reply($post="", $tids=array(), $incpost=0)
	{
		global $std, $ibforums, $DB;
		
		if ( $post == "" )
		{
			return FALSE;
		}
		
		if ( count( $tids ) < 1 )
		{
			return FALSE;
		}
		
		$post = array(
					  'author_id'   => $ibforums->member['id'],
					  'use_sig'     => 1,
					  'use_emo'     => 1,
					  'ip_address'  => $ibforums->input['IP_ADDRESS'],
					  'post_date'   => time(),
					  'icon_id'     => 0,
					  'post'        => $post,
					  'author_name' => $ibforums->member['name'],
					  'forum_id'    => "",
					  'topic_id'    => "",
					  'queued'      => 0,
					  'attach_id'   => "",
					  'attach_hits' => "",
					  'attach_type' => "",
					 );
					 
		//-------------------------------------
		// Add posts...
		//-------------------------------------
		 
		$seen_fids = array();
		$add_posts = 0;
		
		foreach( $tids as $row )
		{
			$tid = intval($row[0]);
			$fid = intval($row[1]);
			$pa  = array();
			$ta  = array();
			
			if ( ! in_array( $fid, $seen_fids ) )
			{
				$seen_fids[] = $fid;
			}
			
			if ( $tid and $fid )
			{
				$pa = $post;
				$pa['forum_id'] = $fid;
				$pa['topic_id'] = $tid;
				
				$db_string = $DB->compile_db_insert_string( $pa );
		
				$DB->query("INSERT INTO ibf_posts (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
				
				unset($db_string);
				
				$ta = array (
							  'last_poster_id'   => $ibforums->member['id'],
							  'last_poster_name' => $ibforums->member['name'],
							  'last_post'        => $pa['post_date'],
							);
							
				$db_string = $DB->compile_db_update_string( $ta );
				
				$DB->query("UPDATE ibf_topics SET ".$db_string.", posts=posts+1 WHERE tid=$tid");
				
				$add_posts++;
			}
		}
				
		if ( $this->auto_update != FALSE )
		{
			if ( count($seen_fids) > 0 )
			{
				foreach( $seen_fids as $id )
				{
					$this->forum_recount( $id );
				}
			}
		}
		
		if ( $add_posts > 0 )
		{
			$DB->query("UPDATE ibf_stats SET TOTAL_REPLIES=TOTAL_REPLIES+".$add_posts);
			
			//-------------------------------------------------
			// Update current members stuff
			//-------------------------------------------------
		
			$pcount = "";
			$mgroup = "";
			
			
			if ( ($this->forum['inc_postcount']) and ($incpost != 0) )
			{
				//------------------------------------
				// Increment the users post count
				//------------------------------------
				
				$pcount = "posts=posts+".$add_posts.", ";
			}
			
			//------------------------------------
			// Are we checking for auto promotion?
			//------------------------------------
			
			if ($ibforums->member['g_promotion'] != '-1&-1')
			{
				list($gid, $gposts) = explode( '&', $ibforums->member['g_promotion'] );
				
				if ( $gid > 0 and $gposts > 0 )
				{
					if ( $ibforums->member['posts'] + $add_posts >= $gposts )
					{
						$mgroup = "mgroup='$gid', ";
					}
				}
			}
			
			$DB->query("UPDATE ibf_members SET ".$pcount.$mgroup."last_post=".time()." WHERE id=".$ibforums->member['id']);
			
		}
		
		return TRUE;
		
	}
	
	//------------------------------------------------------
	// @topic_close: close topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_close($id)
	{
		global $ibforums, $DB;
		
		$this->stm_init();
		$this->stm_add_close();
		$this->stm_exec($id);
	}
	
	
	//------------------------------------------------------
	// @topic_open: open topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_open($id)
	{
		global $ibforums, $DB;
		
		$this->stm_init();
		$this->stm_add_open();
		$this->stm_exec($id);
	}
	
	//------------------------------------------------------
	// @topic_pin: pin topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_pin($id)
	{
		global $ibforums, $DB;
		
		$this->stm_init();
		$this->stm_add_pin();
		$this->stm_exec($id);
	}
	
	//------------------------------------------------------
	// @topic_unpin: unpin topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_unpin($id)
	{
		global $ibforums, $DB;
		
		$this->stm_init();
		$this->stm_add_unpin();
		$this->stm_exec($id);
	}
	
	
	//------------------------------------------------------
	// @topic_delete: deletetopic ID(s)
	// -----------
	// Accepts: $id (array | string) 
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_delete($id)
	{
		global $std, $ibforums, $DB;
		
		$this->error = "";

		if ( is_array( $id ) )
		{
			if ( count($id) > 0 )
			{
				$tid = " IN(".implode(",",$id).")";
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$tid   = "=$id";
			}
			else
			{
				return FALSE;
			}
		}
		
		//------------------------------------
		// Remove polls assigned to this topic
		//------------------------------------
		
		$DB->query("DELETE FROM ibf_polls WHERE tid".$tid);
		
		//------------------------------------
		// Remove polls assigned to this topic
		//------------------------------------
		
		$DB->query("DELETE FROM ibf_voters WHERE tid".$tid);
		
		//------------------------------------
		// Remove polls assigned to this topic
		//------------------------------------
		
		$DB->query("DELETE FROM ibf_topics WHERE tid".$tid);
		
		//------------------------------------
		// Remove polls assigned to this topic
		//------------------------------------
		
		$DB->query("SELECT attach_id, attach_hits, attach_file FROM ibf_posts WHERE attach_id <> '' AND topic_id".$tid);
		
		//------------------------------------
		// Remove the attachments
		//------------------------------------
		
		if ( $DB->get_num_rows() )
		{
			while ( $r = $DB->fetch_row() )
			{
				if (is_file($this->upload_dir."/".$r['attach_id']))
				{
					@unlink ($this->upload_dir."/".$r['attach_id']);
				}
			}
		}
		
		//------------------------------------
		// Remove the posts
		//------------------------------------
		
		$DB->query("DELETE FROM ibf_posts WHERE topic_id".$tid);
		
		//------------------------------------
		// Recount forum...
		//------------------------------------
		
		if ( $this->forum['id'] )
		{
			$this->forum_recount( $this->forum['id'] );
		}
		
		$this->stats_recount();
	}
	
	
	//------------------------------------------------------
	// @topic_move: move topic ID(s)
	// -----------
	// Accepts: $topics (array | string) $source,
	//          $moveto
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function topic_move($topics, $source, $moveto, $leavelink=0)
	{
		global $std, $ibforums, $DB;
		
		$this->error = "";
		
		$source = intval($source);
		$moveto = intval($moveto);
		
		if ( is_array( $topics ) )
		{
			if ( count($topics) > 0 )
			{
				$tid = " IN(".implode(",",$topics).")";
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( intval($topics) )
			{
				$tid   = "=$topics";
			}
			else
			{
				return FALSE;
			}
		}
		
		//----------------------------------
		// Update the topic
		//----------------------------------
		
		$DB->query("UPDATE ibf_topics SET forum_id=$moveto WHERE forum_id=$source AND tid".$tid);
		
		//----------------------------------
		// Update the posts
		//----------------------------------
		
		$DB->query("UPDATE ibf_posts SET forum_id=$moveto WHERE forum_id=$source AND topic_id".$tid);
		
		//----------------------------------
		// Update the polls
		//----------------------------------
		
		$DB->query("UPDATE ibf_polls SET forum_id=$moveto WHERE forum_id=$source AND tid".$tid);
		
		//----------------------------------
		// Are we leaving a stink er link?
		//----------------------------------
		
		if ( $leavelink != 0 )
		{
			$oq = $DB->query("SELECT * FROM ibf_topics WHERE tid".$tid);
			
			while ( $row = $DB->fetch_row($oq) )
			{
				$db_string = $DB->compile_db_insert_string( array (
																	 'title'            => $row['title'],
																	 'description'      => $row['description'],
																	 'state'            => 'link',
																	 'posts'            => 0,
																	 'views'            => 0,
																	 'starter_id'       => $row['starter_id'],
																	 'start_date'       => $row['start_date'],
																	 'starter_name'     => $row['starter_name'],
																	 'last_post'        => $row['last_post'],
																	 'forum_id'         => $source,
																	 'approved'         => 1,
																	 'pinned'           => 0,
																	 'moved_to'         => $row['tid'].'&'.$moveto,
																	 'last_poster_id'   => $row['last_poster_id'],
																	 'last_poster_name' => $row['last_poster_name']
														 )        );
														 
				$DB->query("INSERT INTO ibf_topics (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
			}
		
		}
		
		//----------------------------------
		// Sort out subscriptions
		//----------------------------------
		
		$DB->query("SELECT tr.*, m.id, m.mgroup, m.org_perm_id, f.read_perms, f.id, t.tid, g.g_id, g.g_perm_id
				     FROM ibf_tracker tr
				     LEFT JOIN ibf_topics t ON (tr.topic_id=t.tid)
				     LEFT JOIN ibf_forums f ON (t.forum_id=f.id)
				     LEFT JOIN ibf_members m on (m.id=tr.member_id)
				     LEFT JOIN ibf_groups g on (g.g_id=m.mgroup)
				    WHERE tr.topic_id".$tid);
				    
		$trid_to_delete = array();
		
		while ( $r = $DB->fetch_row() )
		{
			//----------------------------------------
			// Match the perm group against forum_mask
			//----------------------------------------
			
			$perm_id = $r['g_perm_id'];
			
			if ( $r['org_perm_id'] )
			{
				$perm_id = $r['org_perm_id'];
			}
			
			$pass = 0;
			
			$forum_perm_array = explode( ",", $r['read_perms'] );
			
			foreach( explode( ',', $perm_id ) as $u_id )
			{
				if ( in_array( $u_id, $forum_perm_array ) )
				{
					$pass = 1;
				}
			}
			
			if ( $pass != 1 )
			{
				$trid_to_delete[] = $r['trid'];
			}		
		}
		
		if ( count($trid_to_delete) > 0 )
		{
			$DB->query("DELETE FROM ibf_tracker WHERE trid IN(".implode(',', $trid_to_delete ).")");
		}
		
		
		return TRUE;
	
	}
	
	//------------------------------------------------------
	// @stats_recount: Recount all topics & posts
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stats_recount()
	{
		global $ibforums, $DB, $std;
	
		$DB->query("SELECT COUNT(tid) as tcount from ibf_topics WHERE approved=1");
		$topics = $DB->fetch_row();
		
		$DB->query("SELECT COUNT(pid) as pcount from ibf_posts WHERE queued <> 1");
		$posts  = $DB->fetch_row();
		
		$posts = $posts['pcount'] - $topics['tcount'];
		
		$DB->query("UPDATE ibf_stats SET TOTAL_TOPICS=".$topics['tcount'].", TOTAL_REPLIES=".$posts);
	}
	
	//------------------------------------------------------
	// @forum_recount_queue: Resets use_mod_posts boolean
	// -----------
	// Accepts: forum_id
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function forum_recount_queue($fid="")
	{
		global $ibforums, $DB, $std;
		
		$fid = intval($fid);
		
		if ( ! $fid )
		{
			return FALSE;
		}
		
		$DB->query("SELECT count(tid) as cnt FROM ibf_topics WHERE forum_id=$fid and approved <> 1");
		
		$topic = $DB->fetch_row();
		
		$tcount = intval( $topic['cnt'] );
		
		$DB->query("SELECT count(pid) as cnt FROM ibf_posts WHERE forum_id=$fid and queued=1");
		
		$post = $DB->fetch_row();
		
		$pcount = intval( $post['cnt'] );
		
		if ( ($tcount > 0) or ($pcount > 0) )
		{
			$DB->query("UPDATE ibf_forums SET has_mod_posts=1 WHERE id=$fid");
		}
		else
		{
			$DB->query("UPDATE ibf_forums SET has_mod_posts=0 WHERE id=$fid");
		}
		
		return TRUE;
		
	}
	
	//------------------------------------------------------
	// @forum_recount: Recount topic & posts in a forum
	// -----------
	// Accepts: forum_id
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function forum_recount($fid="")
	{
		global $ibforums, $DB, $std;
		
		$fid = intval($fid);
		
		if ( ! $fid )
		{
			if ( $this->forum['id'] )
			{
				$fid = $this->forum['id'];
			}
			else
			{
				return FALSE;
			}
		}
		
		//----------------------------------------------
		// Get the topics..
		//----------------------------------------------
		
		$DB->query("SELECT COUNT(tid) as count FROM ibf_topics WHERE approved=1 and forum_id=$fid");
		$topics = $DB->fetch_row();
		
		//----------------------------------------------
		// Get the posts..
		//----------------------------------------------
		
		$DB->query("SELECT COUNT(pid) as count FROM ibf_posts WHERE queued <> 1 and forum_id=$fid");
		$posts = $DB->fetch_row();
		
		//----------------------------------------------
		// Get the forum last poster..
		//----------------------------------------------
		
		$DB->query("SELECT tid, title, last_poster_id, last_poster_name, last_post FROM ibf_topics WHERE approved=1 and forum_id=$fid ORDER BY last_post DESC LIMIT 0,1");
		$last_post = $DB->fetch_row();
		
		//----------------------------------------------
		// Get real post count by removing topic starting posts from the count
		//----------------------------------------------
		
		$real_posts = intval($posts['count']) - intval($topics['count']);
		
		//----------------------------------------------
		// Reset this forums stats
		//----------------------------------------------
		
		$db_string = $DB->compile_db_update_string( array (
															 'last_poster_id'   => $last_post['last_poster_id'],
															 'last_poster_name' => $last_post['last_poster_name'],
															 'last_post'        => $last_post['last_post'],
															 'last_title'       => $last_post['title'],
															 'last_id'          => $last_post['tid'],
															 'topics'           => intval($topics['count']) < 1 ? 0 : intval($topics['count']),
															 'posts'            => intval($real_posts)      < 1 ? 0 : intval($real_posts),
												 )        );
												 
		$DB->query("UPDATE ibf_forums SET $db_string WHERE id=".$fid);
		
		return TRUE;
		
	}
	
	
	//------------------------------------------------------
	// @stm_init: Clear statement ready for multi-actions
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_init()
	{
		$this->stm = array();
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_exec: Executes stored statement
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_exec($id)
	{
		global $ibforums, $DB;
		
		if ( count($this->stm) < 1 )
		{
			return FALSE;
		}
		
		$final_array = array();
		
		foreach( $this->stm as $idx => $real_array )
		{
			foreach( $real_array as $k => $v )
			{
				$final_array[ $k ] = $v;
			}
		}
		
		$db_string = $DB->compile_db_update_string( $final_array );
		
		if ( is_array($id) )
		{
			if ( count($id) > 0 )
			{
				$DB->query("UPDATE ibf_topics SET $db_string WHERE tid IN(".implode( ",", $id ).")");
				
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else if ( intval($id) != "" )
		{
			$DB->query("UPDATE ibf_topics SET $db_string WHERE tid=".intval($id));
		}
		else
		{
			return FALSE;
		}
	}
	
	
	//------------------------------------------------------
	// @stm_add_pin: add pin command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_pin()
	{
		$this->stm[] = array( 'pinned' => 1 );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_add_unpin: add unpin command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_unpin()
	{
		$this->stm[] = array( 'pinned' => 0 );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_add_close: add close command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_close()
	{
		$this->stm[] = array( 'state' => 'closed' );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_add_open: add open command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_open()
	{
		$this->stm[] = array( 'state' => 'open' );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_add_title: add edit title command to statement
	// -----------
	// Accepts: new_title
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_title($new_title='')
	{
		if ( $new_title == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'title' => $new_title );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @stm_add_desc: add edit desc command to statement
	// -----------
	// Accepts: new_title
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function stm_add_desc($new_desc='')
	{
		if ( $new_desc == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'description' => $new_desc );
		
		return TRUE;
	}
	
	//------------------------------------------------------
	// @sql_prune_create: returns formatted SQL statement
	// -----------
	// Accepts: forum_id, poss_starter_id, poss_topic_state, poss_post_min
	//			poss_date_expiration, poss_ignore_pin_state
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function sql_prune_create( $forum_id, $starter_id="", $topic_state="", $post_min="", $date_exp="", $ignore_pin="" )
	{
		$sql = "SELECT tid FROM ibf_topics WHERE approved=1 and forum_id=".intval($forum_id);
		
		if ( intval($date_exp) )
		{
			$sql .= " AND last_post < $date_exp";
		}
		
		if ( intval($starter_id) )
		{
			$sql .= " AND starter_id=$starter_id";
			
		}
		
		if ( intval($post_min) )
		{
			$sql .= " AND posts < $post_min";
		}
		
		if ($topic_state != 'all')
		{
			if ($topic_state)
			{
				$sql .= " AND state='$topic_state'";
			}
		}
		
		if ( $ignore_pin != "" )
		{
			$sql .= " AND pinned <> 1";
		}
		
		return $sql;
	
	}
	
	//------------------------------------------------------
	// @mm_authorize: Authorizes current member
	// -----------
	// Accepts: (NOTHING: Should already be passed to init)
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function mm_authorize()
	{
		global $ibforums, $std;
		
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
		
		return $pass_go;
	}
	
	//------------------------------------------------------
	// @mm_check_id_in_forum: Checks to see if mm_id is in
    //                        the forum saved topic_mm_id
	// -----------
	// Accepts: (forum_topic_mm_id , this_mm_id)
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function mm_check_id_in_forum( $forum_topic_mm_id, $this_mm_id )
	{
		$retval = FALSE;
		
		if ( stristr( $forum_topic_mm_id , ','.$this_mm_id.',' ) )
		{
			$retval = TRUE;
		}
		
		return $retval;	
	}
	
	//------------------------------------------------------
	// @add_moderate_log: Adds entry to mod log
	// -----------
	// Accepts: (forum_id, topic_id, topic_title, post_id, title)
	// Returns: NOTHING (TRUE/FALSE)
	//------------------------------------------------------
	
	function add_moderate_log($fid, $tid, $pid, $t_title, $mod_title='Unknown')
	{
		global $std, $ibforums, $DB, $HTTP_REFERER, $QUERY_STRING;
		
		$db_string = $std->compile_db_string( array (
														'forum_id'    => $fid,
														'topic_id'    => $tid,
														'post_id'     => $pid,
														'member_id'   => $ibforums->member['id'],
														'member_name' => $ibforums->member['name'],
														'ip_address'  => $ibforums->input['IP_ADDRESS'],
														'http_referer'=> $HTTP_REFERER,
														'ctime'       => time(),
														'topic_title' => $t_title,
														'action'      => $mod_title,
														'query_string'=> $QUERY_STRING,
													)
										    );
		
		$DB->query("INSERT INTO ibf_moderator_logs (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
		
	}
	
	
}







	

?>