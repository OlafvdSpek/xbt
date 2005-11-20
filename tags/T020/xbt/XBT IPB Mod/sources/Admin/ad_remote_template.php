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
|   > Skin -> Templates pop up functions
|   > Module written by Matt Mecham
|   > Date started: 9th July 2002
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
			case 'preview':
				$this->do_preview();
				break;
				
			case 'edit_bit':
				$this->edit_bit();
				break;
				
			case 'macro_one':
				$this->macro_one();
				break;
				
			case 'macro_two':
				$this->macro_two();
				break;
				
			case 'compare':
				$this->compare_frames();
				break;
				
			case 'dotop':
				$this->print_compare_top();
				break;
				
			case 'donew':
				$this->print_compare_new();
				break;
			
			//-----------------------------
			
			case 'search':
				$this->search_frames();
				break;
				
			case 'searchbox':
				$this->print_search_box();
				break;
				
			case 'searchlinks':
				$this->print_searchlinks();
				break;
			
			//-----------------------------
			
			case 'css_search':
				$this->css_search_frames();
				break;
				
			case 'csssearchlinks':
				$this->print_css_searchlinks();
				break;
				
			case 'previewstate':
				$this->do_message("Preview Window");
				break;
				
			//-------------------------
			
			case 'css_preview':
				$this->css_preview();
				break;
				
			default:
				exit();
				break;
		}
		
	}
	
	//+---------------------------------------
	
	function css_search_frames()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_GET_VARS;
		
		print "<html>
				 <head><title>Search</title></head>
				   <frameset cols='200, *' frameborder='no' border='1' framespacing='0'>
					<frame name='links' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=csssearchlinks&id={$IN['id']}&element={$HTTP_GET_VARS['element']}'>
					<frame name='preview' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=previewstate'>
				   </frameset>
			   </html>";
			   
		exit();
	}
	
	//+---------------------------------------
	
	function css_preview()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_GET_VARS;
		
		//+---------------------------------------
		// GET THE TEMPLATES THAT THIS CSS USES
		//+---------------------------------------
		
		$DB->query("SELECT css_text, css_name FROM ibf_css WHERE cssid='".$IN['id']."'");
		
		if ( ! $set = $DB->fetch_row() )
		{
			$ADMIN->error("Cannot query the database using that information");
		}
		
		$DB->query("SELECT img_dir FROM ibf_skins WHERE css_id='".$IN['id']."'");
		
		$skin = $DB->fetch_row();
		
		$element = trim(stripslashes($HTTP_GET_VARS['element']));
		
		
		list($type, $name) = explode( "|", $element );
		
		$like = "class='{$name}'";
		$first = '.';
		
		if ($type == 'id')
		{
			$like = "id='{$name}'";
			$first = '#';
		}
		
		preg_match( "/($first"."$name)\s{0,}\{(.+?)\}/s", $set['css_text'], $match );
		
		//$definition = nl2br( str_replace( "\n\n", "\n", str_replace( "\r\n", "\n", $match[2]) ) );
		
		//preg_match_all( "/(\S+?):(.+?);?/s", trim($match[2]), $defs );
		
		$defs = explode( ";", str_replace( "\n\n", "\n", str_replace( "\r\n", "\n", trim($match[2]) ) ) );
		
		$def_output = "";
		
		foreach($defs as $bit)
		{
			list($type, $value) = explode( ":", trim($bit) );
			
			$type = trim($type);
			
			$value = trim($value);
			
			if ($type != "" and $value != "")
			{
			    $extra = "";
			    
				if ($type == 'color' or $type == 'background-color')
				{
					$extra = "&nbsp;&nbsp;&nbsp;<input type='text' size='6' style='background-color:$value' readonly>";
				}
			
				$def_output .= "<tr><td width='40%'><b>$type</b></td><td width='60%'>$value $extra</td></tr>\n";
			}
		}
	
		$css = "\n<style>\n<!--\n".str_replace( "<#IMG_DIR#>", $skin['img_dir'], $set['css_text'] )."\n//-->\n</style>";
		
    	$html = "<html>
    	           <head>
    	              <title>CSS Preview</title>
    	              $css
    	           </head>
    	           <body topmargin='0' leftmargin='0' rightmargin='0' marginwidth='0' marginheight='0' alink='#000000' vlink='#000000'>
    	           <table border='1' width='95%' cellspacing='0' cellpadding='4' align='center'>
    	           <tr>
    	            <td bgcolor='#EEEEEE' style='font-size:14px'><b>Preview CSS Element '$name'<br>From style sheet '{$set['css_name']}'</b></td>
    	           </tr>
    	           </table>
    	           <br>
    	           <table border='1' width='95%' cellspacing='0' cellpadding='4' align='center'>
    	           <tr>
    	            <td><b>Preview</b></td>
    	           </tr>
    	           <tr>
    	           	<td $like>Cozy Lummux Gives Smart Squid Who Asks For Job Pen ([Indeed!])</td>
    	           	</tr>
    	           	</table>
    	           	<br>
    	           	<table border='1' width='95%' cellspacing='0' cellpadding='4' align='center'>
    	            <tr>
    	             <td colspan='2'><b>Formatted CSS Definition</b></td>
    	            </tr>
    	              $def_output
    	           </table>
    	           </body>
    	         </html>
    	        ";
    	        
		print $html;
		
		exit();
    	        
    }
    	        
   	//+---------------------------------------
   	//+---------------------------------------
	
	function print_css_searchlinks()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_GET_VARS;
		
		//+---------------------------------------
		// GET THE TEMPLATES THAT THIS CSS USES
		//+---------------------------------------
		
		$DB->query("SELECT set_id FROM ibf_skins WHERE css_id='".$IN['id']."'");
		
		if ( ! $set = $DB->fetch_row() )
		{
			$ADMIN->error("Cannot query the database using that information");
		}
		
		$element = trim(stripslashes($HTTP_GET_VARS['element']));
		
		
		list($type, $name) = explode( "|", $element );
		
		$like = "class=_{$name}";
		
		if ($type == 'id')
		{
			$like = "id=_{$name}";
		}
		
		require './sources/Admin/skin_info.php';
		
		//die("SELECT suid, set_id, group_name, func_name FROM ibf_skin_templates WHERE set_id='".$set['tmpl_id']."' AND section_content LIKE '%".$like."%' ORDER BY group_name");
		
		$DB->query("SELECT suid, set_id, group_name, func_name FROM ibf_skin_templates WHERE set_id='".$set['set_id']."' AND section_content LIKE '%".$like."%' ORDER BY group_name");
		
		if (! $DB->get_num_rows() )
		{
			$this->do_message("No matches for that string in template set ID {$set['set_id']}");
		}
		
		$results = array();
		
		while ( $r = $DB->fetch_row() )
		{
			if ( ! isset($result[ $r['group_name'] ]) )
			{
				$result[ $r['group_name'] ] = array();
			}
			
			$result[ $r['group_name'] ][] = array( 'suid' => $r['suid'], 'func_name' => $r['func_name'] );
			
		}
		
		$ADMIN->html .= $SKIN->start_table();
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Search Results</b>" )  , 'title' );
		
		foreach( $result as $group_name => $sub_array )
		{
			if ( isset($skin_names[ $group_name ]) )
			{
				$group_name = $skin_names[ $group_name ][0];
			}
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>$group_name</b>" )  , 'catrow' );
			
			if (is_array($sub_array) and count($sub_array) > 0 )
			{
				foreach( $sub_array as $idx => $data )
				{
					$ADMIN->html .= $SKIN->add_td_row( array( "+ <a href='{$SKIN->base_url}&act=rtempl&code=preview&suid={$data['suid']}&type=text&hl=".urlencode($name)."' target='preview'>{$data['func_name']}</a>" )  );
				}
			}
		}
										 
		
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
		
	}
	
	//+---------------------------------------
	//+---------------------------------------
	
	function print_searchlinks()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['bypass'] == 1)
		{
			$this->do_message("No Search Results");
		}
		
		$search_text = trim($this->unconvert_tags(stripslashes($HTTP_POST_VARS['search'])));
		
		$search_text = str_replace( "\$", "\\$", $search_text);
		$search_text = str_replace( "'" , "\\'", $search_text);
		
		if ($search_text == "")
		{
			$this->do_message("Please enter a string to search");
		}
		
		require './sources/Admin/skin_info.php';
		
		$DB->query("SELECT set_id FROM ibf_skin_templates WHERE suid='".$IN['suid']."'");
		
		$set = $DB->fetch_row();
		
		$DB->query("SELECT suid, set_id, group_name, func_name FROM ibf_skin_templates WHERE set_id='".$set['set_id']."' AND section_content LIKE '%$search_text%' ORDER BY group_name");
		
		if (! $DB->get_num_rows() )
		{
			$this->do_message("No matches for that string");
		}
		
		$results = array();
		
		while ( $r = $DB->fetch_row() )
		{
			if ( ! isset($result[ $r['group_name'] ]) )
			{
				$result[ $r['group_name'] ] = array();
			}
			
			$result[ $r['group_name'] ][] = array( 'suid' => $r['suid'], 'func_name' => $r['func_name'] );
			
		}
		
		$ADMIN->html .= $SKIN->start_table();
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Search Results</b>" )  , 'title' );
		
		foreach( $result as $group_name => $sub_array )
		{
			if ( isset($skin_names[ $group_name ]) )
			{
				$group_name = $skin_names[ $group_name ][0];
			}
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>$group_name</b>" )  , 'catrow' );
			
			if (is_array($sub_array) and count($sub_array) > 0 )
			{
				foreach( $sub_array as $idx => $data )
				{
					$ADMIN->html .= $SKIN->add_td_row( array( "+ <a href='{$SKIN->base_url}&act=rtempl&code=preview&suid={$data['suid']}&type=text&hl=".urlencode(stripslashes($HTTP_POST_VARS['search']))."' target='preview'>{$data['func_name']}</a>" )  );
				}
			}
		}
										 
		
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
		
	}
	
	//+---------------------------------------
	
	function do_message($message="")
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->html = "<tr><td id='tdrow1' height='100%' align='center' valign='middle'><br><br><b>$message</b><br><br>&nbsp;</td></tr>";
		
		$ADMIN->print_popup();
		
	}
	
	//+---------------------------------------
	
	function print_search_box()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'searchlinks'   ),
												  2 => array( 'act'   , 'rtempl'        ),
												  3 => array( 'suid'  , $IN['suid']   )
									     )  ,'theform', 'target="links"'    );
									     
		//+-------------------------------
		
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table(  );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enter text to search for</b><br>".
										          $SKIN->form_input( 'search' )
										 )      );
										 
		$ADMIN->html .= $SKIN->end_form("Search!");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
		
	}
	
	//+---------------------------------------
	
	function search_frames()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		print "<html>
				 <head><title>Search</title></head>
				   <frameset cols='200, *' frameborder='no' border='1' framespacing='0'>
					 <frameset rows='*, 100' frameborder='no' border='1' framespacing='0'>
					   <frame name='links' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=searchlinks&suid={$IN['suid']}&bypass=1'>
					   <frame name='searchbox' scrolling='no' src='{$SKIN->base_url}&act=rtempl&code=searchbox&suid={$IN['suid']}'>
					 </frameset>
					<frame name='preview' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=previewstate'>
				   </frameset>
			   </html>";
			   
		exit();
	}
	
	//+---------------------------------------
	
	function compare_frames()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		print "<html>
				 <head>
				  <title>Compare</title>
				 </head>
				   <frameset cols='50%, 50%' frameborder='yes' border='1' framespacing='0'>
				     <frameset rows='30, *' frameborder='yes' border='1' framespacing='0'>
				       <frame name='origtop' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=dotop&content=orig'>
				       <frame name='origbot' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=preview&suid={$IN['suid']}&type=css'>
				     </frameset>
					<frameset rows='30, *' frameborder='yes' border='1' framespacing='0'>
				       <frame name='newtop' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=dotop&content=new'>
				       <frame name='newbot' scrolling='auto' src='{$SKIN->base_url}&act=rtempl&code=donew&suid={$IN['suid']}'>
				     </frameset>
				   </frameset>
			   </html>";
			   
		exit();
	}
	
	//+---------------------------------------
	
	function print_compare_top()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$content = $IN['content'] == 'orig' ? 'Original Template' : 'Current Template';
		
		print "<html>
			   <body marginheight='0' marginwidth='0' leftmargin='0' topmargin='0' bgcolor='#000055'>
			   <center><font face='verdana' size='2' color='white'><b>$content</b></font></center>
			   </body></html>";
		
		exit();
	}
	
	//+---------------------------------------
	
	function print_compare_new()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$DB->query("SELECT * FROM ibf_skin_templates WHERE suid='".$IN['suid']."'");
		
		if ( ! $template = $DB->fetch_row() )
		{
			$ADMIN->error("You must specify an existing template set ID, go back and try again");
		}
		
		$DB->query("SELECT css_id, img_dir FROM ibf_skins WHERE set_id='".$template['set_id']."'");
		$r = $DB->fetch_row();
		
		$DB->query("SELECT css_text FROM ibf_css WHERE cssid='".$r['css_id']."'");
		
		$css = $DB->fetch_row();
		
		$css_text = "\n<style>\n<!--\n".str_replace( "<#IMG_DIR#>", "style_images/".$r['img_dir'], $css['css_text'] )."\n//-->\n</style>";
		
		print "<html><head>
				$css_text
				
				</head>
				<body>
				<script language='Javascript'>
				
					templatedata = window.parent.opener.document.theform.template.value;
					
					document.write( templatedata);
					document.close();
					
				</script>
				</body></html>
				";
				
		exit();
		
		
	}
	
	//+---------------------------------------
	
	function macro_one()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'macro_two'   ),
												  2 => array( 'act'   , 'rtempl'       ),
												  3 => array( 'suid'  , $IN['suid']   )
									     )      );
									     
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"   , "60%" );
		$SKIN->td_header[] = array( "&nbsp;"   , "40%" );

		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Macro Look-up" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enter the Macro to look up</b><br>(EG: {ibf.skin.tbl_width})",
										          $SKIN->form_input( 'lookup' )
										 )      );
										 
		$ADMIN->html .= $SKIN->end_form("Look-up");
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
		
	}
	
	//+-------------------------------
	
	function macro_two()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['lookup'] == "")
		{
			$ADMIN->error("You must enter a macro to look up", 1);
		}
		
		$is_valid = 0;
		$macro    = "";
		$extra    = "";
		
		if ( preg_match( "/&lt;\{(\S+)\}&gt;/", $IN['lookup'], $match ) )
		{
			$is_valid = 1;
			$macro    = $match[1];
		}
		
		else if ( preg_match( "/{ibf\.(skin|lang|vars|member)\.(\w+)}$/", $IN['lookup'], $match ) )
		{
			$is_valid = 1;
			$macro    = $match[2];
			$extra    = $match[1];
		}
		else
		{
			$is_valid = 0;
		}
		
		if ($is_valid != 1)
		{
			$ADMIN->error("The entered macro was in the wrong format, please try again", 1);
		}
		
		if ($extra != "")
		{
			if ($extra == 'member')
			{
				if (isset($MEMBER[$macro]))
				{
					if ($MEMBER[$macro] == "")
					{
						$answer = "";
					}
					else
					{
						$answer = $MEMBER[$macro];
					}
				}
				else if (isset($GROUP[$macro]))
				{
					if ($GROUP[$macro] == "")
					{
						$answer = "";
					}
					else
					{
						$answer = $GROUP[$macro]." (This is member group information)";
					}
				}
					
				$result = "Loaded Member Information";
			}
			else if ($extra == 'vars')
			{
			
				// Filter out sensitive stuff
				
				$safe_INFO['board_name'] = $INFO['board_name'];
				$safe_INFO['board_url']  = $INFO['board_url'];
				
				$answer = $safe_INFO[$macro];
				
				$result = "Config Variable Information (May be protected)";
			}
			else if ($extra == 'lang')
			{
				$result = "Language Text";
				
				$DB->query("SELECT group_name FROM ibf_skin_templates WHERE suid='{$IN['suid']}'");
				
				if ( $r = $DB->fetch_row() )
				{
					$filename = preg_replace( "/^skin_/", "lang_", $r['group_name'] );
					
					if ( @file_exists( "./lang/en/$filename".".php" ) )
					{
						require "./lang/en/$filename".".php";
						
						$answer = $lang[$macro];
					}
				}
			}
		}
		else
		{
			// Is macro
			
			$DB->query("SELECT set_id FROM ibf_skin_templates WHERE suid='{$IN['suid']}'");
			
			$template = $DB->fetch_row();
			
			$DB->query("SELECT macro_id FROM ibf_skins WHERE set_id='".$template['set_id']."'");
			
			$macrod = $DB->fetch_row();
			
			$DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set='".$macrod['macro_id']."' AND macro_value='$macro'");
			
			if ($val = $DB->fetch_row())
			{
				$answer = $val['macro_replace'];
				$result = "From Macro Set";
			}
			else
			{
				$answer = "Macro not found";
			}
		}
		
		$SKIN->td_header[] = array( "&nbsp;"   , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"    , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Macro Look-up Result <a href='{$SKIN->base_url}&act=rtempl&code=macro_one&suid={$IN['suid']}'>Go Again</a>" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Entered Macro</b>", $IN['lookup'] ) );
										 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Macro Type</b>", $result )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Result</b><br>May be blank if no info", $answer )      );
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->print_popup();
		
		
		
	}
	
	//+-------------------------------
	//+-------------------------------
	
	function do_preview()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_GET_VARS;
		
		
		if ($IN['suid'] == "")
		{
			$ADMIN->error("You must specify an existing template set ID, go back and try again");
		}
		
		$DB->query("SELECT * FROM ibf_skin_templates WHERE suid='".$IN['suid']."'");
		
		if ( ! $template = $DB->fetch_row() )
		{
			$ADMIN->error("You must specify an existing template set ID, go back and try again");
		}
		
		$DB->query("SELECT macro_id FROM ibf_skins WHERE set_id='".$template['set_id']."'");
		
		$macrod = $DB->fetch_row();
		
		$DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set='".$macrod['macro_id']."'");
		
		while ( $mc = $DB->fetch_row() )
		{
			$macro_orig[] = "<{".$mc['macro_value']."}>";
			$macro_repl[] = $mc['macro_replace'];
		}
		
		$table = "<table width='100%' bgcolor='black' cellpadding='4' style='font-family:verdana, arial;font-size:11px;color:white'>
				  <tr>
				   <td align='center' style='font-family:verdana, arial;font-size:11px;color:white'>Template Group: {$template['group_name']} : Template bit: {$template['func_name']}</td>
				  </tr>
				  <tr>
				   <td align='center' style='font-family:verdana, arial;font-size:11px;color:white'>View as [ <a href='{$ADMIN->base_url}&act=rtempl&code=preview&suid={$IN['suid']}&type=text' style='font-family:verdana, arial;font-size:11px;color:white'>Text</a> | <a href='{$ADMIN->base_url}&act=rtempl&code=preview&suid={$IN['suid']}&type=html' style='font-family:verdana, arial;font-size:11px;color:white'>HTML</a> | <a href='{$ADMIN->base_url}&act=rtempl&code=preview&suid={$IN['suid']}&type=css' style='font-family:verdana, arial;font-size:11px;color:white'>HTML with CSS</a> ]</td>
				  </tr>
				  </table>
				  <br><br>
				  ";
		
		if ($IN['type'] == 'text')
		{
			@header("Content-type: text/html");
			print $table;
			$html = $this->convert_tags($template['section_content']);
			
			$html = str_replace( "<" , "&lt;"  , $html);
			$html = str_replace( ">" , "&gt;"  , $html);
			$html = str_replace( "\"", "&quot;", $html);
			
			if ($HTTP_GET_VARS['hl'] != "")
			{
				$hl = urldecode(stripslashes($HTTP_GET_VARS['hl']));
				
				$hl = str_replace( "<" , "&lt;"  , $hl);
				$hl = str_replace( ">" , "&gt;"  , $hl);
				$hl = str_replace( "\"", "&quot;", $hl);
				
				$html = str_replace( $hl, "<span style='color:red;font-weight:bold;background-color:yellow'>$hl</span>", $html );
			}
			
			$html = preg_replace( "!&lt;\!--(.+?)(//)?--&gt;!s"                    , "&#60;&#33;<span style='color:red'>--\\1--\\2</span>&#62;", $html );
			$html = preg_replace( "#&lt;([^&<>]+)&gt;#s"                           , "&lt;<span style='color:blue'>\\1</span>&gt;"        , $html );   //Matches <tag>
			$html = preg_replace( "#&lt;([^&<>]+)=#s"                              , "&lt;<span style='color:blue'>\\1</span>="           , $html );   //Matches <tag
			$html = preg_replace( "#&lt;/([^&]+)&gt;#s"                            , "&lt;/<span style='color:blue'>\\1</span>&gt;"       , $html );   //Matches </tag>
			$html = preg_replace( "!=(&quot;|')([^<>])(&quot;|')(\s|&gt;)!s"   , "=\\1<span style='color:purple'>\\2</span>\\3\\4"       , $html );   //Matches ='this'
			
			$html = str_replace( "\n", "<br>", str_replace("\r\n", "\n", $html ) );
			
			print "<pre>".$html."</pre>";
			exit();
			
		}
		else if ($IN['type'] == 'html')
		{
			@header("Content-type: text/html");
			print $table;
			print $this->convert_tags($template['section_content']);
			
			exit();
		}
		else if($IN['type'] == 'css')
		{
			$DB->query("SELECT css_id, img_dir FROM ibf_skins WHERE set_id='".$template['set_id']."'");
			$r = $DB->fetch_row();
			
			$DB->query("SELECT css_text FROM ibf_css WHERE cssid='".$r['css_id']."'");
			
			$css = $DB->fetch_row();
			
			$css_text = "\n<style>\n<!--\n".str_replace( "<#IMG_DIR#>", "style_images/".$r['img_dir'], $css['css_text'] )."\n//-->\n</style>";
			
			@header("Content-type: text/html");
			print "<html><head><title>Preview</title>$css_text</head><body>$table \n";
			print str_replace( $macro_orig, $macro_repl, $this->convert_tags($template['section_content']) );
			
			exit();
		
		}
			
		
		
	}
	
	
	//------------------------------------------------------
	
	
	
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
	
	function unconvert_tags($t="")
	{
		if ($t == "")
		{
			return "";
		}
		
		$t = preg_replace( "/{ibf\.script_url}/i"   , '{$ibforums->base_url}'         , $t);
		$t = preg_replace( "/{ibf\.session_id}/i"   , '{$ibforums->session_id}'       , $t);
		$t = preg_replace( "/{ibf\.skin\.(\w+)}/"   , '{$ibforums->skin[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.lang\.(\w+)}/"   , '{$ibforums->lang[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.vars\.(\w+)}/"   , '{$ibforums->vars[\''."\\1".'\']}'   , $t);
		$t = preg_replace( "/{ibf\.member\.(\w+)}/" , '{$ibforums->member[\''."\\1".'\']}' , $t);
		
		return $t;
		
	}
	
	
}


?>