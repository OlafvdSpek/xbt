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
|   > Skin -> Image Macro functions
|   > Module written by Matt Mecham
|   > Date started: 4th April 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_settings();


class ad_settings {

	var $base_url;

	function ad_settings() {
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
			case 'wrapper':
				$this->list_current();
				break;
				
			case 'add':
				$this->add_macro_set();
				break;
				
			case 'edit':
				$this->show_macros();
				break;
				
			case 'doedit':
				$this->edit_set_name();
				break;
				
			case 'macroedit':
				$this->macro_form('edit');
				break;
				
			case 'macroadd':
				$this->macro_form('add');
				break;
				
			case 'macroremove':
				$this->macro_remove();
				break;
				
			case 'doeditmacro':
				$this->macro_edit();
				break;
				
			case 'doaddmacro':
				$this->macro_add();
				break;
				
			case 'remove':
				$this->remove();
				break;
				
			case 'export':
				$this->export();
				break;
			
			case 'import':
				$this->import();
				break;
			
			//-------------------------
			default:
				$this->list_current();
				break;
		}
		
	}
	
	function import()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_FILES;
		
		$FILE_NAME = $HTTP_POST_FILES['FILE_UPLOAD']['name'];
		$FILE_SIZE = $HTTP_POST_FILES['FILE_UPLOAD']['size'];
		$FILE_TYPE = $HTTP_POST_FILES['FILE_UPLOAD']['type'];
		
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		
		// Naughty Mozilla likes to use "none" to indicate an empty upload field.
		// I love universal languages that aren't universal.
		
		if ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "" or !$HTTP_POST_FILES['FILE_UPLOAD']['name'] or ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			// We're adding new templates based on another set
			
			$ADMIN->error("No file was chosen to upload, please go back and try again");
		}
		
		if (! is_dir($INFO['upload_dir']) )
		{
			$ADMIN->error("Could not locate the uploads directory - make sure the 'uploads' path is set correctly");
		}
		
		//-------------------------------------------------
		// Copy the upload to the uploads directory
		//-------------------------------------------------
		
		if (! @move_uploaded_file( $HTTP_POST_FILES['FILE_UPLOAD']['tmp_name'], $INFO['upload_dir']."/".$FILE_NAME) )
		{
			$ADMIN->error("The upload failed");
		}
		else
		{
			@chmod( $INFO['upload_dir']."/".$FILE_NAME, 0777 );
		}
		
		//-------------------------------------------------
		// Attempt to open the file..
		//-------------------------------------------------
		
		$filename = $INFO['upload_dir']."/".$FILE_NAME;
		
		if ( $FH = @fopen( $filename, 'r' ) )
		{
			$data = @fread( $FH, filesize($filename) );
			@fclose($FH);
			
			@unlink($filename);
		}
		else
		{
			$ADMIN->error("Could not open the uploaded file for reading!");
		}
		
		//-------------------------------------------------
		// If we're here, we'll assume that we've read the
		// file and the contents are in $data
		// So, lets make sure its the correct macro file..
		//-------------------------------------------------
		
		if ( ! preg_match( "/~=~/", $data ) )
		{
			$ADMIN->error("This file does not appear to be a valid Invision Power Board macro file");
		}
		
		//-------------------------------------
		// Get the new set_id
		//-------------------------------------
		
		$DB->query("SELECT MAX(set_id) as max FROM ibf_macro_name");
		
		$max = $DB->fetch_row();
		
		$new_id = $max['max'] + 1;
		
		//-------------------------------------
		// Process data
		//-------------------------------------
		
		$init_array = array();
		$final_keys = array();
		
		$init_array = explode("\n", $data);
		
		foreach( $init_array as $l )
		{
			if (preg_match( "~=~", $l ) )
			{
				// is valid line
				
				list($k, $v) = explode( "~=~", $l );
				
				$k = trim($k);
				$v = trim($v);
				
				$final_keys[$k] = $v;
			}
		}
		
		//-------------------------------------
		// Insert the data in the DB
		//-------------------------------------
		
		foreach( $final_keys as $k => $v)
		{
			if ($v == '*UNASSIGNED*')
			{
				$v = "";
			}
			
			$row['macro_replace'] = str_replace( '\n', "\n", $v);
			
			$str = $DB->compile_db_insert_string( array (
														'macro_value'   => $std->txt_stripslashes($k),
														'macro_replace' => $v,
														'macro_set'     => $new_id,
														'can_remove'    => 1,
											)       );
		
			$DB->query("INSERT INTO ibf_macro ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
		}
		
		//----------------------------------
		// Insert macro name into DB
		//----------------------------------
		
		$set_name = "New Macro Set (Upload ID: ".substr( time(), -6 ).")";
		
		$str = $DB->compile_db_insert_string( array ( 'set_name'     => $set_name,
													  'set_id'       => $new_id,
											)       );
											
		$DB->query("INSERT INTO ibf_macro_name ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
		
		$ADMIN->done_screen("Macro Set Import Complete", "Manage Macro Sets", "act=image" );
		
	}
	
	//-------------------------------------------------------------
	// Edit set name
	//-------------------------------------------------------------
	
	function edit_set_name()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing macro set ID, go back and try again");
		}
		
		$DB->query("SELECT * FROM ibf_macro WHERE macro_set='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not retrieve the record from the DB");
		}
		
		$DB->query("UPDATE ibf_macro_name SET set_name='{$IN['setname']}' WHERE set_id='".$IN['id']."'");
		
		$ADMIN->done_screen("Macro Set Updated", "Manage Macro Set {$row['macro_name']}", "act=image&code=edit&id={$row['macro_set']}" );
		
	}
	
	//-------------------------------------------------------------
	// Apply the edit to the DB
	//-------------------------------------------------------------
	
	function macro_remove()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['mid'] == "")
		{
			$ADMIN->error("You must specify an existing macro ID, go back and try again");
		}
		
		$DB->query("SELECT * FROM ibf_macro WHERE macro_id='".$IN['mid']."'");
		
		$row = $DB->fetch_row();
		
		$DB->query("DELETE FROM ibf_macro WHERE macro_id='".$IN['mid']."'");
		
		$ADMIN->done_screen("Macro Removed", "Manage Macro Set {$row['macro_name']}", "act=image&code=edit&id={$row['macro_set']}" );
		
	}
	
	//-------------------------------------------------------------
	// Apply the edit to the DB
	//-------------------------------------------------------------
	
	function macro_edit()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['mid'] == "")
		{
			$ADMIN->error("You must specify an existing image set ID, go back and try again");
		}
		
		$DB->query("SELECT * FROM ibf_macro WHERE macro_id='".$IN['mid']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not retrieve the record from the DB");
		}
		
		$key = str_replace( "'", "\\'", $std->txt_safeslashes($HTTP_POST_VARS['macro_value']) );
		$val = str_replace( "'", "\\'", $std->txt_UNhtmlspecialchars($std->txt_safeslashes($HTTP_POST_VARS['macro_replace']) ) );
		
		$DB->query("UPDATE ibf_macro SET macro_value='$key', macro_replace='$val' WHERE macro_id='".$IN['mid']."'");
		
		$ADMIN->done_screen("Macro Updated", "Manage Macro Set {$row['macro_name']}", "act=image&code=edit&id={$row['macro_set']}" );
		
	}
	
	//------------------------------------------------
	
	
	function macro_add()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing macro set ID, go back and try again");
		}
		
		$DB->query("SELECT * FROM ibf_macro WHERE macro_set='".$IN['id']."' LIMIT 0,1");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not retrieve the record from the DB");
		}
		
		$str = $DB->compile_db_insert_string( array (
														'macro_value'   => $std->txt_safeslashes($HTTP_POST_VARS['macro_value']),
														'macro_replace' => $std->txt_UNhtmlspecialchars($std->txt_safeslashes($HTTP_POST_VARS['macro_replace'])),
														'can_remove'    => 1,
														'macro_set'     => $row['macro_set']
											)       );
		
		$DB->query("INSERT INTO ibf_macro ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
		
		$ADMIN->done_screen("Macro Added", "Manage Macro Set {$row['macro_name']}", "act=image&code=edit&id={$row['macro_set']}" );
		
	}
	
	//-------------------------------------------------------------
	// Print the edit/add formy wormy
	//-------------------------------------------------------------
	
	function macro_form($type='edit')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		
		if ($type == 'edit')
		{
		
			if ($IN['mid'] == "")
			{
				$ADMIN->error("You must specify an existing macro ID, go back and try again");
			}
			
			//+-------------------------------
			
			$DB->query("SELECT m.*, mn.set_name from ibf_macro m, ibf_macro_name mn WHERE macro_id='".$IN['mid']."' AND mn.set_id=m.macro_set");
			
			if ( ! $row = $DB->fetch_row() )
			{
				$ADMIN->error("Could not query the information from the database");
			}
			
			$button = "Edit this Macro";
			$title  = "Editing Macro in set <a href='{$SKIN->base_url}&act=image&code=edit&id={$row['macro_set']}'>{$row['set_name']}</a>";
			$code   = 'doeditmacro';
		
		}
		else
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("You must specify an existing macro set ID, go back and try again");
			}
			
			//+-------------------------------
			
			$DB->query("SELECT set_name from ibf_macro_name WHERE set_id='".$IN['id']."'");
			
			if ( ! $row = $DB->fetch_row() )
			{
				$ADMIN->error("Could not query the information from the database");
			}
			
			$code = 'doaddmacro';
			$title = "Adding Macro to set <a href='{$SKIN->base_url}&act=image&code=edit&id={$IN['id']}'>{$row['set_name']}</a>";
			$button = 'Add this Macro';
		}
		
		//+-------------------------------
	
		$ADMIN->page_detail = "The macro 'key' will be useable in any wrapper or template file.<br>
							 <b>Example:</b> If you added a key of 'green_font' and a replacement of '&lt;font color='green'>', each instance of <span style='color:red'><b>&lt;{green_font}&gt;</b></span> would be converted to &lt;font color='green'>
							 <br><b>&lt;#IMG_DIR#></b> is available to any macro, this is automatically replaced with the name of the image directory you choose when using this macro set in a skin";
		$ADMIN->page_title  = "Manage Macro Set: {$row['set_name']}";
		
		//+-------------------------------
		
		
		$ADMIN->html .= $SKIN->js_no_specialchars();
		
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $code    ),
												  2 => array( 'act'   , 'image'     ),
												  3 => array( 'id'    , $IN['id']   ),
												  4 => array( 'mid'   , $IN['mid']   ),
									     )       );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"   , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"   , "80%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( $title );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Macro Key",
													"&lt;{".$SKIN->form_input('macro_value', $row['macro_value'])."}&gt;",
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Macro Replacement",
													$SKIN->form_textarea('macro_replace', $std->txt_htmlspecialchars($row['macro_replace']) ),
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form($button);
									     
		$ADMIN->html .= $SKIN->end_table();
									     
		$ADMIN->output();
	}
	
	
	
	//+-------------------------------
	
	
	function export()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing image set ID, go back and try again");
		}
		
		$DB->query("SELECT set_name FROM ibf_macro_name WHERE set_id='".$IN['id']."'");
		
		$name = $DB->fetch_row();
		
		//+-------------------------------
		// Pass file as an attachment, yeah!
		//+-------------------------------
		
		$l_name = preg_replace( "/\s{1,}/", "_", $name['set_name'] );
		
		$file_name = "macro-".substr($l_name, 0, 8).".txt";
		
		$contents = "";
		
		$DB->query("SELECT macro_replace, macro_value FROM ibf_macro WHERE macro_set='".$IN['id']."'");
		
		while( $row = $DB->fetch_row() )
		{
			if ($row['macro_replace'] == "")
			{
				$row['macro_replace'] = "*UNASSIGNED*";
			}
			
			// Fix newlines..
			
			$row['macro_replace'] = str_replace( "\n", '\n', str_replace("\r", "\n", $row['macro_replace'] ) );
			
			$contents .= $row['macro_value']."~=~".$row['macro_replace']."\n";
		}
		
		@header("Content-type: unknown/unknown");
		@header("Content-Disposition: attachment; filename=$file_name");
		
		print $contents."\n";
		
		exit();
		
	}
	
	//-------------------------------------------------------------
	// Add images..
	//-------------------------------------------------------------
	
	
	function add_macro_set()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing macro set ID, go back and try again");
		}
		
		
		$DB->query("SELECT * FROM ibf_macro_name WHERE set_id='".$IN['id']."'");
		
		//-------------------------------------
		
		if ( ! $mac = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query that macro set from the DB, so there");
		}
		
		//-------------------------------------
		
		$mac['set_name'] = $mac['set_name']." (Copy)";
		
		//-------------------------------------
		// Get the new set_id
		//-------------------------------------
		
		$DB->query("SELECT MAX(set_id) as max FROM ibf_macro_name");
		
		$max = $DB->fetch_row();
		
		$new_id = $max['max'] + 1;
		
		$q1 = $DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set='{$IN['id']}'");
		
		while( $row = $DB->fetch_row($q1) )
		{
			$str = $DB->compile_db_insert_string( array (
														'macro_value'   => $std->txt_stripslashes($row['macro_value']),
														'macro_replace' => $std->txt_stripslashes($row['macro_replace']),
														'macro_set'     => $new_id,
														'can_remove'    => 1,
											)       );
		
			$q2 = $DB->query("INSERT INTO ibf_macro ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
			//$q2 = $DB->query("INSERT INTO ibf_macro SET macro_value='{$row['macro_value']}',macro_replace='{$row['macro_replace']}', macro_set='$new_id'");
		}
		
		$DB->query("INSERT INTO ibf_macro_name SET set_id='$new_id', set_name='{$mac['set_name']}'");
		
		$ADMIN->done_screen("Macro Set Added", "Manage Macro Sets", "act=image" );
		
	}
	
	//-------------------------------------------------------------
	// REMOVE WRAPPERS
	//-------------------------------------------------------------
	
	function remove()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//+-------------------------------
		
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing macro set ID, go back and try again");
		}
		
		$DB->query("DELETE FROM ibf_macro WHERE macro_set='".$IN['id']."'");
		
		$DB->query("DELETE FROM ibf_macro_name WHERE set_id='".$IN['id']."'");
				
		$std->boink_it($SKIN->base_url."&act=image");
	}
	
	
	
	
	//-------------------------------------------------------------
	// ADD / EDIT MACRO SETS
	//-------------------------------------------------------------
	
	function show_macros()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing macro set ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT m.*, ms.* from ibf_macro m, ibf_macro_name ms WHERE m.macro_set='".$IN['id']."' AND ms.set_id=m.macro_set LIMIT 0,1");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
	
		$ADMIN->page_detail = "To edit a macro, simply click on the 'edit' link of the appropriate macro.
							 <br>The macro 'key' will be useable in any wrapper or template file.<br>
							 <b>Example:</b> If you added a key of 'green_font' and a replacement of '&lt;font color='green'>', each instance of <span style='color:red'><b>&lt;{green_font}&gt;</b></span> would be converted to &lt;font color='green'>
							 <br><b>&lt;#IMG_DIR#></b> is available to any macro, this is automatically replaced with the name of the image directory you choose when using this macro set in a skin";
		$ADMIN->page_title  = "Manage Macro Set: {$row['set_name']}";
		
		//+-------------------------------
		
		
		$ADMIN->html .= $SKIN->js_no_specialchars();
		$ADMIN->html .= $SKIN->js_checkdelete();
		
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'doedit'    ),
												  2 => array( 'act'   , 'image'     ),
												  3 => array( 'id'    , $IN['id']   ),
									     ), "theAdminForm", "onSubmit=\"return no_specialchars('images')\""       );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"   , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"   , "80%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Edit Macro Set Title" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Macro Set Title",
													$SKIN->form_input('setname', $row['set_name']),
									     )      );
									     
		$ADMIN->html .= $SKIN->end_form("Edit Macro Title");
									     
		$ADMIN->html .= $SKIN->end_table();
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Macro Key"           , "20%" );
		$SKIN->td_header[] = array( "Macro Replacement"   , "45%" );
		$SKIN->td_header[] = array( "Preview"             , "15%" );
		$SKIN->td_header[] = array( "Edit"                , "10%" );
		$SKIN->td_header[] = array( "Remove"              , "10%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Current Macros ["."<a href='".$SKIN->base_url."&act=image&code=macroadd&id={$row['macro_set']}'>Add a macro</a>"." ]" );
		
		// Get img_dir this set is using...
		
		$DB->query("SELECT img_dir from ibf_skins WHERE macro_id='".$IN['id']."'");
		
		$skin = $DB->fetch_row();
		
		$DB->query("SELECT * from ibf_macro WHERE macro_set='".$IN['id']."'");
		
		while( $row = $DB->fetch_row() )
		{
			
			$real = str_replace( "<", "&lt;", $row['macro_replace'] );
			$real = str_replace( ">", "&gt;", $real );
			
			if ( strlen($real) > 55 )
			{
				$real = substr( $real, 0, 52 ) . '...';
			}
			
			if ( $INFO['preview'] == 0 )
			{
				$preview = '<em>Disabled via ACP Prefs</em>';
			}
			else
			{
				$preview = str_replace( "<#IMG_DIR#>", $skin['img_dir'], $row['macro_replace'] );
			}
			
			$remove = "Cannot remove";
			
			if ($row['can_remove'] == 1)
			{
				$remove = "<center><a href='javascript:checkdelete(\"act=image&code=macroremove&mid={$row['macro_id']}\")'>Remove</a></center>";
				
			}
			
			
			$ADMIN->html .= $SKIN->add_td_row( array( 
												"<b>{$row['macro_value']}</b>",
												$real,
												$preview,
												"<center><a href='".$SKIN->base_url."&act=image&code=macroedit&mid={$row['macro_id']}'>Edit</a></center>",
												$remove,
									 )      );
		}
									     
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	//-------------------------------------------------------------
	// SHOW WRAPPERS
	//-------------------------------------------------------------
	
	function list_current()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
	
		$ADMIN->page_detail = "You may manage your macro sets from this section. This enables you to use text links instead of buttons or flash movies for certain images.";
		$ADMIN->page_title  = "Manage Macro Sets";
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Title"        , "40%" );
		$SKIN->td_header[] = array( "Allocation"   , "30%" );
		$SKIN->td_header[] = array( "Export"       , "10%" );
		$SKIN->td_header[] = array( "Edit"         , "10%" );
		$SKIN->td_header[] = array( "Remove"       , "10%" );
		
		//+-------------------------------
		
		$DB->query("SELECT DISTINCT(s.macro_id), i.macro_set, ms.set_name, s.sname from ibf_macro i, ibf_macro_name ms, ibf_skins s WHERE s.macro_id=i.macro_set  AND ms.set_id=i.macro_set ORDER BY ms.set_name ASC");
		
		$used_ids = array();
		$show_array = array();
		
		if ( $DB->get_num_rows() )
		{
		
			$ADMIN->html .= $SKIN->start_table( "Current Macro sets In Use" );
			
			while ( $r = $DB->fetch_row() )
			{
			
				$show_array[ $r['macro_set'] ] .= stripslashes($r['sname'])."<br>";
			
				if ( in_array( $r['macro_set'], $used_ids ) )
				{
					continue;
				}
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".$std->txt_stripslashes($r['set_name'])."</b>",
														  "<#X-{$r['macro_set']}#>",
														  "<center><a href='".$SKIN->base_url."&act=image&code=export&id={$r['macro_set']}'>Export</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=image&code=edit&id={$r['macro_set']}'>Edit</a></center>",
														  "<i>Deallocate before removing</i>",
												 )      );
												   
				$used_ids[] = $r['macro_set'];
				
				$form_array[] = array( $r['macro_set'], $r['set_name'] );
				
			}
			
			foreach( $show_array as $idx => $string )
			{
				$string = preg_replace( "/<br>$/", "", $string );
				
				$ADMIN->html = preg_replace( "/<#X-$idx#>/", "$string", $ADMIN->html );
			}
			
			$ADMIN->html .= $SKIN->end_table();
		}
		
		if ( count($used_ids) > 0 )
		{
		
			$DB->query("SELECT set_id, set_name FROM ibf_macro_name WHERE set_id NOT IN(".implode(",",$used_ids).")");
		
			if ( $DB->get_num_rows() )
			{
			
				$SKIN->td_header[] = array( "Title"  , "70%" );
				$SKIN->td_header[] = array( "Export" , "10%" );
				$SKIN->td_header[] = array( "Edit"   , "10%" );
				$SKIN->td_header[] = array( "Remove" , "10%" );
			
				$ADMIN->html .= $SKIN->start_table( "Current Unallocated Macro sets" );
				
				$ADMIN->html .= $SKIN->js_checkdelete();
				
				while ( $r = $DB->fetch_row() )
				{
					
					$ADMIN->html .= $SKIN->add_td_row( array( "<b>".$std->txt_stripslashes($r['set_name'])."</b>",
															  "<center><a href='".$SKIN->base_url."&act=image&code=export&id={$r['set_id']}'>Export</a></center>",
															  "<center><a href='".$SKIN->base_url."&act=image&code=edit&id={$r['set_id']}'>Edit</a></center>",
															  "<center><a href='javascript:checkdelete(\"act=image&code=remove&id={$r['set_id']}\")'>Remove</a></center>",
													 )      ); 
													 
					$form_array[] = array( $r['set_id'], $r['set_name'] );
													   
				}
				
				$ADMIN->html .= $SKIN->end_table();
			}
		}
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'add'     ),
												  2 => array( 'act'   , 'image'    ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Create a New Macro Set" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Base the new Macro set on...</b>" ,
										  		  $SKIN->form_dropdown( "id", $form_array)
								 )      );
		
		$ADMIN->html .= $SKIN->end_form("Create a new Macro set");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'import'     ),
												  2 => array( 'act'   , 'image'    ),
												  3 => array( 'MAX_FILE_SIZE', '10000000000' ),
									     ) , "uploadform", " enctype='multipart/form-data'"     );
												  
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Import a Macro Set" );
			
		//+-------------------------------
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b><u>OR</u> Choose a file from your computer to import</b><br>Note: This must be a valid macro set.",
												  $SKIN->form_upload(),
										 )      );
		
		$ADMIN->html .= $SKIN->end_form("Import Macro Set");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	
}


?>