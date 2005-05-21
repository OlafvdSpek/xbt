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
|   > CSS management functions
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
				$this->list_sheets();
				break;
				
			case 'add':
				$this->do_form('add');
				break;
				
			case 'edit2':
				$this->do_form('edit');
				break;
				
			case 'edit':
				//$this->edit_splash();
				$this->do_form('edit');
				break;
				//break;
				
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
				break;
				
			case 'optimize':
				$this->optimize();
				break;
				
			case 'css_upload':
				$this->css_upload('new');
				break;
				
			case 'easyedit':
				$this->easy_edit();
				break;
				
			case 'doresync':
				$this->do_resynch();
				break;
			
			case 'colouredit':
				$this->colouredit();
				break;
				
			case 'docolour':
				$this->do_colouredit();
				break;
			
			//-------------------------
			default:
				$this->list_sheets();
				break;
		}
		
	}
	
	//+-------------------------------
	
	function do_resynch()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT cssid, css_text, css_name, css_comments FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $cssinfo = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the CSS details from the database");
		}
		
		if ( $IN['favour'] == 'cache' )
		{
			$cache_file = ROOT_PATH."cache/css_".$IN['id'].".css";
			
			if ( file_exists( $cache_file ) )
			{
				$FH = fopen( $cache_file, 'r' );
				$cache_data = fread( $FH, filesize($cache_file) );
				fclose($FH);
			}
			else
			{
				$ADMIN->error("Could not locate cached CSS file @ $cache_file");
			}
			
			$dbr = $DB->compile_db_update_string( array( 'css_text' => $cache_data ) );
			
			$DB->query("UPDATE ibf_css SET $dbr WHERE cssid='".$IN['id']."'");
		}
		else
		{
			$cache_file = ROOT_PATH."cache/css_".$IN['id'].".css";
			
			$FH = fopen( $cache_file, 'w' );
			fputs( $FH, $cssinfo['css_text'], strlen($cssinfo['css_text']) );
			fclose($FH);
		}
		
		if ( $IN['return'] != 'colouredit' )
		{
			$this->do_form('edit');
		}
		else
		{
			$this->colouredit();
		}
	}
	
	
	
	//+-------------------------------
	
	function resync_splash($db_length, $cache_length, $cache_mtime, $db_mtime, $id, $return="")
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
	
		$ADMIN->page_detail = "A mismatch has been found between the cached style sheet and the style sheet stored in the database";
		$ADMIN->page_title  = "Resynchronise Style Sheet";
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "50%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "50%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'doresync'  ),
												  2 => array( 'act'   , 'style'     ),
												  3 => array( 'id'    , $id         ),
												  4 => array( 'return', $return     ),
									     )    );
									     
		$favour = 'db';
		
		$ADMIN->html .= $SKIN->start_table( "Resynch CSS before editing..." );
		
		if ( intval($cache_mtime) > intval($db_mtime) )
		{
			$ADMIN->html .= $SKIN->add_td_row( array( 
														"<b>CSS in database last updated:</b> ".$ADMIN->get_date($db_mtime, 'LONG'),
														"<b>CSS in database, # characters:</b> $db_length",
											 )      );
											 
			$ADMIN->html .= $SKIN->add_td_row( array( 
														"<span style='color:red'><b>CSS in CACHE last updated:</b> ".$ADMIN->get_date($cache_mtime, 'LONG')."</span>",
														"<span style='color:red'><b>CSS in CACHE, # characters:</b> $cache_length</span>",
											 )      );
			$favour = 'cache';
											 
		}
		else
		{
			$ADMIN->html .= $SKIN->add_td_row( array( 
														"<span style='color:red'><b>CSS in database last updated:</b> ".$ADMIN->get_date($db_mtime, 'LONG')."</span>",
														"<span style='color:red'><b>CSS in database, # characters:</b> $db_length</span>",
											 )      );
											 
			$ADMIN->html .= $SKIN->add_td_row( array( 
														"<b>CSS in CACHE last updated:</b> ".$ADMIN->get_date($cache_mtime, 'LONG'),
														"<b>CSS in CACHE, # characters:</b> $cache_length",
											 )      );
		}
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
														"<b>Resynchronise using....</b>",
														$SKIN->form_dropdown( 'favour', array(
																							    0 => array( 'cache', 'Overwrite database version with cached version'),
																							    1 => array( 'db'   , 'Update cached version from the database' ),
																							 ), $favour ),
											 )      );
		
		$ADMIN->html .= $SKIN->end_form("Resynchronise");
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
		
	}
	
	//-------------------------------------------------------------
	// ADD / EDIT WRAPPERS
	//-------------------------------------------------------------
	
	
	
	
	function css_upload($type='new')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_FILES;
		
		$FILE_NAME = $HTTP_POST_FILES['FILE_UPLOAD']['name'];
		$FILE_SIZE = $HTTP_POST_FILES['FILE_UPLOAD']['size'];
		$FILE_TYPE = $HTTP_POST_FILES['FILE_UPLOAD']['type'];
		
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		
		if (! is_dir($INFO['upload_dir']) )
		{
			$ADMIN->error("Could not locate the uploads directory - make sure the 'uploads' path is set correctly");
		}
							
		// Naughty Mozilla likes to use "none" to indicate an empty upload field.
		// I love universal languages that aren't universal.
		
		if ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "" or !$HTTP_POST_FILES['FILE_UPLOAD']['name'] or ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			$ADMIN->error("No file was chosen to upload!");
		}
		
		//-------------------------------------------------
		// Move the uploaded file to somewhere we can
		// manipulate it in safe mode
		//-------------------------------------------------
		
		if (! @move_uploaded_file( $HTTP_POST_FILES['FILE_UPLOAD']['tmp_name'], $INFO['upload_dir']."/".$FILE_NAME) )
		{
			$ADMIN->error("The upload failed");
		}
		
		// Open the file and copy to the DB
		
		$real_name = str_replace( "_", " ", preg_replace( "/^(.*),\d+\.css$/", "\\1", $FILE_NAME ) );
		$real_name .= ' [UPLOAD]';
		
		if ( $FH = @fopen( $INFO['upload_dir']."/".$FILE_NAME, "r" ) )
		{
			$data = @fread( $FH, @filesize($INFO['upload_dir']."/".$FILE_NAME) );
			@fclose($FH);
			@unlink($INFO['upload_dir']."/".$FILE_NAME);
		}
		else
		{
			@unlink($INFO['upload_dir']."/".$FILE_NAME);
			$ADMIN->error("Could not open the uploaded file for reading, aborting process");
		}
		
		list($css, $comments) = explode( "<|COMMENTS|>", $data );
		
		$css      = trim($css);
		$comments = trim($css);
		
		if ($type == 'new')
		{
			$dbs = $DB->compile_db_insert_string( array (
														  'css_name'     => $real_name,
														  'css_text'     => $css,
														  'css_comments' => $comments,
														  'updated'      => time(),
												)       );
											
												
			$DB->query("INSERT INTO ibf_css (".$dbs['FIELD_NAMES'].") VALUES(".$dbs['FIELD_VALUES'].")");
			
			$new_id = $DB->get_insert_id();
			
			if ( file_exists( ROOT_PATH."cache" ) )
			{
				if ( is_writeable( ROOT_PATH."cache" ) )
				{
					$FH = fopen( ROOT_PATH."cache/css_".$new_id.".css", 'w' );
					fputs( $FH, $css, strlen($css) );
					fclose($FH);
				}
			}
			
			$ADMIN->done_screen("Stylesheet uploaded", "Manage Style Sheets", "act=style" );
		}
		
		
	}
	
	
	//----------------------------------------------------
	
	function optimize()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing CSS ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$orig_size = strlen($row['css_text']);
		
		$orig_text = str_replace( "\r\n", "\n", $row['css_text']);
		$orig_text = str_replace( "\r"  , "\n", $orig_text);
		$orig_text = str_replace( "\n\n", "\n", $orig_text);
		
		$parsed = array();
		
		// Remove comments
		
		$orig_text = preg_replace( "#/\*(.+?)\*/#s", "", $orig_text );
		
		// Grab all the definitions
		
		preg_match_all( "/(.+?)\{(.+?)\}/s", $orig_text, $match, PREG_PATTERN_ORDER );
		
		for ( $i = 0 ; $i < count($match[0]); $i++ )
		{
			$match[1][$i] = trim($match[1][$i]);
			$parsed[ $match[1][$i] ] = trim($match[2][$i]);
		}
		
		//------------------
		
		if ( count($parsed) < 1)
		{
			$ADMIN->error("The stylesheet is in a format that Invision Power Board cannot understand, no optimization done.");
		}
		
		// Clean them up
		
		$final = "";
		
		foreach( $parsed as $name => $p )
		{
			// Ignore comments
			
			if ( preg_match( "#^//#", $name) )
			{
				continue;
			}
			
			// Split up the components
			
			$parts = explode( ";", $p);
			$defs  = array();
			
			foreach( $parts as $part )
			{
				if ($part != "")
				{
					list($definition, $data) = explode( ":", $part );
					$defs[]   = trim($definition).": ".trim($data);
				}
			}
			
			$final .= $name . " { ".implode("; ", $defs). " }\n";
		}
		
		$final_size = strlen($final);
		
		if ($final_size < 1000)
		{
			$ADMIN->error("The stylesheet is in a format that Invision Power Board cannot understand, no optimization done.");
		}
		
		// Update the DB
		
		$dbs = $DB->compile_db_update_string( array( 'css_text' => $final ) );
		
		$DB->query("UPDATE ibf_css SET $dbs WHERE cssid='".$IN['id']."'");
		
		$saved    = $orig_size - $final_size;
		$pc_saved = 0;
		
		if ($saved > 0)
		{
			$pc_saved = sprintf( "%.2f", ($saved / $orig_size) * 100);
		}
		
		$ADMIN->done_screen("Stylesheet updated: Characters Saved: $saved ($pc_saved %)", "Manage Style Sheets", "act=style" );
				    
		
		
	}
	
	
	//----------------------------------------------------
	
	function export()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing CSS ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$name = str_replace( " ", "_", $row['css_name'] );
		
		@header("Content-type: unknown/unknown");
		@header("Content-Disposition: attachment; filename=$name,{$row['cssid']}.css");
		
		print $row['css_text'];
		
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
			$ADMIN->error("You must specify an existing stylesheet ID, go back and try again");
		}
		
		$DB->query("DELETE FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( file_exists( ROOT_PATH."cache/css_".$IN['id'].".css" ) )
		{
			@unlink( ROOT_PATH."cache/css_".$IN['id'].".css" );
		}
		
		$std->boink_it($SKIN->base_url."&act=style");
			
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
				$ADMIN->error("You must specify an existing CSS ID, go back and try again");
			}
			
		}
		
		if ($IN['name'] == "")
		{
			$ADMIN->error("You must specify a name for this stylesheet");
		}
		
		if ($IN['css'] == "")
		{
			$ADMIN->error("You can't have an empty stylesheet, can you?");
		}
		
		$css = stripslashes($HTTP_POST_VARS['css']);
		
		$barney = array( 'css_name'     => stripslashes($HTTP_POST_VARS['name']),
						 'css_text'     => $css,
						 'updated'      => time(),
					   );
					   
		if ($type == 'add')
		{
			$db_string = $DB->compile_db_insert_string( $barney );
			
			$DB->query("INSERT INTO ibf_css (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
			
			$new_id = $DB->get_insert_id();
			
			//--------------------------------------------
			// Update cache?
			//--------------------------------------------
			
			if ( file_exists( ROOT_PATH."cache" ) )
			{
				if ( is_writeable( ROOT_PATH."cache" ) )
				{
					$FH = fopen( ROOT_PATH."cache/css_".$new_id.".css", 'w' );
					fputs( $FH, $css, strlen($css) );
					fclose($FH);
				}
			}
			
			
			$std->boink_it($SKIN->base_url."&act=style");
			
			exit();
			
		}
		else
		{
			$db_string = $DB->compile_db_update_string( $barney );
			
			$DB->query("UPDATE ibf_css SET $db_string WHERE cssid='".$IN['id']."'");
			
			//--------------------------------------------
			// Update cache?
			//--------------------------------------------
			
			$extra = "<b>Cache file updated</b>";
			
			if ( file_exists( ROOT_PATH."cache" ) )
			{
				if ( is_writeable( ROOT_PATH."cache" ) )
				{
					if ( $FH = @fopen( ROOT_PATH."cache/css_".$IN['id'].".css", 'w' ) )
					{
						@fputs( $FH, $css, strlen($css) );
						@fclose($FH);
					}
					else
					{
						$extra = "<b>Cache file not updated. Check CHMOD permissions on ./cache and ./cache/css_".$IN['id'].".css</b>";
					}
				}
				else
				{
					$extra = "<b>Cache file not updated. Check CHMOD permissions on ./cache and ./cache/css_".$IN['id'].".css</b>";
				}
			}
			else
			{
				$extra = "<b>Cache file not updated. Cache folder not present</b>";
			}
			
			$ADMIN->nav[] = array( 'act=style' ,'Style Sheet Control Home' );
			$ADMIN->nav[] = array( "act=style&code=edit2&id={$IN['id']}" ,"Edit Sheet Again" );
			
			$ADMIN->done_screen("Stylesheet updated : $extra", "Manage Style Sheets", "act=style" );
			
			
		}
		
		
	}
	
	
	function do_form( $type='add' )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT cssid, css_text, css_name, updated FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $cssinfo = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the CSS details from the database");
		}
		
		//+-------------------------------
		
		$css = $cssinfo['css_text'];
		
		if ($type == 'add')
		{
			$code = 'doadd';
			$button = 'Create StyleSheet';
			$cssinfo['css_name'] = $cssinfo['css_name'].".2";
		}
		else
		{
			$code = 'doedit';
			$button = 'Edit Stylesheet';
			
			//+-------------------------------
			// DB same as cache version?
			//+-------------------------------
			
			$cache_file = ROOT_PATH."cache/css_".$IN['id'].".css";
			
			if ( file_exists( $cache_file ) )
			{
				$FH = fopen( $cache_file, 'r' );
				$cache_data = fread( $FH, filesize($cache_file) );
				fclose($FH);
			
				$db_length    = strlen( trim($css) );
				$cache_length = strlen(trim($cache_data));
				
				if ($db_length != $cache_length)
				{
					// We've got ourselves a mismatch!
					// Get mtime of cache file
					
					$stat = stat( $cache_file );
					
					$cache_mtime = $stat[9];
					$db_mtime    = $cssinfo['updated'];
					
					$this->resync_splash($db_length, $cache_length, $cache_mtime, $db_mtime, $IN['id']);
					
				}
			}
		}
		
		//+-------------------------------
		// COLURS!ooO!
		//+-------------------------------
		
		//.class { definitions }
		//#id { definitions }
		
		$css_elements = array();
		
		preg_match_all( "/(\.|\#)(\S+?)\s{0,}\{.+?\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			$type = trim($match[1][$i]);
			
			$name = trim($match[2][$i]);
			
			if ($type == '.')
			{
				$css_elements[] = array( 'class|'.$name, $type.$name );
			}
			else
			{
				$css_elements[] = array( 'id|'.$name, $type.$name );
			}
		}
			
		//+-------------------------------
	
		$ADMIN->page_detail = "You may use CSS fully when adding or editing stylesheets.";
		$ADMIN->page_title  = "Manage Style Sheets";
		
		//+-------------------------------
		
		$ADMIN->html .= "<script language='javascript'>
		                 <!--
		                 function cssSearch(theID)
		                 {
		                 	cssChosen = document.cssForm.csschoice.options[document.cssForm.csschoice.selectedIndex].value;
		                 	
		                 	window.open('{$SKIN->base_url}&act=rtempl&code=css_search&id='+theID+'&element='+cssChosen,'CSSSEARCH','width=400,height=500,resizable=yes,scrollbars=yes');
		                 }
		                 
		                 function cssPreview(theID)
		                 {
		                 	cssChosen = document.cssForm.csschoice.options[document.cssForm.csschoice.selectedIndex].value;
		                 	
		                 	window.open('{$SKIN->base_url}&act=rtempl&code=css_preview&id='+theID+'&element='+cssChosen,'CSSSEARCH','width=400,height=500,resizable=yes,scrollbars=yes');
		                 }
		                 
		                 //-->
		                 </script>";
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'css_search' ),
												  2 => array( 'act'   , 'style'      ),
												  3 => array( 'id'    , $IN['id']    ),
									     ), "cssForm"      );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "80%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Find CSS Usage" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Show me where...",
													$SKIN->form_dropdown('csschoice', $css_elements).' ... is used within the templates &nbsp;'
												   .'<input type="button" value="Go!" onClick="cssSearch(\''.$IN['id'].'\');" id="editbutton">'
												   .'&nbsp;<input type="button" value="Preview CSS Style" onClick="cssPreview(\''.$IN['id'].'\');" id="editbutton">'
									     )      );
									     
		
												 
		$ADMIN->html .= $SKIN->end_form();
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->js_no_specialchars();
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $code      ),
												  2 => array( 'act'   , 'style'      ),
												  3 => array( 'id'    , $IN['id']   ),
									     ), "theAdminForm", "onSubmit=\"return no_specialchars('csssheet')\""      );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "80%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( $button );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"Stylesheet Title",
													$SKIN->form_input('name', $cssinfo['css_name']),
									     )      );
									     
		$ADMIN->html .= $SKIN->end_table();
		
		$SKIN->td_header[] = array( "{none}"  , "100%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Contents" );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<center>".$SKIN->form_textarea('css', $css, $INFO['tx'], $INFO['ty'])."<br /><a href='html/sys-img/css.html' target='_blank'>Launch Style Maker</a></center>",
									     )      );
												 
		$ADMIN->html .= $SKIN->end_form($button);
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	//-------------------------------------------------------------
	// EDIT COLOURS START
	//-------------------------------------------------------------
	
	function colouredit()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT cssid, css_text, css_name, updated FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $cssinfo = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the CSS details from the database");
		}
		
		$css = $cssinfo['css_text'];
		
		//+-------------------------------
		// DB same as cache version?
		//+-------------------------------
		
		$cache_file = ROOT_PATH."cache/css_".$IN['id'].".css";
		
		if ( file_exists( $cache_file ) )
		{
			$FH = fopen( $cache_file, 'r' );
			$cache_data = fread( $FH, filesize($cache_file) );
			fclose($FH);
		
			$db_length    = strlen( $css );
			$cache_length = strlen($cache_data);
			
			if ($db_length != $cache_length)
			{
				// We've got ourselves a mismatch!
				// Get mtime of cache file
				
				$stat = stat( $cache_file );
				
				$cache_mtime = $stat[9];
				$db_mtime    = $cssinfo['updated'];
				
				if ( $cache_mtime != $db_mtime and ( $db_length - $cache_length > 3 ))
				{
					$this->resync_splash($db_length, $cache_length, $cache_mtime, $db_mtime, $IN['id'], 'colouredit');
				}
			}
		}
		
		
		//+-------------------------------
		// Start the CSS matcher thingy
		//+-------------------------------
		
		//.class { definitions }
		//#id { definitions }
		
		$colours = array();
		
		// Make http:// safe..
		
		//
		
		preg_match_all( "/([\:\.\#\w\s,]+)\{(.+?)\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			
			$name    = trim($match[1][$i]);
			$content = trim($match[2][$i]);
			
			$defs    = explode( ';', $content );
			
			if ( count( $defs ) > 0 )
			{
				foreach( $defs as $a )
				{
					$a = trim($a);
					
					if ( $a != "" )
					{
						list( $property, $value ) = explode( ":", $a, 2 );
						
						$property = trim($property);
						$value    = trim($value);
						
						if ( $property and $value )
						{
							if ( $property == 'color' or $property == 'background-color' or $property == 'border' or $property == 'background-image' )
							{
								$colours[ $name ][$property] = $value;
							}
						}
					}
				}
			}
		}
		
		if ( count($colours) < 1 )
		{
			$ADMIN->error("CSS all gone wonky! No colours to edit");
		}
		
		//+-------------------------------
		
		// Get $skin_names stuff
		
		require './sources/Admin/skin_info.php';
	
		$ADMIN->page_detail = "You edit the existing colours below. <strong><a href='html/sys-img/colours.html' target='_blank'>Launch Colour Picker</a></center></strong>";
		$ADMIN->page_title  = "Manage Style Sheets [ Colours ]";
		
		
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'docolour'   ),
												  2 => array( 'act'   , 'style'      ),
												  3 => array( 'id'    , $IN['id']    ),
									     )    );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Property"     , "25%" );
		$SKIN->td_header[] = array( "&nbsp;"       , "75%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "CSS Colours" );
		
		foreach ( $colours as $prop => $val )
		{
		
			$tbl_colour = "";
			$tbl_bg     = "";
			$tbl_html   = "";
			
			$desc = $css_names[ $prop ];
			
			if ( $desc == "" )
			{
				$desc = 'None available';
			}
			
			$name = $prop;
			
			$md5 = md5($name);
			
			if ( strlen($name) > 30 )
			{
				$name = substr( $name, 0, 30 ) .'...';
			}
			
			
			
			$ADMIN->html .= $SKIN->add_td_row( array( 
														"<strong>".$name."</strong><br />($desc)",
														"<table width='100%' border='0' cellpadding='4' cellspacing='0'>
														 <tr>
														  <td width='20%'>Font Colour</td><td width='30%'>".
														     $SKIN->form_simple_input('frm_'.$md5.'_color'           , $val['color'], "8")."&nbsp;&nbsp;<input type='text' size='6' style='border:1px solid black;background-color:{$val['color']}' readonly='readonly'>"
														."</td>
														  <td width='20%'>Background Colour</td><td width='30%'>".
														     $SKIN->form_simple_input('frm_'.$md5.'_background-color', $val['background-color'], "8")."&nbsp;&nbsp;<input type='text' size='6' style='border:1px solid black;background-color:{$val['background-color']}' readonly='readonly'>"
											 			."</td>
											 			 </tr>
											 			 <tr>
											 			 <td>Border</td><td width='30%'>".
											 			   $SKIN->form_simple_input('frm_'.$md5.'_border'          , $val['border'], "20")
											 			."</td>
											 			  <td>Background-image</td><td width='30%'>".
											 			   $SKIN->form_simple_input('frm_'.$md5.'_background-image', $val['background-image'], "30")
											 			."</td></tr></table>"
											 )      );
									     
		}
												 
		$ADMIN->html .= $SKIN->end_form("Edit");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	//-------------------------------------------------------------
	// EDIT COLOURS START
	//-------------------------------------------------------------
	
	function do_colouredit()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT cssid, css_text, css_name, updated FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $cssinfo = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the CSS details from the database");
		}
		
		$css = $cssinfo['css_text'];
		
		//+-------------------------------
		// Start the CSS matcher thingy
		//+-------------------------------
		
		$colours = array();
		
		preg_match_all( "/([\:\.\#\w\s,]+)\{(.+?)\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			
			$name    = trim($match[1][$i]);
			$content = trim($match[2][$i]);
			
			$md5     = md5($name);
			
			$defs    = explode( ';', $content );
			
			if ( count( $defs ) > 0 )
			{
				foreach( $defs as $a )
				{
					$a = trim($a);
					
					if ( $a != "" )
					{
						list( $property, $value ) = explode( ":", $a, 2 );
						
						$property = trim($property);
						$value    = trim($value);
						
						if ( $property and $value )
						{
							if ( $property != 'color' and $property != 'background-color' and $property != 'border' and $property != 'background-image' )
							{
								$colours[ $name ][$property] = $value;
							}
						}
					}
				}
			}
			
			foreach( array( 'color', 'background-color', 'border', 'background-image' ) as $prop )
			{
				if ( isset($HTTP_POST_VARS['frm_'.$md5.'_'.$prop]) )
				{
					$colours[ $name ][$prop] = stripslashes($HTTP_POST_VARS['frm_'.$md5.'_'.$prop]);
				}
			}
		}
		
		if ( count($colours) < 1 )
		{
			$ADMIN->error("CSS all gone wonky! No colours to edit");
		}
		
		//+-------------------------------
		
		unset($name);
		unset($property);
		
		$final = "";
		
		foreach( $colours as $name => $property )
		{
			$final .= $name." { ";
			
			if ( is_array($property) and count($property) > 0 )
			{
				foreach( $property as $key => $value )
				{
					if ( $key AND $value )
					{
						$final .= $key.": ".$value.";";
					}
				}
			}
			
			$final .= " }\n";
		
		}
		
		$barney = array( 
						 'css_text'     => $final,
						 'updated'      => time(),
					   );
					   
		$db_string = $DB->compile_db_update_string( $barney );
		
		$DB->query("UPDATE ibf_css SET $db_string WHERE cssid='".$IN['id']."'");
		
		//--------------------------------------------
		// Update cache?
		//--------------------------------------------
		
		$extra = "<b>Cache file updated</b>";
			
			if ( file_exists( ROOT_PATH."cache" ) )
			{
				if ( is_writeable( ROOT_PATH."cache" ) )
				{
					if ( $FH = @fopen( ROOT_PATH."cache/css_".$IN['id'].".css", 'w' ) )
					{
						@fputs( $FH, $css, strlen($css) );
						@fclose($FH);
					}
					else
					{
						$extra = "<b>Cache file not updated. Check CHMOD permissions on ./cache and ./cache/css_".$IN['id'].".css</b>";
					}
				}
				else
				{
					$extra = "<b>Cache file not updated. Check CHMOD permissions on ./cache and ./cache/css_".$IN['id'].".css</b>";
				}
			}
			else
			{
				$extra = "<b>Cache file not updated. Cache folder not present</b>";
			}
		
		$ADMIN->nav[] = array( 'act=style' ,'Style Sheet Control Home' );
		$ADMIN->nav[] = array( "act=style&code=edit2&id={$IN['id']}" ,"Edit Sheet Again (Advanced)" );
		$ADMIN->nav[] = array( "act=style&code=colouredit&id={$IN['id']}" ,"Edit Colours Again?" );
		
		$ADMIN->done_screen("Stylesheet updated : $extra", "Manage Style Sheets", "act=style" );
			
		
		
		
	}
	
	//-------------------------------------------------------------
	// SHOW STYLE SHEETS
	//-------------------------------------------------------------
	
	function list_sheets()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
		$show_array = array();
	
		$ADMIN->page_detail = "You may add/edit and remove stylesheets.<br><br>Style Sheets are CSS files. This is where you can change the colours, fonts and font sizes throughout the board.";
		$ADMIN->page_title  = "Manage Stylesheets";
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Title"  , "40%" );
		$SKIN->td_header[] = array( "Allocation"   , "20%" );
		$SKIN->td_header[] = array( "Optimize" , "10%" );
		$SKIN->td_header[] = array( "Download" , "10%" );
		$SKIN->td_header[] = array( "Edit"   , "10%" );
		$SKIN->td_header[] = array( "Remove" , "10%" );
		
		//+-------------------------------
		
		$DB->query("SELECT DISTINCT(c.cssid), c.css_name, s.sname from ibf_css c, ibf_skins s WHERE s.css_id=c.cssid ORDER BY c.css_name ASC");
		
		$used_ids = array();
		
		if ( $DB->get_num_rows() )
		{
		
			$ADMIN->html .= $SKIN->start_table( "Current Stylesheets In Use" );
			
			while ( $r = $DB->fetch_row() )
			{
			
				$show_array[ $r['cssid'] ] .= stripslashes($r['sname'])."<br>";
			
				if ( in_array( $r['cssid'], $used_ids ) )
				{
					continue;
				}
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".stripslashes($r['css_name'])."</b>",
														  "<#X-{$r['cssid']}#>",
														  "<center><a href='".$SKIN->base_url."&act=style&code=optimize&id={$r['cssid']}'>Optimize</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=style&code=export&id={$r['cssid']}'>Download</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=style&code=edit&id={$r['cssid']}'>Edit</a></center>",
														  "<i>Deallocate before removing</i>",
												 )      );
												   
				$used_ids[] = $r['cssid'];
				
				$form_array[] = array( $r['cssid'], $r['css_name'] );
				
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
		
			$DB->query("SELECT cssid, css_name FROM ibf_css WHERE cssid NOT IN(".implode(",",$used_ids).")");
		
			if ( $DB->get_num_rows() )
			{
			
				$SKIN->td_header[] = array( "Title"  , "60%" );
				$SKIN->td_header[] = array( "Optimize" , "10%" );
				$SKIN->td_header[] = array( "Download" , "10%" );
				$SKIN->td_header[] = array( "Edit"   , "10%" );
				$SKIN->td_header[] = array( "Remove" , "10%" );
			
				$ADMIN->html .= $SKIN->start_table( "Current Unallocated Stylesheets" );
				
				$ADMIN->html .= $SKIN->js_checkdelete();
				
				
				while ( $r = $DB->fetch_row() )
				{
					
					$ADMIN->html .= $SKIN->add_td_row( array( "<b>".stripslashes($r['css_name'])."</b>",
					 										  "<center><a href='".$SKIN->base_url."&act=style&code=optimize&id={$r['cssid']}'>Optimize</a></center>",
					 										  "<center><a href='".$SKIN->base_url."&act=style&code=export&id={$r['cssid']}'>Download</a></center>",
															  "<center><a href='".$SKIN->base_url."&act=style&code=edit&id={$r['cssid']}'>Edit</a></center>",
															  "<center><a href='javascript:checkdelete(\"act=style&code=remove&id={$r['cssid']}\")'>Remove</a></center>",
													 )      );
													 
					$form_array[] = array( $r['cssid'], $r['css_name'] );
													   
				}
				
				$ADMIN->html .= $SKIN->end_table();
			}
		}
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'add'      ),
												  2 => array( 'act'   , 'style'    ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Create New Stylesheet (Copy)" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Base new stylesheet on...</b>" ,
										  		  $SKIN->form_dropdown( "id", $form_array)
								 )      );
		
		$ADMIN->html .= $SKIN->end_form("Copy to new stylesheet");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'css_upload' ),
												  2 => array( 'act'   , 'style'     ),
												  3 => array( 'MAX_FILE_SIZE', '10000000000' ),
									     ) , "uploadform", " enctype='multipart/form-data'"     );
									     
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Upload new stylesheet" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Browse your hard drive</b>" ,
										  		  $SKIN->form_upload()
								 )      );
		
		$ADMIN->html .= $SKIN->end_form("Upload new stylesheet");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
	
	}
	
	
}


?>