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
|   > Admin Forum functions
|   > Module written by Matt Mecham
|   > Date started: 17th March 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_groups();


class ad_groups {

	var $base_url;

	function ad_groups() {
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		//---------------------------------------

		switch($IN['code'])
		{
			case 'doadd':
				$this->save_group('add');
				break;
				
			case 'add':
				$this->group_form('add');
				break;
				
			case 'edit':
				$this->group_form('edit');
				break;
			
			case 'doedit':
				$this->save_group('edit');
				break;
			
			case 'delete':
				$this->delete_form();
				break;
			
			case 'dodelete':
				$this->do_delete();
				break;
				
			//-------------------------	
				
			case 'fedit':
				$this->forum_perms();
				break;
				
			case 'pdelete':
				$this->delete_mask();
				break;
				
			case 'dofedit':
				$this->do_forum_perms();
				break;
				
			case 'permsplash':
				$this->permsplash();
				break;
				
			case 'view_perm_users':
				$this->view_perm_users();
				break;
					
			case 'remove_mask':
				$this->remove_mask();
				break;
				
			case 'preview_forums':
				$this->preview_forums();
				break;
				
			case 'dopermadd':
				$this->add_new_perm();
				break;
				
			case 'donameedit':
				$this->edit_name_perm();
				break;
			//-------------------------			
					
					
						
			default:
				$this->main_screen();
				break;
		}
		
	}
	
	//+---------------------------------------------------------------------------------
	//
	// Member group /forum mask permission form thingy doodle do yes. Viewing Perm users
	//
	//+---------------------------------------------------------------------------------
	
