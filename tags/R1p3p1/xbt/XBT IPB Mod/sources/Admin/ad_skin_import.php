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
|   > Import functions
|   > Module written by Matt Mecham
|   > Date started: 22nd April 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$idx = new ad_langs();


class ad_langs {

	var $base_url;

	function ad_langs() {
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
		
			case 'doimport':
				$this->doimport();
				break;
				
			case 'import':
				$this->import();
				break;
				
			case 'remove':
				$this->remove();
				break;
				
				
			//-------------------------
			default:
				$this->list_current();
				break;
		}
		
	}
	
	
	//---------------------------------------------
	// Remove archived files
	//---------------------------------------------
	
	function remove()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		if ($IN['id'] == "")
		{
			$ADMIN->error("You did not select a tar-chive to import!");
		}
		
		$this->tar_with_path = $INFO['base_dir']."archive_in/".$IN['id'];
		
		if ( ! unlink($this->tar_with_path) )
		{
			$ADMIN->error("Could not remove that file, please check the CHMOD permissions");
		}
		
		$std->boink_it($ADMIN->base_url."&act=import");
		exit();
	
	
	}
	
	
	//---------------------------------------------
	// Import switcheroo
	//---------------------------------------------
	
	function import()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		if ($IN['id'] == "")
		{
			$ADMIN->error("You did not select a tar-chive to import!");
		}
		
		$this->tar_with_path = $INFO['base_dir']."archive_in/".$IN['id'];
		
		$this->work_path     = $INFO['base_dir']."archive_in";
		
		if (! file_exists($this->tar_with_path) )
		{
			$ADMIN->error( "That archive is not found on the server, it may have been deleted by another admin");
		}
		
		$this->tar_file = $IN['id'];
		
		$this->name_translated = preg_replace( "/^(css|image|set|wrap|tmpl)-(.+?)\.(\S+)$/", "\\2", $this->tar_file );
		$this->name_translated = preg_replace( "/_/", " ", $this->name_translated );
		
		require ROOT_PATH."sources/lib/tar.php";
		
		$this->tar = new tar();
		
		switch($IN['type'])
		{
			case 'css':
				$this->css_import();
				break;
				
			case 'wrap':
				$this->wrap_import();
				break;
				
			case 'image':
				$this->image_import();
				break;
				
			case 'tmpl':
				$this->template_import();
				break;
			case 'set':
				$this->set_import();
				break;
				
			//---------
			default:
				$ADMIN->error("Unrecognised archive type");
				break;
		}
	
	
	}
	
	function set_import()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$images_dir    = $INFO['base_dir']."style_images";
		$skins_dir     = $INFO['base_dir']."Skin";
		
		if (! is_writeable($images_dir) )
		{
			$ADMIN->error("Cannot write to the $images_dir directory, please set sufficient CHMOD permissions to allow this. IBF cannot do this for you.");
		}
		
		if (! is_dir($skins_dir) )
		{
			$ADMIN->error("Cannot write to the $skins_dir directory, It is not there.");
		}
		
		if (! is_writeable($skins_dir) )
		{
			$ADMIN->error("Cannot write to the $skins_dir directory, please set sufficient CHMOD permissions to allow this. IBF cannot do this for you.");
		}
		
		//------------------------------------------------------
		
		$this->tar->new_tar( $this->work_path, $this->tar_file );
		
		$files = $this->tar->list_files();
		
		if (! $this->check_archive($files) )
		{
			$ADMIN->error("That is not a valid tar-achive, please re-upload in binary and try again");
		}
		
		//------------------------------------------------------
		
		$DB->query("SELECT COUNT(*) as count FROM ibf_tmpl_names");
		
		$tmpl_count = $DB->fetch_row();
		
		//------------------------------------------------------
		// Attempt to create a new work directory
		//------------------------------------------------------
		
		$new_dir = preg_replace( "/^(.+?)\.tar$/", "\\1", $this->tar_file );
		
		$this->new_dir = $new_dir;
		
		if ( ! mkdir($this->work_path."/".$new_dir, 0777) )
		{
			$ADMIN->error("Directory creation failed, cannot import skin set. Please check the permission in 'archive_in'");
		}
		
		@chmod($this->work_path."/".$new_dir, 0777);
		
		$next_id = array( 'css' => 0, 'wrap' => 0, 'templates' => 0, 'macro' => 0 );
		
		//------------------------------------------------------
		// Add a dummy entries into the DB to get the next insert ID
		//------------------------------------------------------
		
		$DB->query("INSERT INTO ibf_tmpl_names SET skname=\"".$this->name_translated." (Set Import).{$tmpl_count['count']}\"");
		
		$next_id['templates'] = $DB->get_insert_id();
		
		//------------------------------------------------------
		
		$DB->query("INSERT INTO ibf_css SET css_name=\"".$this->name_translated." (Set Import)\"");
		
		$next_id['css'] = $DB->get_insert_id();
		
		//------------------------------------------------------
		
		$DB->query("INSERT INTO ibf_templates SET name=\"".$this->name_translated." (Set Import)\"");
		
		$next_id['wrap'] = $DB->get_insert_id();
		
		//------------------------------------------------------
		// Get the new macro set_id
		//------------------------------------------------------
		
		$DB->query("SELECT MAX(set_id) as max FROM ibf_macro_name");
		
		$max = $DB->fetch_row();
		
		$next_id['macro'] = $max['max'] + 1;
		
		//------------------------------------------------------
		// Attempt to create the new directories
		//------------------------------------------------------
		
		$next_id['images'] = str_replace( " ", "_", substr($this->name_translated,0,8) ) . '-'. substr( time(), 7,10);
		
		if (! mkdir($images_dir."/".$next_id['images'], 0777) )
		{
			$this->import_error("Could not create a new directory in style_images", $next_id);
		}
		
		@chmod($images_dir."/".$next_id['images'], 0777);
		
		//-----------------------------------------------------
		
		if (! mkdir($skins_dir."/s".$next_id['templates'], 0777) )
		{
			$this->import_error("Could not create a new directory in Skin", $next_id);
		}
		
		@chmod($skins_dir."/s".$next_id['templates'], 0777);
		
		//------------------------------------------------------
		
		$this->tar->extract_files($this->work_path."/".$new_dir);
		
		if ($this->tar->error != "")
		{
			$this->import_error($this->tar->error, $next_id);
		}
		
		//------------------------------------------------------
		
		if ( file_exists($this->work_path."/".$new_dir."/templates_conf.inc") )
		{
			require $this->work_path."/".$new_dir."/templates_conf.inc";
			
			$template_config = array( 'author'     => stripslashes($config['author']),
									  'email'      => stripslashes($config['email']),
									  'url'        => stripslashes($config['url']),
									);
									
			$db_string = $DB->compile_db_update_string( $template_config );
						   
			$DB->query("UPDATE ibf_tmpl_names SET $db_string WHERE skid='{$next_id['templates']}'");
		}
		
		//------------------------------------------------------
		// Import the CSS
		//------------------------------------------------------
		
		if ($FH = fopen( $this->work_path."/".$new_dir."/stylesheet.css", 'r' ) )
		{
			$css = fread($FH, filesize($this->work_path."/".$new_dir."/stylesheet.css") );
			fclose($FH);
			
			//-------------------------
			// Swop Binary to Ascii
		    //-------------------------
			
			$css = preg_replace( "/\r/", "\n", stripslashes($css) );
			
			$css_string = $DB->compile_db_update_string( array(
																 'css_name' => stripslashes( $this->name_translated )." (Import)",
																 'css_text' => $css,
													   )      );
												   
			$DB->query("UPDATE ibf_css SET $css_string WHERE cssid='{$next_id['css']}'");
			
		}
		else
		{
			$this->import_error("Could not read the uploaded CSS archive file, please check the permissions on that file and try again", $next_id);
		}
		
		//------------------------------------------------------
		// Import the board wrapper
		//------------------------------------------------------
		
		if ($FH = fopen( $this->work_path."/".$new_dir."/wrapper.html", 'r' ) )
		{
			$text = fread($FH, filesize($this->work_path."/".$new_dir."/wrapper.html") );
			fclose($FH);
			
			//-------------------------
			// Swop Binary to Ascii
		    //-------------------------
			
			$text = preg_replace( "/\r/", "\n", stripslashes($text) );
			
			$wrap_string = $DB->compile_db_update_string( array(
																  'name'     => stripslashes( $this->name_translated )." (Import)",
																  'template' => $text,
														)      );
												   
			$DB->query("UPDATE ibf_templates SET $wrap_string WHERE tmid='{$next_id['wrap']}'");
			
		}
		else
		{
			$this->import_error("Could not read the uploaded board wrapper archive file, please check the permissions on that file and try again", $next_id);
		}
		
		//------------------------------------------------------
		// Attempt to copy over the image files
		//------------------------------------------------------
		
		if (! $ADMIN->copy_dir($this->work_path."/".$new_dir."/images", $images_dir."/".$next_id['images']) )
		{
			$this->import_error("Could not import images, terminating the import", $next_id);
		}
		
		//------------------------------------------------------
		// Import the Macro's
		//------------------------------------------------------
		
		if ($FH = fopen( $this->work_path."/".$new_dir."/macro.txt", 'r' ) )
		{
			$data = fread($FH, filesize($this->work_path."/".$new_dir."/macro.txt") );
			fclose($FH);
		
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
	
			foreach( $final_keys as $k => $v)
			{
				if ($v == '*UNASSIGNED*')
				{
					$v = "";
				}
				
				$str = $DB->compile_db_insert_string( array (
															'macro_value'   => stripslashes($k),
															'macro_replace' => stripslashes($v),
															'macro_set'     => $next_id['macro'],
															'can_remove'    => 1,
												)       );
			
				$DB->query("INSERT INTO ibf_macro ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
			}
			
			// Add the macro name
			
			$DB->query("INSERT INTO ibf_macro_name SET set_id='".$next_id['macro']."', set_name='".$this->name_translated."'");
			
		}
		else
		{
			$this->import_error("Could not read the macro.txt file contained in the skin you're importing.", $next_id);
		}
		
		$tmpl_string = $DB->compile_db_update_string( $template_config );
						   
		$DB->query("UPDATE ibf_tmpl_names SET $tmpl_string WHERE skid='{$next_id['templates']}'");
		
		//------------------------------------------------------
		// Import the TEMPLATES - wohoo, this'll make the server burn
		//------------------------------------------------------
		
		if ($FH = fopen( $this->work_path."/".$new_dir."/templates.html", 'r' ) )
		{
			$data = fread($FH, filesize($this->work_path."/".$new_dir."/templates.html") );
			fclose($FH);
			
			//-------------------------------------------------
			// Divide the file up into different sections
			//-------------------------------------------------
			
			preg_match_all( "/<!--IBF_GROUP_START:(\S+?)-->(.+?)<!--IBF_GROUP_END:\S+?-->/s", $data, $match );
			
			for ($i=0; $i < count($match[0]); $i++)
			{
				$match[1][$i] = trim($match[1][$i]);
				
				$match[2][$i] = trim($match[2][$i]);
				
				// Pass it on to our handler..
			
				$this->process_template_group($match[2][$i], $next_id['templates'], $match[1][$i], 1 );
			}
		}
		else
		{
			$this->import_error("Could not read the templates.html file contained in the skin you're importing.", $next_id);
		}
		
		//------------------------------------------------------
		// Add a new row to the skins table.
		//------------------------------------------------------
		
		$DB->query("SELECT MAX(sid) as new_id FROM ibf_skins");
			
		$set = $DB->fetch_row();
		
		$set['new_id']++;
		
		$new_name = stripslashes( $this->name_translated )." (Import)".$set['new_id'];
		
		$skin_string = $DB->compile_db_insert_string( array( 'sname'       => $new_name,
															 'sid'         => $set['new_id'],
															 'set_id'      => $next_id['templates'],
															 'tmpl_id'     => $next_id['wrap'],
															 'img_dir'     => $next_id['images'],
															 'css_id'      => $next_id['css'],
															 'macro_id'    => $next_id['macro'],
															 'hidden'      => 0,
															 'default_set' => 0
												    )      );
							
		$DB->query("INSERT INTO ibf_skins (".$skin_string['FIELD_NAMES'].") VALUES(".$skin_string['FIELD_VALUES'].")");
		
		$ADMIN->rm_dir($this->work_path."/".$new_dir);
		
		$ADMIN->done_screen("Skin set Imported", "Manage Skin sets", "act=sets" );
		
	}
	
	
	//------------------------------------------------------
	//process the template group
	//------------------------------------------------------
	
	
	function process_template_group($raw, $setid, $group, $isnew=0)
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$root_path = $INFO['base_dir'];
		
		$skin_dir  = $root_path."Skin/s".$setid;
		
		//-------------------------------------------
		// If we are not using safe mode skins, lets
		// test to make sure we can write to that dir
		//-------------------------------------------
		
		if ($INFO['safe_mode_skins'] != 1)
		{
		
			if (SAFE_MODE_ON == 1)
			{
				if ($isnew == 1)
				{
					$DB->query("DELETE FROM ibf_tmpl_names WHERE skid='$setid'");
				}
				$ADMIN->error("Safe mode detected, you will need to change the board configuration to switch 'Safe Mode Skins' on. To do this, click on the 'Board Settings' menu and choose 'Basic Config' when the sub menu appears.");
			}
			
			// Are we creating a new template set? 
			// if so, lets create the directory
			
			if ($isnew == 1)
			{
				if ( ! is_writeable($root_path.'Skin') )
				{	
					$DB->query("DELETE FROM ibf_tmpl_names WHERE skid='$setid'");
					$ADMIN->error("The directory 'Skin' is not writeable by this script. Please check the permissions on that directory. CHMOD to 0777 if in doubt and try again");
				}
				
				/*if ( ! file_exists($skin_dir) )
				{
					// Directory does not exist, lets create it
					
					if ( ! @mkdir($skin_dir, 0777) )
					{
						$DB->query("DELETE FROM ibf_tmpl_names WHERE skid='$setid'");
						$ADMIN->error("Could not create directory '$skin_dir' please check the CHMOD permissions and re-try");
					}
					else
					{
						@chmod($skin_dir, 0777);
					}
				}*/
			}
			else
			{
				if ( ! is_writeable($skin_dir) )
				{
					$ADMIN->error("Cannot write into '$skin_dir', please check the CHMOD value, and if needed, CHMOD to 0777 via FTP. IBF cannot do this for you.");
				}
			}
		
		}
		
		//--------------------------------
		// Remove everything up until the
		// first <!--START tag...
		//--------------------------------
		
		$raw = preg_replace( "/^.*?(<!--IBF_START_FUNC)/s", "\\1", trim($raw));
		
		$raw = str_replace( "\r\n", "\n", $raw);
		
		//+-------------------------------
		// Convert the tags back to php native
		//+-------------------------------
		
		$raw = $this->unconvert_tags($raw);
		
		//+-------------------------------
		// Grab our vars and stuff
		//+-------------------------------
		
		$master = array();
		$flag   = 0;
		
		$eachline = explode( "\n", $raw );
		
		foreach ($eachline as $line)
		{
			if ($flag == 0)
			{
				// We're not gathering HTML, lets see if we have a new
				// function start..
				
				if ( preg_match( "/\s*<!--IBF_START_FUNC\|(\S+?)\|(.*?)-->\s*/", $line, $matches) )
				{
					$func = trim($matches[1]);
					$data = trim($matches[2]);
					
					if ($func != "")
					{
					
						$flag = $func;
						
						$master[$func] = array( 'func_name'  => $func,
												'func_data'  => $data,
												'content'    => ""
											  );
					}
					continue;
					
				}
				
			}
			
			if ( preg_match("/\s*?<!--IBF_END_FUNC\|$flag-->\s*?/", $line) )
			{
				 // We have found the end of the subbie..
				 // Reset the flag and feed the next line.
				 
				 $flag = 0;
				 continue;
			}
			else
			{
				// Carry on feeding the HTML...
				
				if ( isset($master[$flag]['content']) )
				{
					$master[$flag]['content'] .= $line."\n";
					continue;
				}
			}
			
		}
		
		//+-------------------------------
		// Start parsing the php skin file
		//+-------------------------------
		
		if ($INFO['safe_mode_skins'] != 1)
		{
		
			if (SAFE_MODE_ON == 1)
			{
				$ADMIN->error("Safe mode detected, you will need to change the board configuration to switch 'Safe Mode Skins' on. To do this, click on the 'Board Settings' menu and choose 'Basic Config' when the sub menu appears.");
			}
		
			$final = "<"."?php\n\n".
					 "class $group {\n\n";
			
			foreach( $master as $func_name => $data )
			{
			
				$final .= "\n\nfunction ".trim($data['func_name'])."(".trim($data['func_data']).") {\n".
					   	  "global \$ibforums;\n".
					   	  "return <<<EOF\n";
					   	  
			    $final .= trim($data['content']);
					   
				$final .= "\nEOF;\n}\n";
				
			}
			
			$final .= "\n\n}\n?".">";
			
			if ($fh = fopen( $skin_dir."/".$group.".php", 'w' ) )
			{
				fwrite($fh, $final, strlen($final) );
				fclose($fh);
				
				@chmod( $skin_dir."/".$group.".php", 0777 );
			}
			else
			{
				if ($isnew == 1)
				{
					$DB->query("DELETE FROM ibf_tmpl_names WHERE skid='$setid'");
				}
				$errors[] = "Could not save information to $phpskin, please ensure that the CHMOD permissions are correct.";
			}
		
		}
		
		//+-------------------------------
		// Update the DB
		//+-------------------------------
		
		
		foreach( $master as $func_name => $data )
		{
		
			if ($isnew == 0)
			{
				$str = $DB->compile_db_update_string( array(
															  'section_content' => stripslashes(trim($data['content'])),
															  'func_data'       => stripslashes(trim($data['func_data']))
													)      );
													
				$DB->query("UPDATE ibf_skin_templates SET $str WHERE set_id='$setid' AND group_name='$group' AND func_name='".trim($data['func_name'])."'");
			}
			else
			{
				$str = $DB->compile_db_insert_string( array(
															  'section_content' => stripslashes(trim($data['content'])),
															  'func_data'       => stripslashes(trim($data['func_data'])),
															  'set_id'          => $setid,
															  'group_name'      => $group,
															  'func_name'       => trim($data['func_name']),
															  'can_remove'      => 0,
													)      );
													
				$DB->query("INSERT INTO ibf_skin_templates ({$str['FIELD_NAMES']}) VALUES({$str['FIELD_VALUES']})");
			}
		}
		
		
		return TRUE;
		
		
	}
	
	
	
	//-------------------------------------------------------------------
	
	//-------------------------------------------------------------------
	
	function template_import()
	{
		// Depreciated
		
	}
	
	
	//-------------------------------------------------------------------
	
	function image_import()
	{
		// Depreciated
		
	}
	
	//-------------------------------------------------------------------
	
	//-------------------------------------------------------------------
	
	function wrap_import()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		// Depreciated
		
	}
	
	//-------------------------------------------------------------------
	
	
	function css_import()
	{
		// Depreciated
		
	}
	
	
	
	
	//----------------------------------------------------
	
	function check_archive($files)
	{
		if (count($files) > 0)
		{
			foreach($files as $giles)
			{
				if ( ! preg_match( "/^(?:[\(\)\:\;\~\.\w\d\+\-\_\/]+)$/", $giles) )
				{
					return FALSE;
				}
			}
		}
		else
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	//-------------------------------------------------------------
	// SHOW ALL LANGUAGE PACKS
	//-------------------------------------------------------------
	
	function list_current()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
	
		$ADMIN->page_detail = "You can select which archives to import onto your board in this section. All archives must be uploaded into the 'archive_in' directory";
		$ADMIN->page_title  = "Import Skin Archive Manager";
		
		
		
		//+-------------------------------
		
		$files = array();
		
		$dir = $INFO['base_dir']."/archive_in";
			
		if ( is_dir($dir) )
		{
			$handle = opendir($dir);
			
			while (($filename = readdir($handle)) !== false)
			{
				if (($filename != ".") && ($filename != ".."))
				{
					if (preg_match("/^(css|image|set|wrap|tmpl).+?\.(tar|html|css)$/", $filename))
					{
						$files[] = $filename;
					}
				}
			}
			
			closedir($handle);
			
		}
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Name"         , "30%" );
		$SKIN->td_header[] = array( "Type"         , "20%" );
		$SKIN->td_header[] = array( "File Name"     , "30%" );
		$SKIN->td_header[] = array( "Import"       , "10%" );
		$SKIN->td_header[] = array( "Remove"       , "10%" );
		
		$ADMIN->html .= $SKIN->start_table( "Current Archives Uploaded" );
		
		if ( count($files) > 0 )
		{
			foreach($files as $file)
			{
			
				$type = array( 'css'   => 'Style Sheet',
							   'image' => 'Image & Macro set',
							   'set'   => 'Skin Set Collection',
							   'wrap'  => 'Board Wrapper',
							   'tmpl'  => 'Template set'
							 );
							 
				$rtype = preg_replace( "/^(css|image|set|wrap|tmpl).+?\.(\S+)$/"  , "\\1", $file );
				
				$rname = preg_replace( "/^(css|image|set|wrap|tmpl)-(.+?)\.(\S+)$/", "\\2", $file );
				
				$rname = preg_replace( "/_/", " ", $rname );
				
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>$rname</b>",
														  "<center>{$type[$rtype]}</center>",
														  "<center>$file</center>",
														  "<center><a href='".$SKIN->base_url."&act=import&code=import&type=$rtype&id=$file'>Import</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=import&code=remove&id=$file'>Remove</a></center>",
												 )      );
			}
			
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	function unconvert_tags($t="")
	{
		if ($t == "")
		{
			return "";
		}
		
		// Make some tags safe..
		
		$t = preg_replace( "/\{ibf\.vars\.(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)\}/", "" , $t );
		
		
		$t = preg_replace( "/{ibf\.script_url}/i"   , '{$ibforums->base_url}'         , $t);
		$t = preg_replace( "/{ibf\.session_id}/i"   , '{$ibforums->session_id}'       , $t);
		$t = preg_replace( "/{ibf\.skin\.(\w+)}/"   , '{$ibforums->skin[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.lang\.(\w+)}/"   , '{$ibforums->lang[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.vars\.(\w+)}/"   , '{$ibforums->vars[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.member\.(\w+)}/" , '{$ibforums->member[\''."\\1".'\']}' , $t);
		
		return $t;
		
	}
	
	function rebuild_phpskin($templates_dir, $skins_dir)
	{
	
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$errors = array();
		
		require $templates_dir."/config.php";
		
		if ( $handle = opendir($templates_dir) )
		{
		
			while (($filename = readdir($handle)) !== false)
			{
				if (($filename != ".") && ($filename != ".."))
				{
					if ( preg_match( "/^index\./", $filename ) )
					{
						continue;
					}
					
					//-------------------------------------------
				
					if ( preg_match( "/\.html$/", $filename ) )
					{
						
						$name = preg_replace( "/\.html$/", "", $filename );
						
						
						if ($FHD = fopen($templates_dir."/".$filename, 'r') )
						{
							$text = fread($FHD, filesize($templates_dir."/".$filename) );
							fclose($FHD);
						}
						else
						{
							$errors[] = "Could not open $filename, skipping file...";
							continue;
						}
						
						//----------------------------------------------------
						
						$need = count($skin[$name]);
						$start = 0;
						$end   = 0;
						
						if ($need < 1)
						{
							$errors[] = "Error recalling function data for $filename, skipping...";
							continue;
						}
	
						// check to make sure the splitter tags are intact
						
						foreach($skin[$name] as $func_name => $data)
						{
							if ( preg_match("/<!--\|IBF\|$func_name\|START\|-->/", $text) )
							{
								$start++;
							}
							
							//+-------------------------------
							
							if ( preg_match("/<!--\|IBF\|$func_name\|END\|-->/", $text) )
							{
								$end++;
							}
						}
						
						if ($start != $end)
						{
							$errors[] = "Some start or end template splitter comments are missing in $filename, skipping file....";
							continue;
						}
						
						if ($start != $need)
						{
							$errors[] = "Some template splitter comments are missing in $filename, skipping file...";
							continue;
						}
						
						//+-------------------------------
						// Convert the tags back to php native
						//+-------------------------------
						
						$text = $this->unconvert_tags($text);
						
						//+-------------------------------
						// Start parsing the php skin file
						//+-------------------------------
						
						$final = "<"."?php\n\n".
								 "class $name {\n\n";
						
						foreach($skin[$name] as $func_name => $data)
						{
						
							$top = "\n\nfunction $func_name($data) {\n".
								   "global \$ibforums;\n".
								   "return <<<EOF\n";
								   
							$bum = "\nEOF;\n}\n";
						
							$text = preg_replace("/\s*<!--\|IBF\|$func_name\|START\|-->\s*\n/", "$top", $text);
							
							
							//+-------------------------------
							
							$text = preg_replace("/\s*<!--\|IBF\|$func_name\|END\|-->\s*\n/", "$bum", $text);
						}
						
						$end = "\n\n}\n?".">";
						
						$final .= $text.$end;
						
						if ($fh = fopen( $skins_dir."/".$name.".php", 'w' ) )
						{
							fwrite($fh, $final, strlen($final) );
							fclose($fh);
							@chmod( $skins_dir."/".$name.".php", 0777 );
						}
						else
						{
							$errors[] = "Could not save information to $phpskin, please ensure that the CHMOD permissions are correct.";
						}
						
						$end   = "";
						$final = "";
						$top   = "";
						
					} // if *.php
					
				} // if not dir
				
			} // while loop
			
			closedir($handle);
			
		}
		else
		{
			$errors[] = "Could not open the templates directory!";
		}
		
		return $errors;
	
	}
	
	
	function import_error( $error, $next_id)
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$DB->query("DELETE FROM ibf_macro_name WHERE set_id='{$next_id['macro']}'");
		$DB->query("DELETE FROM ibf_macro WHERE macro_id='{$next_id['macro']}'");
		$DB->query("DELETE FROM ibf_tmpl_names WHERE skid='{$next_id['templates']}'");
		$DB->query("DELETE FROM ibf_css WHERE cssid='{$next_id['css']}'");
		$DB->query("DELETE FROM ibf_templates WHERE tmid='{$next_id['wrap']}'");
		$DB->query("DELETE FROM ibf_skin_templates WHERE set_id='{$next_id['templates']}'");
		
		@rmdir($INFO['base_dir']."/style_images/".$next_id['images']);
		@rmdir($INFO['base_dir']."/Skin/s".$next_id['templates']);
		
		$ADMIN->rm_dir($this->work_path."/".$this->new_dir);
		
		$ADMIN->error( $error );
	
	}
	
	
}


?>