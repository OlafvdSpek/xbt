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
|   > Multi function library
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/



class FUNC {

	var $time_formats  = array();
	var $time_options  = array();
	var $offset        = "";
	var $offset_set    = 0;
	var $num_format    = "";
	var $allow_unicode = 1;
	var $get_magic_quotes = 0;

	// Set up some standards to save CPU later
	
	function FUNC() {
		global $INFO;
		
		$this->time_options = array( 'JOINED' => $INFO['clock_joined'],
									 'SHORT'  => $INFO['clock_short'],
									 'LONG'   => $INFO['clock_long']
								   );
								   
		$this->num_format = ($INFO['number_format'] == 'space') ? ' ' : $INFO['number_format'];
		
		$this->get_magic_quotes = get_magic_quotes_gpc();
		
	}
	
	/*-------------------------------------------------------------------------*/
	// expire_subscription
	// ------------------
	// Remove member's subscription
	/*-------------------------------------------------------------------------*/
	
	function expire_subscription()
	{
		global $DB, $ibforums;
		
		$query = "sub_end=0";
		
		// Get subscription details...
		
		$DB->query("SELECT * FROM ibf_subscription_trans WHERE subtrans_state='paid' AND subtrans_member_id={$ibforums->member['id']}");
		
		if ( $row = $DB->fetch_row() )
		{
			if ( $row['subtrans_old_group'] > 0 )
			{
				$DB->query("SELECT g_id FROM ibf_groups WHERE g_id={$row['subtrans_old_group']}");
				
				if ( $group = $DB->fetch_row() )
				{
					$query .= ", mgroup={$row['subtrans_old_group']}";
				}
				else
				{
					// Group has been deleted, reset back to base member group
					
					$query .= ", mgroup={$ibforums->vars['member_group']}";
				}
			}
			
			$DB->query("UPDATE ibf_subscription_trans SET subtrans_state='expired' WHERE subtrans_id={$row['subtrans_id']}");
		}
		
		$DB->query("UPDATE ibf_members SET $query WHERE id={$ibforums->member['id']}");
    }
	
	/*-------------------------------------------------------------------------*/
	// txt_stripslashes
	// ------------------
	// Make Big5 safe - only strip if not already...
	/*-------------------------------------------------------------------------*/
	
	function txt_stripslashes($t)
	{
		if ( $this->get_magic_quotes )
		{
    		$t = stripslashes($t);
    	}
    	
    	return $t;
    }
	
	/*-------------------------------------------------------------------------*/
	// txt_raw2form
	// ------------------
	// makes _POST text safe for text areas
	/*-------------------------------------------------------------------------*/
	
