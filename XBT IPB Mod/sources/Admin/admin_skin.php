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
|   > Admin HTML stuff library
|   > Script written by Matt Mecham
|   > Date started: 1st march 2002
|
+--------------------------------------------------------------------------
*/


class admin_skin {

	var $base_url;
	var $img_url;
	var $has_title;
	var $td_widths = array();
	var $td_header = array();
	var $td_colspan;
	
	function admin_skin() {
		global $INFO, $IN;
		
		$this->base_url = $INFO['board_url']."/admin.".$INFO['php_ext']."?adsess=".$IN['AD_SESS'];
		$this->img_url  = $INFO['html_url'].'/sys-img';
		
	}
	
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	// Javascript elements
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	
	function js_pop_win()
	{
	
		return "
				<script language='javascript'>
				<!--
					function pop_win(theUrl, winName, theWidth, theHeight)
					{
						 	if (winName == '') { winName = 'Preview'; }
						 	if (theHeight == '') { theHeight = 400; }
						 	if (theWidth == '') { theWidth = 400; }
						 	
						 	window.open('{$this->base_url}'+theUrl,winName,'width='+theWidth+',height='+theHeight+',resizable=yes,scrollbars=yes');
					}
					
				//-->
				</script>
				";
				
		}
	
	function js_help_link($help="", $text="Quick Help")
	{
		return "( <a href='#' onClick=\"window.open('{$this->base_url}&act=quickhelp&id=$help','Help','width=250,height=400,resizable=yes,scrollbars=yes'); return false;\">$text</a> )";
		
	}
	