	function delete_mask()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------------------
		// Check for a valid ID
		//+-------------------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the permission mask setty doodle thingy ID, please try again");
		}
		
		$DB->query("DELETE FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		$old_id = intval($IN['id']);
		
		//+-------------------------------------------
		// Remove from forums...
		//+-------------------------------------------
		
		$get = $DB->query("SELECT id, read_perms, reply_perms, start_perms, upload_perms FROM ibf_forums");
				
		while( $f = $DB->fetch_row($get) )
		{
			$d_str = "";
			$d_arr = array();
			
			foreach( array( 'read_perms', 'reply_perms', 'start_perms', 'upload_perms' ) as $perm_bit )
			{
				if ($f[ $perm_bit ] != '*')
				{
					if ( preg_match( "/(^|,)".$old_id."(,|$)/", $f[ $perm_bit ]) )
					{
						$f[ $perm_bit ] = preg_replace( "/(^|,)".$old_id."(,|$)/", "\\1\\2", $f[ $perm_bit ] );
						
						$d_arr[ $perm_bit ] = $this->clean_perms( $f[ $perm_bit ] );
					}
				}
			}
			
			// Do we have anything to save?
				
			if ( count($d_arr) > 0 )
			{
				$d_str = $DB->compile_db_update_string( $d_arr );
				
				// Sure?..
				
				if ( strlen($d_str) > 5)
				{
					$save = $DB->query("UPDATE ibf_forums SET $d_str WHERE id={$f['id']}");
				}
			}
		}
		
		$this->permsplash();
	}
	
	//+---------------------------------------------------------------------------------
	
	
	function add_new_perm()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$IN['new_perm_name'] = trim($IN['new_perm_name']);
		
		if ($IN['new_perm_name'] == "")
		{
			$ADMIN->error("You must enter a name");
		}
		
		$copy_id = $IN['new_perm_copy'];
		
		//+-------------------------------------------
		// UPDATE DB
		//+-------------------------------------------
		
		$DB->query("INSERT INTO ibf_forum_perms SET perm_name='".$IN['new_perm_name']."'");
		
		$new_id = $DB->get_insert_id();
		
		if ( $copy_id != 'none' )
		{
			//+-------------------------------------------
			// Add new mask to forum accesses
			//+-------------------------------------------
		
			$old_id = intval($copy_id);
			
			if ( ($new_id > 0) and ($old_id > 0) )
			{
				$get = $DB->query("SELECT id, read_perms, reply_perms, start_perms, upload_perms FROM ibf_forums");
				
				while( $f = $DB->fetch_row($get) )
				{
					$d_str = "";
					$d_arr = array();
					
					foreach( array( 'read_perms', 'reply_perms', 'start_perms', 'upload_perms' ) as $perm_bit )
					{
						if ($f[ $perm_bit ] != '*')
						{
							if ( preg_match( "/(^|,)".$old_id."(,|$)/", $f[ $perm_bit ]) )
							{
								$d_arr[ $perm_bit ] = $this->clean_perms( $f[ $perm_bit ] ) . ",".$new_id;
							}
						}
					}
					
					// Do we have anything to save?
						
					if ( count($d_arr) > 0 )
					{
						$d_str = $DB->compile_db_update_string( $d_arr );
						
						// Sure?..
						
						if ( strlen($d_str) > 5)
						{
							$save = $DB->query("UPDATE ibf_forums SET $d_str WHERE id={$f['id']}");
						}
					}
				}
			}
			
		
		}
		
		$this->permsplash();
			
		
	}
	
	//-_-_-_-_-_-_-_-_-
	//_-_-_-_-_-_-_-_-_
	//Now that's pretty
	
	function preview_forums()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------------------
		// Check for a valid ID
		//+-------------------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the permission mask setty doodle thingy ID, please try again");
		}
		
		
		$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		if ( ! $perms = $DB->fetch_row() )
		{
			$ADMIN->error("Could not resolve the permission mask setty doodle thingy ID, please try again");
		}
		
		//+-------------------------------------------
		// What we doin'?
		//+-------------------------------------------
		
		switch( $IN['t'] )
		{
			case 'start':
				$human_type = 'Start Topics';
				$code_word  = 'start_perms';
				break;
				
			case 'reply':
				$human_type = 'Reply To Topics';
				$code_word  = 'reply_perms';
				break;
				
			default:
				$human_type = 'View Forum';
				$code_word  = 'read_perms';
				break;
		}
		
		//+-------------------------------------------
		// Get all members using that ID then!
		//+-------------------------------------------
		
		$SKIN->td_header[] = array( "$human_type" , "100%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Preview using: " . $perms['perm_name'] );
		
		$last_cat_id = -1;
		
		$DB->query("SELECT f.id as forum_id, f.parent_id, f.subwrap, f.sub_can_post, f.name as forum_name, f.position, f.read_perms, f.start_perms, f.reply_perms, c.id as cat_id, c.name
				    FROM ibf_forums f
				     LEFT JOIN ibf_categories c ON (c.id=f.category)
				    ORDER BY c.position, f.position");
		
		
		$forum_keys = array();
		$cat_keys   = array();
		$children   = array();
		$subs       = array();
		$the_html   = "";
		
		$perm_id    = intval($IN['id']);
		
		while ( $i = $DB->fetch_row() )
		{
			
			if ($i['subwrap'] == 1 and $i['sub_can_post'] != 1)
			{
				$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "&nbsp;&nbsp;- {$i['forum_name']}\n";
			}
			else
			{
				if ($i[ $code_word ] == '*')
				{
					if ($i['parent_id'] > 0)
					{
						$children[ $i['parent_id'] ][] = "&nbsp;&nbsp;---- {$i['forum_name']}\n";
					}
					else
					{
						$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "&nbsp;&nbsp;- {$i['forum_name']}\n";
					}
				}
				else if (preg_match( "/(^|,)".$perm_id."(,|$)/", $i[ $code_word ]) )
				{
					if ($i['parent_id'] > 0)
					{
						$children[ $i['parent_id'] ][] = "&nbsp;&nbsp;---- {$i['forum_name']}\n";
					}
					else
					{
						$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "&nbsp;&nbsp;- {$i['forum_name']}\n";
					}
				}
				else
				{
					//-------------------------------------
					// CAN'T ACCESS
					//-------------------------------------
					
					if ($i['parent_id'] > 0)
					{
						$children[ $i['parent_id'] ][] = "<span style='color:gray'>&nbsp;&nbsp;---- {$i['forum_name']}</span>\n";
					}
					else
					{
						$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "<span style='color:gray'>&nbsp;&nbsp;- {$i['forum_name']}</span>\n";
					}
				}
			}
			
			if ($last_cat_id != $i['cat_id'])
			{
				
				// Make sure cats with hidden forums are not shown in forum jump
				
				$cat_keys[ $i['cat_id'] ] = "<b>{$i['name']}</b>\n";
							              
				$last_cat_id = $i['cat_id'];
				
			}
		}
		
		foreach($cat_keys as $cat_id => $cat_text)
		{
			if ( is_array( $forum_keys[$cat_id] ) && count( $forum_keys[$cat_id] ) > 0 )
			{
				$the_html .= $cat_text;
				
				foreach($forum_keys[$cat_id] as $idx => $forum_text)
				{
					$the_html .= $forum_text;
					
					if (count($children[$idx]) > 0)
					{
						foreach($children[$idx] as $ii => $tt)
						{
							$the_html .= $tt;
						}
					}
				}
			}
		}
		
		$the_html = str_replace( "\n", "<br />\n", $the_html );
		
		$ADMIN->html .= $SKIN->add_td_row( array( $the_html )      );
		
		$ADMIN->html .= $SKIN->end_table();
		
		//----------------------------------------------------------------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'preview_forums' ),
												  2 => array( 'act'   , 'group'   ),
												  3 => array( 'id'    , $IN['id']      ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;" , "60%" );
		$SKIN->td_header[] = array( "&nbsp;" , "40%" );
		
		$ADMIN->html .= $SKIN->start_table( "Legend & Info" );
		
		$ADMIN->html .= $SKIN->add_td_row( array(
													"Can $human_type for this forum",
													"<input type='text' readonly='readonly' style='border:1px solid black;background-color:black;size=30px' name='blah'>"
										 )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array(
													"CANNOT $human_type for this forum",
													"<input type='text' readonly='readonly' style='border:1px solid gray;background-color:gray;size=30px' name='blah'>"
										 )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array(
													"Test with...",
													$SKIN->form_dropdown( 't',
																		array( 0 => array( 'start', 'Start Topics'    ),
																			   1 => array( 'reply', 'Reply To Topics' ),
																			   2 => array( 'read' , 'Read Forum'      ),
																			  ), $IN['t'] )
										 )      );
										 
		$ADMIN->html .= $SKIN->end_form( "Update" );
		
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
							   
	}
	
	//===========================================================================
	
	function remove_mask()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------------------
		// Check for a valid ID
		//+-------------------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the member ID, please try again");
		}
		
		//+-------------------------------------------
		// Get, check and reset
		//+-------------------------------------------
		
		$DB->query("SELECT id, name, org_perm_id FROM ibf_members WHERE id=".intval($IN['id']));
		
		if ( ! $mem = $DB->fetch_row() )
		{
			$ADMIN->error("Could not resolve the member ID, please try again");
		}
		
		if ( $IN['pid'] == 'all' )
		{
			$DB->query("UPDATE ibf_members SET org_perm_id=0 WHERE id=".intval($IN['id']));
		}
		else
		{
			$IN['pid'] = intval($IN['pid']);
			
			$pid_array = explode( ",", $mem['org_perm_id'] );
			
			if ( count($pid_array) < 2 )
			{
				$DB->query("UPDATE ibf_members SET org_perm_id=0 WHERE id=".intval($IN['id']));
			}
			else
			{
				$new_arr = array();
				
				foreach( $pid_array as $sid )
				{
					if ( $sid != $IN['pid'] )
					{
						$new_arr[] = $sid;
					}
				}
				
				$DB->query("UPDATE ibf_members SET org_perm_id='".implode(",",$new_arr)."' WHERE id=".intval($IN['id']));
			}	
			
		}
			
		//+-------------------------------------------
		// Get all members using that ID then!
		//+-------------------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;" , "100%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Result" );
		
		
		
		$ADMIN->html .= $SKIN->add_td_row( array( "Removed the custom permission mask from <b>{$mem['name']}</b>.",
										 )      );
	
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
							   
	}
	
	//===========================================================================
	
	
	function view_perm_users()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------------------
		// Check for a valid ID
		//+-------------------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the permission mask setty doodle thingy ID, please try again");
		}
		
		
		$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		if ( ! $perms = $DB->fetch_row() )
		{
			$ADMIN->error("Could not resolve the permission mask setty doodle thingy ID, please try again");
		}
		
		//+-------------------------------------------
		// Get all members using that ID then!
		//+-------------------------------------------
		
		$SKIN->td_header[] = array( "User Details" , "50%" );
		$SKIN->td_header[] = array( "Action"       , "50%" );
		
		//+-------------------------------
		
		$ADMIN->html .= "<script language='javascript' type='text/javascript'>
						 <!--
						  function pop_close_and_stop( id )
						  {
						  	opener.location = \"{$SKIN->base_url}&act=mem&code=doform&MEMBER_ID=\" + id;
						  	self.close();
						  }
						  //-->
						  </script>";
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Members using: " . $perms['perm_name'] );
		
		$outer = $DB->query("SELECT id, name, email, posts, org_perm_id FROM ibf_members WHERE (org_perm_id IS NOT NULL AND org_perm_id <> 0) ORDER BY name");
		
		while( $r = $DB->fetch_row($outer) )
		{
			$exp_pid = explode( ",", $r['org_perm_id'] );
			
			foreach( explode( ",", $r['org_perm_id'] ) as $pid )
			{
				if ( $pid == $IN['id'] )
				{
					if ( count($exp_pid) > 1 )
					{
						$extra = "<li>Also using: <em style='color:red'>";
						
						$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id IN ({$r['org_perm_id']}) AND perm_id <> {$IN['id']}");
						
						while ( $mr = $DB->fetch_row() )
						{
							$extra .= $mr['perm_name'].",";
						}
						
						$extra = preg_replace( "/,$/", "", $extra );
						
						$extra .= "</em>";
					}
					else
					{
						$extra = "";
					}
					
					$ADMIN->html .= $SKIN->add_td_row( array( "<div style='font-weight:bold;font-size:11px;padding-bottom:6px;margin-bottom:3px;border-bottom:1px solid #000'>{$r['name']}</div>
															   <li>Posts: {$r['posts']}
															   <li>Email: {$r['email']}
															   $extra" ,
															  "&#149;&nbsp;<a href='{$SKIN->base_url}&amp;act=group&amp;code=remove_mask&amp;id={$r['id']}&amp;pid=$pid' title='Remove this mask from the user (will not remove all if they have multimasks'>Remove This Mask</a>
															   <br />&#149;&nbsp;<a href='{$SKIN->base_url}&amp;act=group&amp;code=remove_mask&amp;id={$r['id']}&amp;pid=all' title='Remove all user masks'>Remove All Masks</a>
															   <br /><br />&#149;&nbsp;<a href='javascript:pop_close_and_stop(\"{$r['id']}\");'>Edit Member</a>",
													 )      );
				}
			}
		}
		
						     
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
							   
	}
	
	
	//+---------------------------------------------------------------------------------
	//
	// Member group /forum mask permission form thingy doodle do yes.
	//
	//+---------------------------------------------------------------------------------
	
	
	function permsplash()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->page_title = "Forum Permission Mask [ HOME ]";
		
		$ADMIN->page_detail = "You can manage your forum permission masks from this section.";
		
		$ADMIN->page_detail .= "<br /><b>Used by Groups</b> relates to the member groups that use this permission mask
								<br /><b>Used by Members</b> relates to the number of members that have this permission mask set to over ride the group used permission mask
							    <br /><b>Preview</b> the forums this mask has access to in a quick, convenient format
							   ";
								
		
		//+-------------------------------------------
		// Get the names for the perm masks w/id
		//+-------------------------------------------
		
		$perms = array();
		
		$DB->query("SELECT * FROM ibf_forum_perms");
		
		while( $r = $DB->fetch_row() )
		{
			$perms[ $r['perm_id'] ] = $r['perm_name'];
		}
		
		//+-------------------------------------------
		// Get the number of members using this mask
		// as an over ride
		//+-------------------------------------------
		
		$mems = array();
		
		$DB->query("SELECT COUNT(id) as count, org_perm_id FROM ibf_members WHERE (org_perm_id IS NOT NULL AND org_perm_id <> 0) GROUP by org_perm_id");
		
		while( $r = $DB->fetch_row() )
		{
			if ( strstr($r['org_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['org_perm_id'] ) as $pid )
				{
					$mems[ $pid ] += $r['count'];
				}
			}
			else
			{
				$mems[ $r['org_perm_id'] ] += $r['count'];
			}
		}
		
		//+-------------------------------------------
		// Get the member group names and the mask
		// they use
		//+-------------------------------------------
		
		$groups = array();
		
		$DB->query("SELECT g_id, g_title, g_perm_id FROM ibf_groups");
		
		while( $r = $DB->fetch_row() )
		{
			if ( strstr($r['g_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['g_perm_id'] ) as $pid )
				{
					$groups[ $pid ][] = $r['g_title'];
				}
			}
			else
			{
				$groups[ $r['g_perm_id'] ][] = $r['g_title'];
			}
		}
		
		//+-------------------------------------------
		// Print the splash screen
		//+-------------------------------------------
		
		$SKIN->td_header[] = array( "Mask Name"          , "20%" );
		$SKIN->td_header[] = array( "Used by Group(s)"   , "20%" );
		$SKIN->td_header[] = array( "Used by Mem(s)"     , "20%" );
		$SKIN->td_header[] = array( "Preview"            , "10%" );
		$SKIN->td_header[] = array( "Edit"               , "15%" );
		$SKIN->td_header[] = array( "Delete"             , "15%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->js_pop_win();
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Forum Permission Masks" );
		
		foreach( $perms as $id => $name )
		{
			$groups_used = "";
			
			$is_active = 0;
			
			if ( is_array( $groups[ $id ] ) )
			{
				foreach( $groups[ $id ] as $bleh => $g_title )
				{
					$groups_used .= $g_title . "<br />";
				}
				
				$is_active = 1;
				
			}
			else
			{
				$groups_used = "<center><i>None</i></center>";
			}
			
			$mems_used = 0;
			
			if ( $mems[ $id ] > 0 )
			{
				$is_active = 1;
				$mems_used = $mems[ $id ] . " (<a href='javascript:pop_win(\"&amp;act=group&amp;code=view_perm_users&amp;id=$id\", \"User\", \"500\",\"350\");' title='View the member names of those using this mask in a new window'>View</a>)";
			}
			
			if ( $is_active > 0 )
			{
				$delete = "<i>Can't, in use</i>";
			}
			else
			{
				$delete = "<a href='{$SKIN->base_url}&amp;act=group&amp;code=pdelete&amp;id=$id'>Delete</a>";
			}
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>$name</b>" ,
													  "$groups_used",
													  "<center>$mems_used</center>",
													  "<center><a href='javascript:pop_win(\"&amp;act=group&amp;code=preview_forums&amp;id=$id&amp;t=read\", \"400\",\"350\");' title='See what this group can see..'>Preview</a></center>",
													  "<center><a href='{$SKIN->base_url}&amp;act=group&amp;code=fedit&amp;id=$id'>Edit</a></center>",
													  "<center>$delete</center>",
											 )      );
		
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$dlist = array();
		
		$dlist[] = array( 'none', 'Do not inherit' );
		
		foreach( $perms as $id => $name )
		{
			$dlist[] = array( $id, $name );
		}
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'dopermadd' ),
												  2 => array( 'act'   , 'group'   ),
									     )      );
									     
		
		$SKIN->td_header[] = array( "{none}" , "60%" );
		$SKIN->td_header[] = array( "{none}" , "40%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Create a new permission mask" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Permission Mask Name</b>" ,
												  $SKIN->form_input( 'new_perm_name' ),
										 )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Inherit forum permission mask from...</b>" ,
												 $SKIN->form_dropdown( 'new_perm_copy', $dlist ),
										 )      );
		
		$ADMIN->html .= $SKIN->end_form("Create");
						     
		$ADMIN->html .= $SKIN->end_table();
		
		
		
		$ADMIN->output();
			
			
	}
	
	
	
	function forum_perms()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the group ID, please try again");
		}
		
		//+----------------------------------
		
		$ADMIN->page_title = "Forum Permission Mask [ EDIT ]";
		
		$ADMIN->page_detail = "You can manage your forum permission masks from this section.";
		
		$ADMIN->page_detail .= "<br />Simply check the boxes to allow permission for that action, or uncheck the box to deny permission for that action.
							   <br /><b>Global</b> indicates that all present and future permission masks have access to that action and as such, cannot be changed";
		
		//+----------------------------------
		
		$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		$group = $DB->fetch_row();
		
		$gid   = $group['perm_id'];
		$gname = $group['perm_name'];
		
		//+-------------------------------
		
		$cats     = array();
		$forums   = array();
		$children = array();
		
		$DB->query("SELECT * from ibf_categories WHERE id > 0 ORDER BY position ASC");
		
		while ($r = $DB->fetch_row())
		{
			$cats[$r['id']] = $r;
		}
		
		$DB->query("SELECT * from ibf_forums ORDER BY position ASC");
		
		while ($r = $DB->fetch_row())
		{
			
			if ($r['parent_id'] > 0)
			{
				$children[ $r['parent_id'] ][] = $r;
			}
			else
			{
				$forums[] = $r;
			}
			
		}
		
		//+----------------------------------
		//| EDIT NAME
		//+----------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'donameedit' ),
												  2 => array( 'act'   , 'group'   ),
												  3 => array( 'id'    , $gid      ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"   , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"   , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Rename Group: ".$group['perm_name'] );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Mask Name</b>" ,
												  $SKIN->form_input("perm_name", $gname )
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form("Edit Name");
		
		$ADMIN->html .= $SKIN->end_table();
		
		
		//+----------------------------------
		//| MAIN FORM
		//+----------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'dofedit' ),
												  2 => array( 'act'   , 'group'   ),
												  3 => array( 'id'    , $gid      ),
									     )      );
		
		$SKIN->td_header[] = array( "Forum Name"   , "40%" );
		$SKIN->td_header[] = array( "Read"         , "15%" );
		$SKIN->td_header[] = array( "Reply"        , "15%" );
		$SKIN->td_header[] = array( "Start"        , "15%" );
		$SKIN->td_header[] = array( "Upload"       , "15%" );
		
		$ADMIN->html .= $SKIN->start_table( "Forum Access Permissions for ".$group['perm_name'] );
		
		$last_cat_id = -1;
		
		foreach ($cats as $c)
		{
			
			$ADMIN->html .= $SKIN->add_td_basic( $c['name'], 'left', 'catrow' );
													   
			$last_cat_id = $c['id'];
			
			
			foreach($forums as $r)
			{	
			
				if ($r['category'] == $last_cat_id)
				{
				
					$read   = "";
					$start  = "";
					$reply  = "";
					$upload = "";
					$global = '<center id="mgblue"><i>Global</i></center>';
					
					if ($r['read_perms'] == '*')
					{
						$read = $global;
					}
					else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['read_perms'] ) )
					{
						$read = "<center id='mgblue'><input type='checkbox' name='read_".$r['id']."' value='1' checked></center>";
					}
					else
					{
						$read = "<center id='mgblue'><input type='checkbox' name='read_".$r['id']."' value='1'></center>";
					}
					
					//---------------------------
					
					$global = '<center id="mgred"><i>Global</i></center>';
					
					if ($r['start_perms'] == '*')
					{
						$start = $global;
					}
					else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['start_perms'] ) )
					{
						$start = "<center id='mgred'><input type='checkbox' name='start_".$r['id']."' value='1' checked></center>";
					}
					else
					{
						$start = "<center id='mgred'><input type='checkbox' name='start_".$r['id']."' value='1'></center>";
					}
					
					//---------------------------
					
					$global = '<center id="mggreen"><i>Global</i></center>';
					
					if ($r['reply_perms'] == '*')
					{
						$reply = $global;
					}
					else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['reply_perms'] ) )
					{
						$reply = "<center id='mggreen'><input type='checkbox' name='reply_".$r['id']."' value='1' checked></center>";
					}
					else
					{
						$reply = "<center id='mggreen'><input type='checkbox' name='reply_".$r['id']."' value='1'></center>";
					}
					
					//---------------------------
					
					$global = '<center id="memgroup"><i>Global</i></center>';
					
					if ($r['upload_perms'] == '*')
					{
						$upload = $global;
					}
					else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['upload_perms'] ) )
					{
						$upload = "<center id='memgroup'><input type='checkbox' name='upload_".$r['id']."' value='1' checked></center>";
					}
					else
					{
						$upload = "<center id='memgroup'><input type='checkbox' name='upload_".$r['id']."' value='1'></center>";
					}
					
					//---------------------------
					
					if ($r['subwrap'] == 1 and $r['sub_can_post'] != 1)
					{
						$ADMIN->html .= $SKIN->add_td_basic( "&gt; ".$r['name'], 'left', 'catrow2' );
					}
					else
					{
						$css = $r['subwrap'] == 1 ? 'catrow2' : '';
						
						$ADMIN->html .= $SKIN->add_td_row( array(
															   "<b> - ".$r['name']."</b>",
															   $read,
															   $reply,
															   $start,
															   $upload
													 )   , $css   );
					}
													 
					if ( ( isset($children[ $r['id'] ]) ) and ( count ($children[ $r['id'] ]) > 0 ) )
					{
						foreach($children[ $r['id'] ] as $idx => $rd)
						{
							$read   = "";
							$start  = "";
							$reply  = "";
							$upload = "";
							$global = "<center id='mgblue'><i>Global</i></center>";
							
							if ($rd['read_perms'] == '*')
							{
								$read = $global;
							}
							else if ( preg_match( "/(^|,)".$gid."(,|$)/", $rd['read_perms'] ) )
							{
								$read = "<center id='mgblue'><input type='checkbox' name='read_".$rd['id']."' value='1' checked></center>";
							}
							else
							{
								$read = "<center id='mgblue'><input type='checkbox' name='read_".$rd['id']."' value='1'></center>";
							}
							
							//---------------------------
							
							$global = "<center id='mgred'><i>Global</i></center>";
							
							if ($rd['start_perms'] == '*')
							{
								$start = $global;
							}
							else if ( preg_match( "/(^|,)".$gid."(,|$)/", $rd['start_perms'] ) )
							{
								$start = "<center id='mgred'><input type='checkbox' name='start_".$rd['id']."' value='1' checked></center>";
							}
							else
							{
								$start = "<center id='mgred'><input type='checkbox' name='start_".$rd['id']."' value='1'></center>";
							}
							
							//---------------------------
							
							$global = "<center id='mggreen'><i>Global</i></center>";
							
							if ($rd['reply_perms'] == '*')
							{
								$reply = $global;
							}
							else if ( preg_match( "/(^|,)".$gid."(,|$)/", $rd['reply_perms'] ) )
							{
								$reply = "<center id='mggreen'><input type='checkbox' name='reply_".$rd['id']."' value='1' checked></center>";
							}
							else
							{
								$reply = "<center id='mggreen'><input type='checkbox' name='reply_".$rd['id']."' value='1'></center>";
							}
							
							//---------------------------
							
							$global = "<center id='memgroup'><i>Global</i></center>";
							
							if ($rd['upload_perms'] == '*')
							{
								$upload = $global;
							}
							else if ( preg_match( "/(^|,)".$gid."(,|$)/", $rd['upload_perms'] ) )
							{
								$upload = "<center id='memgroup'><input type='checkbox' name='upload_".$rd['id']."' value='1' checked></center>";
							}
							else
							{
								$upload = "<center id='memgroup'><input type='checkbox' name='upload_".$rd['id']."' value='1'></center>";
							}
							
							//---------------------------
					
							$ADMIN->html .= $SKIN->add_td_row( array(
															   "<b> --- ".$rd['name']."</b>",
															   $read,
															   $reply,
															   $start,
															   $upload
													 ) , 'subforum'     );
						}
					}					 
				}
			}
		}
		
		$ADMIN->html .= $SKIN->end_form("Update Forum Permissions");
		
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
		
	}
	
	
	function edit_name_perm()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//---------------------------
		// Check for legal ID
		//---------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve that group ID");
		}
		
		if ( $IN['perm_name'] == "" )
		{
			$ADMIN->error("You must enter a name");
		}
		
		$gid = $IN['id'];
		
		//---------------------------
		
		$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		if ( ! $gr = $DB->fetch_row() )
		{
			$ADMIN->error("Not a valid group ID");
		}
		
		$DB->query("UPDATE ibf_forum_perms SET perm_name='{$IN['perm_name']}' WHERE perm_id='".$IN['id']."'");
		
		$ADMIN->save_log("Forum Access Permissions Name Edited for Mask: '{$gr['perm_name']}'");
		
		$ADMIN->done_screen("Forum Access Permissions Updated", "Permission Mask Control", "act=group&code=permsplash" );
		
		
	}
	
	
	
	function do_forum_perms()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//---------------------------
		// Check for legal ID
		//---------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve that group ID");
		}
		
		$gid = $IN['id'];
		
		//---------------------------
		
		$DB->query("SELECT * FROM ibf_forum_perms WHERE perm_id='".$IN['id']."'");
		
		if ( ! $gr = $DB->fetch_row() )
		{
			$ADMIN->error("Not a valid group ID");
		}
		
		//---------------------------
		// Pull the forum data..
		//---------------------------
		
		$forum_q = $DB->query("SELECT id, read_perms, start_perms, reply_perms, upload_perms FROM ibf_forums ORDER BY position ASC");
		
		while ( $row = $DB->fetch_row( $forum_q ) )
		{
		
			$read  = "";
			$reply = "";
			$start = "";
			$upload = "";
			//---------------------------
			// Is this global?
			//---------------------------
			
			if ($row['read_perms'] == '*')
			{
				$read = '*';
				
			}
			else
			{
				//---------------------------
				// Split the set IDs
				//---------------------------
				
				$read_ids = explode( ",", $row['read_perms'] );
				
				if ( is_array($read_ids) )
				{
				   foreach ($read_ids as $i)
				   {
					   //---------------------------
					   // If it's the current ID, skip
					   //---------------------------
					   
					   if ($gid == $i)
					   {
						   continue;
					   }
					   else
					   {
						   $read .= $i.",";
					   }
				   }
				}
				//---------------------------
				// Was the box checked?
				//---------------------------
				
				if ($IN[ 'read_'.$row['id'] ] == 1)
				{
					// Add our group ID...
					
					$read .= $gid.",";
				}
				
				// Tidy..
				
				$read = preg_replace( "/,$/", "", $read );
				$read = preg_replace( "/^,/", "", $read );
				
			}
			
			//---------------------------
			// Reply topics..
			//---------------------------
				
			if ($row['reply_perms'] == '*')
			{
				$reply = '*';
			}
			else
			{
				$reply_ids = explode( ",", $row['reply_perms'] );
				
				if ( is_array($reply_ids) )
				{
				
					foreach ($reply_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$reply .= $i.",";
						}
					}
				
				}
				
				if ($IN[ 'reply_'.$row['id'] ] == 1)
				{
					$reply .= $gid.",";
				}
				
				$reply = preg_replace( "/,$/", "", $reply );
				$reply = preg_replace( "/^,/", "", $reply );
			}
			
			//---------------------------
			// Start topics..
			//---------------------------
				
			if ($row['start_perms'] == '*')
			{
				$start = '*';
			}
			else
			{
				$start_ids = explode( ",", $row['start_perms'] );
				
				if ( is_array($start_ids) )
				{
				
					foreach ($start_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$start .= $i.",";
						}
					}
				
				}
				
				if ($IN[ 'start_'.$row['id'] ] == 1)
				{
					$start .= $gid.",";
				}
				
				$start = preg_replace( "/,$/", "", $start );
				$start = preg_replace( "/^,/", "", $start );
			}
			
			//---------------------------
			// Upload topics..
			//---------------------------
				
			if ($row['upload_perms'] == '*')
			{
				$upload = '*';
			}
			else
			{
				$upload_ids = explode( ",", $row['upload_perms'] );
				
				if ( is_array($upload_ids) )
				{
				
					foreach ($upload_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$upload .= $i.",";
						}
					}
				
				}
				
				if ($IN[ 'upload_'.$row['id'] ] == 1)
				{
					$upload .= $gid.",";
				}
				
				$upload = preg_replace( "/,$/", "", $upload );
				$upload = preg_replace( "/^,/", "", $upload );
			}
			
			//---------------------------
			// Update the DB...
			//---------------------------
			
			if (! $new_q = $DB->query("UPDATE ibf_forums SET read_perms='$read', reply_perms='$reply', start_perms='$start', upload_perms='$upload' WHERE id='".$row['id']."'") )
			{
				die ("Update query failed on Forum ID ".$row['id']);
			}
			
		}
		
		$ADMIN->save_log("Forum Access Permissions Edited for Mask: '{$gr['perm_name']}'");
		
		$ADMIN->done_screen("Forum Access Permissions Updated", "Permission Mask Control", "act=group&code=permsplash" );
		
	}
	
	//+---------------------------------------------------------------------------------
	//
	// Delete a group
	//
	//+---------------------------------------------------------------------------------
	
	function delete_form()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the group ID, please try again");
		}
		
		if ($IN['id'] < 5)
		{
			$ADMIN->error("You can not move the preset groups. You can rename them and edit the functionality");
		}
		
		$ADMIN->page_title = "Deleting a User Group";
		
		$ADMIN->page_detail = "Please check to ensure that you are attempting to remove the correct group.";
		
		
		//+-------------------------------
		
		$DB->query("SELECT COUNT(id) as users FROM ibf_members WHERE mgroup='".$IN['id']."'");
		$black_adder = $DB->fetch_row();
		
		if ($black_adder['users'] < 1)
		{
			$black_adder['users'] = 0;
		}
		
		$DB->query("SELECT g_title FROM ibf_groups WHERE g_id='".$IN['id']."'");
		$group = $DB->fetch_row();
		
		//+-------------------------------
		
		$DB->query("SELECT g_id, g_title FROM ibf_groups WHERE g_id <> '".$IN['id']."'");
		
		$mem_groups = array();
		
		while ( $r = $DB->fetch_row() )
		{
			$mem_groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'dodelete'  ),
												  2 => array( 'act'   , 'group'     ),
												  3 => array( 'id'    , $IN['id']   ),
												  4 => array( 'name'  , $group['g_title'] ),
									     )      );
									     
		
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Removal Confirmation: ".$group['g_title'] );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Number of users in this group</b>" ,
												  "<b>".$black_adder['users']."</b>",
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Move users in this group to...</b>" ,
												  $SKIN->form_dropdown("to_id", $mem_groups )
									     )      );
		
		$ADMIN->html .= $SKIN->end_form("Delete this group");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
			
	}
	
	function do_delete()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the group ID, please try again");
		}
		
		if ($IN['to_id'] == "")
		{
			$ADMIN->error("No move to group ID was specified. /me cries.");
		}
		
		// Check to make sure that the relevant groups exist.
		
		$DB->query("SELECT g_id FROM ibf_groups WHERE g_id IN(".$IN['id'].",".$IN['to_id'].")");
		
		if ( $DB->get_num_rows() != 2 )
		{
			$ADMIN->error("Could not resolve the ID's passed to group deletion");
		}
		
		$DB->query("UPDATE ibf_members SET mgroup='".$IN['to_id']."' WHERE mgroup='".$IN['id']."'");
		
		$DB->query("DELETE FROM ibf_groups WHERE g_id='".$IN['id']."'");
		
		// Look for promotions in case we have members to be promoted to this group...
		
		$prq = $DB->query("SELECT g_id FROM ibf_groups WHERE g_promotion LIKE '{$IN['id']}&%'");
		
		while ( $row = $DB->fetch_row($prq) )
		{
			$nq = $DB->query("UPDATE ibf_groups SET g_promotion='-1&-1' WHERE g_id='".$row['g_id']."'");
		}
		
		// Remove from moderators table
		
		$DB->query("DELETE FROM ibf_moderators WHERE is_group=1 AND group_id=".$IN['id']);
		
		$ADMIN->save_log("Member Group '{$IN['name']}' removed");
		
		$ADMIN->done_screen("Group Removed", "Group Control", "act=group" );
		
	}
	
	
	//+---------------------------------------------------------------------------------
	//
	// Save changes to DB
	//
	//+---------------------------------------------------------------------------------
	
	function save_group($type='edit')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['g_title'] == "")
		{
			$ADMIN->error("You must enter a group title.");
		}
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("Could not resolve the group id");
			}
			
			if ($IN['id'] == $INFO['admin_group'] and $IN['g_access_cp'] != 1)
			{
				$ADMIN->error("You can not remove the ability to access the admin control panel for this group");
			}
		}
		
		//------------------------------------
		// Sort out the perm mask id things
		//------------------------------------
		
		if ( is_array( $HTTP_POST_VARS['permid'] ) )
		{
			$perm_id = implode( ",", $HTTP_POST_VARS['permid'] );
		}
		else
		{
			$ADMIN->error("No permission masks chosen");
		}
		
		// Build up the hashy washy for the database ..er.. wase.
		
		$prefix = preg_replace( "/&#39;/", "'" , $std->txt_safeslashes($HTTP_POST_VARS['prefix']) );
		$prefix = preg_replace( "/&lt;/" , "<" , $prefix          );
		$suffix = preg_replace( "/&#39;/", "'" , $std->txt_safeslashes($HTTP_POST_VARS['suffix']) );
		$suffix = preg_replace( "/&lt;/" , "<" , $suffix          );
		
		$promotion_a = '-1'; //id
		$promotion_b = '-1'; // posts
		
		if ($IN['g_promotion_id'] > 0)
		{
			$promotion_a = $IN['g_promotion_id'];
			$promotion_b = $IN['g_promotion_posts'];
		}
		
		//if ($IN['g_max_messages'] == 0)
		//{
			//$IN['g_max_messages'] = -1;
		//}
		
		//list($p_max, $p_width, $p_height) = explode( ":", $group['g_photo_max_vars'] );
		
		$IN['p_max']    = str_replace( ":", "", $IN['p_max'] );
		$IN['p_width']  = str_replace( ":", "", $IN['p_width'] );
		$IN['p_height'] = str_replace( ":", "", $IN['p_height'] );
		
		$db_string = array(
							 'g_view_board'         => $IN['g_view_board'],
							 'g_mem_info'           => $IN['g_mem_info'],
							 'g_other_topics'       => $IN['g_other_topics'],
							 'g_use_search'         => $IN['g_use_search'],
							 'g_email_friend'       => $IN['g_email_friend'],
							 'g_invite_friend'      => $IN['g_invite_friend'],
							 'g_edit_profile'       => $IN['g_edit_profile'],
							 'g_post_new_topics'    => $IN['g_post_new_topics'],
							 'g_reply_own_topics'   => $IN['g_reply_own_topics'],
							 'g_reply_other_topics' => $IN['g_reply_other_topics'],
							 'g_edit_posts'         => $IN['g_edit_posts'],
							 'g_edit_cutoff'        => $IN['g_edit_cutoff'],
							 'g_delete_own_posts'   => $IN['g_delete_own_posts'],
							 'g_open_close_posts'   => $IN['g_open_close_posts'],
							 'g_delete_own_topics'  => $IN['g_delete_own_topics'],
							 'g_post_polls'         => $IN['g_post_polls'],
							 'g_vote_polls'         => $IN['g_vote_polls'],
							 'g_use_pm'             => $IN['g_use_pm'],
							 'g_is_supmod'          => $IN['g_is_supmod'],
							 'g_access_cp'          => $IN['g_access_cp'],
							 'g_title'              => trim($IN['g_title']),
							 'g_can_remove'         => $IN['g_can_remove'],
							 'g_append_edit'        => $IN['g_append_edit'],
							 'g_access_offline'     => $IN['g_access_offline'],
							 'g_avoid_q'            => $IN['g_avoid_q'],
							 'g_avoid_flood'        => $IN['g_avoid_flood'],
							 'g_icon'               => trim($IN['g_icon']),
							 'g_attach_max'         => $IN['g_attach_max'],
							 'g_avatar_upload'      => $IN['g_avatar_upload'],
							 'g_calendar_post'      => $IN['g_calendar_post'],
							 'g_max_messages'       => $IN['g_max_messages'],
							 'g_max_mass_pm'        => $IN['g_max_mass_pm'],
							 'g_search_flood'       => $IN['g_search_flood'],
							 'prefix'               => $prefix,
							 'suffix'               => $suffix,
							 'g_promotion'          => $promotion_a.'&'.$promotion_b,
							 'g_hide_from_list'     => $IN['g_hide_from_list'],
							 'g_post_closed'        => $IN['g_post_closed'],
							 'g_perm_id'			=> $perm_id,
							 'g_photo_max_vars'	    => $IN['p_max'].':'.$IN['p_width'].':'.$IN['p_height'],
							 'g_dohtml'			    => $IN['g_dohtml'],
							 'g_edit_topic'			=> $IN['g_edit_topic'],
							 'g_email_limit'		=> intval($IN['join_limit']).':'.intval($IN['join_flood']),
							 
						  );
						  
		if ($type == 'edit')
		{
			$rstring = $DB->compile_db_update_string( $db_string );
			
			$DB->query("UPDATE ibf_groups SET $rstring WHERE g_id='".$IN['id']."'");
			
			// Update the title of the group held in the mod table incase it changed.
			
			$DB->query("UPDATE ibf_moderators SET group_name='".trim($IN['g_title'])."' WHERE group_id='".$IN['id']."'");
			
			$ADMIN->save_log("Edited Group '{$IN['g_title']}'");
			
			$ADMIN->done_screen("Group Edited", "Group Control", "act=group" );
			
		}
		else
		{
			$rstring = $DB->compile_db_insert_string( $db_string );
			
			$DB->query("INSERT INTO ibf_groups (" .$rstring['FIELD_NAMES']. ") VALUES (". $rstring['FIELD_VALUES'] .")");
			
			$ADMIN->save_log("Added Group '{$IN['g_title']}'");
			
			$ADMIN->done_screen("Group Added", "Group Control", "act=group" );
		}
	}
	
	function clean_perms($str)
	{
		$str = preg_replace( "/,$/", "", $str );
		$str = str_replace(  ",,", ",", $str );
		
		return $str;
	}
	
	//+---------------------------------------------------------------------------------
	//
	// Add / edit group
	//
	//+---------------------------------------------------------------------------------
	
	function group_form($type='edit')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$all_groups = array( 0 => array ('none', 'Don\'t Promote') );
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("No group id to select from the database, please try again.");
			}
			
			if ( $INFO['admin_group'] == $IN['id'] )
			{
				if ( $MEMBER['mgroup'] != $INFO['admin_group'] )
				{
					$ADMIN->error("Sorry, you are unable to edit that group as it's the root admin group");
				}
			}
			
			$form_code = 'doedit';
			$button    = 'Complete Edit';
				
		}
		else
		{
			$form_code = 'doadd';
			$button    = 'Add Group';
		}
		
		if ($IN['id'] != "")
		{
			$DB->query("SELECT * FROM ibf_groups WHERE g_id='".$IN['id']."'");
			$group = $DB->fetch_row();
			
			$query = "SELECT g_id, g_title FROM ibf_groups WHERE g_id <> {$IN['id']} ORDER BY g_title";
		}
		else
		{
			$group = array();
			
			$query = "SELECT g_id, g_title FROM ibf_groups ORDER BY g_title";
		}
		
		//-------------------------------------------
		// sort out the promotion stuff
		//-------------------------------------------
		
		list($group['g_promotion_id'], $group['g_promotion_posts']) = explode( '&', $group['g_promotion'] );
		
		if ($group['g_promotion_posts'] < 1)
		{
			$group['g_promotion_posts'] = '';
		}
		
		$DB->query($query);
		
		while ( $r = $DB->fetch_row() )
		{
			if ( $r['g_id'] == $INFO['admin_group'] )
			{
				continue;
			}
			
			$all_groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//-------------------------------------------
		
		$perm_masks = array();
		
		$DB->query("SELECT * FROM ibf_forum_perms");
		
		while ( $r = $DB->fetch_row() )
		{
			$perm_masks[] = array( $r['perm_id'], $r['perm_name'] );
		}
		
		//-------------------------------------------
		
		if ($type == 'edit')
		{
			$ADMIN->page_title = "Editing User Group ".$group['g_title'];
		}
		else
		{
			$ADMIN->page_title = 'Adding a new user group';
			$group['g_title'] = 'New Group';
		}
		
		$guest_legend = "";
		
		if ($group['g_id'] == $INFO['guest_group'])
		{
			$guest_legend = "</b><br><i>(Does not apply to guests)</i>";
		}
		
		$ADMIN->page_detail = "Please double check the information before submitting the form.";
		
		
		//+-------------------------------
		
		$ADMIN->html .= "<script language='javascript'>
						 <!--
						  function checkform() {
						  
						  	isAdmin = document.forms[0].g_access_cp;
						  	isMod   = document.forms[0].g_is_supmod;
						  	
						  	msg = '';
						  	
						  	if (isAdmin[0].checked == true)
						  	{
						  		msg += 'Members in this group can access the Admin Control Panel\\n\\n';
						  	}
						  	
						  	if (isMod[0].checked == true)
						  	{
						  		msg += 'Members in this group are super moderators.\\n\\n';
						  	}
						  	
						  	if (msg != '')
						  	{
						  		msg = 'Security Check\\n--------------\\nMember Group Title: ' + document.forms[0].g_title.value + '\\n--------------\\n\\n' + msg + 'Is this correct?';
						  		
						  		formCheck = confirm(msg);
						  		
						  		if (formCheck == true)
						  		{
						  			return true;
						  		}
						  		else
						  		{
						  			return false;
						  		}
						  	}
						  }
						 //-->
						 </script>\n";
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $form_code  ),
												  2 => array( 'act'   , 'group'     ),
												  3 => array( 'id'    , $IN['id']   ),
									     ) , 'adform', "onSubmit='return checkform()'" );
									     
		
		list($p_max, $p_width, $p_height) = explode( ":", $group['g_photo_max_vars'] );
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$prefix = preg_replace( "/'/", "&#39;", $group['prefix'] );
		$prefix = preg_replace( "/</", "&lt;" , $prefix          );
		$suffix = preg_replace( "/'/", "&#39;", $group['suffix'] );
		$suffix = preg_replace( "/</", "&lt;" , $suffix          );
		
		$ADMIN->html .= $SKIN->start_table( "Global Settings", "Basic Group Settings" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Group Title</b>" ,
												  $SKIN->form_input("g_title", $group['g_title'] )
									     )      );
									     
		//+-------------------------------
		// Sort out default array
		//+-------------------------------
		
		$ADMIN->html .=
		"<script type='text/javascript'>
			
			var show   = '';
		";
		
		foreach ($perm_masks as $id => $d)
		{
			$ADMIN->html .= " 		perms_$d[0] = '$d[1]';\n";
		}
		
		$ADMIN->html .=
		"	
			var show = '';
			
		 	function saveit(f)
		 	{
		 		show = '';
		 		
		 		for (var i = 0 ; i < f.options.length; i++)
				{
					if (f.options[i].selected)
					{
						tid  = f.options[i].value;
						show += '\\n' + eval('perms_'+tid);
					}
				}
			}
			
			function show_me()
			{
				if (show == '')
				{
					show = 'No change detected\\nClick on the multi-select box to activate';
				}
				
				alert('Selected Permission Masks\\n---------------------------------\\n' + show);
			}
			
		</script>";
		
		$arr = explode( ",", $group['g_perm_id'] );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Use Forum Permission Access...</b><br>You may choose more than one" ,
												  $SKIN->form_multiselect("permid[]", $perm_masks, $arr, 5, 'onfocus="saveit(this)"; onchange="saveit(this)";' )."<a href='javascript:show_me();'>Show me selected masks</a>"
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Group Icon</b><br>(Can be omitted)" ,
												  $SKIN->form_input("g_icon", $group['g_icon'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>POST: Max upload file size (in KB)</b>".$SKIN->js_help_link('mg_upload')."<br>(Leave blank to disallow uploads)" ,
												  $SKIN->form_input("g_attach_max", $group['g_attach_max'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>PERSONAL PHOTO: Max upload file size (in KB)</b><br>(Leave blank to disallow uploads)" ,
												  $SKIN->form_input("p_max", $p_max )."<br />"
												  ."Max Width (px): <input type='text' size='3' name='p_width' value='{$p_width}'> "
												  ."Max Height (px): <input type='text' size='3' name='p_height' value='{$p_height}'>"
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Online List Format [Prefix]</b><br>(Can be left blank)<br>(Example:&lt;span style='color:red'&gt;)" ,
												  $SKIN->form_input("prefix", $prefix )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Online List Format [Suffix]</b><br>(Can be left blank)<br>(Example:&lt;/span&gt;)" ,
												  $SKIN->form_input("suffix", $suffix )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Hide this group from the member list?</b>" ,
												  $SKIN->form_yes_no("g_hide_from_list", $group['g_hide_from_list'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Global Permissions", "Restricting what this group can do" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can view board?</b>" ,
												  $SKIN->form_yes_no("g_view_board", $group['g_view_board'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can view OFFLINE board?</b>" ,
												  $SKIN->form_yes_no("g_access_offline", $group['g_access_offline'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can view member profiles and the member list?</b>" ,
												  $SKIN->form_yes_no("g_mem_info", $group['g_mem_info'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can view other members topics?</b>" ,
												  $SKIN->form_yes_no("g_other_topics", $group['g_other_topics'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can use search?</b>" ,
												  $SKIN->form_yes_no("g_use_search", $group['g_use_search'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Number of seconds for search flood control</b><br>Stops search abuse, enter 0 or leave blank for no flood control" ,
												  $SKIN->form_input("g_search_flood", $group['g_search_flood'] )
									     )      );
									     
		list( $limit, $flood ) = explode( ":", $group['g_email_limit'] );					     
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can email members from the board?</b><br />Leave bottom section blank to remove limits $guest_legend</b>" ,
												  $SKIN->form_yes_no("g_email_friend", $group['g_email_friend'] )
												 ."<br />Only allow ". $SKIN->form_simple_input("join_limit", $limit, 2 )." emails in a 24hr period"
												 ."<br />...and only allow 1 email every ".$SKIN->form_simple_input("join_flood", $flood, 2 )." minutes"
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can edit own profile info?$guest_legend" ,
												  $SKIN->form_yes_no("g_edit_profile", $group['g_edit_profile'] )
									     )      );							     
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can use PM system?$guest_legend" ,
												  $SKIN->form_yes_no("g_use_pm", $group['g_use_pm'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Max. Number users allowed to mass PM?$guest_legend<br>(Enter 0 or leave blank to disable mass PM)" ,
												  $SKIN->form_input("g_max_mass_pm", $group['g_max_mass_pm'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Max. Number of storable messages?$guest_legend" ,
												  $SKIN->form_input("g_max_messages", $group['g_max_messages'] )
									     )      );
									     						     							     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can Upload avatars?$guest_legend" ,
												  $SKIN->form_yes_no("g_avatar_upload", $group['g_avatar_upload'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Posting Permissions", "Restrict where this group can post" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can post new topics (where allowed)?</b>" ,
												  $SKIN->form_yes_no("g_post_new_topics", $group['g_post_new_topics'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can reply to OWN topics?</b>" ,
												  $SKIN->form_yes_no("g_reply_own_topics", $group['g_reply_own_topics'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can reply to OTHER members topics (where allowed)?</b>" ,
												  $SKIN->form_yes_no("g_reply_other_topics", $group['g_reply_other_topics'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can edit own posts?$guest_legend" ,
												  $SKIN->form_yes_no("g_edit_posts", $group['g_edit_posts'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Edit time restriction (in minutes)?$guest_legend<br>Denies user edit after the time set has passed. Leave blank or enter 0 for no restriction" ,
												  $SKIN->form_input("g_edit_cutoff", $group['g_edit_cutoff'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Allow user to remove 'Edited by' legend?$guest_legend</b>" ,
												  $SKIN->form_yes_no("g_append_edit", $group['g_append_edit'] )
									     )      );							     
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can delete own posts?$guest_legend" ,
												  $SKIN->form_yes_no("g_delete_own_posts", $group['g_delete_own_posts'] )
									     )      );
									     						     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can open/close own topics?$guest_legend" ,
												  $SKIN->form_yes_no("g_open_close_posts", $group['g_open_close_posts'] )
									     )      );							     
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can edit own topic title & description?$guest_legend" ,
												  $SKIN->form_yes_no("g_edit_topic", $group['g_edit_topic'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can delete own topics?$guest_legend" ,
												  $SKIN->form_yes_no("g_delete_own_topics", $group['g_delete_own_topics'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can start new polls (where allowed)?$guest_legend</b>" ,
												  $SKIN->form_yes_no("g_post_polls", $group['g_post_polls'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can vote in polls (where allowed)?$guest_legend" ,
												  $SKIN->form_yes_no("g_vote_polls", $group['g_vote_polls'] )
									     )      );							     
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can avoid flood control?</b>" ,
												  $SKIN->form_yes_no("g_avoid_flood", $group['g_avoid_flood'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can avoid moderation queues?</b>" ,
												  $SKIN->form_yes_no("g_avoid_q", $group['g_avoid_q'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can add events to the calendar?$guest_legend</b>" ,
												  $SKIN->form_yes_no("g_calendar_post", $group['g_calendar_post'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can use [doHTML] tag?$guest_legend</b><br />".$SKIN->js_help_link('mg_dohtml') ,
												  $SKIN->form_yes_no("g_dohtml", $group['g_dohtml'] )
									     )      );
									     					     							     
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Moderation Permissions", "Allow or deny this group moderation abilities" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Is Super Moderator (can moderate anywhere)?$guest_legend" ,
												  $SKIN->form_yes_no("g_is_supmod", $group['g_is_supmod'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Can access the Admin CP?$guest_legend" ,
												  $SKIN->form_yes_no("g_access_cp", $group['g_access_cp'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Allow user group to post in 'closed' topics?" ,
												  $SKIN->form_yes_no("g_post_closed", $group['g_post_closed'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Group Promotion" );
		
		if ($group['g_id'] == $INFO['admin_group'])
		{
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>Choose 'Don't Promote' to disable promotions</b><br>".$SKIN->js_help_link('mg_promote') ,
													  "Feature disable for the root admin group, after all - if you're at the top where can you be promoted to?"
											 )      );
		}
		else
		{
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>Choose 'Don't Promote' to disable promotions</b>$guest_legend<br>".$SKIN->js_help_link('mg_promote') ,
													  'Promote members of this group to: '.$SKIN->form_dropdown("g_promotion_id", $all_groups, $group['g_promotion_id'] )
													 .'<br>when they reach '.$SKIN->form_simple_input('g_promotion_posts', $group['g_promotion_posts'] ).' posts'
											 )      );
		}
		
									     
		$ADMIN->html .= $SKIN->end_form($button);
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
			
			
	}

	//+---------------------------------------------------------------------------------
	//
	// Show "Management Screen
	//
	//+---------------------------------------------------------------------------------
	
	function main_screen()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->page_title = "User Groups";
		
		$ADMIN->page_detail = "User Grouping is a quick and powerful way to organise your members. There are 4 preset groups that you cannot remove (Validating, Guest, Member and Admin) although you may edit these at will. A good example of user grouping is to set up a group called 'Moderators' and allow them access to certain forums other groups do not have access to.<br>Forum access allows you to make quick changes to that groups forum read, write and reply settings. You may do this on a forum per forum basis in forum control.";
		
		$g_array = array();
		
		$SKIN->td_header[] = array( "Group Title"    , "30%" );
		$SKIN->td_header[] = array( "Access ACP?"    , "15%" );
		$SKIN->td_header[] = array( "Super Mod?"     , "15%" );
		$SKIN->td_header[] = array( "Members"        , "10%" );
		$SKIN->td_header[] = array( "Edit Group"     , "20%" );
		$SKIN->td_header[] = array( "Delete"         , "10%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "User Group Management" );
		
		$DB->query("SELECT ibf_groups.g_id, ibf_groups.g_access_cp, ibf_groups.g_is_supmod, ibf_groups.g_title,ibf_groups.prefix, ibf_groups.suffix, COUNT(ibf_members.id) as count FROM ibf_groups "
		          ."LEFT JOIN ibf_members ON (ibf_members.mgroup = ibf_groups.g_id) "
		          ."GROUP BY ibf_groups.g_id ORDER BY ibf_groups.g_title");
		
		while ( $r = $DB->fetch_row() )
		{
		
			$del  = "";
			$mod  = '&nbsp;';
			$adm  = '&nbsp;';
			
			if ($r['g_id'] > 4)
			{
				$del = "<center><a href='{$ADMIN->base_url}&act=group&code=delete&id=".$r['g_id']."'>Delete</a></center>";
			}
			//-----------------------------------
			if ($r['g_access_cp'] == 1)
			{
				$adm = '<center><span style="color:red">Yes</span></center>';
			}
			//-----------------------------------
			if ($r['g_is_supmod'] == 1)
			{
				$mod = '<center><span style="color:red">Yes</span></center>';
			}
			
			if ($r['g_id'] != 1 and $r['g_id'] != 2)
			{
				$total_linkage = "<a href='{$INFO['board_url']}/index.{$INFO['php_ext']}?act=Members&max_results=30&filter={$r['g_id']}&sort_order=asc&sort_key=name&st=0' target='_blank' title='List Users'>".$r['prefix'].$r['g_title'].$r['suffix']."</a>";
			}
			else
			{
				$total_linkage = $r['prefix'].$r['g_title'].$r['suffix'];
			}
			
			if ( $INFO['admin_group'] == $r['g_id'] )
			{
				$is_root = " ( ROOT )";
			}
			else
			{
				$is_root = "";
			}
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>$total_linkage</b> $is_root" ,
												      $adm,
												      $mod,
												      "<center>".$r['count']."</center>",
												      "<center><a href='{$ADMIN->base_url}&act=group&code=edit&id=".$r['g_id']."'>Edit Group</a></center>",
												      $del
												      
									     )      );
									     
			$g_array[] = array( $r['g_id'], $r['g_title'] );
		}
		
		$ADMIN->html .= $SKIN->add_td_basic("&nbsp;", "center", "title");

		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'add' ),
												  2 => array( 'act'   , 'group'     ),
									     )      );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Add a new member group" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Base new group on...</b>" ,
												  $SKIN->form_dropdown("id", $g_array, 3 )
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form("Set up New Group");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
		
		
	}
	
		
}


?>