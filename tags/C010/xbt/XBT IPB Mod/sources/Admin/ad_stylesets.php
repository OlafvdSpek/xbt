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
				$this->do_form('add');
				break;
				
			case 'edit':
				$this->do_form('edit');
				break;
				
			case 'doadd':
				$this->save_skin('add');
				break;
				
			case 'doedit':
				$this->save_skin('edit');
				break;
				
			case 'remove':
				$this->remove();
				break;
			
			//-------------------------
			case 'memskins':
				$this->mem_skins();
				break;
				
			case 'export':
				$this->export();
				break;
				
			default:
				$this->list_sets();
				break;
		}
		
	}
	
	//----------------------------------------------------
	
	function export()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing skin set ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_skins WHERE uid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_macro_name WHERE set_id='".$row['macro_id']."'");
		
		if ( ! $macro_name = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_css WHERE cssid='".$row['css_id']."'");
		
		if ( ! $css = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_tmpl_names WHERE skid='".$row['set_id']."'");
		
		if ( ! $tmpl = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_templates WHERE tmid='".$row['tmpl_id']."'");
		
		if ( ! $wrap = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		
		//+-------------------------------
		
		if ($INFO['base_dir'] == './')
		{
			$INFO['base_dir'] = str_replace( '\\', '/', getcwd() ) . '/';
		}
		
		$archive_dir   = $INFO['base_dir']."archive_out";
		$images_dir    = $INFO['base_dir']."style_images/".$row['img_dir'];
		
		require ROOT_PATH."sources/lib/tar.php";
		
		if (!is_dir($archive_dir))
		{
			$ADMIN->error("Could not locate $archive_dir, is the directory there?");
		}
		
		if (!is_writeable($archive_dir))
		{
			$ADMIN->error("Cannot write in $archive_dir, CHMOD via FTP to 0755 or 0777 to enable this script to write into it. IBF cannot do this for you");
		}
		
		if (!is_dir($images_dir))
		{
			$ADMIN->error("Could not locate $images_dir, is the directory there?");
		}
		
		//+-------------------------------
		// Set up the dir structure
		//+-------------------------------
		
		$css_name  = "stylesheet.css";
		$wrap_name = "wrapper.html";
		$macro_name = "macro.txt";
		$template_name = "templates.html";
		
		$img_dir   = 'images';
		
		$pack_name = preg_replace( "/\s{1,}/", "_", $row['sname'] );
		
		$new_dir = "set-".$pack_name;
		
		//+-------------------------------
		
		if ( ! mkdir($archive_dir."/".$new_dir, 0777) )
		{
			$ADMIN->error("Directory creation failed, cannot export skin set. Please check the permission in 'archive_out'");
		}
		
		//+-------------------------------
		
		if ( ! mkdir($archive_dir."/".$new_dir."/".$img_dir, 0777) )
		{
			$ADMIN->error("Directory creation failed, cannot export skin set. Please check the permission in 'archive_out'");
		}
		
		//+-------------------------------
		// Make the wrapper file...
		//+-------------------------------
		
		$wrap['template'] = preg_replace("/\r/", "\n", $wrap['template'] );
		
		$FH = fopen($archive_dir."/".$new_dir."/".$wrap_name, 'w');
		fwrite($FH, $wrap['template'], strlen($wrap['template']));
		fclose($FH);
		
		@chmod($archive_dir."/".$new_dir."/".$wrap_name, 0777);
		
		//+-------------------------------
		// Make the css file...
		//+-------------------------------
		
		$FH = fopen($archive_dir."/".$new_dir."/".$css_name, 'w');
		fwrite($FH, $css['css_text'], strlen($css['css_text']));
		fclose($FH);
		
		@chmod($archive_dir."/".$new_dir."/".$css_name, 0777);
		
		//+-------------------------------
		// Copy over the images...
		//+-------------------------------
		
		if ( ! $ADMIN->copy_dir($images_dir, $archive_dir."/".$new_dir."/".$img_dir) )
		{
			$ADMIN->error( $ADMIN->errors );
		}
		
		//+-------------------------------
		// Make the macro file...
		//+-------------------------------
		
		$file_content = "";
		
		$DB->query("SELECT macro_replace, macro_value FROM ibf_macro WHERE macro_set='".$row['macro_id']."'");
		
		while( $mrow = $DB->fetch_row() )
		{
			if ($mrow['macro_replace'] == "")
			{
				$mrow['macro_replace'] = "*UNASSIGNED*";
			}
			
			// Fix newlines..
			
			$mrow['macro_replace'] = str_replace( "\n", '\n', str_replace("\r", "\n", $mrow['macro_replace'] ) );
			
			$file_content .= $mrow['macro_value']."~=~".$mrow['macro_replace']."\n";
		}
		
		$FH = fopen($archive_dir."/".$new_dir."/".$macro_name, 'w');
		fwrite($FH, $file_content, strlen($file_content));
		fclose($FH);
		
		@chmod($archive_dir."/".$new_dir."/".$macro_name, 0777);
		
		//+----------------------------------------------------------------------------
		// Generate template HTML file
		//+----------------------------------------------------------------------------
		
		$output = "";
		
		$groups = $DB->query("SELECT DISTINCT(group_name) FROM ibf_skin_templates WHERE set_id='".$row['set_id']."'");
		
		if ( ! $DB->get_num_rows($groups) )
		{
			$ADMIN->rm_dir($archive_dir);
			$ADMIN->error("Export Failed at template set creation: Can't query the information from the database");
		}
		
		$output .= "<!--TEMPLATE_SET|internal,internal,internal,internal-->\n\n";
		
		while ( $trow = $DB->fetch_row($groups) )
		{
		
			$aq = $DB->query("SELECT * FROM ibf_skin_templates WHERE set_id='".$row['set_id']."' AND group_name='".$trow['group_name']."'");
			
			if ( ! $DB->get_num_rows($aq) )
			{
				$ADMIN->rm_dir($archive_dir);
				$ADMIN->error("Can't query the information from the database");
			}
			
			$output .= "<!-- PLEASE LEAVE ALL 'IBF' COMMENTS IN PLACE, DO NOT REMOVE THEM! -->\n<!--IBF_GROUP_START:{$trow['group_name']}-->\n\n";
			
			while ( $next_row = $DB->fetch_row($aq) )
			{
				$text = $this->convert_tags($next_row['section_content']);
				$text = str_replace( "\r\n", "\n", $text);
				$text = str_replace( "\n\n", "\n", $text);
				
				$output .= "<!--IBF_START_FUNC|{$next_row['func_name']}|{$next_row['func_data']}-->\n\n";
				$output .= $text."\n";
				$output .= "<!--IBF_END_FUNC|{$next_row['func_name']}-->\n\n";
			}
			
			$output .= "\n<!--IBF_GROUP_END:{$trow['group_name']}-->\n";
		
		}
		
		$FH = fopen($archive_dir."/".$new_dir."/".$template_name, 'w');
		fwrite($FH, $output, strlen($output));
		fclose($FH);
		
		@chmod($archive_dir."/".$new_dir."/".$template_name, 0777);
		
		//+----------------------------------------------------------------------------
		// Generate the config file..
		//+----------------------------------------------------------------------------
		
		$file_content = "<?php\n\n".
					    "\$config=array('author' => \"".addslashes($tmpl['author'])."\", ".
					    "'email'=>\"".addslashes($tmpl['email'])."\", ".
					    "'url'=>\"".addslashes($tmpl['url'])."\")\n\n?".">";
		
		$FH = fopen($archive_dir."/".$new_dir."/"."templates_conf.inc", 'w');
		fwrite($FH, $file_content, strlen($file_content));
		fclose($FH);
		
		@chmod($archive_dir."/".$new_dir."/"."templates_conf.inc", 0777);
		
		//+-------------------------------
		// Add files and write tarball
		//+-------------------------------
		
		$tar = new tar();
		
		$tar->new_tar( $archive_dir, $new_dir.".tar" );
		$tar->add_directory( $archive_dir."/".$new_dir );
		$tar->write_tar();
		
		// Check for errors.
		
		if ($tar->error != "")
		{
			$ADMIN->rm_dir($archive_dir);
			$ADMIN->error($tar->error);
		}
		
		// remove original unarchived directory
		
		$ADMIN->rm_dir($archive_dir."/".$new_dir);
		
		$ADMIN->done_screen("Skin Pack Export Created<br><br>You can download the tar-chive <a href='archive_out/{$new_dir}.tar' target='_blank'>here</a>", "Manage Skin Sets", "act=sets" );
		
		
	}
	
	//----------------------------------------------
	// I must get around to centralising this...
	//----------------------------------------------
	
	function convert_tags($t="")
	{
		if ($t == "")
		{
			return "";
		}
		
		$t = preg_replace( "/{?\\\$ibforums->base_url}?/"            , "{ibf.script_url}"   , $t );
		$t = preg_replace( "/{?\\\$ibforums->session_id}?/"          , "{ibf.session_id}"   , $t );
		$t = preg_replace( "/{?\\\$ibforums->skin\['?(\w+)'?\]}?/"   , "{ibf.skin.\\1}"      , $t );
		$t = preg_replace( "/{?\\\$ibforums->lang\['?(\w+)'?\]}?/"   , "{ibf.lang.\\1}"      , $t );
		$t = preg_replace( "/{?\\\$ibforums->vars\['?(\w+)'?\]}?/"   , "{ibf.vars.\\1}"      , $t );
		$t = preg_replace( "/{?\\\$ibforums->member\['?(\w+)'?\]}?/" , "{ibf.member.\\1}"    , $t );
		
		return $t;
		
	}
	
	//----------------------------------------------
	
	function mem_skins()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		$DB->query("SELECT sid FROM ibf_skins WHERE uid='".$IN['oid']."'");
		$old = $DB->fetch_row();
		
		if ($IN['nid'] == 'n')
		{
			$DB->query("UPDATE ibf_members SET skin=NULL WHERE skin='".$old['sid']."'");
		}
		else
		{
			$DB->query("SELECT sid FROM ibf_skins WHERE uid='".$IN['nid']."'");
			$new = $DB->fetch_row();
			$DB->query("UPDATE ibf_members SET skin='".$new['sid']."' WHERE skin='".$old['sid']."'");
		}
		
		$ADMIN->done_screen("Member Skin Choices Updated", "Manage Skin Sets", "act=sets" );
		
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
			$ADMIN->error("You must specify an existing skin set ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * FROM ibf_skins WHERE uid='".$IN['id']."'");
		
		if (! $skin = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query that skin set information from the DB");
		}
		
		//+-------------------------------
		
		if ($skin['default_set'] == 1)
		{
			$ADMIN->error("You can not remove this skin set as it is set as the default. Set another skin as default and try again");
		}
		
		//+-------------------------------
		
		// Update the members skins..
		
		$DB->query("UPDATE ibf_members SET skin='' WHERE skin='".$skin['sid']."'");
		
		// Update the forums DB
		
		$DB->query("SELECT id FROM ibf_forums WHERE skin_id='".$skin['sid']."'");
		
		if ( $DB->get_num_rows() )
		{
			$arr = array();
			
			while ( $i = $DB->fetch_row() )
			{
				$arr['forum_skin_'.$i['id']] = '';
			}
		
			$DB->query("UPDATE ibf_forums SET skin_id='' WHERE skin_id='".$skin['sid']."'");
			
			// Remove it from the config file..
			
			$ADMIN->rebuild_config($arr);
			
		}
		
		// Remove skin from the DB
		
		$DB->query("DELETE FROM ibf_skins WHERE uid='".$IN['id']."'");
		
		$std->boink_it($SKIN->base_url."&act=sets");
			
		exit();
		
		
	}
	
	
	
	//-------------------------------------------------------------
	// ADD / EDIT SKIN SETS
	//-------------------------------------------------------------
	
	function save_skin( $type='add' )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//+-------------------------------
		
		if ($type == 'edit')
		{
			if ($IN['id'] == "")
			{
				$ADMIN->error("You must specify an existing skin set ID, go back and try again");
			}
		}
		
		if ($IN['sname'] == "")
		{
			$ADMIN->error("You must specify a name for this skin pack ID");
		}
		
		
		$barney = array( 'sname'      => $std->txt_stripslashes($HTTP_POST_VARS['sname']),
						 'set_id'     => $IN['template'],
						 'tmpl_id'    => $IN['wrapper'],
						 'img_dir'    => $IN['img_dir'],
						 'css_id'     => $IN['css'],
						 'css_method' => $IN['css_method'],
						 'hidden'     => $IN['hidden'],
						 'default_set'=> $IN['default_set'],
						 'macro_id'   => $IN['macro_id'],
					   );
					   
		if ($type == 'add')
		{
		
			$DB->query("SELECT MAX(sid) as new_id FROM ibf_skins");
			
			$row = $DB->fetch_row();
			
			$barney['sid'] = $row['new_id'] + 1;
		
			$db_string = $DB->compile_db_insert_string( $barney );
			
			$DB->query("INSERT INTO ibf_skins (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
			
			if ($IN['default_set'] == 1)
			{
				$DB->query("UPDATE ibf_skins SET default_set=0 WHERE sid <> '".$barney['sid']."'");
			}
			
			$std->boink_it($SKIN->base_url."&act=sets");
			
			exit();
			
		}
		else
		{
			$db_string = $DB->compile_db_update_string( $barney );
			
			$DB->query("UPDATE ibf_skins SET $db_string WHERE uid='".$IN['id']."'");
			
			if ($IN['default_set'] == 1)
			{
				$DB->query("UPDATE ibf_skins SET default_set=0 WHERE uid <> '".$IN['id']."'");
			}
			
			$ADMIN->done_screen("Skin Set Updated", "Manage Skin Sets", "act=sets" );
		}
		
		
	}
	
	//-------------------------------------------------------------
	// ADD / EDIT SETS
	//-------------------------------------------------------------
	
	function do_form( $type='add' )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//+-------------------------------
		
		$css       = array();
		$wrappers  = array();
		$templates = array();
		$macros    = array();
		
		//+-------------------------------
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * from ibf_skins WHERE uid='".$IN['id']."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$ADMIN->error("Could not query the information from the database");
		}
		
		//+-------------------------------
		
		$DB->query("SELECT * FROM ibf_macro_name");
		
		while( $img = $DB->fetch_row() )
		{
			$macros[] = array( $img['set_id'], $img['set_name'] );
		}
		
		//+-------------------------------
		
		$DB->query("SELECT cssid, css_name FROM ibf_css");
		
		while ( $c = $DB->fetch_row() )
		{
			$css[] = array( $c['cssid'], $c['css_name'] );
		}
		
		//+-------------------------------
		
		$DB->query("SELECT tmid, name FROM ibf_templates");
		
		while ( $t = $DB->fetch_row() )
		{
			$wrappers[] = array( $t['tmid'], $t['name'] );
		}
		
		//+-------------------------------
		
		$DB->query("SELECT skid, skname FROM ibf_tmpl_names");
		
		while ( $s = $DB->fetch_row() )
		{
			$templates[] = array( $s['skid'], $s['skname'] );
		}
		
		
		//+-------------------------------
		
		if ($type == 'add')
		{
			$code = 'doadd';
			$button = 'Create Skin Set';
			$row['sname'] = $row['sname'].".2";
			$row['default_set'] = 0;
		}
		else
		{
			$code = 'doedit';
			$button = 'Edit Skin Set';
		}
		
		$dirs = array();
		
		$dh = opendir( './style_images' );
 		while ( $file = readdir( $dh ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
				if ( is_dir('./style_images/'.$file) )
				{
					$dirs[] = array( $file, $file );
				}
 			}
 		}
 		closedir( $dh );
 		
 		//+-------------------------------
 		
 		if ( file_exists( ROOT_PATH."cache" ) )
		{
			if ( is_writeable( ROOT_PATH."cache" ) )
			{
				$cssextra = $SKIN->form_dropdown('css_method', array( 0 => array( 'inline', 'Inline'), 1 => array( 'external', 'External' ) ), $row['css_method']);
			}
		}
		
		//+-------------------------------
	
		$ADMIN->page_detail = "You may mix n' match which skin resources you wish to apply to this skin set.";
		$ADMIN->page_title  = "Manage Skin Sets";
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $code      ),
												  2 => array( 'act'   , 'sets'      ),
												  3 => array( 'id'    , $IN['id']   ),
									     ), "theAdminForm", "onSubmit=\"return no_specialchars('sets')\""    );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( $button );
		
		$ADMIN->html .= $SKIN->js_no_specialchars();
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Skin Set Title</b>",
													$SKIN->form_input('sname', $row['sname']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Use Templates:</b>",
													$SKIN->form_dropdown('template', $templates, $row['set_id']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Use Macro Set:</b>",
													$SKIN->form_dropdown('macro_id', $macros, $row['macro_id']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Use Image Directory:</b>",
													$SKIN->form_dropdown('img_dir', $dirs, $row['img_dir']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Use Stylesheet:</b>",
													$SKIN->form_dropdown('css', $css, $row['css_id']).'&nbsp;'.$cssextra,
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Use Wrapper:</b>",
													$SKIN->form_dropdown('wrapper', $wrappers, $row['tmpl_id']),
									     )      );
									     

		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Hide from Members?</b><br>Useful if you want to make a forum only skin",
													$SKIN->form_yes_no('hidden', $row['hidden']),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Set as default skin set?</b><br>Used for unallocated forum and member skins",
													$SKIN->form_yes_no('default_set', $row['default_set']),
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
	
	function list_sets()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
		
		$forums      = array();
		$forum_skins = array();
	
		$ADMIN->page_detail = "You may add/edit and remove skin sets.<br><br>Skin sets are groups of style resources. You can mix n' match board wrappers, image and macro sets, stylesheets and skin templates. If you wish to edit any of the resources, please choose the resource you wish to manage from the menu bar on the left.";
		$ADMIN->page_title  = "Manage Skin Sets";
		
		//+-------------------------------
		
		$DB->query("SELECT id, name FROM ibf_forums");
		
		while ( $f = $DB->fetch_row() )
		{
			$forums[ $f['id'] ] = $f['name'];
			
			if ($INFO['forum_skin_'.$f['id']] != "")
			{
				$forum_skins[ $INFO['forum_skin_'.$f['id']] ][] = $f['name'];
			}
		}
		
		
		$DB->query("select ibf_skins.*, count(ibf_members.id) as mcount from ibf_skins left join ibf_members on(ibf_members.skin=ibf_skins.sid) where (ibf_members.skin is not null or ibf_skins.default_set = 1) group by ibf_skins.sid order by ibf_skins.sname");
		
		$used_ids = array();
		
		if ( $DB->get_num_rows() )
		{
		
			$SKIN->td_header[] = array( "Title"       , "40%" );
			$SKIN->td_header[] = array( "No. Members" , "20%" );
			$SKIN->td_header[] = array( "Export"      , "10%" );
			$SKIN->td_header[] = array( "Edit"        , "10%" );
			$SKIN->td_header[] = array( "Remove"      , "10%" );
			$SKIN->td_header[] = array( "Hidden"      , "5%" );
			$SKIN->td_header[] = array( "Default"     , "5%" );
			
			//+-------------------------------
		
			$ADMIN->html .= $SKIN->start_table( "Current Skins Used by Members" );
			
			while ( $r = $DB->fetch_row() )
			{
				$extra = "";
				
				if ( is_array($forum_skins[ $r['sid'] ]) )
				{
					if ( count($forum_skins[ $r['sid'] ]) > 0 )
					{
						$extra = "<br>(Used in forums: ".implode( ",", $forum_skins[ $r['sid'] ] )." )";
					}
				}
				
				$default = '&nbsp;';
				$hidden  = '&nbsp;';
				
				if ($r['hidden'] == 1)
				{
					$hidden = "<span style='color:red;font-weight:bold'>X</span>";
				}
				
				if ($r['default_set'] == 1)
				{
					$default = "<span style='color:red;font-weight:bold'>X</span>";
				}
				
				$editlist = "<br /><b>Edit:</b> <a href='{$SKIN->base_url}&act=wrap&code=edit&id={$r['tmpl_id']}'>Wrapper</a>
							&middot; <a href='{$SKIN->base_url}&act=templ&code=edit&id={$r['set_id']}'>HTML</a>
							&middot; <a href='{$SKIN->base_url}&act=style&code=edit&id={$r['css_id']}'>CSS</a>
							&middot; <a href='{$SKIN->base_url}&act=style&code=colouredit&id={$r['css_id']}'>Colours</a>
							&middot; <a href='{$SKIN->base_url}&act=image&code=edit&id={$r['macro_id']}'>Macros</a>";
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".$std->txt_stripslashes($r['sname'])."</b>$extra".$editlist,
														  "<center>".$r['mcount']."</center>",
														  "<center><a href='".$SKIN->base_url."&act=sets&code=export&id={$r['uid']}'>Export</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=sets&code=edit&id={$r['uid']}'>Edit</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=sets&code=remove&id={$r['uid']}'>Remove</a></center>",
														  "<center>$hidden</center>",
														  "<center>$default</center>",
												 )      );
												   
				$used_ids[] = $r['uid'];
				
				$form_array[] = array( $r['uid'], $r['sname'] );
				
			}
			
			$ADMIN->html .= $SKIN->end_table();
		}
		
		if ( count($used_ids) < 1 )
		{
			$DB->query("SELECT * FROM ibf_skins");
			
			$left_one = $DB->get_num_rows();
		}
		else if ( count($used_ids) > 0 )
		{
			$DB->query("SELECT * FROM ibf_skins WHERE uid NOT IN(".implode(",",$used_ids).")");
			
			$left_two = $DB->get_num_rows();
		}
		
		if ( $left_one > 0 or $left_two > 0 )
		{
			$SKIN->td_header[] = array( "Title"  , "60%" );
			$SKIN->td_header[] = array( "Export" , "10%" );
			$SKIN->td_header[] = array( "Edit"   , "10%" );
			$SKIN->td_header[] = array( "Remove" , "10%" );
			$SKIN->td_header[] = array( "Hidden" , "5%" );
			$SKIN->td_header[] = array( "Default", "5%" );
		
			$ADMIN->html .= $SKIN->start_table( "Skin Sets not used by Members" );
			
			while ( $r = $DB->fetch_row() )
			{
				
				$extra = "";
			
				if ( is_array($forum_skins[ $r['sid'] ]) )
				{
					if ( count($forum_skins[ $r['sid'] ]) > 0 )
					{
						$extra = "<br>(Used in forums: ".implode( ",", $forum_skins[ $r['sid'] ] )." )";
					}
				}
				
				$default = '&nbsp;';
				$hidden  = '&nbsp;';
				
				if ($r['hidden'] == 1)
				{
					$hidden = "<span style='color:red;font-weight:bold'>X</span>";
				}
				
				if ($r['default_set'] == 1)
				{
					$default = "<span style='color:red;font-weight:bold'>X</span>";
				}
				
				$editlist = "<br /><b>Edit:</b> <a href='{$SKIN->base_url}&act=wrap&code=edit&id={$r['tmpl_id']}'>Wrapper</a>
							&middot; <a href='{$SKIN->base_url}&act=templ&code=edit&id={$r['set_id']}'>HTML</a>
							&middot; <a href='{$SKIN->base_url}&act=style&code=edit&id={$r['css_id']}'>CSS</a>
							&middot; <a href='{$SKIN->base_url}&act=style&code=colouredit&id={$r['css_id']}'>Colours</a>
							&middot; <a href='{$SKIN->base_url}&act=image&code=edit&id={$r['macro_id']}'>Macros</a>";
				
				$ADMIN->html .= $SKIN->js_checkdelete();
				
			
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".$std->txt_stripslashes($r['sname'])."</b>$extra".$editlist,
														  "<center><a href='".$SKIN->base_url."&act=sets&code=export&id={$r['uid']}'>Export</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=sets&code=edit&id={$r['uid']}'>Edit</a></center>",
														  "<center><a href='javascript:checkdelete(\"act=sets&code=remove&id={$r['uid']}\")'>Remove</a></center>",
														  "<center>$hidden</center>",
														  "<center>$default</center>",
												 )      );
												 
				$form_array[] = array( $r['uid'], $r['sname'] );
												   
			}
			
			$ADMIN->html .= $SKIN->end_table();
		}
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'add'     ),
												  2 => array( 'act'   , 'sets'    ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Create New Skin Set" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Base new skin set on...</b>" ,
										  		  $SKIN->form_dropdown( "id", $form_array)
								 )      );
		
		$ADMIN->html .= $SKIN->end_form("Create new Skin Set");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'memskins'     ),
												  2 => array( 'act'   , 'sets'         ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		$ADMIN->html .= $SKIN->start_table( "Swop members skin choice" );
			
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Where members use skin...</b>",
										  		  $SKIN->form_dropdown( "oid", $form_array)
								 )      );
								 
		$form_array[] = array( 'n', 'None (Will use whatever is set as default)' );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>update to use skin...</b>",
										  		  $SKIN->form_dropdown( "nid", $form_array)
								 )      );
		
		$ADMIN->html .= $SKIN->end_form("Update members skin choice");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	
}


?>