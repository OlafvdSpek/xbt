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
|   > Help Control functions
|   > Module written by Matt Mecham
|   > Date started: 2nd April 2002
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
				$this->list_wrappers();
				break;
				
			case 'add':
				$this->add_splash();
				break;
				
			case 'edit':
				$this->do_form('edit');
				break;
				
			case 'doadd':
				$this->save_wrapper('add');
				break;
				
			case 'doedit':
				$this->save_wrapper('edit');
				break;
				
			case 'remove':
				$this->remove();
				break;
				
			case 'export':
				$this->export();
			
			//-------------------------
			default:
				$this->list_wrappers();
				break;
		}
		
	}
	
	
	//---------------------------------------------------------------------
	
	function add_splash()
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
			
			$this->do_form('add');
			exit();
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
		// So, lets make sure its the correct template file..
		//-------------------------------------------------
		
		if ( ! preg_match( "/<% COPYRIGHT %>/", $data ) )
		{
			$ADMIN->error("This file does not appear to be a valid Invision Power Board Wrapper file");
		}
		
		//----------------------------------
		// Insert wrapper into DB
		//----------------------------------
		
		$wrap_name .= "New Wrapper (Upload ID: ".substr( time(), -6 ).")";
		
		$str = $DB->compile_db_insert_string( array ( 'name'     => $wrap_name,
													  'template' => $data,
											)       );
											
		$DB->query("INSERT INTO ibf_templates ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
		
		$ADMIN->done_screen("Board Wrapper import complete", "Manage Board Wrappers", "act=wrap" );
		
	}
	
	//---------------------------------------------------------------------
	
	function export()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_templates WHERE tmid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		// Pass file as an attachment, yeah!
		//+-------------------------------
		
		$l_name = preg_replace( "/\s{1,}/", "_", $row['name'] );
		
		$file_name = "wrap-".substr($l_name, 0, 8).".html";
		
		$row['template'] = preg_replace("/\r\n/", "\n", $row['template'] );
		
		@header("Content-type: unknown/unknown");
		@header("Content-Disposition: attachment; filename=$file_name");
		
		print $row['template']."\n";
		
		exit();
		
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
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		$DB->query("DELETE FROM ibf_templates WHERE tmid='".$IN['id']."'");
		
		$std->boink_it($SKIN->base_url."&act=wrap");
			
		exit();
		
		
	}
	
	
	
	//-------------------------------------------------------------
	// ADD / EDIT WRAPPERS
	//-------------------------------------------------------------
	
	function save_wrapper( $type='add' )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//+-------------------------------
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
			}
		}
		
		if ($IN['name'] == "")
		{
			$ADMIN->error("You must specify a name for this wrapper");
		}
		
		if ($IN['template'] == "")
		{
			$ADMIN->error("You can't have an empty template, can you?");
		}
		
		$tmpl = preg_replace( "!&lt;/textarea>!", "/textarea>", stripslashes($HTTP_POST_VARS['template']) );
		
		$tmpl = str_replace( "&amp;amp;" , "&amp;"   , $tmpl );
		$tmpl = str_replace( "&amp;nbsp;", "&nbsp;"  , $tmpl );
		$tmpl = str_replace( "&amp;copy;", "&copy;"  , $tmpl );
		$tmpl = preg_replace( "/\\\/"    , "&#092;"  , $tmpl );
		$tmpl = preg_replace( "/&#092;/" , '\\\\\\\\'    , $tmpl ); // o_O
		
		
		if ( ! preg_match( "/<% BOARD %>/", $tmpl ) )
		{
			$ADMIN->error("You cannot remove the &lt% BOARD %> tag silly!");
		}
		
		if ( ! preg_match( "/<% COPYRIGHT %>/", $tmpl ) )
		{
			$ADMIN->error("You cannot remove the &lt% COPYRIGHT %> tag silly!");
		}
		
		$barney = array( 'name'     => stripslashes($HTTP_POST_VARS['name']),
						 'template' => $tmpl
					   );
					   
		if ($type == 'add')
		{
			$db_string = $DB->compile_db_insert_string( $barney );
			
			$DB->query("INSERT INTO ibf_templates (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
			
			$std->boink_it($SKIN->base_url."&act=wrap");
			
			exit();
			
		}
		else
		{
			$db_string = $DB->compile_db_update_string( $barney );
			
			$DB->query("UPDATE ibf_templates SET $db_string WHERE tmid='".$IN['id']."'");
			
			$ADMIN->done_screen("Wrapper updated", "Manage Board Wrappers", "act=wrap" );
		}
		
		
	}
	
	//-------------------------------------------------------------
	// ADD / EDIT WRAPPERS
	//-------------------------------------------------------------
	
	function do_form( $type='add' )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		$DB->query("SELECT * from ibf_templates WHERE tmid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		if ($type == 'add')
		{
			$code = 'doadd';
			$button = 'Create Wrapper';
			$row['name'] = $row['name'].".2";
		}
		else
		{
			$code = 'doedit';
			$button = 'Edit Wrapper';
		}
		
		//+-------------------------------
	
		$ADMIN->page_detail = "You may use HTML fully when adding or editing wrappers.";
		$ADMIN->page_title  = "Manage Board Wrappers";
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->js_no_specialchars();
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $code      ),
												  2 => array( 'act'   , 'wrap'      ),
												  3 => array( 'id'    , $IN['id']   ),
									     ), "theAdminForm", "onSubmit=\"return no_specialchars('wrapper')\""      );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "80%" );

		//+-------------------------------
		
		$row['template'] = preg_replace( "/\/textarea>/", "&lt;/textarea>", $row['template'] );  // Stop html killing the text area
		
		// Sort out amps and stuff
		
		$row['template'] = str_replace( "&amp;", "&amp;amp;", $row['template'] );
		$row['template'] = str_replace( "&nbsp;", "&amp;nbsp;", $row['template'] );
		$row['template'] = str_replace( "&copy;", "&amp;copy;", $row['template'] );
		$row['template'] = preg_replace( "/\\\/" , "&#092;" , $row['template'] );
		
		$ADMIN->html .= $SKIN->start_table( $button );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Wrapper Title",
													$SKIN->form_input('name', $row['name']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Content",
													$SKIN->form_textarea('template', $row['template'], $INFO['tx'], $INFO['ty']),
									     )      );
												 
		$ADMIN->html .= $SKIN->end_form($button);
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	//-------------------------------------------------------------
	// SHOW WRAPPERS
	//-------------------------------------------------------------
	
	function list_wrappers()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
	
		$ADMIN->page_detail = "You may add/edit and remove the board wrapper templates.<br><br>Board wrapper refers to the main board template which allows additional HTML to be added to the header and footer of the board";
		$ADMIN->page_title  = "Manage Board Wrappers";
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Title"        , "40%" );
		$SKIN->td_header[] = array( "Allocation"   , "30%" );
		$SKIN->td_header[] = array( "Export"       , "10%" );
		$SKIN->td_header[] = array( "Edit"         , "10%" );
		$SKIN->td_header[] = array( "Remove"       , "10%" );
		
		//+-------------------------------
		
		$DB->query("SELECT DISTINCT(w.tmid), w.name, s.sname from ibf_templates w, ibf_skins s WHERE s.tmpl_id=w.tmid ORDER BY w.name ASC");
		
		$used_ids = array();
		$show_array = array();
		
		if ( $DB->get_num_rows() )
		{
		
			$ADMIN->html .= $SKIN->start_table( "Current Wrappers In Use" );
			
			while ( $r = $DB->fetch_row() )
			{
			
				$show_array[ $r['tmid'] ] .= stripslashes($r['sname'])."<br>";
			
				if ( in_array( $r['tmid'], $used_ids ) )
				{
					continue;
				}
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".stripslashes($r['name'])."</b>",
														  "<#X-{$r['tmid']}#>",
														  "<center><a href='".$SKIN->base_url."&act=wrap&code=export&id={$r['tmid']}'>Export</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=wrap&code=edit&id={$r['tmid']}'>Edit</a></center>",
														  "<i>Deallocate before removing</i>",
												 )      );
												   
				$used_ids[] = $r['tmid'];
				
				$form_array[] = array( $r['tmid'], $r['name'] );
				
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
		
			$DB->query("SELECT tmid, name FROM ibf_templates WHERE tmid NOT IN(".implode(",",$used_ids).")");
		
			if ( $DB->get_num_rows() )
			{
			
				$SKIN->td_header[] = array( "Title"  , "70%" );
				$SKIN->td_header[] = array( "Export" , "10%" );
				$SKIN->td_header[] = array( "Edit"   , "10%" );
				$SKIN->td_header[] = array( "Remove" , "10%" );
			
				$ADMIN->html .= $SKIN->start_table( "Current Unallocated Wrappers" );
				
				$ADMIN->html .= $SKIN->js_checkdelete();
				
				
				while ( $r = $DB->fetch_row() )
				{
					
					$ADMIN->html .= $SKIN->add_td_row( array( "<b>".stripslashes($r['name'])."</b>",
															  "<center><a href='".$SKIN->base_url."&act=wrap&code=export&id={$r['tmid']}'>Export</a></center>",
															  "<center><a href='".$SKIN->base_url."&act=wrap&code=edit&id={$r['tmid']}'>Edit</a></center>",
															  "<center><a href='javascript:checkdelete(\"act=wrap&code=remove&id={$r['tmid']}\")'>Remove</a></center>",
													 )      );
													 
					$form_array[] = array( $r['tmid'], $r['name'] );
													   
				}
				
				$ADMIN->html .= $SKIN->end_table();
			}
		}
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'add'     ),
												  2 => array( 'act'   , 'wrap'    ),
												  3 => array( 'MAX_FILE_SIZE', '10000000000' ),
									     ) , "uploadform", " enctype='multipart/form-data'"     );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Create New Wrapper" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Base new wrapper on...</b>" ,
										  		  $SKIN->form_dropdown( "id", $form_array)
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b><u>OR</u> Choose a wrapper file from your computer to import</b><br>Note: This must be in the correct format.",
												  $SKIN->form_upload(),
										 )      );
		
		$ADMIN->html .= $SKIN->end_form("Create new wrapper");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	
}


?>