	function txt_raw2form($t="")
	{
		$t = str_replace( '$', "&#036;", $t);
			
		if ( get_magic_quotes_gpc() )
		{
			$t = stripslashes($t);
		}
		
		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Safe Slashes - ensures slashes are saved correctly
	// ------------------
	// 
	/*-------------------------------------------------------------------------*/
	
	function txt_safeslashes($t="")
	{
		return str_replace( '\\', "\\\\", $this->txt_stripslashes($t));
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_htmlspecialchars
	// ------------------
	// Custom version of htmlspecialchars to take into account mb chars
	/*-------------------------------------------------------------------------*/
	
	function txt_htmlspecialchars($t="")
	{
		// Use forward look up to only convert & not &#123;
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_UNhtmlspecialchars
	// ------------------
	// Undoes what the above function does. Yes.
	/*-------------------------------------------------------------------------*/
	
	function txt_UNhtmlspecialchars($t="")
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// return_md5_check
	// ------------------
	// md5 hash for server side validation of form / link stuff
	/*-------------------------------------------------------------------------*/
	
	function return_md5_check()
	{
		global $ibforums;
		
		if ( $ibforums->member['id'] )
		{
			return md5($ibforums->member['email'].'&'.$ibforums->member['password'].'&'.$ibforums->member['joined']);
		}
		else
		{
			return md5("this is only here to prevent it breaking on guests");
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// C.O.C.S (clean old comma-delimeted strings)
	// ------------------
	// <>
	/*-------------------------------------------------------------------------*/
	
	function trim_leading_comma($t)
	{
		return preg_replace( "/^,/", "", $t );
	}
	
	function trim_trailing_comma($t)
	{
		return preg_replace( "/,$/", "", $t );
	}
	
	
	function clean_comma($t)
	{
		return preg_replace( "/,{2,}/", ",", $t );
	}
	
	function clean_perm_string($t)
	{
		$t = $this->clean_comma($t);
		$t = $this->trim_leading_comma($t);
		$t = $this->trim_trailing_comma($t);
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// size_format
	// ------------------
	// Give it a byte to eat and it'll return nice stuff!
	/*-------------------------------------------------------------------------*/
	
	function size_format($bytes="")
	{
		global $ibforums;
		
		$retval = "";
		
		if ($bytes >= 1048576)
		{
			$retval = round($bytes / 1048576 * 100 ) / 100 . $ibforums->lang['sf_mb'];
		}
		else if ($bytes  >= 1024)
		{
			$retval = round($bytes / 1024 * 100 ) / 100 . $ibforums->lang['sf_k'];
		}
		else
		{
			$retval = $bytes . $ibforums->lang['sf_bytes'];
		}
		
		return $retval;
	}
	
	/*-------------------------------------------------------------------------*/
	// print_forum_rules
	// ------------------
	// Checks and prints forum rules (if required)
	/*-------------------------------------------------------------------------*/
	
	function print_forum_rules($forum)
	{
		global $ibforums, $skin_universal;
		
		$ruleshtml = "";
		
		if ($forum['show_rules'])
		{
			if ( $forum['rules_title'] )
			{
				 $rules['title'] = $forum['rules_title'];
				 $rules['body']  = $forum['rules_text'];
				 $rules['fid']   = $forum['id'];
				 
				 $ruleshtml = $forum['show_rules'] == 2 ? $skin_universal->forum_show_rules_full($rules) : $skin_universal->forum_show_rules_link($rules);
			}
		}
		
		return $ruleshtml;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// hdl_ban_line() : Get / set ban info
	// Returns array on get and string on "set"
	//
	/*-------------------------------------------------------------------------*/
	
	function hdl_ban_line($bline)
	{
		global $ibforums;
		
		if ( is_array( $bline ) )
		{
			// Set ( 'timespan' 'unit' )
			
			$factor = $bline['unit'] == 'd' ? 86400 : 3600;
			
			$date_end = time() + ( $bline['timespan'] * $factor );
			
			return time() . ':' . $date_end . ':' . $bline['timespan'] . ':' . $bline['unit'];
		}
		else
		{
			$arr = array();
			
			list( $arr['date_start'], $arr['date_end'], $arr['timespan'], $arr['unit'] ) = explode( ":", $bline );
			
			return $arr;
		}
		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// check_perms() : Nice little sub to check perms
	// Returns TRUE if access is allowed, FALSE if not.
	//
	/*-------------------------------------------------------------------------*/
	
	function check_perms($forum_perm="")
	{
		global $ibforums;
		
		if ( $forum_perm == "" )
		{
			return FALSE;
		}
		else if ( $forum_perm == '*' )
		{
			return TRUE;
		}
		else
		{
			$forum_perm_array = explode( ",", $forum_perm );
			
			foreach( $ibforums->perm_id_array as $u_id )
			{
				if ( in_array( $u_id, $forum_perm_array ) )
				{
					return TRUE;
				}
			}
			
			// Still here? Not a match then.
			
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// do_number_format() : Nice little sub to handle common stuff
	//
	/*-------------------------------------------------------------------------*/
	
	function do_number_format($number)
	{
		global $ibforums;
		
		if ($ibforums->vars['number_format'] != 'none')
		{
			return number_format($number , 0, '', $this->num_format);
		}
		else
		{
			return $number;
		}
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	//
	// hdl_forum_read_cookie()
	//
	/*-------------------------------------------------------------------------*/
	
	function hdl_forum_read_cookie($set="")
	{
		global $ibforums;
		
		if ( $set == "" )
		{
			// Get cookie and return array...
			
			if ( $fread = $this->my_getcookie('forum_read') )
			{
				$farray = unserialize(stripslashes($fread));
				
				if ( is_array($farray) and count($farray) > 0 )
				{
					foreach( $farray as $id => $stamp )
					{
						$ibforums->forum_read[$id] = $stamp;
					}
				}
			}
			
			return TRUE;
		}
		else
		{
			// Set cookie...
			
			$fread = addslashes(serialize($ibforums->forum_read));
			
			$this->my_setcookie('forum_read', $fread);
			
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Return scaled down image
	//
	/*-------------------------------------------------------------------------*/
	
	function scale_image($arg)
	{
		// max_width, max_height, cur_width, cur_height
		
		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);
		
		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}
		
		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}
		
	
		return $ret;
	
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// Show NORMAL created security image(s)...
	//
	/*-------------------------------------------------------------------------*/
	
	function show_gif_img($this_number="")
	{
		global $ibforums, $DB;
		
		$numbers = array( 0 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUDH5hiKsOnmqSPjtT1ZdnnjCUqBQAOw==',
						  1 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUjAEWyMqoXIprRkjxtZJWrz3iCBQAOw==',
						  2 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUDH5hiKubnpPzRQvoVbvyrDHiWAAAOw==',
						  3 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDH5hiKbaHgRyUZtmlPtlfnnMiGUFADs=',
						  4 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVjAN5mLDtjFJMRjpj1Rv6v1SHN0IFADs=',
						  5 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUhA+Bpxn/DITL1SRjnps63l1M9RQAOw==',
						  6 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVjIEYyWwH3lNyrQTbnVh2Tl3N5wQFADs=',
						  7 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUhI9pwbztAAwP1napnFnzbYEYWAAAOw==',
						  8 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDH5hiKubHgSPWXoxVUxC33FZZCkFADs=',
						  9 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDA6hyJabnnISnsnybXdS73hcZlUFADs=',
						);
		
		flush();
		header("Content-type: image/gif");
		echo base64_decode($numbers[ $this_number ]);
		exit();
		
		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Show GD created security image...
	//
	/*-------------------------------------------------------------------------*/
	
	function show_gd_img($content="")
	{
		global $ibforums, $DB;

		flush();
		
		@header("Content-Type: image/jpeg");
		
		if ( $ibforums->vars['use_ttf'] != 1 )
		{
			$font_style = 5;
			$no_chars   = strlen($content);
			
			$charheight = ImageFontHeight($font_style);
			$charwidth  = ImageFontWidth($font_style);
			$strwidth   = $charwidth * intval($no_chars);
			$strheight  = $charheight;
			
			$imgwidth   = $strwidth  + 15;
			$imgheight  = $strheight + 15;
			$img_c_x    = $imgwidth  / 2;
			$img_c_y    = $imgheight / 2;
			
			$im       = ImageCreate($imgwidth, $imgheight);
			$text_col = ImageColorAllocate($im, 0, 0, 0);
			$back_col = ImageColorAllocate($im, 200,200,200);
			
			ImageFilledRectangle($im, 0, 0, $imgwidth, $imgheight, $text_col);
			ImageFilledRectangle($im, 3, 3, $imgwidth - 4, $imgheight - 4, $back_col);
			
			$draw_pos_x = $img_c_x - ($strwidth  / 2) + 1;
			$draw_pos_y = $img_c_y - ($strheight / 2) + 1;
			
			ImageString($im, $font_style, $draw_pos_x, $draw_pos_y, $content, $text_col);
		
		}
		else
		{
			$image_x = isset($ibforums->vars['gd_width'])  ? $ibforums->vars['gd_width'] : 250;
			$image_y = isset($ibforums->vars['gd_height']) ? $ibforums->vars['gd_height'] : 70;
			
			$im = imagecreate($image_x,$image_y);
			
			$white    = ImageColorAllocate($im, 255, 255, 255);
			$black    = ImageColorAllocate($im, 0, 0, 0);
			$grey     = ImageColorAllocate($im, 200, 200, 200 );
			
			$no_x_lines = ($image_x - 1) / 5;
			
			for ( $i = 0; $i <= $no_x_lines; $i++ )
			{
				// X lines
				
				ImageLine( $im, $i * $no_x_lines, 0, $i * $no_x_lines, $image_y, $grey );
				
				// Diag lines
				
				ImageLine( $im, $i * $no_x_lines, 0, ($i * $no_x_lines)+$no_x_lines, $image_y, $grey );
			}
			
			$no_y_lines = ($image_y - 1) / 5;
			
			for ( $i = 0; $i <= $no_y_lines; $i++ )
			{
				ImageLine( $im, 0, $i * $no_y_lines, $image_x, $i * $no_y_lines, $grey );
			}
			
			$font = isset($ibforums->vars['gd_font']) ? $ibforums->vars['gd_font'] : getcwd().'/fonts/progbot.ttf';
		
			$text_bbox = ImageTTFBBox(20, 0, $font, $content);
			
			$sx = ($image_x - ($text_bbox[2] - $text_bbox[0])) / 2; 
			$sy = ($image_y - ($text_bbox[1] - $text_bbox[7])) / 2; 
			$sy -= $text_bbox[7];
			
			imageTTFtext($im, 20, 0, $sx, $sy, $black, $font, $content);
		}
		
		
		ImageJPEG($im);
		ImageDestroy($im);
		
		exit();
		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Convert newlines to <br /> nl2br is buggy with <br /> on early PHP builds
	//
	/*-------------------------------------------------------------------------*/
	
	function my_nl2br($t="")
	{
		return str_replace( "\n", "<br />", $t );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Convert <br /> to newlines
	//
	/*-------------------------------------------------------------------------*/
	
	function my_br2nl($t="")
	{
		$t = preg_replace( "#(?:\n|\r)?<br />(?:\n|\r)?#", "\n", $t );
		$t = preg_replace( "#(?:\n|\r)?<br>(?:\n|\r)?#"  , "\n", $t );
		
		return $t;
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// Load a template file from DB or from PHP file
	//
	/*-------------------------------------------------------------------------*/
	
	function load_template( $name, $id='' )
	{
		global $ibforums, $DB;
		
		$tags      = 1;
		
		if ($ibforums->vars['safe_mode_skins'] == 0)
		{
			// Simply require and return
			
			require ROOT_PATH."Skin/".$ibforums->skin_id."/$name.php";
			return new $name();
		}
		else
		{
			// We're using safe mode skins, yippee
			// Load the data from the DB
			
			$DB->query("SELECT func_name, func_data, section_content FROM ibf_skin_templates WHERE set_id='".$ibforums->skin_rid."' AND group_name='$name'");
			
			if ( ! $DB->get_num_rows() )
			{
				fatal_error("Could not fetch the templates from the database. Template $name, ID {$ibforums->skin_rid}");
			}
			else
			{
				$new_class = "class $name {\n";
				
				while( $row = $DB->fetch_row() )
				{
					if ($tags == 1)
					{
						$comment = "<!--TEMPLATE: $name, Template Part: ".$row['func_name']."-->\n";
					}
					
					$new_class .= 'function '.$row['func_name'].'('.$row['func_data'].") {\n";
					$new_class .= "global \$ibforums;\n";
					$new_class .= 'return <<<EOF'."\n".$comment.$row['section_content']."\nEOF;\n}\n";
				}
				
				$new_class .= "}\n";
				
				eval($new_class);
				
				return new $name();
			}
		}
	}
		
		
	/*-------------------------------------------------------------------------*/
	//
	// Creates a profile link if member is a reg. member, else just show name
	//
	/*-------------------------------------------------------------------------*/
	
	function make_profile_link($name, $id="")
	{
		global $ibforums;
		
		if ($id > 0)
		{
			return "<a href='{$ibforums->base_url}showuser=$id'>$name</a>";
		}
		else
		{
			return $name;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Redirect using HTTP commands, not a page meta tag.
	//
	/*-------------------------------------------------------------------------*/
	
	function boink_it($url)
	{
		global $ibforums;
		
		// Ensure &amp;s are taken care of
		
		$url = str_replace( "&amp;", "&", $url );
		
		if ($ibforums->vars['header_redirect'] == 'refresh')
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ($ibforums->vars['header_redirect'] == 'html')
		{
			@flush();
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header("Location: ".$url);
		}
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Create a random 8 character password
	//
	/*-------------------------------------------------------------------------*/
	
	function make_password()
	{
		$pass = "";
		$chars = array(
			"1","2","3","4","5","6","7","8","9","0",
			"a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J",
			"k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T",
			"u","U","v","V","w","W","x","X","y","Y","z","Z");
	
		$count = count($chars) - 1;
	
		srand((double)microtime()*1000000);

		for($i = 0; $i < 8; $i++)
		{
			$pass .= $chars[rand(0, $count)];
		}
	
		return($pass);
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Generate the appropriate folder icon for a forum
	//
	/*-------------------------------------------------------------------------*/
	
	function forum_new_posts($forum_data, $sub=0) {
        global $ibforums, $std;
        
        $rtime = $ibforums->input['last_visit'];
        
        $fid   = $forum_data['fid'] == "" ? $forum_data['id'] : $forum_data['fid'];
        
        $ftime = $ibforums->forum_read[ $fid ];
        
        $rtime = $ftime > $rtime ? $ftime : $rtime;
        
        if ($sub == 0)
        {
			if ( ! $forum_data['status'] )
			{
				return "<{C_LOCKED}>";
			}
			
			$sub_cat_img = '';
        }
        else
        {
        	$sub_cat_img = '_CAT';
        }
        
        if ($forum_data['password'] and $sub == 0)
        {
            return $forum_data['last_post'] > $rtime ? "<{C_ON_RES}>"
                                                     : "<{C_OFF_RES}>";
        }
        
        return $forum_data['last_post']  > $rtime ? "<{C_ON".$sub_cat_img."}>"
                                                  : "<{C_OFF".$sub_cat_img."}>";
    }
    
	/*-------------------------------------------------------------------------*/
	//
	// Generate the appropriate folder icon for a topic
	//
	/*-------------------------------------------------------------------------*/
	
	function folder_icon($topic, $dot="", $last_time=-1) {
		global $ibforums;
		
		$last_time = $last_time > $ibforums->input['last_visit'] ? $last_time : $ibforums->input['last_visit'];
		
		if ($dot != "")
		{
			$dot = "_DOT";
		}
		
		if ($topic['state'] == 'closed')
		{
			return "<{B_LOCKED}>";
		}
		
		if ($topic['poll_state'])
		{
		
			if ( ! $ibforums->member['id'] )
			{
				return "<{B_POLL".$dot."}>";
			}
			
			if ($topic['last_post'] > $topic['last_vote'])
			{
				$topic['last_vote'] = $topic['last_post'];
			}
			
			if ($last_time  && ($topic['last_vote'] > $last_time ))
			{
				return "<{B_POLL".$dot."}>";
			}
			if ($last_time  && ($topic['last_vote'] < $last_time ))
			{
				return "<{B_POLL_NN".$dot."}>";
			}
			
			return "<{B_POLL".$dot."}>";
		}
		
		
		if ($topic['state'] == 'moved' or $topic['state'] == 'link')
		{
			return "<{B_MOVED}>";
		}
		
		if ( ! $ibforums->member['id'] )
		{
			return "<{B_NORM".$dot."}>";
		}
		
		if (($topic['posts'] + 1 >= $ibforums->vars['hot_topic']) and ( (isset($last_time) )  && ($topic['last_post'] <= $last_time )))
		{
			return "<{B_HOT_NN".$dot."}>";
		}
		if ($topic['posts'] + 1 >= $ibforums->vars['hot_topic'])
		{
			return "<{B_HOT".$dot."}>";
		}
		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			return "<{B_NEW".$dot."}>";
		}
		
		return "<{B_NORM".$dot."}>";
		
	}
	
	/*-------------------------------------------------------------------------*/
    // text_tidy:
    // Takes raw text from the DB and makes it all nice and pretty - which also
    // parses un-HTML'd characters. Use this with caution!         
    /*-------------------------------------------------------------------------*/
    
    function text_tidy($txt = "") {
    
    	$trans = get_html_translation_table(HTML_ENTITIES);
    	$trans = array_flip($trans);
    	
    	$txt = strtr( $txt, $trans );
    	
    	$txt = preg_replace( "/\s{2}/" , "&nbsp; "      , $txt );
    	$txt = preg_replace( "/\r/"    , "\n"           , $txt );
    	$txt = preg_replace( "/\t/"    , "&nbsp;&nbsp;" , $txt );
    	//$txt = preg_replace( "/\\n/"   , "&#92;n"       , $txt );
    	
    	return $txt;
    	
    }

	/*-------------------------------------------------------------------------*/
    // compile_db_string:
    // Takes an array of keys and values and formats them into a string the DB
    // can use.
    // $array = ( 'THIS' => 'this', 'THAT' => 'that' );
    // will be returned as THIS, THAT  'this', 'that'                
    /*-------------------------------------------------------------------------*/
    
    function compile_db_string($data) {
    
    	$field_names  = "";
		$field_values = "";
		
		foreach ($data as $k => $v) {
			$v = preg_replace( "/'/", "\\'", $v );
			$field_names  .= "$k,";
			$field_values .= "'$v',";
		}
		
		$field_names  = preg_replace( "/,$/" , "" , $field_names  );
		$field_values = preg_replace( "/,$/" , "" , $field_values );
		
		return array( 'FIELD_NAMES'  => $field_names,
					  'FIELD_VALUES' => $field_values,
					);
	}



    /*-------------------------------------------------------------------------*/
    // Build up page span links                
    /*-------------------------------------------------------------------------*/
    
	function build_pagelinks($data)
	{
		global $ibforums, $skin_universal;

		$work = array();
		
		$section = ($data['leave_out'] == "") ? 2 : $data['leave_out'];  // Number of pages to show per section( either side of current), IE: 1 ... 4 5 [6] 7 8 ... 10
	
		$work['pages']  = 1;
		
		if ( ($data['TOTAL_POSS'] % $data['PER_PAGE']) == 0 )
		{
			$work['pages'] = $data['TOTAL_POSS'] / $data['PER_PAGE'];
		}
		else
		{
			$number = ($data['TOTAL_POSS'] / $data['PER_PAGE']);
			$work['pages'] = ceil( $number);
		}
		
		
		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['CUR_ST_VAL'] > 0 ? ($data['CUR_ST_VAL'] / $data['PER_PAGE']) + 1 : 1;
		
	
		if ($work['pages'] > 1)
		{
			$work['first_page'] = $skin_universal->make_page_jump($data['TOTAL_POSS'],$data['PER_PAGE'], $data['BASE_URL'])." (".$work['pages'].")";
			
			for( $i = 0; $i <= $work['pages'] - 1; ++$i )
			{
				$RealNo = $i * $data['PER_PAGE'];
				$PageNo = $i+1;
				
				if ($RealNo == $data['CUR_ST_VAL'])
				{
					$work['page_span'] .= "&nbsp;<b>[{$PageNo}]</b>";
				}
				else
				{
					
					if ($PageNo < ($work['current_page'] - $section))
					{
						$work['st_dots'] = "&nbsp;<a href='{$data['BASE_URL']}&amp;st=0' title='{$ibforums->lang['ps_page']} 1'>&laquo; {$ibforums->lang['ps_first']}</a>&nbsp;...";
						continue;
					}
					
					// If the next page is out of our section range, add some dotty dots!
					
					if ($PageNo > ($work['current_page'] + $section))
					{
						$work['end_dots'] = "...&nbsp;<a href='{$data['BASE_URL']}&amp;st=".($work['pages']-1) * $data['PER_PAGE']."' title='{$ibforums->lang['ps_page']} {$work['pages']}'>{$ibforums->lang['ps_last']} &raquo;</a>";
						break;
					}
					
					
					$work['page_span'] .= "&nbsp;<a href='{$data['BASE_URL']}&amp;st={$RealNo}'>{$PageNo}</a>";
				}
			}
			
			$work['return']    = $work['first_page'].$work['st_dots'].$work['page_span'].'&nbsp;'.$work['end_dots'];
		}
		else
		{
			$work['return']    = $data['L_SINGLE'];
		}
	
		return $work['return'];
	}
    
    
    
    /*-------------------------------------------------------------------------*/
    // Build the forum jump menu               
    /*-------------------------------------------------------------------------*/ 
    
	function build_forum_jump($html=1, $override=0, $remove_redirects=0)
	{
		global $INFO, $DB, $ibforums;
		// $html = 0 means don't return the select html stuff
		// $html = 1 means return the jump menu with select and option stuff
		
		$last_cat_id = -1;
		
		if ( $remove_redirects )
		{
			$qe = 'AND f.redirect_on <> 1';
		}
		else
		{
			$qe = '';
		}
		
		$DB->query("SELECT f.id as forum_id, f.parent_id, f.subwrap, f.sub_can_post, f.name as forum_name, f.position, f.redirect_on, f.read_perms, c.id as cat_id, c.name
				    FROM ibf_forums f
				     LEFT JOIN ibf_categories c ON (c.id=f.category)
				    WHERE c.state IN (1,2) $qe
				    ORDER BY c.position, f.position");
		
		
		if ($html == 1) {
		
			$the_html = "<form onsubmit=\"if(document.jumpmenu.f.value == -1){return false;}\" action='{$ibforums->base_url}act=SF' method='get' name='jumpmenu'>
			             <input type='hidden' name='act' value='SF' />\n<input type='hidden' name='s' value='{$ibforums->session_id}' />
			             <select name='f' onchange=\"if(this.options[this.selectedIndex].value != -1){ document.jumpmenu.submit() }\" class='forminput'>
			             <optgroup label=\"{$ibforums->lang['sj_title']}\">
			              <option value='sj_home'>{$ibforums->lang['sj_home']}</option>
			              <option value='sj_search'>{$ibforums->lang['sj_search']}</option>
			              <option value='sj_help'>{$ibforums->lang['sj_help']}</option>
			             </optgroup>
			             <optgroup label=\"{$ibforums->lang['forum_jump']}\">";
		}
		
		$forum_keys = array();
		$cat_keys   = array();
		$children   = array();
		$subs       = array();
		$subwrap    = array();
		
		// disable short mode if we're compiling a mod form
		
		if ($html == 0 or $override == 1)
		{
			$ibforums->vars['short_forum_jump'] = 0;
		}
			
		while ( $i = $DB->fetch_row() )
		{
			$selected = '';
			$redirect = "";
		
			if ($html == 1 or $override == 1)
			{
				if ($ibforums->input['f'] and $ibforums->input['f'] == $i['forum_id'])
				{
					$selected = ' selected="selected"';
				}
			}
			
			if ( $i['redirect_on'] )
			{
				$redirect = $ibforums->lang['fj_redirect'];
			}
			
			if ($i['subwrap'] == 1)
			{
				$subwrap[ $i['forum_id'] ] = 1;
			}
			
			if ($i['subwrap'] == 1 and $i['sub_can_post'] != 1)
			{
				$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "<option value=\"{$i['forum_id']}\"".$selected.">&nbsp;&nbsp;- {$i['forum_name']}</option>\n";
			}
			else
			{
				if ( $this->check_perms($i['read_perms']) == TRUE )
				{
					if ($i['parent_id'] > 0)
					{
						$children[ $i['parent_id'] ][] = "<option value=\"{$i['forum_id']}\"".$selected.">&nbsp;&nbsp;---- {$i['forum_name']} $redirect</option>\n";
					}
					else
					{
						$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "<option value=\"{$i['forum_id']}\"".$selected.">&nbsp;&nbsp;- {$i['forum_name']} $redirect</option><!--fx:{$i['forum_id']}-->\n";
					}
				}
				else
				{
					continue;
				}
			}
			
			if ($last_cat_id != $i['cat_id'])
			{
				
				// Make sure cats with hidden forums are not shown in forum jump
				
				$cat_keys[ $i['cat_id'] ] = "<option value='-1'>{$i['name']}</option>\n";
							              
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
					if ( $subwrap[$idx] != 1 )
					{
						$the_html .= $forum_text;
					}
					else if (count($children[$idx]) > 0)
					{
						$the_html .= $forum_text;
						
						if ($ibforums->vars['short_forum_jump'] != 1)
						{
							foreach($children[$idx] as $ii => $tt)
							{
								$the_html .= $tt;
							}
						}
						else
						{
							$the_html = str_replace( "</option><!--fx:$idx-->", " (+".count($children[$idx])." {$ibforums->lang['fj_subforums']})</option>", $the_html );
						}
					}
					else
					{
						$the_html .= $forum_text;
					}
				}
			}
		}
			
		
		if ($html == 1)
		{
			$the_html .= "</optgroup>\n</select>&nbsp;<input type='submit' value='{$ibforums->lang['jmp_go']}' class='forminput' /></form>";
		}
		
		return $the_html;
		
	}
	
	function clean_email($email = "") {

		$email = trim($email);
		
		$email = str_replace( " ", "", $email );
		
    	$email = preg_replace( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", "", $email );
    	
    	if ( preg_match( "/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/", $email) )
    	{
    		return $email;
    	}
    	else
    	{
    		return FALSE;
    	}
	}
    
    
    /*-------------------------------------------------------------------------*/
    // SKIN, sort out the skin stuff                 
    /*-------------------------------------------------------------------------*/
    
    function load_skin() {
    	global $ibforums, $INFO, $DB;
    	
    	$id       = -1;
    	$skin_set = 0;
    	
    	if ( ( $ibforums->is_bot == 1 ) and ($ibforums->vars['spider_suit'] != "") )
    	{
    		$skin_set = 1;
    		$id       = $ibforums->vars['spider_suit'];
    	}
    	else
    	{
			//------------------------------------------------
			// Do we have a skin for a particular forum?
			//------------------------------------------------
			
			if ($ibforums->input['f'] and $ibforums->input['act'] != 'UserCP')
			{
				if ( $ibforums->vars[ 'forum_skin_'.$ibforums->input['f'] ] != "" )
				{
					$id = $ibforums->vars[ 'forum_skin_'.$ibforums->input['f'] ];
					
					$skin_set = 1;
				}
			}
			
			//------------------------------------------------
			// Are we allowing user chooseable skins?
			//------------------------------------------------
			
			$extra = "";
			
			if ($skin_set != 1 and $ibforums->vars['allow_skins'] == 1)
			{
				if (isset($ibforums->input['skinid']))
				{
					$id    = intval($ibforums->input['skinid']);
					$extra = " AND s.hidden=0";
					$skin_set = 1;
				}
				else if ( $ibforums->member['skin'] != "" and intval($ibforums->member['skin']) >= 0 )
				{
					$id = $ibforums->member['skin'];
					
					if ($id == 'Default') $id = -1;
					
					$skin_set = 1;
				}
			}
    	}
    	
    	//------------------------------------------------
    	// Load the info from the database.
    	//------------------------------------------------
    	
    	if ( $id >= 0 and $skin_set == 1)
    	{
    		$DB->query("SELECT s.*, t.template, c.css_text
    					FROM ibf_skins s
    					  LEFT JOIN ibf_templates t ON (t.tmid=s.tmpl_id)
    					  LEFT JOIN ibf_css c ON (c.cssid=s.css_id)
    	           	   WHERE s.sid=$id".$extra);
    	           	   
    	    // Didn't get a row?
    	    
    	    if (! $DB->get_num_rows() )
    	    {
    	    	// Update this members profile
    	    	
    	    	if ( $ibforums->member['id'] )
    	    	{
    	    		$DB->query("UPDATE ibf_members SET skin='-1' WHERE id='".$ibforums->member['id']."'");
    	    	}
    	    	
    	    		$DB->query("SELECT s.*, t.template, c.css_text
    							FROM ibf_skins s
    					  		 LEFT JOIN ibf_templates t ON (t.tmid=s.tmpl_id)
    					 		 LEFT JOIN ibf_css c ON (s.css_id=c.cssid)
    	           	   		    WHERE s.default_set=1");
    	    }
    	    
    	}
    	else
    	{
    		$DB->query("SELECT s.*, t.template, c.css_text
    					FROM ibf_skins s
    					  LEFT JOIN ibf_templates t ON (t.tmid=s.tmpl_id)
    					  LEFT JOIN ibf_css c ON (s.css_id=c.cssid)
    	           	   WHERE s.default_set=1");
    	}
    	
    	if ( ! $row = $DB->fetch_row() )
    	{
    		echo("Could not query the skin information!");
    		exit();
    	}
    	
    	//-------------------------------------------
    	// Setting the skin?
    	//-------------------------------------------
    	
    	if ( ($ibforums->input['setskin']) and ($ibforums->member['id']) )
    	{
    		$DB->query( "UPDATE ibf_members SET skin=".intval($row['sid'])." WHERE id=".intval($ibforums->member['id']) );
    		
    		$ibforums->member['skin'] = $row['sid'];
    	}
    	
    	return $row;
    	
    }
    
    /*-------------------------------------------------------------------------*/
    // Require, parse and return an array containing the language stuff                 
    /*-------------------------------------------------------------------------*/ 
    
    function load_words($current_lang_array, $area, $lang_type) {
    
        require ROOT_PATH."lang/".$lang_type."/".$area.".php";
        
        foreach ($lang as $k => $v)
        {
        	$current_lang_array[$k] = stripslashes($v);
        }
        
        unset($lang);
        
        return $current_lang_array;

    }

    
    /*-------------------------------------------------------------------------*/
    // Return a date or '--' if the date is undef.
    // We use the rather nice gmdate function in PHP to synchronise our times
    // with GMT. This gives us the following choices:
    //
    // If the user has specified a time offset, we use that. If they haven't set
    // a time zone, we use the default board time offset (which should automagically
    // be adjusted to match gmdate.             
    /*-------------------------------------------------------------------------*/    
    
    function get_date($date, $method) {
        global $ibforums;
        
        if (!$date)
        {
            return '--';
        }
        
        if (empty($method))
        {
        	$method = 'LONG';
        }
        
        if ($this->offset_set == 0)
        {
        	// Save redoing this code for each call, only do once per page load
        	
			$this->offset = $this->get_time_offset();
			
			$this->offset_set = 1;
        }
        
        
        return gmdate($this->time_options[$method], ($date + $this->offset) );
    }
    
    /*-------------------------------------------------------------------------*/
    // Returns the offset needed and stuff - quite groovy.              
    /*-------------------------------------------------------------------------*/    
    
    function get_time_offset()
    {
    	global $ibforums;
    	
    	$r = 0;
    	
    	$r = (($ibforums->member['time_offset'] != "") ? $ibforums->member['time_offset'] : $ibforums->vars['time_offset']) * 3600;
			
		if ( $ibforums->vars['time_adjust'] )
		{
			$r += ($ibforums->vars['time_adjust'] * 60);
		}
		
		if ($ibforums->member['dst_in_use'])
		{
			$r += 3600;
		}
    	
    	return $r;
    	
    }
    
    /*-------------------------------------------------------------------------*/
    // Sets a cookie, abstract layer allows us to do some checking, etc                
    /*-------------------------------------------------------------------------*/    
    
    function my_setcookie($name, $value = "", $sticky = 1) {
        global $INFO;
        
        //$expires = "";
        
        if ($sticky == 1)
        {
        	$expires = time() + 60*60*24*365;
        }

        $INFO['cookie_domain'] = $INFO['cookie_domain'] == "" ? ""  : $INFO['cookie_domain'];
        $INFO['cookie_path']   = $INFO['cookie_path']   == "" ? "/" : $INFO['cookie_path'];
        
        $name = $INFO['cookie_id'].$name;
      
        @setcookie($name, $value, $expires, $INFO['cookie_path'], $INFO['cookie_domain']);
    }
    
    /*-------------------------------------------------------------------------*/
    // Cookies, cookies everywhere and not a byte to eat.                
    /*-------------------------------------------------------------------------*/  
    
    function my_getcookie($name)
    {
    	global $INFO, $HTTP_COOKIE_VARS;
    	
    	if (isset($HTTP_COOKIE_VARS[$INFO['cookie_id'].$name]))
    	{
    		return urldecode($HTTP_COOKIE_VARS[$INFO['cookie_id'].$name]);
    	}
    	else
    	{
    		return FALSE;
    	}
    	
    }
    
    /*-------------------------------------------------------------------------*/
    // Makes incoming info "safe"              
    /*-------------------------------------------------------------------------*/
    
    function parse_incoming()
    {
    	global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_CLIENT_IP, $REQUEST_METHOD, $REMOTE_ADDR, $HTTP_PROXY_USER, $HTTP_X_FORWARDED_FOR;
    	$return = array();
    	
		if( is_array($HTTP_GET_VARS) )
		{
			while( list($k, $v) = each($HTTP_GET_VARS) )
			{
				if ( $k == 'INFO' )
				{
					continue;
				}
				
				if( is_array($HTTP_GET_VARS[$k]) )
				{
					while( list($k2, $v2) = each($HTTP_GET_VARS[$k]) )
					{
						$return[$k][ $this->clean_key($k2) ] = $this->clean_value($v2);
					}
				}
				else
				{
					$return[$k] = $this->clean_value($v);
				}
			}
		}
		
		// Overwrite GET data with post data
		
		if( is_array($HTTP_POST_VARS) )
		{
			while( list($k, $v) = each($HTTP_POST_VARS) )
			{
				if ( is_array($HTTP_POST_VARS[$k]) )
				{
					while( list($k2, $v2) = each($HTTP_POST_VARS[$k]) )
					{
						$return[$k][ $this->clean_key($k2) ] = $this->clean_value($v2);
					}
				}
				else
				{
					$return[$k] = $this->clean_value($v);
				}
			}
		}
		
		//----------------------------------------
		// Sort out the accessing IP
		// (Thanks to Cosmos and schickb)
		//----------------------------------------
		
		$addrs = array();
		
		foreach( array_reverse( explode( ',', $HTTP_X_FORWARDED_FOR ) ) as $x_f )
		{
			$x_f = trim($x_f);
			
			if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )
			{
				$addrs[] = $x_f;
			}
		}
		
		$addrs[] = $_SERVER['REMOTE_ADDR'];
		$addrs[] = $HTTP_PROXY_USER;
		$addrs[] = $REMOTE_ADDR;
		
		//header("Content-type: text/plain"); print_r($addrs); print $_SERVER['HTTP_X_FORWARDED_FOR']; exit();
		
		$return['IP_ADDRESS'] = $this->select_var( $addrs );
												 
		// Make sure we take a valid IP address
		
		$return['IP_ADDRESS'] = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3.\\4", $return['IP_ADDRESS'] );
		
		$return['request_method'] = ( $_SERVER['REQUEST_METHOD'] != "" ) ? strtolower($_SERVER['REQUEST_METHOD']) : strtolower($REQUEST_METHOD);
		
		return $return;
	}
	
    /*-------------------------------------------------------------------------*/
    // Key Cleaner - ensures no funny business with form elements             
    /*-------------------------------------------------------------------------*/
    
    function clean_key($key) {
    
    	if ($key == "")
    	{
    		return "";
    	}
    	$key = preg_replace( "/\.\./"           , ""  , $key );
    	$key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
    	$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
    	return $key;
    }
    
    function clean_value($val)
    {
    	global $ibforums;
    	
    	if ($val == "")
    	{
    		return "";
    	}
    	
    	$val = str_replace( "&#032;", " ", $val );
    	
    	if ( $ibforums->vars['strip_space_chr'] )
    	{
    		$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
    	}
    	
    	$val = str_replace( "&"            , "&amp;"         , $val );
    	$val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"          , "--&#62;"       , $val );
    	$val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
    	$val = str_replace( ">"            , "&gt;"          , $val );
    	$val = str_replace( "<"            , "&lt;"          , $val );
    	$val = str_replace( "\""           , "&quot;"        , $val );
    	$val = preg_replace( "/\n/"        , "<br>"          , $val ); // Convert literal newlines
    	$val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
    	$val = preg_replace( "/\r/"        , ""              , $val ); // Remove literal carriage returns
    	$val = str_replace( "!"            , "&#33;"         , $val );
    	$val = str_replace( "'"            , "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.
    	
    	// Ensure unicode chars are OK
    	
    	if ( $this->allow_unicode )
		{
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );
		}
		
		// Strip slashes if not already done so.
		
    	if ( $this->get_magic_quotes )
    	{
    		$val = stripslashes($val);
    	}
    	
    	// Swop user inputted backslashes
    	
    	$val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val ); 
    	
    	return $val;
    }
    
    
    function remove_tags($text="")
    {
    	// Removes < BOARD TAGS > from posted forms
    	
    	$text = preg_replace( "/(<|&lt;)% (BOARD HEADER|CSS|JAVASCRIPT|TITLE|BOARD|STATS|GENERATOR|COPYRIGHT|NAVIGATION) %(>|&gt;)/i", "&#60;% \\2 %&#62;", $text );
    	
    	//$text = str_replace( "<%", "&#60;%", $text );
    	
    	return $text;
    }
    
    function is_number($number="")
    {
    
    	if ($number == "") return -1;
    	
    	if ( preg_match( "/^([0-9]+)$/", $number ) )
    	{
    		return $number;
    	}
    	else
    	{
    		return "";
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // MEMBER FUNCTIONS             
    /*-------------------------------------------------------------------------*/
    
    
    function set_up_guest($name='Guest') {
    	global $INFO;
    
    	return array( 'name'     => $name,
    				  'id'       => 0,
    				  'password' => "",
    				  'email'    => "",
    				  'title'    => "Unregistered",
    				  'mgroup'    => $INFO['guest_group'],
    				  'view_sigs' => $INFO['guests_sig'],
    				  'view_img'  => $INFO['guests_img'],
    				  'view_avs'  => $INFO['guests_ava'],
    				);
    }
    
    /*-------------------------------------------------------------------------*/
    // GET USER AVATAR         
    /*-------------------------------------------------------------------------*/
    
    function get_avatar($member_avatar="", $member_view_avatars=0, $avatar_dims="x")
    {
    	global $ibforums;
    	
    	if (!$member_avatar or $member_view_avatars == 0 or !$ibforums->vars['avatars_on'])
    	{
    		return "";
    	}
    	
    	if (preg_match ( "/^noavatar/", $member_avatar ))
    	{
    		return "";
    	}
    	
    	if ( (preg_match ( "/\.swf/", $member_avatar)) and ($ibforums->vars['allow_flash'] != 1) )
    	{
    		return "";
    	}
    	
    	$davatar_dims    = explode( "x", $ibforums->vars['avatar_dims'] );
		$default_a_dims  = explode( "x", $ibforums->vars['avatar_def'] );
    	
    	//---------------------------------------
		// Have we enabled URL / Upload avatars?
		//---------------------------------------
	 
		$this_dims = explode( "x", $avatar_dims );
		if (!$this_dims[0]) $this_dims[0] = $davatar_dims[0];
		if (!$this_dims[1]) $this_dims[1] = $davatar_dims[1];
			
		if ( preg_match( "/^http:\/\//", $member_avatar ) )
		{
			// Ok, it's a URL..
			
			if (preg_match ( "/\.swf/", $member_avatar))
			{
				return "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" width='{$this_dims[0]}' height='{$this_dims[1]}'>
						<param name='movie' value='{$member_avatar}'><param name='play' value='true'>
						<param name='loop' value='true'><param name='quality' value='high'>
						<embed src='{$member_avatar}' width='{$this_dims[0]}' height='{$this_dims[1]}' play='true' loop='true' quality='high'></embed>
						</object>";
			}
			else
			{
				return "<img src='{$member_avatar}' border='0' width='{$this_dims[0]}' height='{$this_dims[1]}' alt='' />";
			}
			
			//---------------------------------------
			// Not a URL? Is it an uploaded avatar?
			//---------------------------------------
		}
		else if ( ($ibforums->vars['avup_size_max'] > 1) and ( preg_match( "/^upload:av-(?:\d+)\.(?:\S+)/", $member_avatar ) ) )
		{
			$member_avatar = preg_replace( "/^upload:/", "", $member_avatar );
			
			if ( preg_match ( "/\.swf/", $member_avatar) )
			{
				return "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" width='{$this_dims[0]}' height='{$this_dims[1]}'>
						<param name='movie' value='{$ibforums->vars['upload_url']}/$member_avatar'><param name='play' value='true'>
						<param name='loop' value='true'><param name='quality' value='high'>
					    <embed src='{$ibforums->vars['upload_url']}/$member_avatar\' width='{$this_dims[0]}' height='{$this_dims[1]}' play='true' loop='true' quality='high'></embed>
						</object>";
			}
			else
			{
				return "<img src='{$ibforums->vars['upload_url']}/$member_avatar' border='0' width='{$this_dims[0]}' height='{$this_dims[1]}' alt='' />";
			}
		}
		
		//---------------------------------------
		// No, it's not a URL or an upload, must
		// be a normal avatar then
		//---------------------------------------
		
		else if ($member_avatar != "")
		{
			//---------------------------------------
			// Do we have an avatar still ?
		   	//---------------------------------------
		   	
			return "<img src='{$ibforums->vars['AVATARS_URL']}/{$member_avatar}' border='0' alt='' />";
		}
		else
		{
			//---------------------------------------
			// No, ok - return blank
			//---------------------------------------
			
			return "";
		}
    }
 
 
 
 
    /*-------------------------------------------------------------------------*/
    // ERROR FUNCTIONS             
    /*-------------------------------------------------------------------------*/
    
    function Error($error) {
    	global $DB, $ibforums, $skin_universal;
    	
    	//INIT is passed to the array if we've not yet loaded a skin and stuff
    	
    	if ( $error['INIT'] == 1)
    	{
    		
    		$DB->query("SELECT s.*, t.template, c.css_text
    					FROM ibf_skins s
    					  LEFT JOIN ibf_templates t ON (t.tmid=s.tmpl_id)
    					  LEFT JOIN ibf_css c ON (s.css_id=c.cssid)
    	           	   WHERE s.default_set=1");
    	           	   
    	    $ibforums->skin = $DB->fetch_row();
    	           	   
    		$ibforums->session_id = $this->my_getcookie('session_id');

			$ibforums->base_url   = $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?s='.$ibforums->session_id;
			$ibforums->skin_rid   = $ibforums->skin['set_id'];
			$ibforums->skin_id    = 's'.$ibforums->skin['set_id'];
			
			if ($ibforums->vars['default_language'] == "")
			{
				$ibforums->vars['default_language'] = 'en';
			}
			
			$ibforums->lang_id = $ibforums->member['language'] ? $ibforums->member['language'] : $ibforums->vars['default_language'];
			
			if ( ($ibforums->lang_id != $ibforums->vars['default_language']) and (! is_dir( ROOT_PATH."lang/".$ibforums->lang_id ) ) )
			{
				$ibforums->lang_id = $ibforums->vars['default_language'];
			}
			
			$ibforums->lang = $this->load_words($ibforums->lang, "lang_global", $ibforums->lang_id);
			
			$ibforums->vars['img_url']   = 'style_images/' . $ibforums->skin['img_dir'];
			
			$skin_universal = $this->load_template('skin_global');

		}
		
    	$ibforums->lang = $this->load_words($ibforums->lang, "lang_error", $ibforums->lang_id);
    	
    	list($em_1, $em_2) = explode( '@', $ibforums->vars['email_in'] );
    	
    	$msg = $ibforums->lang[ $error['MSG'] ];
    	
    	if ($error['EXTRA'])
    	{
    		$msg = preg_replace( "/<#EXTRA#>/", $error['EXTRA'], $msg );
    	}
    	
    	$html = $skin_universal->Error( $msg, $em_1, $em_2);
    	
    	//-----------------------------------------
    	// If we're a guest, show the log in box..
    	//-----------------------------------------
    	
    	if ($ibforums->member['id'] == "" and $error['MSG'] != 'server_too_busy' and $error['MSG'] != 'account_susp')
    	{
    		$html = str_replace( "<!--IBF.LOG_IN_TABLE-->", $skin_universal->error_log_in($_SERVER['QUERY_STRING']), $html);
    	}
    	
    	//-----------------------------------------
    	// Do we have any post data to keepy?
    	//-----------------------------------------
    	
    	if ( $ibforums->input['act'] == 'Post' OR $ibforums->input['act'] == 'Msg' OR $ibforums->input['act'] == 'calendar' )
    	{
    		if ( $_POST['Post'] )
    		{
    			$post_thing = $skin_universal->error_post_textarea($this->txt_htmlspecialchars($this->txt_stripslashes($_POST['Post'])) );
    			
    			$html = str_replace( "<!--IBF.POST_TEXTAREA-->", $post_thing, $html );
    		}
    	}
    	
    	
    	$print = new display();
    	
    	$print->add_output($html);
    		
    	$print->do_output( array(
    								OVERRIDE   => 1,
    								TITLE      => $ibforums->lang['error_title'],
    							 )
    					  );
    }
    
    
    
    
    function board_offline()
    {
    	global $DB, $ibforums, $root_path, $skin_universal;
    	
    	$ibforums->lang = $this->load_words($ibforums->lang, "lang_error", $ibforums->lang_id);
    	
    	$msg = preg_replace( "/\n/", "<br>", stripslashes($ibforums->vars['offline_msg']) );
    	
    	$html = $skin_universal->board_offline( $msg );
    	
    	$print = new display();
    	
    	$print->add_output($html);
    		
    	$print->do_output( array(
    								OVERRIDE   => 1,
    								TITLE      => $ibforums->lang['offline_title'],
    							 )
    					  );
    }
    								
    /*-------------------------------------------------------------------------*/
    // Variable chooser             
    /*-------------------------------------------------------------------------*/
    
    function select_var($array) {
    	
    	if ( !is_array($array) ) return -1;
    	
    	ksort($array);
    	
    	
    	$chosen = -1;  // Ensure that we return zero if nothing else is available
    	
    	foreach ($array as $k => $v)
    	{
    		if (isset($v))
    		{
    			$chosen = $v;
    			break;
    		}
    	}
    	
    	return $chosen;
    }
      
    
} // end class


//######################################################
// Our "print" class
//######################################################


class display {

    var $to_print = "";
    
    //-------------------------------------------
    // Appends the parsed HTML to our class var
    //-------------------------------------------
    
    function add_output($to_add) {
        $this->to_print .= $to_add;
        //return 'true' on success
        return true;
    }
    
    //-------------------------------------------
    // Parses all the information and prints it.
    //-------------------------------------------
    
    function do_output($output_array)
    {
        global $DB, $Debug, $skin_universal, $ibforums;
        
       
		// Note, this is designed to allow IPS validate boards who've purchased copyright removal / registration . The order number
		// is the only thing shown and the order number is unique to the person who paid and is no good to anyone else.
		// Showing the order number poses no risk at all - the information is useless to anyone outside of IPS.
		
		if ( $ibforums->input['ipscheck'] )
		{
			if ( $ibforums->input['ipscheck'] == 'copy' )
			{
				flush();
				print preg_replace( "/^(\d+?)-(\d+?)-(\d+?)-(\d+?)$/", "\\2", $ibforums->vars['ipb_copy_number'] );
				exit();
			}
			else if ( $ibforums->input['ipscheck'] == 'reg' )
			{
				flush();
				print preg_replace( "/^(\d+?)-(\d+?)-(\d+?)-(\d+?)-(\d+?)$/", "\\2", $ibforums->vars['ipb_reg_number'] );
				exit();
			}
        }	
        
        $TAGS = $DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set={$ibforums->skin['macro_id']}");
        
        $ex_time     = sprintf( "%.4f",$Debug->endTimer() );
        
        $query_cnt   = $DB->get_query_cnt();
        
        if ($DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>mySQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print $ibforums->debug_html;
        	print "</body></html>";
        	exit();
        }
        
        $input   = "";
        $queries = "";
        $sload   = "";
        
        $gzip_status = $ibforums->vars['disable_gzip'] == 1 ? $ibforums->lang['gzip_off'] : $ibforums->lang['gzip_on'];
        
        if ($ibforums->server_load > 0)
        {
        	$sload = '&nbsp; [ Server Load: '.$ibforums->server_load.' ]';
        }
        
        //+----------------------------------------------
        
        if ($ibforums->vars['debug_level'] > 0)
        {
        
			$stats = "<br clear='all' />\n<br />\n<div align='center'>[ Script Execution time: $ex_time ] &nbsp; [ $query_cnt queries used ] &nbsp; [ $gzip_status ] $sload</div>\n<br />";
        }
        
        		  
       //+----------------------------------------------
        		  
       if ($ibforums->vars['debug_level'] >= 2)
       {
       		$stats .= "<br />\n<div class='tableborder'>\n<div class='pformstrip'>FORM and GET Input</div><div class='row1' style='padding:6px'>\n";
        
			while( list($k, $v) = each($ibforums->input) )
			{
				$stats .= "<strong>$k</strong> = $v<br />\n";
			}
			
			$stats .= "</div>\n</div>";
        
        }
        
        //+----------------------------------------------
        
        if ($ibforums->vars['debug_level'] >= 3)
        {
           	$stats .= "<br />\n<div class='tableborder'>\n<div class='pformstrip'>Queries Used</div><div class='row1' style='padding:6px'>";
       					
        	foreach($DB->obj['cached_queries'] as $q)
        	{
        		$q = htmlspecialchars($q);
        		$q = preg_replace( "/^SELECT/i" , "<span class='red'>SELECT</span>"   , $q );
        		$q = preg_replace( "/^UPDATE/i" , "<span class='blue'>UPDATE</span>"  , $q );
        		$q = preg_replace( "/^DELETE/i" , "<span class='orange'>DELETE</span>", $q );
        		$q = preg_replace( "/^INSERT/i" , "<span class='green'>INSERT</span>" , $q );
        		$q = str_replace( "LEFT JOIN"   , "<span class='red'>LEFT JOIN</span>" , $q );
        		
        		$q = preg_replace( "/(".$ibforums->vars['sql_tbl_prefix'].")(\S+?)([\s\.,]|$)/", "<span class='purple'>\\1\\2</span>\\3", $q );
        		
        		$stats .= "$q<hr />\n";
        	}
        	
        	$stats .= "</div>\n</div>";
        }

        
        /********************************************************/
        // NAVIGATION
        
        $nav  = $skin_universal->start_nav();
        
        $nav .= "<a href='{$ibforums->base_url}act=idx'>{$ibforums->vars['board_name']}</a>";
        
        if ( empty($output_array['OVERRIDE']) )
        {
			if (is_array( $output_array['NAV'] ) )
			{
				foreach ($output_array['NAV'] as $n)
				{
					if ($n)
					{
						$nav .= "<{F_NAV_SEP}>" . $n;
					}
				}
			}
        }
        
        $nav .= $skin_universal->end_nav();
     
        //---------------------------------------------------------
        // CSS
        //---------------------------------------------------------
        
        if ( $ibforums->skin['css_method'] == 'external' )
        {
        	$css = $skin_universal->css_external($ibforums->skin['css_id'], $ibforums->skin['img_dir']);
        }
        else
        {
        	$css = $skin_universal->css_inline(  str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $ibforums->skin['css_text'] ) );
        }
        
        //---------------------------------------------------------
        
        $extra = "";
        $ur    = '(U)';
        
        if ( $ibforums->vars['ipb_reg_number'] )
        {
        	$ur = '(R)';
        	
        	if ( $ibforums->vars['ipb_reg_show'] and $ibforums->vars['ipb_reg_name'] )
        	{
        		$extra = "<div align='center' class='copyright'>Registered to: ". $ibforums->vars['ipb_reg_name']."</div>";
        	}
        }
		
		// Yes, I realise that this is silly and easy to remove the copyright, but
		// as it's not concealed source, there's no point having a 1337 fancy hashing
		// algorithm if all you have to do is delete a few lines, so..
		// However, be warned: If you remove the copyright and you have not purchased
		// copyright removal, you WILL be spotted and your licence to use Invision Power Board
		// will be terminated, requiring you to remove your board immediately.
		// So, have a nice day.
		
        $copyright = "<!-- Copyright Information -->\n\n<div align='center' class='copyright'>Powered by <a href=\"http://www.invisionboard.com\" target='_blank'>Invision Power Board</a>{$ur} {$ibforums->version} &copy; 2003 &nbsp;<a href='http://www.invisionpower.com' target='_blank'>IPS, Inc.</a></div>";
        
        if ($ibforums->vars['ips_cp_purchase'])
        {
        	$copyright = "";
        }
        
        $copyright .= $extra;
        
        // Awww, cmon, don't be mean! Literally thousands of hours have gone into
        // coding Invision Power Board and all we ask in return is one measly little line
        // at the bottom. That's fair isn't it?
        // No? Hmmm...
        // Have you seen how much it costs to remove the copyright from UBB? o_O
                       
        /********************************************************/
        // Build the board header
        
        $this_header  = $skin_universal->BoardHeader();
        
        // Show rules link?
        
        if ($ibforums->vars['gl_show'] and $ibforums->vars['gl_title'])
        {
        	if ($ibforums->vars['gl_link'] == "")
        	{
        		$ibforums->vars['gl_link'] = $ibforums->base_url."act=boardrules";
        	}
        	
        	$this_header = str_replace( "<!--IBF.RULES-->", $skin_universal->rules_link($ibforums->vars['gl_link'], $ibforums->vars['gl_title']), $this_header );
        }
        
        //---------------------------------------
        // Build the members bar
		//---------------------------------------
		
        if ($ibforums->member['id'] == 0)
        {
        	$output_array['MEMBER_BAR'] = $skin_universal->Guest_bar();
        }
        else
        {
			$pm_js = "";
			
			if ( ($ibforums->member['g_max_messages'] > 0) and ($ibforums->member['msg_total'] >= $ibforums->member['g_max_messages']) )
			{
				$msg_data['TEXT'] = $ibforums->lang['msg_full'];
			}
			else
			{
				$ibforums->member['new_msg'] = $ibforums->member['new_msg'] == "" ? 0 : $ibforums->member['new_msg'];
			
				$msg_data['TEXT'] = sprintf( $ibforums->lang['msg_new'], $ibforums->member['new_msg']);
			}
			
			//---------------------------------------
			// Do we have a pop up to show?
			//---------------------------------------
			
			if ($ibforums->member['show_popup'])
			{
				$DB->query("UPDATE ibf_members SET show_popup=0 WHERE id={$ibforums->member['id']}");
				
				if ( $ibforums->input['act'] != 'Msg' )
				{
					$pm_js = $skin_universal->PM_popup();
				}
			}
			
			if ( ($ibforums->member['is_mod']) or ($ibforums->member['g_is_supmod'] == 1) )
			{
				$mod_link = $skin_universal->mod_link();
			}
	
			$admin_link = $ibforums->member['g_access_cp'] ? $skin_universal->admin_link() : '';
			$valid_link = $ibforums->member['mgroup'] == $ibforums->vars['auth_group'] ? $skin_universal->validating_link() : '';
			
			if ( ! $ibforums->member['g_use_pm'])
        	{
        		$output_array['MEMBER_BAR'] = $skin_universal->Member_no_usepm_bar($admin_link, $mod_link, $valid_link);
        	}
        	else
			{
				$output_array['MEMBER_BAR'] = $pm_js . $skin_universal->Member_bar($msg_data, $admin_link, $mod_link, $valid_link);
			}
 			
 		}
 		
 		if ($ibforums->vars['board_offline'] == 1)
 		{
 			$output_array['TITLE'] = $ibforums->lang['warn_offline']." ".$output_array['TITLE'];
 		}
        
        //---------------------------------------
        // Get the template
        //---------------------------------------
        
        $ibforums->skin['template'] = str_replace( "<% CSS %>"            , $css                     , $ibforums->skin['template']);
		$ibforums->skin['template'] = str_replace( "<% JAVASCRIPT %>"     , ""                       , $ibforums->skin['template']);
        $ibforums->skin['template'] = str_replace( "<% TITLE %>"          , $output_array['TITLE']   , $ibforums->skin['template']);
        $ibforums->skin['template'] = str_replace( "<% BOARD %>"          , $this->to_print          , $ibforums->skin['template']);
        $ibforums->skin['template'] = str_replace( "<% STATS %>"          , $stats                   , $ibforums->skin['template']);
        $ibforums->skin['template'] = str_replace( "<% GENERATOR %>"      , ""                       , $ibforums->skin['template']);
		$ibforums->skin['template'] = str_replace( "<% COPYRIGHT %>"      , $copyright               , $ibforums->skin['template']);
		$ibforums->skin['template'] = str_replace( "<% BOARD HEADER %>"   , $this_header             , $ibforums->skin['template']);
		$ibforums->skin['template'] = str_replace( "<% NAVIGATION %>"     , $nav                     , $ibforums->skin['template']);
		
		if ( empty($output_array['OVERRIDE']) )
		{
      	    $ibforums->skin['template'] = str_replace( "<% MEMBER BAR %>"     , $output_array['MEMBER_BAR'], $ibforums->skin['template']);
        }
        else
        {
      	    $ibforums->skin['template'] = str_replace( "<% MEMBER BAR %>"     , $skin_universal->member_bar_disabled(), $ibforums->skin['template']);
      	}
      	
      	
		//+--------------------------------------------
		// Stick in chat link? top_site_list_integrate
		//+--------------------------------------------
		
		if ($ibforums->vars['chat_account_no'])
		{
			$ibforums->vars['chat_height'] += $ibforums->vars['chat_poppad'] ? $ibforums->vars['chat_poppad'] : 50;
			$ibforums->vars['chat_width']  += $ibforums->vars['chat_poppad'] ? $ibforums->vars['chat_poppad'] : 50;
			
			$chat_link = ( $ibforums->vars['chat_display'] == 'self' )
					   ? $skin_universal->show_chat_link_inline()
					   : $skin_universal->show_chat_link_popup();
			
			$ibforums->skin['template'] = str_replace( "<!--IBF.CHATLINK-->", $chat_link, $ibforums->skin['template'] );
		}
		
		//+--------------------------------------------
		// Stick in TSL link? 
		//+--------------------------------------------
		
		if ($ibforums->vars['top_site_list_integrate'])
		{
			$ibforums->skin['template'] = str_replace( "<!--IBF.TSLLINK-->", $skin_universal->show_tsl_link_inline(), $ibforums->skin['template'] );
		}
      	
      	//+--------------------------------------------
      	//| Get the macros and replace them
      	//+--------------------------------------------
      	
      	while ( $row = $DB->fetch_row($TAGS) )
      	{
			if ($row['macro_value'] != "")
			{
				$ibforums->skin['template'] = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $ibforums->skin['template'] );
			}
		}
		
		$ibforums->skin['template'] = str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $ibforums->skin['template'] );
		
		/*$ibforums->skin['template'] = preg_replace( "#img\s+?src=[\"'](?!http://)(.+?)[\"'](.+?)?>#is", "img src=\"http://domain.com/\\1\"\\2>", $ibforums->skin['template'] );*/
		
		//---------------------------------------
		// Close this DB connection
		//---------------------------------------
		
		$DB->close_db();
		
		//---------------------------------------
		// Start GZIP compression
        //---------------------------------------
        
        if ($ibforums->vars['disable_gzip'] != 1)
        {
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	ob_start('ob_gzhandler');
        	print $buffer;
        }
        
        $this->do_headers();
		
        print $ibforums->skin['template'];
        
        exit;
    }
    
    //-------------------------------------------
    // print the headers
    //-------------------------------------------
        
    function do_headers() {
    	global $ibforums;
    	
    	if ($ibforums->vars['print_headers'])
    	{
			@header("HTTP/1.0 200 OK");
			@header("HTTP/1.1 200 OK");
			@header("Content-type: text/html");
			
			if ($ibforums->vars['nocache'])
			{
				@header("Cache-Control: no-cache, must-revalidate, max-age=0");
				@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				@header("Pragma: no-cache");
			}
        }
    }
    
    //-------------------------------------------
    // print a pure redirect screen
    //-------------------------------------------
    
    
    function redirect_screen($text="", $url="", $override=0)
    {
    	global $ibforums, $skin_universal, $DB;
    	
    	if ($ibforums->input['debug'])
        {
        	flush();
        	exit();
        }
        
        if ( $override != 1 )
        {
			if ( $ibforums->base_url )
			{
				$url = $ibforums->base_url.$url;
			}
			else
			{
				$url = "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}?".$url;
			}
    	}
    	
    	$ibforums->lang['stand_by'] = stripslashes($ibforums->lang['stand_by']);
    	
    	//---------------------------------------------------------
        // CSS
        //---------------------------------------------------------
        
        if ( $ibforums->skin['css_method'] == 'external' )
        {
        	$css = $skin_universal->css_external($ibforums->skin['css_id'], $ibforums->skin['img_dir']);
        }
        else
        {
        	$css = $skin_universal->css_inline( str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $ibforums->skin['css_text'] ) );
        }
          	
    	$htm = $skin_universal->Redirect($text, $url, $css);
    	
    	$TAGS = $DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set='{$ibforums->skin['macro_id']}'");
    	
    	while ( $row = $DB->fetch_row($TAGS) )
      	{
			if ($row['macro_value'] != "")
			{
				$htm = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $htm );
			}
		}
		
		$htm = str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $htm );
    	
    	// Close this DB connection
		
		$DB->close_db();
		
		// Start GZIP compression
        
        if ($ibforums->vars['disable_gzip'] != 1)
        {
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	ob_start('ob_gzhandler');
        	print $buffer;
        }
        
        $this->do_headers();
        
    	echo ($htm);
    	exit;
    }
    
    //-------------------------------------------
    // print a minimalist screen suitable for small
    // pop up windows
    //-------------------------------------------
    
    function pop_up_window($title = 'Invision Power Board', $text = "" )
    {
    	global $ibforums, $skin_universal, $DB;
    	
    	//---------------------------------------------------------
        // CSS
        //---------------------------------------------------------
        
        if ( $ibforums->skin['css_method'] == 'external' )
        {
        	$css = $skin_universal->css_external($ibforums->skin['css_id'], $ibforums->skin['img_dir']);
        }
        else
        {
        	$css = $skin_universal->css_inline( str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $ibforums->skin['css_text'] ) );
        }
		
    	$html = $skin_universal->pop_up_window($title, $css, $text);
    	        
    	$TAGS = $DB->query("SELECT macro_value, macro_replace FROM ibf_macro WHERE macro_set='{$ibforums->skin['macro_id']}'");
    	
    	while ( $row = $DB->fetch_row($TAGS) )
      	{
			if ($row['macro_value'] != "")
			{
				$html = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $html );
			}
		}
		
		$html = str_replace( "<#IMG_DIR#>", $ibforums->skin['img_dir'], $html );
    	
    	$DB->close_db();
    	  
    	if ($ibforums->vars['disable_gzip'] != 1)
        {
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	ob_start('ob_gzhandler');
        	print $buffer;
        }
        
        $this->do_headers();
        
    	echo ($html);
    	exit;
    } 
    
    
    
} // END class
    



//######################################################
// Our "session" class
//######################################################


class session {

    var $ip_address = 0;
    var $user_agent = "";
    var $time_now   = 0;
    var $session_id = 0;
    var $session_dead_id = 0;
    var $session_user_id = 0;
    var $session_user_pass = "";
    var $last_click        = 0;
    var $location          = "";
    var $member            = array();

    // No need for a constructor
    
    function authorise()
    {
        global $DB, $INFO, $ibforums, $std, $HTTP_SERVER_VARS;
        
        //-------------------------------------------------
        // Before we go any lets check the load settings..
        //-------------------------------------------------
        
        if ($ibforums->vars['load_limit'] > 0)
        {
        	if ( file_exists('/proc/loadavg') )
        	{
        		if ( $fh = @fopen( '/proc/loadavg', 'r' ) )
        		{
        			$data = @fread( $fh, 6 );
        			@fclose( $fh );
        			
        			$load_avg = explode( " ", $data );
        			
        			$ibforums->server_load = trim($load_avg[0]);
        			
        			if ($ibforums->server_load > $ibforums->vars['load_limit'])
        			{
        				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'server_too_busy', 'INIT' => 1 ) );
        			}
        		}
        	}
        	else
        	{
				if ( $serverstats = @exec("uptime") )
				{
					preg_match( "/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $serverstats, $load );
					
					$ibforums->server_load = $load[1];
				}
			}
        }
       
        //--------------------------------------------
		// Are they banned?
		//--------------------------------------------
		
		if ($ibforums->vars['ban_ip'])
		{
			$ips = explode( "|", $ibforums->vars['ban_ip'] );
			
			foreach ($ips as $ip)
			{
				$ip = preg_replace( "/\*/", '.*' , preg_quote($ip, "/") );
				
				if ( preg_match( "/^$ip/", $ibforums->input['IP_ADDRESS'] ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'you_are_banned', 'INIT' => 1 ) );
				}
			}
		}
        
        //--------------------------------------------
        
        $this->member = array( 'id' => 0, 'password' => "", 'name' => "", 'mgroup' => $INFO['guest_group'] );
        
        //--------------------------------------------
        // no new headers if we're simply viewing an attachment..
        //--------------------------------------------
        
        if ( $ibforums->input['act'] == 'Attach' )
        {
        	return $this->member;
        }
        
        $HTTP_SERVER_VARS['HTTP_USER_AGENT'] = $std->clean_value($HTTP_SERVER_VARS['HTTP_USER_AGENT']);
        
        $this->ip_address = $ibforums->input['IP_ADDRESS'];
        $this->user_agent = substr($HTTP_SERVER_VARS['HTTP_USER_AGENT'],0,50);
        $this->time_now   = time();
        
        //-------------------------------------------------
        // Manage bots? (tee-hee)
        //-------------------------------------------------
        
        if ( $ibforums->vars['spider_sense'] == 1 )
        {
        
        	$remap_agents = array(
        						   'googlebot'     => 'google',
        						   'slurp@inktomi' => 'inktomi',
        						   'ask jeeves'    => 'jeeves',
        						   'lycos'         => 'lycos',
        						   'whatuseek'     => 'wuseek',
        						   'ia_archiver'   => 'Archive_org',
        						 );
        						
        	if ( preg_match( '/(googlebot|slurp@inktomi|ask jeeves|lycos|whatuseek|ia_archiver)/i', $HTTP_SERVER_VARS['HTTP_USER_AGENT'], $match ) )
        	{
        		
        		$DB->query("SELECT * from ibf_groups WHERE g_id=".$ibforums->vars['spider_group']);
        		
        		$group = $DB->fetch_row();
        
				foreach ($group as $k => $v)
				{
					$this->member[ $k ] = $v;
				}
				
				$this->member['restrict_post']    = 1;
				$this->member['g_use_search']     = 0;
				$this->member['g_email_friend']   = 0;
				$this->member['g_edit_profile']   = 0;
				$this->member['g_use_pm']         = 0;
				$this->member['g_is_supmod']      = 0;
				$this->member['g_access_cp']      = 0;
				$this->member['g_access_offline'] = 0;
				$this->member['g_avoid_flood']    = 0;
				$this->member['id']               = 0;
				
				$ibforums->perm_id       = $this->member['g_perm_id'];
       			$ibforums->perm_id_array = explode( ",", $ibforums->perm_id );
       			$ibforums->session_type  = 'cookie';
       			$ibforums->is_bot        = 1;
       			$this->session_id        = "";
       			
       			if ( ! $agent = $remap_agents[ $match[1] ] )
				{
					$agent = 'google';
				}	
       			
       			if ( $ibforums->vars['spider_visit'] )
       			{
       				$dba = $DB->compile_db_insert_string( array (
       																'bot'          => $agent,
       																'query_string' => str_replace( "'", "", $HTTP_SERVER_VARS['QUERY_STRING']),
       																'ip_address'   => $_SERVER['REMOTE_ADDR'],
       																'entry_date'   => time(),
       													)        );
       													
       				$DB->query("INSERT INTO ibf_spider_logs ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})");
       			}
       			
       			if ( $ibforums->vars['spider_active'] )
       			{
       				$DB->query("DELETE FROM ibf_sessions WHERE id='".$agent."_session'");
       				
       				$this->create_bot_session($agent);
       			}
       			
       			return $this->member;
        	}
        }
        
        //-------------------------------------------------
        // Continue!
        //-------------------------------------------------
        
        $cookie = array();
        $cookie['session_id']   = $std->my_getcookie('session_id');
        $cookie['member_id']    = $std->my_getcookie('member_id');
        $cookie['pass_hash']    = $std->my_getcookie('pass_hash');
        
       
        if ( $cookie['session_id'] )
        {
        	$this->get_session($cookie['session_id']);
        	$ibforums->session_type = 'cookie';
        }
        elseif ( $ibforums->input['s'] )
        {
        	$this->get_session($ibforums->input['s']);
        	$ibforums->session_type = 'url';
        }
        else
        {
        	$this->session_id = 0;
        }
        
        //-------------------------------------------------
        // Finalise the incoming data..
        //-------------------------------------------------
        
        $ibforums->input['Privacy'] = $std->select_var( array( 
															   1 => $ibforums->input['Privacy'],
															   2 => $std->my_getcookie('anonlogin')
												      )      );
												      
		//-------------------------------------------------								  
		// Do we have a valid session ID?
		//-------------------------------------------------
		
		if ( $this->session_id )
		{
			// We've checked the IP addy and browser, so we can assume that this is
			// a valid session.
			
			if ( ($this->session_user_id != 0) and ( ! empty($this->session_user_id) ) )
			{
				// It's a member session, so load the member.
				
				$this->load_member($this->session_user_id);
				
				// Did we get a member?
				
				if ( (! $this->member['id']) or ($this->member['id'] == 0) )
				{
					$this->unload_member();
					$this->update_guest_session();
				}
				else
				{
					$this->update_member_session();
				}
			}
			else
			{
				$this->update_guest_session();
			}
		
		}
		else
		{
			// We didn't have a session, or the session didn't validate
			
			// Do we have cookies stored?
			
			if ($cookie['member_id'] != "" and $cookie['pass_hash'] != "")
			{
				$this->load_member($cookie['member_id']);
				
				if ( (! $this->member['id']) or ($this->member['id'] == 0) )
				{
					$this->unload_member();
					$this->create_guest_session();
				}
				else
				{
					if ($this->member['password'] == $cookie['pass_hash'])
					{
						$this->create_member_session();
					}
					else
					{
						$this->unload_member();
						$this->create_guest_session();
					}
				}
			}
			else
			{
				$this->create_guest_session();
			}
		}
		
        //-------------------------------------------------
        // Set up a guest if we get here and we don't have a member ID
        //-------------------------------------------------
        
        if (! $this->member['id'])
        {
        	$this->member = $std->set_up_guest();
        	$DB->query("SELECT * from ibf_groups WHERE g_id='".$INFO['guest_group']."'");
        	$group = $DB->fetch_row();
        
			foreach ($group as $k => $v)
			{
				$this->member[ $k ] = $v;
			}
		
		}
		
        //------------------------------------------------
        // Synchronise the last visit and activity times if
        // we have some in the member profile
        //-------------------------------------------------
        
        if ($this->member['id'])
        {
        	if ( ! $ibforums->input['last_activity'] )
        	{
				if ($this->member['last_activity'])
				{
					$ibforums->input['last_activity'] = $this->member['last_activity'];
				}
				else
				{
					$ibforums->input['last_activity'] = $this->time_now;
				}
        	}
        	//------------
        	
        	if ( ! $ibforums->input['last_visit'] )
        	{
				if ($this->member['last_visit'])
				{
					$ibforums->input['last_visit'] = $this->member['last_visit'];
				}
				else
				{
					$ibforums->input['last_visit'] = $this->time_now;
				}
        	}
        
			//-------------------------------------------------
			// If there hasn't been a cookie update in 2 hours,
			// we assume that they've gone and come back
			//-------------------------------------------------
			
			if (!$this->member['last_visit'])
			{
				// No last visit set, do so now!
				
				$DB->query("UPDATE ibf_members SET last_visit='".$this->time_now."', last_activity='".$this->time_now."' WHERE id=".$this->member['id']);
				
			}
			else if ( (time() - $ibforums->input['last_activity']) > 300 )
			{
				// If the last click was longer than 5 mins ago and this is a member
				// Update their profile.
				
				$DB->query("UPDATE ibf_members SET last_activity='".$this->time_now."' WHERE id=".$this->member['id']);
				
			}
			
			//-------------------------------------------------
			// Check ban status
			//-------------------------------------------------
			
			if ( $this->member['temp_ban'] )
			{
				$ban_arr = $std->hdl_ban_line(  $this->member['temp_ban'] );
				
				if ( time() >= $ban_arr['date_end'] )
				{
					// Update this member's profile
					
					$DB->query("UPDATE ibf_members SET temp_ban='' WHERE id=".intval($this->member['id']) );
				}
				else
				{
					$ibforums->member = $this->member; // Set time right
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'account_susp', 'INIT' => 1, 'EXTRA' => $std->get_date($ban_arr['date_end'],'LONG') ) );
				}
			}
			
		}
		
		//-------------------------------------------------
        // Set a session ID cookie
        //-------------------------------------------------
        
        $std->my_setcookie("session_id", $this->session_id, -1);
        
        $ibforums->perm_id = ( $this->member['org_perm_id'] ) ? $this->member['org_perm_id'] : $this->member['g_perm_id'];
        
        $ibforums->perm_id_array = explode( ",", $ibforums->perm_id );
        
        return $this->member;
        
    }
    
    //+-------------------------------------------------
	// Attempt to load a member
	//+-------------------------------------------------
	
    function load_member($member_id=0)
    {
    	global $DB, $std, $ibforums;
    	
    	$member_id = intval($member_id);
    	
     	if ($member_id != 0)
        {
            				  
            $DB->query("SELECT moderator.mid as is_mod, moderator.allow_warn, m.id, m.name, m.mgroup, m.password, m.email, m.restrict_post, m.view_sigs, m.view_avs, m.view_pop, m.view_img, m.auto_track,
                              m.mod_posts, m.language, m.skin, m.new_msg, m.show_popup, m.msg_total, m.time_offset, m.posts, m.joined, m.last_post,
            				  m.last_visit, m.last_activity, m.dst_in_use, m.view_prefs, m.org_perm_id, m.temp_ban, m.sub_end, g.*
            				  FROM ibf_members m
            				    LEFT JOIN ibf_groups g ON (g.g_id=m.mgroup)
            				    LEFT JOIN ibf_moderators moderator ON (moderator.member_id=m.id OR moderator.group_id=m.mgroup )
            				  WHERE m.id=$member_id");
            
            if ( $DB->get_num_rows() )
            {
            	$this->member = $DB->fetch_row();
            }
            
            //-------------------------------------------------
            // Unless they have a member id, log 'em in as a guest
            //-------------------------------------------------
            
            if ( ($this->member['id'] == 0) or (empty($this->member['id'])) )
            {
				$this->unload_member();
            }
		}
		
		unset($member_id);
	}
	
	//+-------------------------------------------------
	// Remove the users cookies
	//+-------------------------------------------------
	
	function unload_member()
	{
		global $DB, $std, $ibforums;
		
		// Boink the cookies
		
		$std->my_setcookie( "member_id" , "0", -1  );
		$std->my_setcookie( "pass_hash" , "0", -1  );
		
		$this->member['id']       = 0;
		$this->member['name']     = "";
		$this->member['password'] = "";
		
	}
    
    //-------------------------------------------
    // Updates a current session.
    //-------------------------------------------
    
    function update_member_session() {
        global $DB, $ibforums;
        
        // Make sure we have a session id.
        
        if ( ! $this->session_id )
        {
        	$this->create_member_session();
        	return;
        }
        
        if (empty($this->member['id']))
        {
        	$this->unload_member();
        	$this->create_guest_session();
        	return;
        }
        
        				
        $db_str = $DB->compile_db_update_string(
        										 array(
        										 		'member_name'  => $this->member['name'],
														'member_id'    => intval($this->member['id']),
														'member_group' => $this->member['mgroup'],
														'in_forum'     => intval($ibforums->input['f']),
														'in_topic'     => intval($ibforums->input['t']),
														'login_type'   => $ibforums->input['Privacy'],
														'running_time' => $this->time_now,
														'location'     => $ibforums->input['act'].",".$ibforums->input['p'].",".$ibforums->input['CODE']
													  )
											  );
											  
        $DB->query("UPDATE ibf_sessions SET $db_str WHERE id='{$this->session_id}'");
        
    }        
    
    //--------------------------------------------------------------------
    
    function update_guest_session()
    {
        global $DB, $ibforums, $INFO;
        
        // Make sure we have a session id.
        
        if ( ! $this->session_id )
        {
        	$this->create_guest_session();
        	return;
        }
        
        $query  = "UPDATE ibf_sessions SET member_name='',member_id='0',member_group='".$INFO['guest_group']."'";
        $query .= ",login_type='0', running_time='".$this->time_now."', in_forum='".$ibforums->input['f']."', in_topic='".$ibforums->input['t']."', location='".$ibforums->input['act'].",".$ibforums->input['p'].",".$ibforums->input['CODE']."' ";
        $query .= "WHERE id='".$this->session_id."'";
        
        // Update the database
        
        $DB->query($query);
    } 
                    
    
    //-------------------------------------------
    // Get a session based on the current session ID
    //-------------------------------------------
    
    function get_session($session_id="")
    {
        global $DB, $INFO, $std;
        
        $result = array();
        
        $query = "";
        
        $session_id = preg_replace("/([^a-zA-Z0-9])/", "", $session_id);
        
        if ( $session_id )
        {
        
			if ($INFO['match_browser'] == 1)
			{
				$query = " AND browser='".$this->user_agent."'";
			}
				
			$DB->query("SELECT id, member_id, running_time, location FROM ibf_sessions WHERE id='".$session_id."' and ip_address='".$this->ip_address."'".$query);
			
			if ( $DB->get_num_rows() != 1 )
			{
				// Either there is no session, or we have more than one session..
				
				$this->session_dead_id   = $session_id;
				$this->session_id        = 0;
        		$this->session_user_id   = 0;
        		return;
			}
			else
			{
				$result = $DB->fetch_row();
				
				if ($result['id'] == "")
				{
					$this->session_dead_id   = $session_id;
					$this->session_id        = 0;
					$this->session_user_id   = 0;
					unset($result);
					return;
				}
				else
				{
					$this->session_id        = $result['id'];
					$this->session_user_id   = $result['member_id'];
					$this->last_click        = $result['running_time'];
        			$this->location          = $result['location'];
        			unset($result);
					return;
				}
			}
		}
    }
    
    //-------------------------------------------
    // Creates a member session.
    //-------------------------------------------
    
    function create_member_session()
    {
        global $DB, $INFO, $std, $ibforums;
        
        if ($this->member['id'])
        {
        	//---------------------------------
        	// Remove the defunct sessions
        	//---------------------------------
        	
			$INFO['session_expiration'] = $INFO['session_expiration'] ? (time() - $INFO['session_expiration']) : (time() - 3600);
			
			$DB->query( "DELETE FROM ibf_sessions WHERE running_time < {$INFO['session_expiration']} or member_id='".$this->member['id']."'");
			
			$this->session_id  = md5( uniqid(microtime()) );
			
			//---------------------------------
        	// Insert the new session
        	//---------------------------------
        	
			$DB->query("INSERT INTO ibf_sessions (id, member_name, member_id, ip_address, browser, running_time, location, login_type, member_group) ".
					   "VALUES ('".$this->session_id."', '".$this->member['name']."', '".$this->member['id']."', '".$this->ip_address."', '".$this->user_agent."', '".$this->time_now."', ".
					   "',,', '".$ibforums->input['Privacy']."', ".$this->member['mgroup'].")");
					   
			// If this is a member, update their last visit times, etc.
			
			if (time() - $this->member['last_activity'] > 300)
			{
				//---------------------------------
				// Reset the topics read cookie..
				//---------------------------------
				
				$std->my_setcookie('topicsread', '');
				
				$DB->query("UPDATE ibf_members SET last_visit=last_activity, last_activity='".$this->time_now."' WHERE id='".$this->member['id']."'");
				
				//---------------------------------
				// Fix up the last visit/activity times.
				//---------------------------------
				
				$ibforums->input['last_visit']    = $this->member['last_activity'];
				$ibforums->input['last_activity'] = $this->time_now;
			}
		}
		else
		{
			$this->create_guest_session();
		}
    }
    
    //--------------------------------------------------------------------
    
    function create_guest_session() {
        global $DB, $INFO, $std, $ibforums;
        
		//---------------------------------
		// Remove the defunct sessions
		//---------------------------------
		
		if ( ($this->session_dead_id != 0) and ( ! empty($this->session_dead_id) ) )
		{
			$extra = " or id='".$this->session_dead_id."'";
		}
		else
		{
			$extra = "";
		}
		
		$INFO['session_expiration'] = $INFO['session_expiration'] ? (time() - $INFO['session_expiration']) : (time() - 3600);
		
		$DB->query( "DELETE FROM ibf_sessions WHERE running_time < {$INFO['session_expiration']} or ip_address='".$this->ip_address."'".$extra);
		
		$this->session_id  = md5( uniqid(microtime()) );
		
		//---------------------------------
		// Insert the new session
		//---------------------------------
		
		$DB->query("INSERT INTO ibf_sessions (id, member_name, member_id, ip_address, browser, running_time, location, login_type, member_group) ".
				   "VALUES ('".$this->session_id."', '', '0', '".$this->ip_address."', '".$this->user_agent."', '".$this->time_now."', ".
				   "',,', '0', ".$INFO['guest_group'].")");
					   
    }
    
    //-------------------------------------------
    // Creates a BOT session
    //-------------------------------------------
    
    function create_bot_session($bot)
    {
        global $DB, $INFO, $std, $ibforums;
        
        $db_str = $DB->compile_db_insert_string(
        										 array(
        										 		'id'           => $bot.'_session',
        										 		'member_name'  => $ibforums->vars['sp_'.$bot],
														'member_id'    => 0,
														'member_group' => $ibforums->vars['spider_group'],
														'in_forum'     => intval($ibforums->input['f']),
														'in_topic'     => intval($ibforums->input['t']),
														'login_type'   => $ibforums->vars['spider_anon'],
														'running_time' => $this->time_now,
														'location'     => $ibforums->input['act'].",".$ibforums->input['p'].",".$ibforums->input['CODE'],
														'ip_address'   => $this->ip_address,
														'browser'      => $this->user_agent,
													  )
											  );
											  
		$DB->query("INSERT INTO ibf_sessions ({$db_str['FIELD_NAMES']}) VALUES({$db_str['FIELD_VALUES']})");
					   
    }
    
    //-------------------------------------------
    // Updates a BOT current session.
    //-------------------------------------------
    
    function update_bot_session($bot)
    {
        global $DB, $ibforums, $INFO;
        
        $db_str = $DB->compile_db_update_string(
        										 array(
        										 		'member_name'  => $ibforums->vars['sp_'.$bot],
														'member_id'    => 0,
														'member_group' => $ibforums->vars['spider_group'],
														'in_forum'     => intval($ibforums->input['f']),
														'in_topic'     => intval($ibforums->input['t']),
														'login_type'   => $ibforums->vars['spider_anon'],
														'running_time' => $this->time_now,
														'location'     => $ibforums->input['act'].",".$ibforums->input['p'].",".$ibforums->input['CODE']
													  )
											  );
											  
        $DB->query("UPDATE ibf_sessions SET $db_str WHERE id='".$bot."_session'");
        
    }        
    
        
}




?>