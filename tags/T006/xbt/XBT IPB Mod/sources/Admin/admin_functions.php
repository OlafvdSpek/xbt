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
|   > Admin functions library
|   > Script written by Matt Mecham
|   > Date started: 1st march 2002
|
+--------------------------------------------------------------------------
*/


class admin_functions {

	var $img_url;
	var $page_title  = "Welcome to the Invision Power Board Administration CP";
	var $page_detail = "You can set up and customize your board from within this control panel.<br><br>Clicking on one of the links in the left menu pane will show you the relevant options for that administration category. Each option will contain further information on configuration, etc.";
	var $html;
	var $errors = "";
	var $nav    = array();
	var $time_offset = 0;
	var $jump_menu = "";
	var $no_jump = 0;

	function admin_functions() {
		global $INFO, $IN;
		
		$this->img_url = $INFO['html_url'].'/sys-img';
		$this->base_url = $INFO['board_url']."/admin.".$INFO['php_ext']."?adsess=".$IN['AD_SESS'];
		
	}
	
	//---------------------------
	// Makes good raw form text
	//----------------------------
	
	function make_safe($t)
	{
		$t = stripslashes($t);
		
		$t = preg_replace( "/\\\/", "&#092;", $t );
		
		return $t;
	}
	
	//---------------------------
	// Sets up time offset for ACP use
	//----------------------------
	
	
	function get_date($date="", $method="")
	{
		global $INFO, $IN, $MEMBER;
		
		$this->time_options = array( 'JOINED' => $INFO['clock_joined'],
									 'SHORT'  => $INFO['clock_short'],
									 'LONG'   => $INFO['clock_long']
								   );
								   
		if (!$date)
        {
            return '--';
        }
        
        if (empty($method))
        {
        	$method = 'LONG';
        }
        
        $this->time_offset = (($MEMBER['time_offset'] != "") ? $MEMBER['time_offset'] : $INFO['time_offset']) * 3600;
			
		if ($INFO['time_adjust'] != "" and $INFO['time_adjust'] != 0)
		{
			$this->time_offset += ($INFO['time_adjust'] * 60);
		}
		
		if ($MEMBER['dst_in_use'])
		{
			$this->time_offset += 3600;
		}
        
        return gmdate($this->time_options[$method], ($date + $this->time_offset) );
		
		
		
	}
	
	//**********************************************/
	// save_log
	//
	// Add an entry into the admin logs, yeah.
	//**********************************************/
	
