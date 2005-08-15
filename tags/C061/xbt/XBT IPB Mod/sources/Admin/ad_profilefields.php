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
|   > Custom profile field functions
|   > Module written by Matt Mecham
|   > Date started: 24th June 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_fields();


class ad_fields {

	var $base_url;

	function ad_fields() {
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
			case 'add':
				$this->main_form('add');
				break;
				
			case 'doadd':
				$this->main_save('add');
				break;
				
			case 'edit':
				$this->main_form('edit');
				break;
				
			case 'doedit':
				$this->main_save('edit');
				break;
				
			case 'delete':
				$this->delete_form();
				break;
				
			case 'dodelete':
				$this->do_delete();
				break;
						
			default:
				$this->main_screen();
				break;
		}
		
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
		
		$ADMIN->page_title = "Deleting a Custom Profile Field";
		
		$ADMIN->page_detail = "Please check to ensure that you are attempting to remove the correct custom profile field as <b>all data will be lost!</b>.";
		
		
		//+-------------------------------
		
		$DB->query("SELECT ftitle, fid FROM ibf_pfields_data WHERE fid='".$IN['id']."'");
		
		if ( ! $field = $DB->fetch_row() )
		{
			$ADMIN->error("Could not fetch the row from the database");
		}
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'dodelete'  ),
												  2 => array( 'act'   , 'field'     ),
												  3 => array( 'id'    , $IN['id']   ),
									     )      );
									     
		
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Removal Confirmation" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Custom Profile field to remove</b>" ,
												  "<b>".$field['ftitle']."</b>",
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form("Delete this custom field");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
			
			
	}
	
	function do_delete()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("Could not resolve the field ID, please try again");
		}
		
		
		// Check to make sure that the relevant groups exist.
		
		$DB->query("SELECT ftitle, fid FROM ibf_pfields_data WHERE fid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not resolve the ID's passed to deletion");
		}
		
		$DB->query("ALTER TABLE ibf_pfields_content DROP field_{$row['fid']}");
		
		$DB->query("DELETE FROM ibf_pfields_data WHERE fid='".$IN['id']."'");
		
		$ADMIN->done_screen("Profile Field Removed", "Custom Profile Field Control", "act=field" );
		
	}
	
	
	//+---------------------------------------------------------------------------------
	//
	// Save changes to DB
	//
	//+---------------------------------------------------------------------------------
	
	function main_save($type='edit')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['ftitle'] == "")
		{
			$ADMIN->error("You must enter a field title.");
		}
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("Could not resolve the field id");
			}
			
		}
		
		$content = "";
		
		if ($HTTP_POST_VARS['fcontent'] != "")
		{
			$content = str_replace( "\n", '|', str_replace( "\n\n", "\n", trim($HTTP_POST_VARS['fcontent']) ) );
		}
		
		$db_string = array( 'ftitle'    => $IN['ftitle'],
						    'fdesc'     => $IN['fdesc'],
						    'fcontent'  => stripslashes($content),
						    'ftype'     => $IN['ftype'],
						    'freq'      => $IN['freq'],
						    'fhide'     => $IN['fhide'],
						    'fmaxinput' => $IN['fmaxinput'],
						    'fedit'     => $IN['fedit'],
						    'forder'    => $IN['forder'],
						    'fshowreg'  => $IN['fshowreg'],
						  );
		
						  
		if ($type == 'edit')
		{
			$rstring = $DB->compile_db_update_string( $db_string );
			
			$DB->query("UPDATE ibf_pfields_data SET $rstring WHERE fid='".$IN['id']."'");
			
			$ADMIN->done_screen("Profile Field Edited", "Custom Profile Field Control", "act=field" );
			
		}
		else
		{
			$rstring = $DB->compile_db_insert_string( $db_string );
			
			$DB->query("INSERT INTO ibf_pfields_data (" .$rstring['FIELD_NAMES']. ") VALUES (". $rstring['FIELD_VALUES'] .")");
			
			$new_id = $DB->get_insert_id();
			
			$DB->query("ALTER TABLE ibf_pfields_content ADD field_$new_id text default ''");
			
			$DB->query("OPTIMIZE TABLE ibf_pfields_content");
			
			$ADMIN->done_screen("Profile Field Added", "Custom Profile Field Control", "act=field" );
		}
	}
	
	
	//+---------------------------------------------------------------------------------
	//
	// Add / edit group
	//
	//+---------------------------------------------------------------------------------
	
	function main_form($type='edit')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("No group id to select from the database, please try again.");
			}
			
			$form_code = 'doedit';
			$button    = 'Complete Edit';
				
		}
		else
		{
			$form_code = 'doadd';
			$button    = 'Add Field';
		}
		
		if ($IN['id'] != "")
		{
			$DB->query("SELECT * FROM ibf_pfields_data WHERE fid='".$IN['id']."'");
			$fields = $DB->fetch_row();
		}
		else
		{
			$fields = array();
		}
		
		if ($type == 'edit')
		{
			$ADMIN->page_title = "Editing Profile Field ".$fields['ftitle'];
		}
		else
		{
			$ADMIN->page_title = 'Adding a new profile field';
			$fields['ftitle'] = '';
		}
		
		$ADMIN->page_detail = "Please double check the information before submitting the form.";
		
		
		
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $form_code  ),
												  2 => array( 'act'   , 'field'     ),
												  3 => array( 'id'    , $IN['id']   ),
									     )  );
									     
		$fields['fcontent'] = str_replace( '|', "\n", $fields['fcontent'] );
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Field Settings" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Field Title</b><br>Max characters: 200" ,
												  $SKIN->form_input("ftitle", $fields['ftitle'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Description</b><br>Max Characters: 250<br>Can be used to note hidden/required status" ,
												  $SKIN->form_input("fdesc", $fields['fdesc'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Field Type</b>" ,
												  $SKIN->form_dropdown("ftype",
												  					   array(
												  					   			0 => array( 'text' , 'Text Input' ),
												  					   			1 => array( 'drop' , 'Drop Down Box' ),
												  					   			2 => array( 'area' , 'Text Area' ),
												  					   		),
												  					   $fields['ftype'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Max Input (for text input and text areas) in characters</b>" ,
												  $SKIN->form_input("fmaxinput", $fields['fmaxinput'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Display order (when editing and displaying) numeric 1 lowest." ,
												  $SKIN->form_input("forder", $fields['forder'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Option Content (for drop downs)</b><br>In sets, one set per line<br>Example for 'Gender' field:<br>m=Male<br>f=Female<br>u=Not Telling<br>Will produce:<br><select name='pants'><option value='m'>Male</option><option value='f'>Female</option><option value='u'>Not Telling</option></select><br>m,f or u stored in database. When showing field in profile, will use value from pair (f=Female, shows 'Female')" ,
												  $SKIN->form_textarea("fcontent", $fields['fcontent'] )
									     )      );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Include on registration page?</b>" ,
												  $SKIN->form_yes_no("fshowreg", $fields["fshowreg"] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Field MUST be completed and not left empty?</b><br>(Will not apply if you choose to hide below)" ,
												  $SKIN->form_yes_no("freq", $fields['freq'] )
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Hide from the member's profile?</b><br>If yes, only admins and super mods can see it, user can still edit." ,
												  $SKIN->form_yes_no("fhide", $fields['fhide'] )
									     )      );						     							     
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Field can be edited by the member?</b><br>If no, user cannot edit information, field can only be seen by admins and super mods. Admins can edit information via ACP" ,
												  $SKIN->form_yes_no("fedit", $fields['fedit'] )
									     )      );
									     
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
		
		$ADMIN->page_title = "Custom Profile Fields";
		
		$ADMIN->page_detail = "Custom Profile fields can be used to add optional or required fields to be completed when registering or editing a profile. This is useful if you wish to record data from your members that is not already present in the base board.";
		
		$ADMIN->page_detail .= "<br /><br /><strong>Using Custom Profile Fields in Topic View</strong><br /><br />
								When you have enabled this feature (via System Settings -> CPU Saving) you can use the custom fields
								in your Topic View skin.<br />Simply add <strong>\$author[field_1]</strong> (or whatever the corresponding 'Topicview var.' is) in 'Post Entry'
								where you would like it to be shown";
		
		$SKIN->td_header[] = array( "Field Title"    , "20%" );
		$SKIN->td_header[] = array( "Type"           , "10%" );
		$SKIN->td_header[] = array( "TopicView var." , "20%" );
		$SKIN->td_header[] = array( "REQUIRED"       , "10%" );
		$SKIN->td_header[] = array( "HIDDEN"         , "10%" );
		$SKIN->td_header[] = array( "SHOW REG"       , "10%" );
		$SKIN->td_header[] = array( "Edit"           , "10%" );
		$SKIN->td_header[] = array( "Delete"         , "10%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Custom Profile Field Management" );
		
		$real_types = array( 'drop' => 'Drop Down Box',
							 'area' => 'Text Area',
							 'text' => 'Text Input',
						   );
		
		$DB->query("SELECT * FROM ibf_pfields_data");
		
		if ( $DB->get_num_rows() )
		{
			while ( $r = $DB->fetch_row() )
			{
			
				$hide   = '&nbsp;';
				$req    = '&nbsp;';
				$regi   = '&nbsp;';
				
				"<center><a href='{$ADMIN->base_url}&act=group&code=delete&id=".$r['g_id']."'>Delete</a></center>";
				
				//-----------------------------------
				if ($r['fhide'] == 1)
				{
					$hide = '<center><span style="color:red">Y</span></center>';
				}
				//-----------------------------------
				if ($r['freq'] == 1)
				{
					$req = '<center><span style="color:red">Y</span></center>';
				}
				
				if ($r['fshowreg'] == 1)
				{
					$regi = '<center><span style="color:red">Y</span></center>';
				}
				
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>{$r['ftitle']}</b>" ,
														  "<center>{$real_types[$r['ftype']]}</center>",
														  "<center>field_".$r['fid']."</center>",
														  $req,
														  $hide,
														  $regi,
														  "<center><a href='{$ADMIN->base_url}&act=field&code=edit&id=".$r['fid']."'>Edit</a></center>",
														  "<center><a href='{$ADMIN->base_url}&act=field&code=delete&id=".$r['fid']."'>Delete</a></center>",
											 )      );
											 
			}
		}
		else
		{
			$ADMIN->html .= $SKIN->add_td_basic("None found", "center", "pformstrip");
		}
		
		$ADMIN->html .= $SKIN->add_td_basic("<a href='{$ADMIN->base_url}&act=field&code=add' class='fauxbutton'>ADD NEW FIELD</a></center>", "center", "pformstrip");

		$ADMIN->html .= $SKIN->end_table();
		
		
		$ADMIN->output();
		
		
	}
	
		
}


?>