	function js_template_tools()
	{
	
		return "
				<script language='javascript'>
				<!--
					
					var baseUrl = \"{$this->base_url}\";
					
					function restore(suid, expand)
					{
						 if (confirm(\"Are you sure you want to restore the template?\\nALL UNSAVED CHANGES WILL BE LOST!\"))
						 {
          					self.location.href= baseUrl + '&act=templ&code=edit_bit&suid=' + suid + '&expand=' + expand;
       					 }
       					 else
       					 {
          					alert (\"Restore Cancelled\");
      					 }
      				}
      				
      				function edit_box_size(cols, rows)
      				{
      					if (cols == '') { cols = 80; }
      					if (rows == '') { rows = 40; }
      					
      					userCols = prompt(\"Enter the number of columns for the text area (width)\", cols);
						if ( (userCols != null) && (userCols != \"\") )
						{
							userRows = prompt(\"Enter the number of rows for the text area (height)\", rows);
							if ( (userRows != null) && (userRows != \"\") )
							{
								// Rows and cols set, save cookie, present alert.
								
								document.cookie = 'ad_tempform='+userRows+'-'+userCols+'; path=/; expires=Wed, 1 Jan 2020 00:00:00 GMT;';
								alert('Edit box preferences updated.\\nThe changes will take effect next time the edit screen is loaded');
							}
							else
							{
								alert('You must enter a value for the number of rows');
							}
						}
						else
						{
							alert('You must enter a value for the number of columns');
						}
					}
					
					function pop_win(theUrl, winName, theWidth, theHeight)
					{
						 	if (winName == '') { winName = 'Preview'; }
						 	if (theHeight == '') { theHeight = 400; }
						 	if (theWidth == '') { theWidth = 400; }
						 	
						 	window.open('{$this->base_url}&act=rtempl&'+theUrl,winName,'width='+theWidth+',height='+theHeight+',resizable=yes,scrollbars=yes');
					}
					
				//-->
				</script>
				";
				
	}
	
	
	function js_checkdelete()
	{
	
		return "
				<script language='javascript'>
				<!--
				function checkdelete(theURL) {
				
					final_url = \"{$this->base_url}&\" + theURL;
					
					if ( confirm('Are you sure you wish to remove this?\\nIt cannot be undone!') )
					{
						document.location.href=final_url;
					}
					else
					{
						alert('Ok, remove cancelled!');
					}
				}
				//-->
				</script>
				";
	}
	
	
	
	function js_no_specialchars()
	{
		return "
				<script language='javascript'>
				<!--
				function no_specialchars(type) {
				
			      var name;
				
				  if (type == 'sets')
				  {
				  	var field = document.theAdminForm.sname;
				  	name = 'Skin Set Title';
				  }
				  
				  if (type == 'wrapper')
				  {
				  	var field = document.theAdminForm.name;
				  	name = 'Wrapper Title';
				  }
				  
				  if (type == 'csssheet')
				  {
				  	var field = document.theAdminForm.name;
				  	name = 'StyleSheet Title';
				  }
				  
				  if (type == 'templates')
				  {
				  	var field = document.theAdminForm.skname;
				  	name = 'Template Set Name';
				  }
				  
				  if (type == 'images')
				  {
				  	var field = document.theAdminForm.setname;
				  	name = 'Image & Macro Set Title';
				  }
				
				  var valid = 'abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.()[]:;~+-_';
				  var ok = 1;
				  var temp;
				  
				  for (var i=0; i < field.value.length; i++) {
				      temp = \"\" + field.value.substring(i,i+1);
				      if (valid.indexOf(temp) == \"-1\")
				      {
				      	ok = 0;
				      }
				  }
				  if (ok == 0)
				  {
				  	alert('Invalid entry for: ' + name + ', you can only use alphanumerics and the following special characters.\\n. ( ) : ; ~ + - _');
				  	return false;
				  } else {
				  	return true;
				  }
				}
				//-->
				</script>
				";
	}
	
	function make_page_jump($tp="", $pp="", $ub="" )
	{
		global $IN, $INFO;
		return "<a href='#' title=\"Jump to a page...\" onclick=\"multi_page_jump('$ub',$tp,$pp);\">Pages:</a>";
	}
	
	
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	// FORM ELEMENTS
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	
	function start_form($hiddens="", $name='theAdminForm', $js="") {
		global $IN, $INFO;
	
		$form = "<form action='{$this->base_url}' method='post' name='$name' $js>
				 <input type='hidden' name='adsess' value='{$IN['AD_SESS']}'>";
		
		if (is_array($hiddens))
		{
			foreach ($hiddens as $k => $v) {
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}'>";
			}
		}
		
		return $form;
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_hidden($hiddens="") {
	
		if (is_array($hiddens))
		{
			foreach ($hiddens as $k => $v) {
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}'>";
			}
		}
		
		return $form;
	}
	
	
	//+--------------------------------------------------------------------
	
	function end_form($text = "", $js = "")
	{
		// If we have text, we print another row of TD elements with a submit button
		
		$html    = "";
		$colspan = "";
		
		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}
			
			$html .= "<tr><td align='center' class='pformstrip'".$colspan."><input type='submit' value='$text'".$js." id='button' accesskey='s'></td></tr>\n";
		}
		
		$html .= "</form>";
		
		return $html;
		
	}
	
	//+--------------------------------------------------------------------
	
	function end_form_standalone($text = "", $js = "")
	{
		
		$html    = "";
		$colspan = "";
		
		if ($text != "")
		{
			$html .= "<div class='tableborder'><div align='center' class='pformstrip'><input type='submit' value='$text'".$js." id='button' accesskey='s'></div></div>\n";
		}
		
		$html .= "</form>";
		
		return $html;
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_upload($name="FILE_UPLOAD", $js="") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		return "<input class='textinput' type='file' $js size='30' name='$name'>";
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_input($name, $value="", $type='text', $js="", $size="30") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		return "<input type='$type' name='$name' value='$value' size='$size'".$js." class='textinput'>";
		
	}
	
	function form_simple_input($name, $value="", $size='5') {
	
		return "<input type='text' name='$name' value='$value' size='$size' class='textinput'>";
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_textarea($name, $value="", $cols='60', $rows='5', $wrap='soft') {
	
		return "<textarea name='$name' cols='$cols' rows='$rows' wrap='$wrap' class='multitext'>$value</textarea>";
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_dropdown($name, $list=array(), $default_val="", $js="") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		$html = "<select name='$name'".$js." class='dropdown'>\n";
		
		foreach ($list as $k => $v)
		{
		
			$selected = "";
			
			if ( ($default_val != "") and ($v[0] == $default_val) )
			{
				$selected = ' selected';
			}
			
			$html .= "<option value='".$v[0]."'".$selected.">".$v[1]."</option>\n";
		}
		
		$html .= "</select>\n\n";
		
		return $html;
	
	
	}
	
	//+--------------------------------------------------------------------
	
	function form_multiselect($name, $list=array(), $default=array(), $size=5, $js="") {
	
		if ($js != "")
		{
			$js = ' '.$js.' ';
		}
	
		//$html = "<select name='$name".'[]'."'".$js." id='dropdown' multiple='multiple' size='$size'>\n";
		$html = "<select name='$name"."'".$js." class='dropdown' multiple='multiple' size='$size'>\n";
		foreach ($list as $k => $v)
		{
		
			$selected = "";
			
			if ( count($default) > 0 )
			{
				if ( in_array( $v[0], $default ) )
				{
					$selected = ' selected="selected"';
				}
			}
			
			$html .= "<option value='".$v[0]."'".$selected.">".$v[1]."</option>\n";
		}
		
		$html .= "</select>\n\n";
		
		return $html;
	
	
	}
	
	//+--------------------------------------------------------------------
	
	function form_yes_no( $name, $default_val="", $js=array() ) {
	
		$y_js = "";
		$n_js = "";
		
		if ( $js['yes'] != "" )
		{
			$y_js = $js['yes'];
		}
		
		if ( $js['no'] != "" )
		{
			$n_js = $js['no'];
		}
	
		$yes = "Yes &nbsp; <input type='radio' name='$name' value='1' $y_js id='green'>";
		$no  = "<input type='radio' name='$name' value='0' $n_js id='red'> &nbsp; No";
		
		
		
		if ($default_val == 1)
		{
			
			$yes = "Yes &nbsp; <input type='radio' name='$name' value='1'$y_js checked id='green'>";
		}
		else
		{
			$no  = "<input type='radio' name='$name' value='0' checked $n_js id='red'> &nbsp; No";
		}
		
		
		return $yes.'&nbsp;&nbsp;&nbsp;'.$no;
		
	}
	
	//+--------------------------------------------------------------------
	
	function form_checkbox( $name, $checked=0, $val=1, $js=array() ) {
		
		if ($checked == 1)
		{
			
			return "<input type='checkbox' name='$name' value='$val' checked='checked'>";
		}
		else
		{
			return "<input type='checkbox' name='$name' value='$val'>";
		}
		
	}
	
	//+--------------------------------------------------------------------
	
	function build_group_perms( $read='*', $write='*', $reply='*', $upload='*' ) {
		global $DB;
		
		
		$html = "
		
				<script language='Javascript1.1'>
				<!--
				
				function check_all(str_part) {
				
					var f = document.theAdminForm;
				
					for (var i = 0 ; i < f.elements.length; i++)
					{
						var e = f.elements[i];
						
						if ( (e.name != 'UPLOAD_ALL') && (e.name != 'READ_ALL') && (e.name != 'REPLY_ALL') && (e.name != 'START_ALL') && (e.type == 'checkbox') && (! e.disabled) )
						{
							s = e.name;
							a = s.substring(0, 4);
							
							if (a == str_part)
							{
								e.checked = true;
							}
						}
					}
				}
				
				function obj_checked(IDnumber) {
				
					var f = document.theAdminForm;
					
					str_part = '';
					
					if (IDnumber == 1) { str_part = 'READ' }
					if (IDnumber == 2) { str_part = 'REPL' }
					if (IDnumber == 3) { str_part = 'STAR' }
					if (IDnumber == 4) { str_part = 'UPLO' }
					
					totalboxes = 0;
					total_on   = 0;
					
					for (var i = 0 ; i < f.elements.length; i++)
					{
						var e = f.elements[i];
						
						if ( (e.name != 'UPLOAD_ALL') && (e.name != 'READ_ALL') && (e.name != 'REPLY_ALL') && (e.name != 'START_ALL') && (e.type == 'checkbox') )
						{
							s = e.name;
							a = s.substring(0, 4);
							
							if (a == str_part)
							{
								totalboxes++;
								
								if (e.checked)
								{
									total_on++;
								}
							}
						}
					}
					
					if (totalboxes == total_on)
					{
						if (IDnumber == 1) { f.READ_ALL.checked  = true; }
						if (IDnumber == 2) { f.REPLY_ALL.checked = true; }
						if (IDnumber == 3) { f.START_ALL.checked = true; }
						if (IDnumber == 4) { f.UPLOAD_ALL.checked = true; }
					}
					else
					{
						if (IDnumber == 1) { f.READ_ALL.checked  = false; }
						if (IDnumber == 2) { f.REPLY_ALL.checked = false; }
						if (IDnumber == 3) { f.START_ALL.checked = false; }
						if (IDnumber == 4) { f.UPLOAD_ALL.checked = false; }
					}
					
				}
				
				function checkcol(IDnumber,status) {
				
					var f = document.theAdminForm;
					
					str_part = '';
					
					if (IDnumber == 1) { str_part = 'READ' }
					if (IDnumber == 2) { str_part = 'REPL' }
					if (IDnumber == 3) { str_part = 'STAR' }
					if (IDnumber == 4) { str_part = 'UPLO' }
					
					for (var i = 0 ; i < f.elements.length; i++)
					{
						var e = f.elements[i];
						
						if ( (e.name != 'UPLOAD_ALL') && (e.name != 'READ_ALL') && (e.name != 'REPLY_ALL') && (e.name != 'START_ALL') && (e.type == 'checkbox') )
						{
							s = e.name;
							a = s.substring(0, 4);
							
							if (a == str_part)
							{
								if ( status == 1 )
								{
									e.checked = true;
									if (IDnumber == 1) { f.READ_ALL.checked  = true; }
									if (IDnumber == 2) { f.REPLY_ALL.checked = true; }
									if (IDnumber == 3) { f.START_ALL.checked = true; }
									if (IDnumber == 4) { f.UPLOAD_ALL.checked = true; }
								}
								else
								{
									e.checked = false;
									if (IDnumber == 1) { f.READ_ALL.checked  = false; }
									if (IDnumber == 2) { f.REPLY_ALL.checked = false; }
									if (IDnumber == 3) { f.START_ALL.checked = false; }
									if (IDnumber == 4) { f.UPLOAD_ALL.checked = false; }
								}
							}
						}
					}
				}
				
				function checkrow(IDnumber,status) {
				
					var f = document.theAdminForm;
					
					str_part = '';
					
					if ( status == 1 )
					{
						mystat = 'true';
					}
					else
					{
						mystat = 'false';
					}
					
					eval( 'f.READ_'+IDnumber+'.checked='+mystat );
					eval( 'f.REPLY_'+IDnumber+'.checked='+mystat );
					eval( 'f.START_'+IDnumber+'.checked='+mystat );
					eval( 'f.UPLOAD_'+IDnumber+'.checked='+mystat );
					
					obj_checked(1);
					obj_checked(2);
					obj_checked(3);
					obj_checked(4);
				}
				
				//-->
				
				</script>
				
				";

		$html .= $this->add_td_basic( "GLOBAL: All current and future permission masks", "left", "pformstrip" );		
				
		//+-------------------------------------------------------------------------
				 	
		if ($read == '*')
		{
			$html_read = "<input type='checkbox' onClick='check_all(\"READ\")' name='READ_ALL' value='1' checked>\n";
		}
		else
		{
			$html_read = "<input type='checkbox' onClick='check_all(\"READ\")' name='READ_ALL' value='1'>\n";
		}
		
		//+-------------------------------------------------------------------------
		
		if ($reply == '*')
		{
			$html_reply = "<input type='checkbox' onClick='check_all(\"REPL\")' name='REPLY_ALL' value='1' checked>\n";
		}
		else
		{
			$html_reply = "<input type='checkbox' onClick='check_all(\"REPL\")' name='REPLY_ALL' value='1'>\n";
		}
		
		//+-------------------------------------------------------------------------
		
		if ($write == '*')
		{
			$html_start = "<input type='checkbox' onClick='check_all(\"STAR\")' name='START_ALL' value='1' checked>\n";
		}
		else
		{
			$html_start = "<input type='checkbox' onClick='check_all(\"STAR\")' name='START_ALL' value='1'>\n";
		}
		
		if ($upload == '*')
		{
			$html_upload = "<input type='checkbox' onClick='check_all(\"UPLO\")' name='UPLOAD_ALL' value='1' checked>\n";
		}
		else
		{
			$html_upload = "<input type='checkbox' onClick='check_all(\"UPLO\")' name='UPLOAD_ALL' value='1'>\n";
		}
		
		//+-------------------------------------------------------------------------
		
		$html .= $this->add_td_row( array(   "<b>All current and future permission masks</b>",
											 "<center id='mgblue'>$html_read</center>",
											 "<center id='mggreen'>$html_reply</center>",
											 "<center id='mgred'>$html_start</center>",
											 "<center id='memgroup'>$html_upload</center>",
								 )       );
		
		//+-------------------------------------------------------------------------
		
		$html .= $this->add_td_basic( "OR: Adjust permissions per mask below", "left", "pformstrip" );
		
		
		$DB->query("SELECT * FROM ibf_forum_perms ORDER BY perm_name ASC");
				 
				 
		while ( $data = $DB->fetch_row() )
		{
			if ($read == '*')
			{
				$html_read = "<input type='checkbox' name='READ_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(1)\">";
			}
			else if ( preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $read ) )
			{
				$html_read = "<input type='checkbox' name='READ_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(1)\">";
			}
			else
			{
				$html_read = "<input type='checkbox' name='READ_{$data['perm_id']}' value='1' onclick=\"obj_checked(1)\">";
			}
			
			//+----------------------------------------------------------------------------------------
			
			if ($reply == '*')
			{
				$html_reply = "<input type='checkbox' name='REPLY_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(2)\">";
			}
			else if ( preg_match( "/(?:^|,)".$data['perm_id']."(?:,|$)/", $reply ) )
			{
				$html_reply = "<input type='checkbox' name='REPLY_{$data['perm_id']}' value='1' onclick=\"obj_checked(2)\" checked>";
			}
			else
			{
				$html_reply = "<input type='checkbox' name='REPLY_{$data['perm_id']}' value='1' onclick=\"obj_checked(2)\">";
			}
			
			//+----------------------------------------------------------------------------------------
			
			if ($write == '*')
			{
				$html_start = "<input type='checkbox' name='START_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(3)\">";
			}
			else if ( preg_match( "/(?:^|,)".$data['perm_id']."(?:,|$)/", $write ) )
			{
				$html_start = "<input type='checkbox' name='START_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(3)\">";
			}
			else
			{
				$html_start = "<input type='checkbox' name='START_{$data['perm_id']}' value='1' onclick=\"obj_checked(3)\">";
			}
			
			//+----------------------------------------------------------------------------------------
			
			if ($upload == '*')
			{
				$html_upload = "<input type='checkbox' name='UPLOAD_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(4)\">";
			}
			else if ( preg_match( "/(?:^|,)".$data['perm_id']."(?:,|$)/", $upload ) )
			{
				$html_upload = "<input type='checkbox' name='UPLOAD_{$data['perm_id']}' value='1' checked onclick=\"obj_checked(4)\">";
			}
			else
			{
				$html_upload = "<input type='checkbox' name='UPLOAD_{$data['perm_id']}' value='1' onclick=\"obj_checked(4)\">";
			}
			
			$html .= $this->add_td_row( array(   "<div align='right' style='font-weight:bold'>{$data['perm_name']} &nbsp; <input type='button' id='button' value='+' onclick='checkrow({$data['perm_id']},1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkrow({$data['perm_id']},0)' /></div>",
												 "<center id='mgblue'>$html_read</center>",
											 	 "<center id='mggreen'>$html_reply</center>",
											  	 "<center id='mgred'>$html_start</center>",
											     "<center id='memgroup'>$html_upload</center>",
									  )       );
		}
		
		$html .= $this->add_td_row( array(   "&nbsp;",
											 "<center>Select: <input type='button' id='button' value='+' onclick='checkcol(1,1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol(1,0)' /></center>",
											 "<center>Select: <input type='button' id='button' value='+' onclick='checkcol(2,1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol(2,0)' /></center>",
											 "<center>Select: <input type='button' id='button' value='+' onclick='checkcol(3,1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol(3,0)' /></center>",
											 "<center>Select: <input type='button' id='button' value='+' onclick='checkcol(4,1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol(4,0)' /></center>",
								  )       );
	
		return $html;
		
	}
	
	
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	// SCREEN ELEMENTS
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	
	function add_subtitle($title="",$id="subtitle", $colspan="") {
		
		if ($colspan != "")
		{
			$colspan = " colspan='$colspan' ";
		}
		
		return "\n<tr><td id='$id'".$colspan.">$title</td><tr>\n";
		
	}
	
	//+--------------------------------------------------------------------
	
	function start_table( $title="", $desc="") {
	
		if ($title != "")
		{
			$this->has_title = 1;
			$html .= "<div class='tableborder'>
						<div class='maintitle'>$title</div>\n";
						
			if ( $desc != "" )
			{
				$html .= "<div class='pformstrip'>$desc</div>\n";
			}
		}
	
	
	
		$html .= "\n<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>";
		
		
		if (isset($this->td_header[0]))
		{
			$html .= "<tr>\n";
			
			// Auto remove two &nbsp; only headers.. 
			
			if ( $this->td_header[0][0] == '&nbsp;' && $this->td_header[1][0] == '&nbsp;' && ( ! isset( $this->td_header[2][0] ) ) )
			{
				$this->td_header[0][0] = '{none}';
				$this->td_header[1][0] = '{none}';
			}
			
			foreach ($this->td_header as $td)
			{
				if ($td[1] != "")
				{
					$width = " width='{$td[1]}' ";
				}
				else
				{
					$width = "";
				}
				
				if ($td[0] != '{none}')
				{
					$html .= "<td class='titlemedium'".$width."align='center'>{$td[0]}</td>\n";
				}
				
				$this->td_colspan++;
			}
			
			$html.= "</tr>\n";
		}
		
		return $html;
		
	}
	
	//+--------------------------------------------------------------------
	
	
	function add_td_row( $array, $css="", $align='middle' ) {
	
		if (is_array($array))
		{
			$html = "<tr>\n";
			
			$count = count($array);
			
			$this->td_colspan = $count;
			
			for ($i = 0; $i < $count ; $i++ )
			{
			
				$td_col = $i % 2 ? 'tdrow2' : 'tdrow1';
				
				if ($css != "")
				{
					$td_col = $css;
				}
			
				if (is_array($array[$i]))
				{
					$text    = $array[$i][0];
					$colspan = $array[$i][1];
					
					$html .= "<td class='$td_col' colspan='$colspan' valign='$align' class='$css'>".$text."</td>\n";
				}
				else
				{
					if ($this->td_header[$i][1] != "")
					{
						$width = " width='{$this->td_header[$i][1]}' ";
					}
					else
					{
						$width = "";
					}
					
					$html .= "<td class='$td_col' $width valign='$align'>".$array[$i]."</td>\n";
				}
			}
			
			$html .= "</tr>\n";
			
			return $html;
		}
		
	}
	
	//+--------------------------------------------------------------------
	
	function add_td_basic($text="",$align="left",$id="tdrow1") {
	
		$html    = "";
		$colspan = "";
		
		if ($text != "")
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='".$this->td_colspan."' ";
			}
			
			
			$html .= "<tr><td align='$align' class='$id'".$colspan.">$text</td></tr>\n";
		}
		
		return $html;
	
	}
	
	//+--------------------------------------------------------------------
	
	function add_td_spacer() {
	
		if ($this->td_colspan > 0)
		{
			$colspan = " colspan='".$this->td_colspan."' ";
		}
	
		return "<tr><td".$colspan."><img src='html/sys-img/blank.gif' height='7' width='1'></td></tr>";
	
	}
	
	
	
	//+--------------------------------------------------------------------
	
	function end_table() {
	
		$this->td_header = array();  // Reset TD headers
	
		if ($this->has_title == 1)
		{
			$this->has_title = 0;
			
			return "</table></div><br />\n\n";
		}
		else
		{
			return "</table>\n\n";
		}
		
	}
	
	
	//+--------------------------------------------------------------------
	
	
			
	
	
	//+--------------------------------------------------------------------
	//+--------------------------------------------------------------------
	
	function get_css()
	{
		return "<style type='text/css'>
					BODY {
							font-size: 10px;
							font-family: Verdana, Arial, Sans-Serif;
							color:#000;
							padding:0px;
							margin:0px 5px 0px 5px;
						  }
						  
				    TABLE, TD, TR {
							font-family: Verdana,Arial, Sans-Serif;
							color:#000;
							font-size: 10px;
						  }
						  
					a:link, a:visited, a:active  { color:#000055 }
					a:hover                      { color:#333377;text-decoration:underline }
					
					#maintop { font-size:18px;
					           color:#FFF;
					           font-weight:bold;
					           letter-spacing:-1px;
					           padding:9px 5px 9px 5px;
					           border:1px solid #345487;
					           background-image: url({$this->img_url}/tile_back.gif);
					         }
					
					
					#nav { font-size:11px;margin:8px 0px 8px 0px;color:#3A4F6C;font-weight:bold;}
					#nav a:link, #nav  a:visited, #nav a:active { font-weight:bold;font-size:11px; }
					
					#submenu   { border:1px solid #BCD0ED;background-color: #DFE6EF;font-size:10px;margin:3px 0px 3px 0px;color:#3A4F6C;font-weight:bold;}
					#submenu a:link, #submenu  a:visited, #submenu a:active { font-weight:bold;font-size:10px;text-decoration: none; color: #3A4F6C; }

					
					.tableborder { border:1px solid #345487;background-color:#FFF;width:100% }
					
					.offdiv { font-size:12px;font-weight:bold }
					
					form { display:inline }
					input { vertical-align:middle }
					
					img  { vertical-align:middle }
					
					.titlemedium { border:1px solid #FFF; font-weight:bold; color:#3A4F6C; padding:7px 0px 7px 2px; background-image: url({$this->img_url}/tile_sub.gif) }
					.titlemedium  a:link, .titlemedium  a:visited, .titlemedium  a:active  { text-decoration: underline; color: #3A4F6C }
					
					.maintitle { font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF; padding:8px 0px 8px 5px; background-image: url({$this->img_url}/tile_back.gif) }
					.maintitle a:link, .maintitle  a:visited, .maintitle  a:active { text-decoration: none; color: #FFF }
					.maintitle a:hover { text-decoration: underline }
					
					.pformstrip { background-color: #D1DCEB; color:#3A4F6C;font-weight:bold;padding:5px;margin-top:1px }
					
					#normal      { font: 10px Verdana; color:#333333 }
					
					#detail { font-family: Arial; font-size:11px; color: #333333 }
					
					#subtitle { font-family: Arial,Verdana; font-size:16px; color:#FF9900; font-weight:bold }
					#smalltitle { font-family: Arial,Verdana; font-size:11px; color:#FF9900; font-weight:bold }
					
					#table1 {  background-color:#FFFFFF; width:100%; align:center; border:1px solid black }
					
					.tdrow1 { background-color:#EEF2F7;
					          border-bottom:1px solid #D1DCEB;
					          border-right:1px solid #D1DCEB ;
					          border-top:1px solid #FFF;
					          border-left:1px solid #FFF;
					        }
					
					.subforum { background-color:#DFE6EF }
					
					.tdrow2 { background-color:#F5F9FD;
							  border-bottom:1px solid #D1DCEB;
					          border-right:1px solid #D1DCEB;
					          border-top:1px solid #FFF;
					          border-left:1px solid #FFF;
					        }
					
					.catrow { font-weight:bold; height:24px; line-height:150%; color:#4C77B6; background-image: url({$this->img_url}/tile_sub.gif); }
					.catrow2 { font-size:11px; font-weight:bold; line-height:150%; color:#4C77B6; background-color:#D3DFEF; }
					
					.copy { color:#000; font-size:10px; border-top:1px solid #000; margin-bottom:10px; padding:6px;  margin-left:auto;margin-right:auto; text-align:center; }
					
					#nav { color:#000000; font-size:9px }
					
					#description { color:#000000; font-size:10px }
					
					#memgroup { border:1px solid #777777 }
					
					#mgred   { border:1px solid #777777; background-color: #f5cdcd }
					#mggreen { border:1px solid #777777; background-color: #caf2d9 }
					#mgblue  { border:1px solid #777777; background-color: #DFE6EF }
					
					#green    { background-color: #caf2d9 }
					#red      { background-color: #f5cdcd }
					
					#button   { border:1px solid #4C77B6;background-color: #DFE6EF; font-family:Verdana, Arial; font-size:11px }
					
					#editbutton   { background-color: #DDDDDD; color: #000; font-family:Verdana, Arial; font-size:9px }
					
					.fauxbutton   { border:1px solid #4C77B6;
								    background-color: #DFE6EF;
								    font-family:Verdana, Arial;
								    font-size:10px;
								    font-weight:bold;
								    padding:3px;
								    margin:6px;
								  }
								  
					.darksep { background-color: #B1BECE; color:#4C77B6; height:5px }
					
					.fauxbutton a:link, .fauxbutton  a:visited, .fauxbutton  a:active { font-size:10px;font-weight:bold; }
					
					.textinput { background-color: #FFF; color:Ê#000; font-size:10px; font-family: Verdana,Arial, Sans-Serif; padding:2px; border:2px inset #BCD0ED; }
					
					.dropdown { background-color: #FFF; color:Ê#000; font-size:10px; font-family: Verdana,Arial, Sans-Serif; padding:2px;  border:2px inset #BCD0ED; }
					
					.multitext { background-color: #FFF; color:Ê#000; font-size:10px;font-family: Verdana,Arial, Sans-Serif; padding:2px;  border:2px inset #BCD0ED; }
					
					.jmenu, .jmenubutton { vertical-align:middle;
										  background-color: #FFF;
										  border:1px solid #345487;
										  font-size:11px;
										 }
										 
					#jwrap { border:1px solid #BCD0ED;background-color: #DFE6EF;font-size:10px;margin:3px 0px 3px 0px;color:#3A4F6C;font-weight:bold;padding:8px }
					
				  </style>";
	}
	
	
	
	function print_top($title="",$desc="") {
		global $INFO, $IN;
	
		$css = $this->get_css();
	
		return "<html>
		          <head><title>Menu</title>
		          <meta HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\">
				  <meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">
				  <meta HTTP-EQUIV=\"Expires\" CONTENT=\"Mon, 06 May 1996 04:57:00 GMT\">
		          $css
		          <script type='text/javascript'>
					function ShowHide(id1, id2) {
					  if (id1 != '') expMenu(id1);
					  if (id2 != '') expMenu(id2);
					}
					
					function expMenu(id) {
					  var itm = null;
					  if (document.getElementById) {
						itm = document.getElementById(id);
					  } else if (document.all){
						itm = document.all[id];
					  } else if (document.layers){
						itm = document.layers[id];
					  }
					
					  if (!itm) {
					   // do nothing
					  }
					  else if (itm.style) {
						if (itm.style.display == \"none\") {
						  itm.style.display = \"\";
						}
						else {
						  itm.style.display = \"none\";
						}
					  }
					  else {
						itm.visibility = \"show\";
					  }
					}
					function multi_page_jump( url_bit, total_posts, per_page )
					{
					  pages = 1; cur_st = parseInt(\"{$IN['st']}\"); cur_page  = 1;
					  if ( total_posts % per_page == 0 ) { pages = total_posts / per_page; }
					   else { pages = Math.ceil( total_posts / per_page ); }
					  msg = \"Choose a page between 1 and\" + \" \" + pages;
					  if ( cur_st > 0 ) { cur_page = cur_st / per_page; cur_page = cur_page -1; }
					  show_page = 1;
					  if ( cur_page < pages )  { show_page = cur_page + 1; }
					  if ( cur_page >= pages ) { show_page = cur_page - 1; }
					   else { show_page = cur_page + 1; }
					  userPage = prompt( msg, show_page );
					  if ( userPage > 0  ) {
						  if ( userPage < 1 )     {    userPage = 1;  }
						  if ( userPage > pages ) { userPage = pages; }
						  if ( userPage == 1 )    {     start = 0;    }
						  else { start = (userPage - 1) * per_page; }
						  self.location.href = url_bit + \"&st=\" + start;
					  }
					}
				   </script>
				  </head>
				 <body marginheight='0' marginwidth='0' leftmargin='0' topmargin='0' bgcolor='#FFFFFF' {$this->top_extra}>
				 <div id='maintop'>$title</div>
				 <table width='100%' cellspacing='6' id='submenu'>
				 <tr>
				  <td><a href='{$this->base_url}&act=menu&show=all' target='menu'>Expand Menu</a> &middot; <a href='{$this->base_url}&act=menu&show=none' target='menu'>Reduce Menu</a></td>
				  <td align='right'><a href='{$this->base_url}&act=index' target='body'>ACP Home</a> &middot; <a href='{$INFO['board_url']}/index.{$INFO['php_ext']}' target='_blank'>Board Home</a></td>
				 </tr>
				 </table>
				 <!--NAV-->
				 <div id='description'>$desc</div>
				 <br />";
				  	   
	}
	
	function wrap_nav($links)
	{
		return "\n<div id='nav'><img src='html/sys-img/nav.gif' border='0' title='ACP Navigation'>&nbsp;$links</div>\n";
	}
	
	//+--------------------------------------------------------------------
	
	function print_foot() {
		
		return "<br />
				<div align='right' id='jwrap'><strong>Quick Jump</strong> <!--JUMP--></div>
				<div class='copy' align='center'>Invision Power Board &copy 2003 <a href='http://www.invisionpower.com' target='_blank'>IPS, Inc.</a></div>
				 </body>
				 </html>";
	}
	
	
	//+--------------------------------------------------------------------
	
	
	
	
	//{ background-color:#C2CFDF; font-weight:bold; font-size:12px; color:#000055 }
	
	
	function menu_top() {
		global $INFO;
		
		
		$pop_win = $this->js_pop_win();
	
		return "<html>
		          <head><title>Menu</title>
		          <style type='text/css'>
		          	TABLE, TR, TD     { font-family:Verdana, Arial;font-size: 9px; color:#000 }
					BODY      {
								 font: 9px Verdana;
								 color:#000;
								 background-color:#FFF;
								 margin:0px 3px 0px 3px;
								 padding:0px;
							  }
					a:link, a:visited, a:active  { color:#000 }
					a:hover                      { text-decoration:underline }
					
					img { vertical-align:middle }
					#title  { font-size:10px; font-weight:bold; line-height:150%; color:#FFFFFF; height: 24px; background-image: url({$this->img_url}/tile_back.gif); }
					#title  a:link, #title  a:visited, #title  a:active { text-decoration: none; color : #555555 }
					
					.desc {
							font-size:9px;
							color: #000;
							background-color:#DFE6EF;
							padding:2px 0px 2px 5px;
							line-height:1.2em;
						   }
					
					.plain {
							font-size:9px;
							color: #000;
							background-color:#EEF2F7;
							padding:2px 0px 2px 5px;
							line-height:1.7em;
						   }
					
					.tableborder { border:1px solid #345487;background-color:#FFF; }
					
					.cattitle  {
								font-size:10px;
								font-weight:bold;
								line-height:150%;
								background-color:#C4DCF7;
								color:#000;
								padding:5px 4px 5px 5px;
								background-image: url({$this->img_url}/tile_sub.gif);
								border-bottom:1px solid #345487;
							   }
					.cattitle  a:link, .cattitle  a:visited, .cattitle  a:active { text-decoration: underline; color:#000; }
					
				  </style>
				  $pop_win
				  </head>
				 <body marginheight='0' marginwidth='0' leftmargin='0' topmargin='0' bgcolor='#FFF'>
				 <div align='center'><img src='{$this->img_url}/ad-logo.jpg' border='0' style='width:100%'></div>
				 <div class='tableborder'>
				  <div class='plain'>
				   <img src='{$this->img_url}/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->base_url}&act=menu&show=all' target='menu'>Expand</a> &middot; <a href='{$this->base_url}&act=menu&show=none' target='menu'>Reduce</a> Menu
				   <br /><img src='{$this->img_url}/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->base_url}&act=index' target='body'>ACP</a> &middot; <a href='{$INFO['board_url']}/index.{$INFO['php_ext']}' target='body'>Board</a> Home
				   <br /><img src='{$this->img_url}/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$this->base_url}&act=ips&code=docs' target='body' style='text-decoration:none'>IPB Documentation</a>
				   <br /><img src='{$this->img_url}/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href=\"javascript:pop_win('&act=prefs', 'prefs', 400, 200 )\"  style='text-decoration:none'>Your ACP prefs</a>
				  </div>
				 </div>
				 <br />
				";
				  	   
	}
	
	//+--------------------------------------------------------------------
	
	function menu_foot() {
		
		return "
				</body>
				 </html>";
	}
	
	
	//+--------------------------------------------------------------------
	

	function menu_cat_expanded($name="", $links="", $id = "") {
		global $IN;
	
		return "<a name='cat$id'></a>
				<div class='tableborder'>
				  <div class='cattitle'>
				    <a href='{$this->base_url}&act=menu&show={$IN['show']}&out=$id' target='menu'><img src='{$this->img_url}/minus.gif' border='0' alt='Collapse Category' title='Collapse Category'></a>
				    <a href='{$this->base_url}&act=menu&show={$IN['show']}&out=$id' target='menu'>$name</a>
				  </div>
				  <div class='plain'>$links</div>
				</div>
				<br />\n";
				
	
	}
	
	//+--------------------------------------------------------------------
	
	
	function menu_cat_collapsed($name="", $id = "", $desc="") {
		global $IN;
	
		return "<div class='tableborder'>
				  <div class='cattitle'>
				    <a href='{$this->base_url}&act=menu&show=,{$IN['show']},$id#cat$id' target='menu'><img src='{$this->img_url}/plus.gif' border='0' alt='Collapse Category' title='Collapse Category'></a>
				    <a href='{$this->base_url}&act=menu&show=,{$IN['show']},$id#cat$id' target='menu'>$name</a>
				  </div>
				  <div class='desc'>$desc</div>
				</div>
				<br />\n";
	
	}
	
	//+--------------------------------------------------------------------
	
	function menu_cat_link($url="", $name="", $urltype=0)
	{
		global $INFO;
		
		if ( $urltype == 1 )
		{
			$theurl = $INFO['board_url'].'/index.'.$INFO['php_ext'].'?';
		}
		else
		{
			$theurl = $this->base_url;
		}
			
		return "<img src='{$this->img_url}/item.gif' border='0' alt='' valign='absmiddle'>&nbsp;<a href='{$theurl}&$url' target='body' style='text-decoration:none'>$name</a><br />";
	
	}
	
	
	//+--------------------------------------------------------------------
	
	function frame_set() {
		global $IN, $ibforums;
		
		$frames = "<html>
		   			 <head><title>Invision Power Board Administration Center</title></head>
					   <frameset cols='185, *' frameborder='no' border='0' framespacing='0'>
					   	<frame name='menu' noresize scrolling='auto' src='{$this->base_url}&act=menu'>
					   	<frame name='body' noresize scrolling='auto' src='{$this->base_url}&act=index'>
					   </frameset>
				   </html>";
				   
		return $frames;
					  
	}


}






?>