	function save_log($action="")
	{
		global $INFO, $DB, $IN, $MEMBER;
		
		$str = $DB->compile_db_insert_string( array(
													  'act'        => $IN['act'],
													  'code'       => $IN['code'],
													  'member_id'  => $MEMBER['id'],
													  'ctime'      => time(),
													  'note'       => $action,
													  'ip_address' => $IN['IP_ADDRESS'],
											)       );
											
		$DB->query("INSERT INTO ibf_admin_logs ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		return true;  // to anyone that cares..
		
	}
	
	
	//**********************************************/
	// get_tar_names
	//
	// Simply returns a list of tarballs that start
	// with the given filename
	//**********************************************/
	
	function get_tar_names($start='lang-')
	{
		global $INFO;
		
		// Remove trailing slashes..
		
		$files = array();
		
		$dir = $INFO['base_dir']."archive_in";
			
		if ( is_dir($dir) )
		{
			$handle = opendir($dir);
			
			while (($filename = readdir($handle)) !== false)
			{
				if (($filename != ".") && ($filename != ".."))
				{
					if (preg_match("/^$start.+?\.tar$/", $filename))
					{
						$files[] = $filename;
					}
				}
			}
			
			closedir($handle);
			
		}
		
		return $files;
			
	}
	
	//**********************************************/
	// copy_dir
	//
	// Copies to contents of a dir to a new dir, creating
	// destination dir if needed.
	//
	//**********************************************/
	
	function copy_dir($from_path, $to_path, $mode = 0777)
	{
	
		global $INFO;
		
		// Strip off trailing slashes...
		
		$from_path = preg_replace( "#/$#", "", $from_path);
		$to_path   = preg_replace( "#/$#", "", $to_path);
	
		if ( ! is_dir($from_path) )
		{
			$this->errors = "Could not locate directory '$from_path'";
			return FALSE;
		}
	
		if ( ! is_dir($to_path) )
		{
			if ( ! @mkdir($to_path, $mode) )
			{
				$this->errors = "Could not create directory '$to_path' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod($to_path, $mode);
			}
		}
		
		$this_path = getcwd();
		
		if (is_dir($from_path))
		{
			chdir($from_path);
			$handle=opendir('.');
			while (($file = readdir($handle)) !== false)
			{
				if (($file != ".") && ($file != ".."))
				{
					if (is_dir($file))
					{
						
						$this->copy_dir($from_path."/".$file, $to_path."/".$file);
						
						chdir($from_path);
					}
					
					if ( is_file($file) )
					{
						copy($from_path."/".$file, $to_path."/".$file);
						@chmod($to_path."/".$file, 0777);
					} 
				}
			}
			closedir($handle); 
		}
		
		if ($this->errors == "")
		{
			return TRUE;
		}
	}
	
	//**********************************************/
	// rm_dir
	//
	// Removes directories, if non empty, removes
	// content and directories
	// (Code based on annotations from the php.net
	// manual by pal@degerstrom.com)
	//**********************************************/
	
	function rm_dir($file)
	{
		global $INFO;
		
		$errors = 0;
		
		// Remove trailing slashes..
		
		$file = preg_replace( "#/$#", "", $file );
		
		if ( file_exists($file) )
		{
			// Attempt CHMOD
			
			@chmod($file, 0777);
			
			if ( is_dir($file) )
			{
				$handle = opendir($file);
				
				while (($filename = readdir($handle)) !== false)
				{
					if (($filename != ".") && ($filename != ".."))
					{
						$this->rm_dir($file."/".$filename);
					}
				}
				
				closedir($handle);
				
				if ( ! @rmdir($file) )
				{
					$errors++;
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if ($errors == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	//**********************************************/
	// rebuild_config:
	//
	// Er, rebuilds the config file
	//
	//**********************************************/
	
	function rebuild_config( $new = "" )
	{
		global $IN, $std, $ADMIN, $root_path;
		
		//-----------------------------------------
		// Check to make sure this is a valid array
		//-----------------------------------------
		
		if (! is_array($new) )
		{
			$ADMIN->error("Error whilst attempting to rebuild the board config file, attempt aborted");
		}
		
		//-----------------------------------------
		// Do we have anything to save out?
		//-----------------------------------------
		
		if ( count($new) < 1 )
		{
			return "";
		}
		
		//-----------------------------------------
		// Get an up to date copy of the config file
		// (Imports $INFO)
		//-----------------------------------------
		
		require ROOT_PATH.'conf_global.php';
		
		//-----------------------------------------
		// Rebuild the $INFO hash
		//-----------------------------------------
		
		foreach( $new as $k => $v )
		{
			// Update the old...
			
			$v = preg_replace( "/'/", "\\'" , $v );
			$v = preg_replace( "/\r/", ""   , $v );
			
			$INFO[ $k ] = $v;
		}	
		
		//-----------------------------------------
		// Rename the old config file
		//-----------------------------------------
		
		@rename( ROOT_PATH.'conf_global.php', ROOT_PATH.'conf_global-bak.php' );
		@chmod( ROOT_PATH.'conf_global-bak.php', 0777);
		
		//-----------------------------------------
		// Rebuild the old file
		//-----------------------------------------
		
		ksort($INFO);
		
		$file_string = "<?php\n";
		
		foreach( $INFO as $k => $v )
		{
			if ($k == 'skin' or $k == 'languages')
			{
				// Protect serailized arrays..
				$v = stripslashes($v);
				$v = addslashes($v);
			}
			
			$file_string .= '$INFO['."'".$k."'".']'."\t\t\t=\t'".$v."';\n";
		}
		
		$file_string .= "\n".'?'.'>';   // Question mark + greater than together break syntax hi-lighting in BBEdit 6 :p
		
		if ( $fh = fopen( ROOT_PATH.'conf_global.php', 'w' ) )
		{
			fwrite($fh, $file_string, strlen($file_string) );
			fclose($fh);
		}
		else
		{
			$ADMIN->error("Fatal Error: Could not open conf_global for writing - no changes applied. Try changing the CHMOD to 0777");
		}
		
		// Pass back the new $INFO array to anyone who cares...
		
		return $INFO;
		
	}
	
	//**********************************************/
	// compile_forum_perms:
	//
	// Returns the READ/REPLY/START DB strings
	//
	//**********************************************/
	
	
	function compile_forum_perms() {
		global $DB, $IN;
		
		$r_array = array( 'READ' => '', 'REPLY' => '', 'START' => '', 'UPLOAD' => '' );
		
		if ($IN['READ_ALL'] == 1)
		{
			$r_array['READ'] = '*';
		}
		
		if ($IN['REPLY_ALL'] == 1)
		{
			$r_array['REPLY'] = '*';
		}
		
		if ($IN['START_ALL'] == 1)
		{
			$r_array['START'] = '*';
		}
		
		if ($IN['UPLOAD_ALL'] == 1)
		{
			$r_array['UPLOAD'] = '*';
		}
		
		
		$DB->query("SELECT perm_id, perm_name FROM ibf_forum_perms ORDER BY perm_id");
			 
		while ( $data = $DB->fetch_row() )
		{
			if ($r_array['READ'] != '*')
			{
				if ($IN[ 'READ_'.$data['perm_id'] ] == 1)
				{
					$r_array['READ'] .= $data['perm_id'].",";
				}
			}
			//+----------------------------
			if ($r_array['REPLY'] != '*')
			{
				if ($IN[ 'REPLY_'.$data['perm_id'] ] == 1)
				{
					$r_array['REPLY'] .= $data['perm_id'].",";
				}
			}
			//+----------------------------
			if ($r_array['START'] != '*')
			{
				if ($IN[ 'START_'.$data['perm_id'] ] == 1)
				{
					$r_array['START'] .= $data['perm_id'].",";
				}
			}
			//+----------------------------
			if ($r_array['UPLOAD'] != '*')
			{
				if ($IN[ 'UPLOAD_'.$data['perm_id'] ] == 1)
				{
					$r_array['UPLOAD'] .= $data['perm_id'].",";
				}
			}
		}
		
		$r_array['START']   = preg_replace( "/,$/", "", $r_array['START']   );
		$r_array['REPLY']   = preg_replace( "/,$/", "", $r_array['REPLY']   );
		$r_array['READ']    = preg_replace( "/,$/", "", $r_array['READ']    );
		$r_array['UPLOAD']  = preg_replace( "/,$/", "", $r_array['UPLOAD']  );
		
		return $r_array;
		
	}
	
	
	//+------------------------------------------------
	//+------------------------------------------------
	// OUTPUT FUNCTIONS
	//+------------------------------------------------
	//+------------------------------------------------
	
	function print_popup() {
		global $IN, $INFO, $DB, $std, $SKIN, $use_gzip;
	
		$html = "<html>
		          <head><title>Remote</title>
		          <meta HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\">
				  <meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">
				  <meta HTTP-EQUIV=\"Expires\" CONTENT=\"Mon, 06 May 1996 04:57:00 GMT\">";
		
		$html .= $SKIN->get_css();
		
		$html .= "</head>\n";
		
		$html .= "</head>
				  <body marginheight='0' marginwidth='0' leftmargin='0' topmargin='0' bgcolor='#FFFFFF'>
				  ";
						 
		$html .= $this->html;
		
		$html .= "</body></html>";
		
		print $html;
		
		exit();
	}
					   
		
	
	
	function output() {
		global $IN, $INFO, $DB, $std, $SKIN, $use_gzip;
	
		$html  = $SKIN->print_top($this->page_title, $this->page_detail);
		$html .= $this->html;
		$html .= $SKIN->print_foot();
		
		$DB->close_db();
		
		$navigation = array( "<a href='{$this->base_url}&act=index' target='body'>ACP Home</a>" );
		
		if ( count($this->nav) > 0 )
		{
			foreach ( $this->nav as $idx => $links )
			{
				if ($links[0] != "")
				{
					$navigation[] = "<a href='{$this->base_url}&{$links[0]}' target='body'>{$links[1]}</a>";
				}
				else
				{
					$navigation[] = $links[1];
				}
			}
		}
		
		if ( count($navigation) > 0 )
		{
			$html = str_replace( "<!--NAV-->", $SKIN->wrap_nav( implode( " -&gt; ", $navigation ) ), $html );
		}
		
		if ( $this->no_jump != 1 )
		{
			$html = str_replace( "<!--JUMP-->", $this->build_jump_menu(), $html );
		}
		
		if ($use_gzip == 1)
		{
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	ob_start('ob_gzhandler');
        	print $buffer;
    	}
    	
    	//@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		//@header("Cache-Control: no-cache, must-revalidate");
		//@header("Pragma: no-cache");
				
    	print $html;
    	
    	exit();
	
	}
	
	//**********************************************/
	// Error:
	//
	// Displays an error
	//
	//**********************************************/
	
	function error($error="", $is_popup=0) {
		global $IN, $INFO, $DB, $std, $SKIN, $HTTP_REFERER;
		
		$this->page_title  = "Admin CP Message";
		$this->page_detail = "&nbsp;";
		
		$this->html .= "<div class='tableborder'>
		                 <div class='maintitle'>Admin CP Message</div>
		                 <div class='tdrow1' style='padding:8px'>
						  <span style='font-size:12px'>$error</span>
						 </div>
						</div>";
		
		if ($is_popup == 0)
		{
			$this->output();
		}
		else
		{
			$this->print_popup();
		}
		
	}
	
	//**********************************************/
	// Done Screen:
	//
	// Displays the "done" screen. Really? Yes.
	//
	//**********************************************/
	
	function done_screen($title, $link_text="", $link_url="") {
		global $IN, $INFO, $DB, $std, $SKIN;
		
		$this->page_title  = $title;
		$this->page_detail = "The action was executed successfully";
		
		$SKIN->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->html .= $SKIN->start_table("Result");
		
		$this->html .= $SKIN->add_td_basic( "<a href='{$this->base_url}&{$link_url}' target='body'>Go to: $link_text</a>", "center" );
		
		$this->html .= $SKIN->add_td_basic( "<a href='{$this->base_url}&act=index' target='body'>Go to: Administration Home</a>", "center" );
										 
		$this->html .= $SKIN->end_table();
			
		$this->output();
	
	}
	
	function info_screen($text="", $title='Safe Mode Restriction Warning') {
		global $IN, $INFO, $DB, $std, $SKIN;
		
		$this->page_title  = $title;
		$this->page_detail = "Please note the following:";
		
		$SKIN->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->html .= $SKIN->start_table("Result");
		
		$this->html .= $SKIN->add_td_basic( $text );
		
		$this->html .= $SKIN->add_td_basic( "<a href='{$this->base_url}&act=index' target='body'>Go to: Administration Home</a>", "center" );
										 
		$this->html .= $SKIN->end_table();
			
		$this->output();
	
	}
	
	
	//**********************************************/
	// MENU:
	//
	// Build the collapsable menu trees
	//
	//**********************************************/
	
	function menu() {
		global $IN, $std, $INFO, $PAGES, $CATS, $SKIN;
		
		$links = $this->build_tree();
		
		$html = $SKIN->menu_top() . $links . $SKIN->menu_foot();
		
		// Saving cookie?
		
		if ( $INFO['menu'] )
		{
			$std->my_setcookie( 'acpmenu', $IN['show'] );
		}
		
		print $html;
		exit();

		
	}
	
	//+------------------------------------------------
	
	function build_tree() {
		global $IN, $std, $INFO, $PAGES, $CATS, $SKIN, $DESC;
		
		$html  = "";
		$links = "";
		
		
		foreach($CATS as $cid => $name)
		{
			
			$ADMIN->jump_menu .= "<optgroup label='$name'>\n";
			
			if ( preg_match( "/(?:^|,)$cid(?:,|$)/", $IN['show'] ) )
			{
			
				foreach($PAGES[ $cid ] as $pid => $pdata)
				{
					if ( $pdata[2] != "" )
					{
						if ( ! @is_dir( ROOT_PATH.$pdata[2] ) )
				  		{
					  		continue;
					  	}
					}
					
					$links .= $SKIN->menu_cat_link($pdata[1], $pdata[0], $pdata[3]);
				}
				  
				$html .= $SKIN->menu_cat_expanded( $name, $links, $cid );
				unset($links);
			}
			else
			{
				$html .= $SKIN->menu_cat_collapsed( $name, $cid, $DESC[ $cid ] );
			}
			
			$ADMIN->jump_menu .= "</optgroup>\n";
		}
		
		return $html;
		
	}
	
	//-------------------------------------------------------------
	// BUILDS JUMP MENU, yay!
	//-------------------------------------------------------------
	
	function build_jump_menu()
	{
		global $IN, $std, $PAGES, $CATS, $SKIN, $DESC;
		
		$html = "<script type='text/javascript'>
				 function dojump()
				 {
				 	if ( document.jumpmenu.val.options[document.jumpmenu.val.selectedIndex].value != '' )
				 	{
				 		window.location.href = '{$this->base_url}' + '&' + document.jumpmenu.val.options[document.jumpmenu.val.selectedIndex].value;
				 	}
				 }
				 </script>
				 ";
		
		$html .= "<form name='jumpmenu'>\n<select class='jmenu' name='val'>";
		
		foreach($CATS as $cid => $name)
		{
			$html .= "<optgroup label='$name'>\n";
			
			foreach($PAGES[ $cid ] as $pid => $pdata)
			{
				$html .= "<option value='$pdata[1]'>$pdata[0]</option>\n";
			}
			
			$html .= "</optgroup>\n";
		}
		
		$html .= "</select>&nbsp;<input type='button' class='jmenubutton' value='Go!' onclick='dojump();' />\n</form>";
		
		return $html;
		
	}
	
}





?>