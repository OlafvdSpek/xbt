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
|   > Topic Multi-Moderation
|   > Module written by Matt Mecham
|   > Date started: 14th May 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_multimod();


class ad_multimod
{

	var $base_url;

	function ad_multimod()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		$ADMIN->nav[] = array( 'act=multimod', 'Topic multi-moderation home' );
		
		switch($IN['code'])
		{
		
			case 'list':
				$this->list_current();
				break;
				
			case 'new':
				$this->do_form('new');
				break;
				
			case 'edit':
				$this->do_form('edit');
				break;
				
			case 'donew':
				$this->do_save('new');
				break;
				
			case 'doedit':
				$this->do_save('edit');
				break;
				
			case 'delete':
				$this->do_delete();
				break;
				
			//-------------------------
			
			default:
				$this->list_current();
				break;
		}
		
	}
	
	//-------------------------------------------------------------
	// DELETE!
	//-------------------------------------------------------------
	
	function do_delete()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the MMOD ID, please try again");
		}
		
		$DB->query("DELETE FROM ibf_topic_mmod WHERE mm_id='".$IN['id']."'");
		
		$mm_id = intval($IN['id']);
		
		//------------------------------------
		// Remove the MMOD from relevant forums
		//------------------------------------
		
		$final   = array();
		
		$DB->query("SELECT id,name,topic_mm_id FROM ibf_forums WHERE topic_mm_id LIKE '%,$mm_id,%'");
		
		while( $r = $DB->fetch_row() )
		{
			$final[ $r['id'] ] = str_replace( ",$mm_id,", ",", $r['topic_mm_id'] );
		}
		
		// now lets update the affected forum_id's.
		
		if ( count( $final ) > 0 )
		{
			foreach( $final as $fid => $raw_mm_id )
			{
				$new_mm_id = "," . $std->clean_perm_string($raw_mm_id) . ",";
				
				if ( $new_mm_id == ',,' )
				{
					$new_mm_id = '';
				}
				
				$DB->query("UPDATE ibf_forums SET topic_mm_id='{$new_mm_id}' WHERE id=$fid");
			}
		}
		
		$ADMIN->save_log("Topic Multi-Mod removed");
		
		$std->boink_it($SKIN->base_url."&act=multimod");
		
	}
	
	//-------------------------------------------------------------
	// SAVE!
	//-------------------------------------------------------------
	
	function do_save($type='new')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		$forums = array();
		
		$IN['id'] = intval($IN['id']);
		
		if ( $type == 'edit' )
		{
			if ( $IN['id'] < 1 )
			{
				$ADMIN->error("You must use a valid id");
			}
		}
		
		if ( $IN['mm_title'] == "" )
		{
			$ADMIN->error("You must enter a valid title");
		}
		
		//----------------------------------------
		// Check for forums...
		//----------------------------------------
		
		$forums = $this->get_activein_forums();
		
		if ( count($forums) < 1 )
		{
			$ADMIN->error("You must select some forums to activate with this multi-moderation suite");
		}
		
		if ( $IN['topic_move'] == 'n' )
		{
			$ADMIN->error("Incorrect forum chosen in the 'move to' section of the topic multi-moderation. Please note that you cannot choose to move the topic to a category");
		}
			
		$save = array(
						'mm_title'              => $IN['mm_title'],
						'mm_enabled'            => 1,
						'topic_state'           => $IN['topic_state'],
						'topic_pin'	            => $IN['topic_pin'],
						'topic_move'            => $IN['topic_move'],
						'topic_move_link'       => $IN['topic_move_link'],
						'topic_title_st'        => $ADMIN->make_safe($HTTP_POST_VARS['topic_title_st']),
						'topic_title_end'       => $ADMIN->make_safe($HTTP_POST_VARS['topic_title_end']),
						'topic_reply'           => $IN['topic_reply'],
						'topic_reply_content'   => $ADMIN->make_safe($HTTP_POST_VARS['topic_reply_content']),
						'topic_reply_postcount' => $IN['topic_reply_postcount'],
					 );
					 
		if ( $type == 'edit' )
		{
			$str = $DB->compile_db_update_string( $save );
			
			$mm_id = $IN['id'];
			
			$DB->query("UPDATE ibf_topic_mmod SET $str WHERE mm_id=$mm_id");
			
		}
		else
		{
			$str = $DB->compile_db_insert_string( $save );
			
			$DB->query("INSERT INTO ibf_topic_mmod ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
			
			$mm_id = $DB->get_insert_id();
		}
		
		//------------------------------------
		// Sort out the forum id things
		//------------------------------------
		
		$final   = array();
		$fstring = ','.implode( ",", $forums ).',';
		
		$DB->query("SELECT id,name,topic_mm_id FROM ibf_forums");
		
		while( $r = $DB->fetch_row() )
		{
			if ( $r['topic_mm_id'] == "" )
			{
				// Is this forum_id one of the forums to activate?
				
				if ( stristr( $fstring, ','.$r['id'].',' ) )
				{
					$final[ $r['id'] ] = $mm_id;
				}
				else
				{
					continue;
				}
			}
			
			// Is this mm_id already active?
			
			else if ( stristr( $r['topic_mm_id'], ','.$mm_id.',' ) )
			{
				// Should it still be?
				
				if ( ! stristr( $fstring, ','.$r['id'].',' ) )
				{
					// No? Remove it at once!
					
					$final[ $r['id'] ] = str_replace( ",$mm_id,", ",", $r['topic_mm_id'] );
				}
					// No else, there's no need to update this forum_id's mm_id's
			}
			else if ( stristr( $fstring, ','.$r['id'].',' ) )
			{
				// forums' topic_mm_id is not blank and mm_id is not in that topic_mm_id, so...
				
				$final[ $r['id'] ] = $r['topic_mm_id'] . ','.$mm_id.',';
			}
		}
		
		// Phew - ok, now lets update the affected forum_id's.
		
		if ( count( $final ) > 0 )
		{
			foreach( $final as $fid => $raw_mm_id )
			{
				$new_mm_id = "," . $std->clean_perm_string($raw_mm_id) . ",";
				
				$DB->query("UPDATE ibf_forums SET topic_mm_id='{$new_mm_id}' WHERE id=$fid");
			}
		}
		
		$ADMIN->save_log("Update topic multi-moderation entries ($type)");
		
		$std->boink_it($ADMIN->base_url."&act=multimod");
		
		
	}
	
	//-------------------------------------------------------------
	// SHOW MM FORM
	//-------------------------------------------------------------
	
	function do_form($type='new')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		$ADMIN->page_detail = "Multi moderation allows you to combine moderation actions to create easy to use shortcuts to several moderation options.";
		$ADMIN->page_title  = "Topic Multi-Moderation";
		
		$form_code   = 'donew';
		$description = 'Add a new topic multi-moderation';
		$button      = "Add New Multi-Moderation";
		if ( $type == 'edit' )
		{
			$id = intval($IN['id']);
			
			$DB->query("SELECT * FROM ibf_topic_mmod WHERE mm_id=$id");
			
			if ( ! $topic_mm = $DB->fetch_row() )
			{
				$ADMIN->error("Could not retrieve the information ($id)");
			}
			
			$form_code   = 'doedit';
			$description = 'Edit the topic multi-moderation';
			$button      = "Edit Multi-Moderation";
		}
		
		//---------------------------------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $form_code ),
												  2 => array( 'act'   , 'multimod' ),
												  3 => array( 'id'    , $id        ),
									     )      );
		
		//---------------------------------------------------------
		
		$state_dd = array(
						  0 => array( 'leave', 'Leave' ),
						  1 => array( 'close', 'Close' ),
						  2 => array( 'open' , 'Open'  ),
					   );
					  
		$pin_dd   = array(
						  0 => array( 'leave', 'Leave' ),
						  1 => array( 'pin'  , 'Pin'   ),
						  2 => array( 'unpin', 'Unpin' ),
					    );
					  
		//---------------------------------------------------------
		
		$db_cats     = array();
		$db_children = array();
		$db_forums   = array();
		$last_cat_id = -1;
		
		$DB->query("SELECT f.id as forum_id, f.name as forum_name, f.position, f.read_perms, f.parent_id, f.subwrap, f.sub_can_post, f.redirect_on, f.topic_mm_id, c.id as cat_id, c.name as cat_name from ibf_forums f, ibf_categories c where c.id=f.category ORDER BY c.position, f.position");
		
		$forums     = "<select name='forums[]' class='textinput' size='15' multiple='multiple'>\n";
		
		$forum_jump = array();
		
		while ( $r = $DB->fetch_row() )
		{
			if ($last_cat_id != $r['cat_id'])
        	{
        		$db_cats[ $r['cat_id'] ] = array( 'id'   => $r['cat_id'],
        										  'name' => $r['cat_name'],
        										);
        										   
        		$last_cat_id = $r['cat_id'];
        	}
        	
        	if ($r['parent_id'] > 0)
			{
				$db_children[ $r['parent_id'] ][$r['forum_id']] = $r;
			}
			else
			{
				$db_forums[ $r['forum_id'] ] = $r;
			}
			
		}
				
		
		$last_cat_id = -1;
			
		foreach ( $db_forums as $an_eye_for_an => $i )
		{
			
			if ( stristr( $i['topic_mm_id'], ",".$id."," ) )
			{
				$selected = ' selected="selected"';
			}
			else
			{
				$selected = "";
			}
			
			if ( $i['redirect_on'] == 1 )
			{
				continue;
			}
			
			if ($last_cat_id != $i['cat_id'])
        	{
        		$forums     .= "<option value=\"c_{$i['cat_id']}\" style='font-weight:bold'>{$db_cats[ $i['cat_id'] ]['name']}</option>\n";
        		$forum_jump[] = array( 'n', $db_cats[ $i['cat_id'] ]['name'] );
        		$last_cat_id = $i['cat_id'];
        	}
			
			$forums      .= "<option value=\"{$i['forum_id']}\" $selected>&middot;&middot;&nbsp;{$i['forum_name']}</option>\n";
			$forum_jump[] = array( $i['forum_id'], "&middot;&middot;&nbsp;{$i['forum_name']}" );
			
			// Add in sub cat children
			
			if ( ($i['subwrap'] == 1) and (count($db_children[ $i['forum_id'] ]) > 0) )
			{
				
				foreach( $db_children[ $i['forum_id'] ] as $idx => $cdata )
				{
					if ( $cdata['redirect_on'] == 1 OR ( $cdata['subwrap'] and ( ! $cdata['sub_can_post'] ) ) )
					{
						continue;
					}
					
					if ( stristr( $cdata['topic_mm_id'], ",".$id."," ) )
					{
						$selected = ' selected="selected"';
					}
					else
					{
						$selected = "";
					}
					
					$forums      .= "<option value=\"{$cdata['forum_id']}\" $selected>&middot;&middot;&middot;&middot;&nbsp;{$cdata['forum_name']}</option>\n";
					
					$forum_jump[] = array( $cdata['forum_id'], "&middot;&middot;&middot;&middot;&nbsp;{$cdata['forum_name']}" );
				}
			}
		}
		
		$forums .= "</select>";
		
		//---------------------------------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"   , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"   , "60%" );

		$ADMIN->html .= $SKIN->start_table( "Topic Multi-Moderation", $description );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Title for this Multi-Moderation Suite?</b>" ,
												  $SKIN->form_input("mm_title", $topic_mm['mm_title'] )
									     )      );
		
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Active in Forums...</b><br>You may choose more than one" ,
												  $forums
									     )      );							     
		
		$ADMIN->html .= $SKIN->add_td_basic( 'Moderation Options', 'left', 'pformstrip' );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Add to <i>START</i> of topic title?</b>" ,
												  $SKIN->form_input("topic_title_st", $topic_mm['topic_title_st'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Add to <i>END</i> of topic title?</b>" ,
												  $SKIN->form_input("topic_title_end", $topic_mm['topic_title_end'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Alter topic state?</b>" ,
												  $SKIN->form_dropdown("topic_state", $state_dd, $topic_mm['topic_state'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Alter pinned state?</b>" ,
												  $SKIN->form_dropdown("topic_pin", $pin_dd, $topic_mm['topic_pin'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Move topic?</b>" ,
					    						  $SKIN->form_dropdown("topic_move", array_merge( array( 0 => array('-1', 'Don\'t Move' ) ), $forum_jump ), $topic_mm['topic_move'] )
					    						  ."<br />".$SKIN->form_checkbox('topic_move_link', $topic_mm['topic_move_link'] )."<strong>Leave a link to the source topic?</strong>"
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_basic( 'Post Options', 'left', 'pformstrip' );
	
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Add a reply to the topic?</b><br>HTML enabled" ,
												  "Enable this reply? &nbsp;".$SKIN->form_yes_no('topic_reply', $topic_mm['topic_reply'] )
												  ."<br />"
												  . $SKIN->form_textarea("topic_reply_content", $topic_mm['topic_reply_content'] )
												  ."<br />".$SKIN->form_checkbox('topic_reply_postcount', $topic_mm['topic_reply_postcount'] )."<strong>Increment poster's post count?</strong>"
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form($button);
		
		$ADMIN->html .= $SKIN->end_table();
		
		
		
		$ADMIN->output();
	
	}
	
	
	//-------------------------------------------------------------
	// SHOW ALL AVAILABLE MM's
	//-------------------------------------------------------------
	
	function list_current()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		$ADMIN->page_detail = "Multi moderation allows you to combine moderation actions to create easy to use shortcuts to several moderation options.";
		$ADMIN->page_title  = "Topic Multi-Moderation";
		
		
		$SKIN->td_header[] = array( "Title"  , "50%" );
		$SKIN->td_header[] = array( "Edit"   , "25%" );
		$SKIN->td_header[] = array( "Remove" , "25%" );

		$ADMIN->html .= $SKIN->start_table( "Current Topic Multi-Moderation" );
		
		$DB->query("SELECT * FROM ibf_topic_mmod ORDER BY mm_title");
		
		if ( $DB->get_num_rows() )
		{
			while ( $row = $DB->fetch_row() )
			{
			
				$ADMIN->html .= $SKIN->add_td_row( array( 
														  "<strong>{$row['mm_title']}</strong>",
														  "<center><a href='{$ADMIN->base_url}&amp;act=multimod&amp;code=edit&amp;id={$row['mm_id']}'>Edit</a></center>",
														  "<center><a href='{$ADMIN->base_url}&amp;act=multimod&amp;code=delete&amp;id={$row['mm_id']}'>Remove</a></center>",
												 )      );
			
			
			}
		}
		else
		{
			$ADMIN->html .= $SKIN->add_td_basic("<center>None set up</center>");
		}
		
		$ADMIN->html .= $SKIN->add_td_basic("<a href='{$ADMIN->base_url}&amp;act=multimod&amp;code=new' class='fauxbutton'>Add New</a>", 'center', 'pformstrip');
		
		
		$ADMIN->html .= $SKIN->end_table();
		
		
		$ADMIN->output();
	
	}
	
	
	//------------------------------------------------------
    // Get the active in forums
    //------------------------------------------------------    
        
    function get_activein_forums()
    {
    	global $IN, $INFO, $DB, $std, $HTTP_POST_VARS;
    	
    	$forum_array  = array();
    	$forum_string = "";
    	$sql_query    = "";
    	$check_sub    = 0;
    	
    	$cats         = array();
    	$forums       = array();
    	
    	// If we have an array of "forums", loop
    	// through and build our *SQL IN( ) statement.
    	
    	//------------------------------------------------
    	// Check for an array
    	//------------------------------------------------
    	
    	if ( is_array( $HTTP_POST_VARS['forums'] )  )
    	{
    	
			 //--------------------------------------------
			 // Go loopy loo
			 //--------------------------------------------
			 
			 foreach( $HTTP_POST_VARS['forums'] as $l )
			 {
				 if ( preg_match( "/^c_/", $l ) )
				 {
					 $cats[] = intval( str_replace( "c_", "", $l ) );
				 }
				 else
				 {
					 $forums[] = intval($l);
				 }
			 }
			 
			 //--------------------------------------------
			 // Do we have cats? Give 'em to Charles!
			 //--------------------------------------------
			 
			 if ( count( $cats ) )
			 {
				 $sql_query = "SELECT id, read_perms, password, subwrap from ibf_forums WHERE category IN(".implode(",",$cats).")";
				 $boolean   = "OR";
			 }
			 else
			 {
				 $sql_query = "SELECT id, read_perms, password, subwrap from ibf_forums";
				 $boolean   = "WHERE";
			 }
			 
			 if ( count( $forums ) )
			 {
				  $sql_query .= " $boolean (id IN(".implode(",",$forums).") or parent_id IN(".implode(",",$forums).") )";
			 }
			 
			 if ( $sql_query == "" )
			 {
				 // Return empty..
				 
				 return;
			 }
  
    		
    		//--------------------------------------------
    		// Run query and finish up..
    		//--------------------------------------------
			
			$DB->query( $sql_query );
				
			while ($i = $DB->fetch_row())
			{
				$forum_array[] = $i['id'];
			}
		}
		else
		{
			//--------------------------------------------
			// Not an array...
			//--------------------------------------------
			
			if ( $IN['forums'] != "" )
			{
				$l = $IN['forums'];
				
				//--------------------------------------------
				// Single  Cat
				//--------------------------------------------
				
				if ( preg_match( "/^c_/", $l ) )
				{
					$c = intval( str_replace( "c_", "", $l ) );
					
					if ($c)
					{
						$DB->query("SELECT id, read_perms, password FROM ibf_forums WHERE category=$c");
						
						while ($i = $DB->fetch_row())
						{
							$forum_array[] = $i['id'];
						}
					}
				}
				else
				{
					//--------------------------------------------
					// Single forum
					//--------------------------------------------
				
					$f = intval($l);
					
					if ($f)
					{
						$qe = " OR parent_id=$f ";
						
						$DB->query("SELECT id, read_perms, password FROM ibf_forums WHERE id=$f".$qe);
						
						while ($i = $DB->fetch_row())
						{
							$forum_array[] = $i['id'];
						}
					}
				}
			}
		}
    					
    	return $forum_array;
    	
    }
	
	
	
}


?>