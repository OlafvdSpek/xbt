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
|   > UserCP functions
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

$idx = new UserCP;

class UserCP {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    var $parser;

    var $member     = array();
    var $m_group    = array();
    
    var $jump_html  = "";
    var $parser     = "";
    
    var $links      = array();
    
    var $bio        = "";
    var $notes      = "";
    var $size       = "m";
    
    var $email      = "";
    var $md5_check  = "";
    
    var $modules    = "";
    
    var $lib;
    
    function UserCP()
    {
    	global $ibforums, $DB, $std, $print;
    	
    	require "./sources/lib/post_parser.php";
        
        $this->parser = new post_parser();
        
        $this->md5_check = $std->return_md5_check();
        
        //--------------------------------------------
    	// Get the emailer module
		//--------------------------------------------
		
		require "./sources/lib/emailer.php";
		
		$this->email = new emailer();
		
		//--------------------------------------------
    	// Get the sync module
		//--------------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
		}
        
    	if ($ibforums->input['CODE'] == "") $ibforums->input['CODE'] = 00;
    	
    	//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
    	$ibforums->lang = $std->load_words($ibforums->lang, 'lang_post'  , $ibforums->lang_id );
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_ucp'   , $ibforums->lang_id );
		
		require "./sources/lib/usercp_functions.php";
    	
    	$this->html = $std->load_template('skin_ucp');
    	
    	$this->base_url        = $ibforums->base_url;
    	$this->base_url_nosess = "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}";
    	
    	//--------------------------------------------
    	// Check viewing permissions, etc
		//--------------------------------------------
		
		$this->member  = $ibforums->member;
		
		if ( empty($this->member['id']) or $this->member['id'] == "" or $this->member['id'] == 0 )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		// Get more member info..
    	
    	$DB->query("SELECT m.*, me.notes,me.ta_size,me.photo_type,me.photo_location,me.photo_dimensions 
    			   FROM ibf_members m
    			   LEFT JOIN ibf_member_extra me ON(me.id=m.id)
    			   WHERE m.id='".$this->member['id']."'");
    			   
    	$this->member = $DB->fetch_row();
		
		$this->links = $this->member['links'];
		$this->notes = $this->member['notes'];
		$this->size  = $this->member['ta_size'] ? $this->member['ta_size'] : $this->size;
		
		//--------------------------------------------
    	// Print the top button menu
    	//--------------------------------------------
    	
    	$menu_html = $this->html->Menu_bar($this->base_url);
    	
    	//--------------------------------------------
    	// If no messenger, remove the links!
    	//--------------------------------------------
    	
    	if ( ! $ibforums->member['g_use_pm'])
        {
        	$menu_html = preg_replace( "/<!-- Messenger -->.+?<!-- End Messenger -->/s", "", $menu_html );
        }
        else
        {
        	//--------------------------------------------
    		// Print folder links
    		//--------------------------------------------
    		
    		$folder_links = "";
    		
			if (empty($this->member['vdirs']))
			{
				$this->member['vdirs'] = "in:Inbox|sent:Sent Items";
			}
			
			
			foreach( explode( "|", $this->member['vdirs'] ) as $dir )
			{
				list ($id, $real) = explode( ":", $dir );
				
				if ( empty($id) or ( $id == 'in' ) )
				{
					continue;
				}
				
				$folder_links .= $this->html->menu_bar_msg_folder_link($id, $real);
			}
			
			if ( $folder_links != "" )
			{
				$menu_html = str_replace( "<!--IBF.FOLDER_LINKS-->", $folder_links, $menu_html );
			}
        }
		
		//--------------------------------------------
    	// Using Sub Manager?
    	//--------------------------------------------
    	
		if ( @is_dir( ROOT_PATH.'/modules/subsmanager' ) )
		{
			$url  = $ibforums->base_url."act=module&amp;module=subscription&amp;CODE=index";
			$name = $ibforums->lang['new_sub_link'];
			
			$menu_html = str_replace( "<!--IBF.OPTION_LINKS-->", $this->html->menu_bar_new_link( $url, $name ), $menu_html );
		}
		
		if ($ibforums->vars['top_site_list_integrate'])
		{
			$menu_html = str_replace( "<!--IBF.TSLLINKS-->", $this->html->tsl_links(), $menu_html );
		}
		
		
    	$print->add_output( $menu_html );
    	
    	
    	
    	$this->lib    = new usercp_functions(&$this);
    	
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	
    	switch($ibforums->input['CODE']) {
    		case '00':
    			$this->splash();
    			break;
    		case '01':
    			$this->personal();
    			break;
    		//------------------------------
    		case '02':
    			$this->email_settings();
    			break;
    		case '03':
    			$this->do_email_settings();
    			break;
    		//------------------------------
    		case '04':
    			$this->board_prefs();
    			break;
    		case '05':
    			$this->do_board_prefs();
    			break;
    		//------------------------------
    		case '06':
    			$this->skin_langs();
    			break;
    		case '07':
    			$this->do_skin_langs();
    			break;
    		//------------------------------
    		case '08':
    			$this->email_change();
    			break;
    		case '09':
    			$this->do_email_change();
    			break;
    		//------------------------------
    		case '21':
    			$this->do_personal();
    			break;
    		case '20':
    			$this->update_notepad();
    			break;
    		//------------------------------
    		case '22':
    			$this->signature();
    			break;
    		case '23':
    			$this->do_signature();
    			break;
    		//------------------------------
    		case '24':
    			$this->avatar();
    			break;
    		case '25':
    			$this->do_avatar();
    			break;
    		//------------------------------
    		case '26':
    			$this->tracker();
    			break;
    		case '27':
    			$this->do_delete_tracker();
    			break;
    		//------------------------------
    		case '28':
    			$this->pass_change();
    			break;
    		case '29':
    			$this->do_pass_change();
    			break;
    		//------------------------------
    		
    		case '50':
    			$this->forum_tracker();
    			break;
    		case '51':
    			$this->remove_forum_tracker();
    			break;
    			
    		//-------------------------------
    		
    		case 'show_image':
    			$this->show_image();
    			break;
    			
    		case 'photo':
    			$this->photo();
    			break;
    			
    		case 'dophoto':
    			$this->lib->do_photo();
    			break;
    			
    		case 'getgallery':
    			$this->avatar_gallery();
    			break;
    			
    		case 'setinternalavatar':
    			$this->lib->set_internal_avatar();
    			break;
    		
    		default:
    			$this->splash();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$fj = $std->build_forum_jump();
		$fj = preg_replace( "!#Forum Jump#!", $ibforums->lang['forum_jump'], $fj);
		
		$this->output .= $this->html->CP_end();
		
		$this->output .= $this->html->forum_jump($fj, $links);
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, NAV => $this->nav ) );
    		
 	}
 	
 	//*******************************************************************/
 	//| Photo:
 	//|
 	//| Change / Add / Edit Users Photo
 	//*******************************************************************/
 	
 	function photo()
 	{
 		global $ibforums, $DB, $std;
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 		if ( $ibforums->member['g_photo_max_vars'] == "" or $ibforums->member['g_photo_max_vars'] == "::" )
 		{
 			// Nothing set up yet...
 			
 			$this->output .= $this->html->dead_section();
 			return;
 		}
 		
 		//----------------------------------------------------------------
 		// SET DIMENSIONS
 		//----------------------------------------------------------------
 		
 		list($p_max, $p_width, $p_height) = explode( ":", $ibforums->member['g_photo_max_vars'] );
 		
 		if ( $p_max )
 		{
 			$ibforums->lang['pph_max']  = sprintf( $ibforums->lang['pph_max'], $p_max );
 			$ibforums->lang['pph_max'] .= sprintf( $ibforums->lang['pph_max2'], $p_width, $p_height );
 		}
 		else
 		{
 			$ibforums->lang['pph_max'] = sprintf( $ibforums->lang['pph_max2'], $p_width, $p_height );
 		}
 		
 		list($p_w, $p_h) = explode( ",", $this->member['photo_dimensions'] );
 		
 		$cur_photo = $ibforums->lang['pph_none'];
 		$cur_type  = "";
 		$url_photo = "";
 		
 		$width  = ( $p_w ) ? "width='$p_w'"  : "";
 		$height = ( $p_h ) ? "height='$p_h'" : "";
 		
 		$show_size = str_replace( ",", " X ", $this->member['photo_dimensions'] );
 		
 		//----------------------------------------------------------------
 		// TYPE?
 		//----------------------------------------------------------------
 		
 		if ( $this->member['photo_type'] == 'upload' )
 		{
 			$cur_type  = $ibforums->lang['pph_t_upload'];
 			$cur_photo = "<img src=\"".$ibforums->vars['upload_url']."/".$this->member['photo_location']."\" $width $height alt='Photo' />";
 		}
 		else if ( $this->member['photo_type'] == 'url' )
 		{
 			$cur_type  = $ibforums->lang['pph_t_url'];
 			$cur_photo = "<img src=\"".$this->member['photo_location']."\" $width $height alt='Photo' />";
 			$url_photo = $this->member['photo_location'];
 		}
 		
 		//----------------------------------------------------------------
 		// SHOW THE FORM
 		//----------------------------------------------------------------
 		
 		$this->output .= $this->html->photo_page($cur_photo, $cur_type, $url_photo, $show_size, $this->md5_check);
 		
 		if ($p_max)
 		{
 			$this->output = str_replace( "<!--IPB.UPLOAD-->", $this->html->photo_page_upload($p_max * 1024), $this->output );
 		}
 		
 		$size_html = $ibforums->vars['disable_ipbsize'] ? $this->html->photo_page_mansize() : $this->html->photo_page_autosize();
 		
 		$this->output = str_replace( "<!--IPB.SIZE-->", $size_html, $this->output );
 		
 	}
 	
 	
 	//*******************************************************************/
 	//| Forum tracker
 	//|
 	//| What, you need a definition with that title?
 	//| What are you doing poking around in the code for anyway?
 	//*******************************************************************/
 	
 	function remove_forum_tracker() {
 		global $ibforums, $std, $DB;
 		
 		if ($ibforums->input['f'] == 'all')
 		{
 			$DB->query("DELETE FROM ibf_forum_tracker WHERE member_id='".$this->member['id']."'");
 		}
 		else
 		{
 			$id = intval($ibforums->input['f']);
 			
 			$DB->query("DELETE FROM ibf_forum_tracker WHERE member_id='".$this->member['id']."' AND forum_id='$id'");
 		}
 			
 	    $std->boink_it($this->base_url."act=UserCP&CODE=50");
 		
 	}
 	
 	function forum_tracker()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$this->output .= $this->html->forum_subs_header();
 		
 		//----------------------------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//----------------------------------------------------------
 		
 		$DB->query("SELECT t.frid, t.start_date, f.*, c.id as cat_id, c.name as cat_name
 		            FROM ibf_forum_tracker t
 		             LEFT JOIN ibf_forums f ON (t.forum_id=f.id)
 		             LEFT JOIN ibf_categories c ON (c.id=f.category)
 		            WHERE t.member_id='".$this->member['id']."'
 		            ORDER BY c.position, f.position");
 		          
 		if ( $DB->get_num_rows() )
 		{
 		
 			$last_cat_id = -1;
 		
 			while( $forum = $DB->fetch_row() )
 			{
 			
 				if ($last_cat_id != $forum['cat_id'])
 				{
 					$last_cat_id = $forum['cat_id'];
 					
 					$this->output .= $this->html->subs_forum_row($forum['cat_id'], $forum['cat_name']);
 				}
 				
 				$forum['folder_icon'] = $std->forum_new_posts($forum);
 				
 				$forum['last_post'] = $std->get_date($forum['last_post'], 'LONG');
						
				$forum['last_topic'] = $ibforums->lang['f_none'];
 				
 				$forum['last_title'] = str_replace( "&#33;" , "!", $forum['last_title'] );
				$forum['last_title'] = str_replace( "&quot;", "\"", $forum['last_title'] );
					
				if (strlen($forum['last_title']) > 30)
				{
					$forum['last_title'] = substr($forum['last_title'],0,27) . "...";
					$forum['last_title'] = preg_replace( '/&(#(\d+;?)?)?\.\.\.$/', '...', $forum['last_title'] );
				}
				
				if ($forum['password'] != "")
				{
					$forum['last_topic'] = $ibforums->lang['f_none'];
				}
				else
				{
					$forum['last_topic'] = "<a href='{$ibforums->base_url}showtopic={$forum['last_id']}&view=getlastpost'>{$forum['last_title']}</a>";
				}
			 
							
				if ( isset($forum['last_poster_name']))
				{
					$forum['last_poster'] = $forum['last_poster_id'] ? "<a href='{$ibforums->base_url}showuser={$forum['last_poster_id']}'>{$forum['last_poster_name']}</a>"
																	 : $forum['last_poster_name'];
				}
				else
				{
					$forum['last_poster'] = $ibforums->lang['f_none'];
				}
 			
				
				
				$this->output .= $this->html->forum_subs_row($forum);
			}
			
		}
		else
		{
			$this->output .= $this->html->forum_subs_none();
		}
		
		$this->output .= $this->html->forum_subs_end();
 		
		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	
 	//*******************************************************************/
 	//| pass change:
 	//|
 	//| Change the users password.
 	//*******************************************************************/
 	
 	function pass_change()
 	{
 		global $ibforums, $DB, $std;
 		
 		$this->output    .= $this->html->pass_change();
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_pass_change()
 	{
 		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
 		
 		if ( $HTTP_POST_VARS['current_pass'] == "" or empty($HTTP_POST_VARS['current_pass']) )
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		//--------------------------------------------
 		
 		$cur_pass = trim($ibforums->input['current_pass']);
 		$new_pass = trim($ibforums->input['new_pass_1']);
 		$chk_pass = trim($ibforums->input['new_pass_2']);
 		
 		//--------------------------------------------
 		
 		if ( ( empty($new_pass) ) or ( empty($chk_pass) ) )
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		//--------------------------------------------
 		
 		if ($new_pass != $chk_pass)
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_no_match' ) );
 		}
 		
 		//--------------------------------------------
 		
 		if (md5($cur_pass) != $this->member['password'])
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
 		}
 		
 		//--------------------------------------------
 		
 		$md5_pass = md5($new_pass);
 		
 		//--------------------------------------------
 		// Update the DB
 		//--------------------------------------------
 		
 		$DB->query("UPDATE ibf_members SET password='$md5_pass' WHERE id='".$this->member['id']."'");
 		
 		//--------------------------------------------
 		// Update the cookie..
 		//--------------------------------------------
 		
 		$std->my_setcookie( 'pass_hash', $md5_pass, 1 );
 		
 		//--------------------------------------------
 		// Use sync module?
 		//--------------------------------------------
 		
 		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class(&$this);
    		$this->modules->on_pass_change($ibforums->member['id'], $new_pass);
   		}
 		
 		//--------------------------------------------
 		// Redirect...
 		//--------------------------------------------
 		
 		$print->redirect_screen( $ibforums->lang['pass_redirect'], 'act=UserCP&CODE=00' );
 		
 	}
 	
 	
 	//*******************************************************************/
 	//| email change:
 	//|
 	//| Change the users email address
 	//*******************************************************************/
 	
 	function email_change($msg="") {
 		global $ibforums, $DB, $std;
 		
 		$txt = $ibforums->lang['ce_current'].$this->member['email'];
 		
 		if ($ibforums->vars['reg_auth_type'])
 		{
 			$txt .= $ibforums->lang['ce_auth'];
 		}
 		
 		if ($ibforums->vars['bot_antispam'])
 		{
			//-----------------------------------------------
			// Set up security code
			//-----------------------------------------------
			
			// Get a time roughly 6 hours ago...
			
			$r_date = time() - (60*60*6);
			
			// Remove old reg requests from the DB
			
			$DB->query("DELETE FROM ibf_reg_antispam WHERE ctime < '$r_date'");
			
			// Set a new ID for this reg request...
			
			$regid = md5( uniqid(microtime()) );
			
			// Set a new 6 character numerical string
			
			mt_srand ((double) microtime() * 1000000);
			
			$reg_code = mt_rand(100000,999999);
			
			// Insert into the DB
			
			$str = $DB->compile_db_insert_string( array (
															'regid'      => $regid,
															'regcode'    => $reg_code,
															'ip_address' => $ibforums->input['IP_ADDRESS'],
															'ctime'      => time(),
												)       );
												
			$DB->query("INSERT INTO ibf_reg_antispam ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		}
 		
 		$this->output    .= $this->html->email_change($txt, $ibforums->lang[$msg]);
 		
 		if ($ibforums->vars['bot_antispam'])
 		{
 		
			if ($ibforums->vars['bot_antispam'] == 'gd')
			{
				$this->output = str_replace( "<!--ANTIBOT-->", $this->html->email_change_gd($regid), $this->output );
			}
			else
			{
				$this->output = str_replace( "<!--ANTIBOT-->", $this->html->email_change_img($regid), $this->output );
			}
 		
 		}
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	
 	
 	function do_email_change()
 	{
 		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
 		
 		if ($HTTP_POST_VARS['in_email_1'] == "")
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		if ($HTTP_POST_VARS['in_email_2'] == "")
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
 		}
 		
 		//--------------------------------------------
 		
 		if ($ibforums->member['mgroup'] == $ibforums->vars['auth_group'])
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'email_change_v' ) );
 		}
 		
 		//--------------------------------------------
 		
 		if ( $ibforums->member['password'] !=  md5($ibforums->input['password']) )
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
 		}
 		
 		//--------------------------------------------
 		
 		$email_one    = strtolower( trim($ibforums->input['in_email_1']) );
 		$email_two    = strtolower( trim($ibforums->input['in_email_2']) );
 		
 		if ($email_one != $email_two)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'email_addy_mismatch' ) );
		}
		
		//--------------------------------------------
		
		$email_one = $std->clean_email($email_one);
		
		if ( $email_one == "" )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_email' ) );
		}
		
		//--------------------------------------------
		
		if (! $ibforums->vars['allow_dup_email'] )
		{
			$DB->query("SELECT id FROM ibf_members WHERE email='".$email_one."'");
			
			$email_check = $DB->fetch_row();
			
			if ($email_check['id'])
			{
				$std->Error( array( LEVEL => 1, MSG => 'email_exists' ) );
			}
		}
		
		//--------------------------------------------
		// Check in banned list
		//--------------------------------------------
		
		if ($ibforums->vars['ban_email'])
		{
			$ips = explode( "|", $ibforums->vars['ban_email'] );
			
			foreach ($ips as $ip)
			{
				$ip = preg_replace( "/\*/", '.*' , $ip );
				
				if ( preg_match( "/$ip/", $email_one ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'banned_email' ) );
				}
			}
		}
		
		if ($ibforums->vars['bot_antispam'])
 		{
			//---------------------------
			// Check the security code:
			//---------------------------
			
			if ($ibforums->input['regid'] == "")
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			$DB->query("SELECT * FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['regid']))."'");
			
			if ( ! $row = $DB->fetch_row() )
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			if ( trim( intval($ibforums->input['reg_code']) ) != $row['regcode'] )
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			$DB->query("DELETE FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['regid']))."'");
		}
		
		//--------------------------------------------
 		// Use sync module?
 		//--------------------------------------------
 		
 		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class(&$this);
    		$this->modules->on_email_change($ibforums->member['id'], $email_one);
   		}
		
		//--------------------------
		
		if ($ibforums->vars['reg_auth_type'])
		{
			$validate_key = md5( $std->make_password() . time() );
			
			//--------------------------------------------
			// Update the new email, but enter a validation key
			// and put the member in "awaiting authorisation"
			// and send an email..
			//--------------------------------------------
			
			$db_str = $DB->compile_db_insert_string( array (
															 'vid'         => $validate_key,
															 'member_id'   => $this->member['id'],
															 'real_group'  => $this->member['mgroup'],
															 'temp_group'  => $ibforums->vars['auth_group'],
															 'entry_date'  => time(),
															 'coppa_user'  => 0,
															 'email_chg'   => 1,
															 'ip_address'  => $ibforums->input['IP_ADDRESS']
												   )       );
			
			$DB->query("INSERT INTO ibf_validating ({$db_str['FIELD_NAMES']}) VALUES({$db_str['FIELD_VALUES']})");
			
			$DB->query("UPDATE ibf_members SET mgroup=".$ibforums->vars['auth_group'].", email='$email_one' WHERE id=".$this->member['id']);
			
			// Update their session with the new member group
			
			if ($ibforums->session_id)
			{
				$DB->query("UPDATE ibf_sessions SET member_name='', member_id=0, member_group=".$ibforums->vars['guest_group']." WHERE member_id=".$this->member['id']." and id='".$ibforums->session_id."'");
 			}
 			
 			// Kill the cookies to stop auto log in
 			
 			$std->my_setcookie( 'pass_hash'  , '-1', 0 );
 			$std->my_setcookie( 'member_id'  , '-1', 0 );
 			$std->my_setcookie( 'session_id' , '-1', 0 );
 			
 			// Dispatch the mail, and return to the activate form.
 			
 			$this->email->get_template("newemail");
				
			$this->email->build_message( array(
												'NAME'         => $this->member['name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&type=newemail&uid=".$this->member['id']."&aid=".$validate_key,
												'ID'           => $this->member['id'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=07",
												'CODE'         => $validate_key,
											  )
										);
										
			$this->email->subject = $ibforums->lang['lp_subject'].' '.$ibforums->vars['board_name'];
			$this->email->to      = $email_one;
			
			$this->email->send_mail();
			
			$print->redirect_screen( $ibforums->lang['ce_redirect'], 'act=Reg&CODE=07' );
		}
		else
		{
			// No authorisation needed, change email addy and return
			
			$DB->query("UPDATE ibf_members SET email='$email_one' WHERE id='".$this->member['id']."'");
			
			$print->redirect_screen( $ibforums->lang['email_changed_now'], 'act=UserCP&CODE=00' );
			
		}
 	}
 	
 	//*******************************************************************/
 	//| tracker:
 	//|
 	//| Print the subscribed topics listings
 	//*******************************************************************/
 	
 	function tracker() {
 		global $ibforums, $DB, $std, $print;
 		
 		$this->output .= $this->html->subs_header();
 		
 		//----------------------------------------------------------
 		// Are we checking for auto-prune?
 		//----------------------------------------------------------
 		
 		$auto_explain = $ibforums->lang['no_auto_prune'];
 		
 		if ($ibforums->vars['subs_autoprune'] > 0)
 		{
			if ( time() % 2 )
			{
				// Every now and again..
				
				$time_limit = time() - ($ibforums->vars['subs_autoprune'] * 86400);
				
				$DB->query("SELECT tr.trid FROM ibf_tracker tr, ibf_topics t WHERE t.tid=tr.topic_id AND t.last_post < '$time_limit'");
				
				$trids = array();
				
				while ( $r = $DB->fetch_row() )
				{
					$trids[] = $r['trid'];
				}
				
				if (count($trids) > 0)
				{
					$DB->query("DELETE FROM ibf_tracker WHERE trid IN (".implode(",",$trids).")");
				}
			}
			
			$auto_explain = sprintf( $ibforums->lang['auto_prune'], $ibforums->vars['subs_autoprune'] );
 		}
 		
 		//----------------------------------------------------------
 		// Do we have an incoming date cut?
 		//----------------------------------------------------------
 		
 		$date_cut   = intval($ibforums->input['datecut']) != "" ? intval($ibforums->input['datecut']) : 30;
 		
 		$date_query = $date_cut != 1000 ? " AND t.last_post > '".(time() - ($date_cut*86400))."' " : "";
 		
 		//----------------------------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//----------------------------------------------------------
 		
 		$DB->query("SELECT s.trid, s.member_id, s.topic_id, s.last_sent, s.start_date as track_started, t.*, f.id as forum_id, f.name as forum_name, f.read_perms "
 		          ."FROM ibf_tracker s, ibf_topics t, ibf_forums f "
 		          ."WHERE s.member_id='".$this->member['id']."' AND t.tid=s.topic_id AND f.id=t.forum_id $date_query"
 		          ."ORDER BY f.id, t.last_post DESC");
 		          
 		if ( $DB->get_num_rows() )
 		{
 		
 			$last_forum_id = -1;
 		
 			while( $topic = $DB->fetch_row() )
 			{
 			
 				if ($last_forum_id != $topic['forum_id'])
 				{
 					$last_forum_id = $topic['forum_id'];
 					
 					$this->output .= $this->html->subs_forum_row($topic['forum_id'], $topic['forum_name']);
 				}
 			
				$topic['last_poster'] = ($topic['last_poster_id'] != 0)
										? "<b><a href='{$this->base_url}act=Profile&CODE=03&MID={$topic['last_poster_id']}'>{$topic['last_poster_name']}</a></b>"
										: "-".$topic['last_poster_name']."-";
									
				$topic['starter']     = ($topic['starter_id']     != 0)
										? "<a href='{$this->base_url}act=Profile&CODE=03&MID={$topic['starter_id']}'>{$topic['starter_name']}</a>"
										: "-".$topic['starter_name']."-";
			 
				if ($topic['poll_state'])
				{
					$topic['prefix']     = $ibforums->vars['pre_polls'].' ';
				}
			
				$topic['folder_icon']    = $std->folder_icon($topic);
				
				$topic['topic_icon']     = $topic['icon_id']  ? '<img src="'.$ibforums->vars[html_url] . '/icon' . $topic['icon_id'] . '.gif" border="0" alt="">'
										 : '&nbsp;';
																	  
				if ($topic['pinned'])
				{
					$topic['topic_icon']       = "<{B_PIN}>";
				}
				
				$topic['start_date'] = $std->get_date( $topic['track_started'], 'LONG' );
				
				if ($topic['description'])
				{
					$topic['description'] = $topic['description'].'<br>';
				}
			
			
				$pages = 1;
				
				if ($topic['posts'])
				{
					if ( (($topic['posts'] + 1) % $ibforums->vars['display_max_posts']) == 0 )
					{
						$pages = ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'];
					}
					else
					{
						$number = ( ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'] );
						$pages = ceil( $number);
					}
					
				}
				
				if ($pages > 1) {
					$topic['PAGES'] = "<span class='small'>({$ibforums->lang['topic_sp_pages']} ";
					for ($i = 0 ; $i < $pages ; ++$i ) {
						$real_no = $i * $ibforums->vars['display_max_posts'];
						$page_no = $i + 1;
						if ($page_no == 4) {
							$topic['PAGES'] .= "<a href='{$this->base_url}act=ST&f={$this->forum['id']}&t={$topic['tid']}&st=" . ($pages - 1) * $ibforums->vars['display_max_posts'] . "'>...$pages </a>";
							break;
						} else {
							$topic['PAGES'] .= "<a href='{$this->base_url}act=ST&f={$this->forum['id']}&t={$topic['tid']}&st=$real_no'>$page_no </a>";
						}
					}
					$topic['PAGES'] .= ")</span>";
				}
				
				if ($topic['posts'] < 0) $topic['posts'] = 0;
				
				// Do the quick goto last page icon stuff
				
				$topic['last_post_date']  = $std->get_date( $topic['last_post'], 'LONG' );
				
				$this->output .= $this->html->subs_row($topic);
			}
			
		}
		else
		{
			$this->output .= $this->html->subs_none();
		}
		
		// Build date box
		
		$date_box = "<option value='1'>".$ibforums->lang['subs_today']."</option>\n";
		
		foreach( array( 1,7,14,21,30,60,90,365 ) as $day )
		{
			$selected = $day == $date_cut ? ' selected' : '';
				
			$date_box .= "<option value='$day'$selected>".sprintf( $ibforums->lang['subs_day'], $day )."</option>\n";
		}
		
		$date_box .= "<option value='1000'>".$ibforums->lang['subs_all']."</option>\n";
			
		$this->output .= $this->html->subs_end($auto_explain, $date_box);
 		
		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_delete_tracker() {
 		global $ibforums, $std, $DB;
 		
 		//--------------------------------------
 		// Get the ID's to delete
 		//--------------------------------------
 		
 		if ($ibforums->input['request_method'] != 'post')
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
 		}
 		
 		$ids = array();
 		
 		foreach ($ibforums->input as $key => $value)
 		{
 			if ( preg_match( "/^id-(\d+)$/", $key, $match ) )
 			{
 				if ($ibforums->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		if ( count($ids) > 0 )
 		{
 			$DB->query("DELETE from ibf_tracker WHERE member_id='".$this->member['id']."' and trid IN (".implode( ",", $ids ).")");
 		}
 			
 	    $std->boink_it($this->base_url."act=UserCP&CODE=26");
 		
 	}
 	
 	//*******************************************************************/
 	//| SKIN LANGS:
 	//|
 	//| Change skin and languages prefs.
 	//*******************************************************************/
 	
 	function skin_langs() {
 		global $ibforums, $DB, $std, $print;
 		
 		// A serialized array holds our langauge settings.
 		// The array is: 1 => array( '$dir', '$name'), 2 => ... etc
 		
 		
 		$lang_array = array();
 		
 		$lang_select = "<select name='u_language' class='forminput'>\n";
		
		$DB->query("SELECT ldir, lname FROM ibf_languages");
		
		while ( $l = $DB->fetch_row() )
		{
			$lang_select .= $l['ldir'] == $this->member['language'] ? "<option value='{$l['ldir']}' selected>{$l['lname']}</option>"
 															        : "<option value='{$l['ldir']}'>{$l['lname']}</option>";
		}
 		
 		$lang_select .= "</select>";
 		
 		$this->output .= $this->html->skin_lang_header($lang_select, $this->md5_check);
 		
 		if ($ibforums->vars['allow_skins'] == 1)
 		{
 			
 			$DB->query("SELECT uid, sid, sname FROM ibf_skins WHERE hidden <> 1");
 			
 			if ( $DB->get_num_rows() )
 			{
 			
				$skin_select = "<select name='u_skin' class='forminput'>\n";
			
				while ( $s = $DB->fetch_row() )
				{
					$skin_select .= $s['sid'] == $this->member['skin'] ? "<option value='{$s['sid']}' selected>{$s['sname']}</option>"
																       : "<option value='{$s['sid']}'>{$s['sname']}</option>";
				}
				
				$skin_select .= "</select>";
				
			}
		
			$this->output .= $this->html->settings_skin($skin_select);
		}
		
		$this->output .= $this->html->skin_lang_end();
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_skin_langs() {
 	
 		$this->lib->do_skin_langs();
 		
 	}
 	
 	
 	//*******************************************************************/
 	//| BOARD PREFS:
 	//|
 	//| Set up view avatar, sig, time zone, etc.
 	//*******************************************************************/
 	
 	function board_prefs() {
 		global $ibforums, $DB, $std, $print;
 		
 		$time = $std->get_date( time(), 'LONG' );
 		
 		// Do we have a user stored offset, or use the board default:
 		
 		$offset = ( $ibforums->member['time_offset'] != "" ) ? $ibforums->member['time_offset'] : $ibforums->vars['time_offset'];
 		
 		$time_select = "<select name='u_timezone' class='forminput'>";
 		
 		// Loop through the langauge time offsets and names to build our
 		// HTML jump box.
 		
 		foreach( $ibforums->lang as $off => $words )
 		{
 			if (preg_match("/^time_(\S+)$/", $off, $match))
 			{
				$time_select .= $match[1] == $offset ? "<option value='{$match[1]}' selected='selected'>$words</option>"
												     : "<option value='{$match[1]}'>$words</option>";
 			}
 		}
 		
 		$time_select .= "</select>";
 		
 		// Print out the header..
 		
 		if ($ibforums->member['dst_in_use'])
 		{
 			$dst_check = 'checked';
 		}
 		else
 		{
 			$dst_check = '';
 		}
 		
 		//---------------------
 		
 		if ($ibforums->vars['postpage_contents'] == "")
		{
			$ibforums->vars['postpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		if ($ibforums->vars['topicpage_contents'] == "")
		{
			$ibforums->vars['topicpage_contents'] = '5,10,15,20,25,30,35,40';
		}
 		
 		list($post_page, $topic_page) = explode( "&", $ibforums->member['view_prefs'] );
 		
 		if ($post_page == "")
 		{
 			$post_page = -1;
 		}
 		if ($topic_page == "")
 		{
 			$topic_page = -1;
 		}
 		
 		$pp_a = array();
 		$tp_a = array();
 		$post_select  = "";
 		$topic_select = "";
 		
 		$pp_a[] = array( '-1', $ibforums->lang['pp_use_default'] );
 		$tp_a[] = array( '-1', $ibforums->lang['pp_use_default'] );
 		
 		foreach( explode( ',', $ibforums->vars['postpage_contents'] ) as $n )
 		{
 			$n      = intval(trim($n));
 			$pp_a[] = array( $n, $n );
 		}
 		
 		foreach( explode( ',', $ibforums->vars['topicpage_contents'] ) as $n )
 		{
 			$n      = intval(trim($n));
 			$tp_a[] = array( $n, $n );
 		}
 		
 		//---------------------
 		
 		foreach( $pp_a as $id => $data )
 		{
 			$post_select .= ($data[0] == $post_page) ? "<option value='{$data[0]}' selected='selected'>{$data[1]}\n" : "<option value='{$data[0]}'>{$data[1]}\n";
 		}
 		
 		foreach( $tp_a as $id => $data )
 		{
 			$topic_select .= ($data[0] == $topic_page) ? "<option value='{$data[0]}' selected='selected'>{$data[1]}\n" : "<option value='{$data[0]}'>{$data[1]}\n";
 		}
 		
 		
 		//---------------------
 		
 		$this->output .= $this->html->settings_header($this->member, $time_select, $time, $dst_check, $this->md5_check);
 		
 		$hide_sess   = $std->my_getcookie('hide_sess');
 		
 		$open_qreply = $std->my_getcookie("open_qr");
 		
 		if ( $open_qreply == FALSE )
 		{
 			$open_qreply = 0;
 		}
 		
 		// View avatars, signatures and images..
 		
 		$view_ava  = "<select name='VIEW_AVS' class='forminput'>";
 		$view_sig  = "<select name='VIEW_SIGS' class='forminput'>";
 		$view_img  = "<select name='VIEW_IMG' class='forminput'>";
 		$view_pop  = "<select name='DO_POPUP' class='forminput'>";
 		$html_sess = "<select name='HIDE_SESS' class='forminput'>";
 		$html_qr   = "<select name='OPEN_QR' class='forminput'>";
 		
 		$view_ava .= $this->member['view_avs'] ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 		
 		$view_sig .= $this->member['view_sigs'] ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 		
 		$view_img .= $this->member['view_img'] ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 											  
 		$view_pop .= $this->member['view_pop'] ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 		
 		$html_sess .= $hide_sess == 1          ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 		
 		$html_qr   .= $open_qreply == 1        ? "<option value='1' selected='selected'>".$ibforums->lang['yes']."</option>\n<option value='0'>".$ibforums->lang['no']."</option>"
 											   : "<option value='1'>".$ibforums->lang['yes']."</option>\n<option value='0' selected='selected'>".$ibforums->lang['no']."</option>";
 		
 		
 		
 		
 		$this->output .= $this->html->settings_end( array ( 'IMG'  => $view_img."</select>",
 															'SIG'  => $view_sig."</select>",
 															'AVA'  => $view_ava."</select>",
 															'POP'  => $view_pop."</select>",
 															'SESS' => $html_sess."</select>",
 															'QR'   => $html_qr."</select>",
 															'TPS'  => $topic_select,
 															'PPS'  => $post_select,
 												  )       );
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	

 	function do_board_prefs() {
 	
 		$this->lib->do_board_prefs();
 		
 	}
 	
 	//*******************************************************************/
 	//| EMAIL SETTINGS:
 	//|
 	//| Set up the email stuff.
 	//*******************************************************************/
 	
 	function email_settings() {
 		global $ibforums, $DB, $std, $print;
 		
 		// PM_REMINDER: First byte = Email PM when received new
 		//   			Second byte= Show pop-up when new PM received
 						
 		
 		$info = array();
 		
 		foreach ( array(hide_email, allow_admin_mails, email_full, email_pm, auto_track) as $k )
 		{
 			if (!empty($this->member[ $k ]))
 			{
 				$info[$k] = 'checked';
 			}
 		}
 		
 		$info['key'] = $this->md5_check;
 		
 		$this->output .= $this->html->email($info);
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_email_settings() {
 	
 		$this->lib->do_email_settings();
 		
 	}
 	
 	
 	//*******************************************************************/
 	//| custom sort routine:
 	//|
 	//| Like wot is seys on the tin
 	//*******************************************************************/
 	
 	function sort_avatars($a, $b)
 	{
 		$aa = strtolower($a[1]);
 		$bb = strtolower($b[1]);
 		
 		if ( $aa == $bb ) return 0;
 		
 		return ( $aa > $bb ) ? 1 : -1;
 	}
 	
 	
 	//*******************************************************************/
 	//| AVATAR:
 	//|
 	//| Displays the avatar choices
 	//*******************************************************************/
 	
 	function avatar_gallery()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$avatar_gallery    = array();
 		$av_categories     = array( 0 => array( "root", $ibforums->lang['av_root'] ) );
 		
 		$av_cat_selected   = preg_replace( "/[^\w\s_\-]/", "", $ibforums->input['av_cat'] );
 		$av_cat_found      = FALSE;
 		$av_human_readable = "";
 		
 		if ($av_cat_selected == 'root')
 		{
 			$av_cat_selected   = "";
 			$av_human_readable = $ibforums->lang['av_root'];
 		}
 		
 		//------------------------------------------
 		// Get the avatar categories
 		//------------------------------------------
 		
 		$dh = opendir( $ibforums->vars['html_dir'].'avatars' );
 		
 		while ( $file = readdir( $dh ) )
 		{
			if ( is_dir( $ibforums->vars['html_dir'].'avatars'."/".$file ) )
			{
				if ( $file != "." && $file != ".." )
				{
					if ( $file == $av_cat_selected )
					{
						$av_cat_found      = TRUE;
						$av_human_readable = str_replace( "_", " ", $file );
					}
					
					$av_categories[] = array( $file, str_replace( "_", " ", $file ) );
				}
			}
 		}
 		
 		closedir( $dh );
 		
 		//------------------------------------------
 		// SORT IT OUT YOU MUPPET!!
 		//------------------------------------------
 		
 		usort( $av_categories, array( 'UserCP', 'sort_avatars' ) );
 		reset( $av_categories );
 		
 		//------------------------------------------
 		// Did we find the directory?
 		//------------------------------------------
 		
 		if ($av_cat_selected)
 		{
 			if ( $av_cat_found != TRUE )
 			{
 				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'av_no_gallery' ) );
 			}
 			
 			$av_cat_real = "/".$av_cat_selected;
 		}
 		
 		//------------------------------------------
 		// Get the avatar images for this category
 		//------------------------------------------
 		
 		$dh = opendir( $ibforums->vars['html_dir'].'avatars'.$av_cat_real);
 		
 		while ( $file = readdir( $dh ) )
 		{
 			if ( ! preg_match( "/^..?$|^index|^\.ds_store|^\.htaccess/i", $file ) )
 			{
 				if ( is_file( $ibforums->vars['html_dir'].'avatars'.$av_cat_real."/".$file) )
 				{
 					if ( preg_match( "/\.(gif|jpg|jpeg|png|swf)$/i", $file ) )
 					{
 						$av_gall_images[] = $file;
 					}
 				}
 			}
 		}
 		
 		//------------------------------------------
 		// SORT IT OUT YOU PLONKER!!
 		//------------------------------------------
 		
 		if ( is_array($av_gall_images) and count($av_gall_images) )
 		{
 			natcasesort($av_gall_images);
 			reset($av_gall_images);
 		}
 		
 		//------------------------------------------
 		// Render drop down box..
 		//------------------------------------------
 		
 		$av_gals = "<select name='av_cat' class='forminput'>\n";
 		
 		foreach( $av_categories as $cat )
 		{
 			$av_gals .= "<option value='".$cat[0]."'>".$cat[1]."</option>\n";
 		}
 		
 		$av_gals .= "</select>\n";
 		
 		closedir( $dh );
 		
 		$gal_cols = $ibforums->vars['av_gal_cols'] == "" ? 5 : $ibforums->vars['av_gal_cols'];
 		$gal_rows = $ibforums->vars['av_gal_rows'] == "" ? 3 : $ibforums->vars['av_gal_rows'];
 		
 		$gal_found = count($av_gall_images);
 		
 		//------------------------------------------
 		// Produce the avatar gallery sheet
 		//------------------------------------------
 		
 		$this->output .= $this->html->avatar_gallery_start_table($av_human_readable,$av_gals,urlencode($av_cat_selected), $this->md5_check);
 		
 		$c = 0;
 		
 		if ( is_array($av_gall_images) and count($av_gall_images) )
 		{
			foreach( $av_gall_images as $img )
			{
				$c++;
				
				if ($c == 1)
				{
					$this->output .= $this->html->avatar_gallery_start_row();
				}
				
				$this->output .= $this->html->avatar_gallery_cell_row(
																	  $av_cat_real."/".$img,
																	  str_replace( "_", " ", preg_replace( "/^(.*)\.\w+$/", "\\1", $img ) ),
																	  urlencode($img)
																	);
				
				
				if ($c == $gal_cols)
				{
					$this->output .= $this->html->avatar_gallery_end_row();
					
					$c = 0;
				}
				
			}
 		}
 		
 		if ($c != $gal_cols)
 		{
			for ($i = $c ; $i < $gal_cols ; ++$i)
			{
				$this->output .= $this->html->avatar_gallery_blank_row();
			}
			
			$this->output .= $this->html->avatar_gallery_end_row();
		}
 		
 		$this->output .= $this->html->avatar_gallery_end_table();
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	//----------------------------------------------------------------------
 	
 	function avatar()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		//------------------------------------------
 		// Organise the dimensions
 		//------------------------------------------
 		
 		list( $this->member['AVATAR_WIDTH'] , $this->member['AVATAR_HEIGHT']  ) = explode ("x", $this->member['avatar_size']);
 		list( $ibforums->vars['av_width']   , $ibforums->vars['av_height']    ) = explode ("x", $ibforums->vars['avatar_dims']);
 		list( $w, $h ) = explode ( "x", $ibforums->vars['avatar_def'] );
 		
 		//------------------------------------------
 		// Get the users current avatar to display
 		//------------------------------------------
 		
 		$my_avatar = $std->get_avatar( $this->member['avatar'], 1, $this->member['avatar_size'] );
 		
 		$my_avatar = $my_avatar ? $my_avatar : 'noavatar';
 		
 		//------------------------------------------
 		// Get the avatar gallery
 		//------------------------------------------
 		
 		$avatar_gallery    = array();
 		$av_categories = array( 0 => array( "root", $ibforums->lang['av_root'] ) );
 		
 		//------------------------------------------
 		// Get the avatar categories
 		//------------------------------------------
 		
 		$dh = opendir( $ibforums->vars['html_dir'].'avatars' );
 		
 		while ( $file = readdir( $dh ) )
 		{
			if ( is_dir( $ibforums->vars['html_dir'].'avatars'."/".$file ) )
			{
				if ( $file != "." && $file != ".." )
				{
					if ( $file == $av_cat_selected )
					{
						$av_cat_found = TRUE;
					}
					
					$av_categories[] = array( $file, str_replace( "_", " ", $file ) );
				}
			}
 		}
 		
 		closedir( $dh );
 		
 		usort( $av_categories, array( 'UserCP', 'sort_avatars' ) );
 		reset( $av_categories );
 		
 		//------------------------------------------
 		// Get the avatar gallery selected
 		//------------------------------------------
 		
 		$url_avatar = "http://";
 		 		
 		$avatar_type = "na";
 		
 		if ( ($this->member['avatar'] != "") and ($this->member['avatar'] != "noavatar") )
 		{
 			if ( preg_match( "/^upload:/", $this->member['avatar'] ) )
 			{
 				$avatar_type = "upload";
 			}
			else if ( !preg_match( "/^http/i", $this->member['avatar'] ) )
			{
				$avatar_type = "gallery";
			}
			else
			{
				$url_avatar = $this->member['avatar'];
				$avatar_type = "url";
			}
 		}
 		
 		//------------------------------------------
 		// Render drop down box..
 		//------------------------------------------
 		
 		$av_gals = "<select name='av_cat' class='forminput'>\n";
 		
 		foreach( $av_categories as $cat )
 		{
 			$av_gals .= "<option value='".$cat[0]."'>".$cat[1]."</option>\n";
 		}
 		
 		$av_gals .= "</select>\n";
 		
 		
 		//------------------------------------------
 		// Rest of the form..
 		//------------------------------------------
 		
 		$formextra   = "";
 		$hidden_field = "";
 		
 		if ($ibforums->member['g_avatar_upload'] == 1)
 		{
 			$formextra    = " enctype='multipart/form-data'";
			$hidden_field = "<input type='hidden' name='MAX_FILE_SIZE' value='".($ibforums->vars['avup_size_max']*1024)."' />";
		}
 		
 		$this->output .= $this->html->avatar_main( array (
															'MEMBER'               => $this->member,
															'avatar_galleries'     => $av_gals,
															'current_url_avatar'   => $url_avatar,
															'current_avatar_image' => $my_avatar,
															'current_avatar_type'  => $ibforums->lang['av_t_'.$avatar_type],
															'current_avatar_dims'  => $this->member['avatar_size'] == "x" ? "" : $this->member['avatar_size'],
												 )  , $formextra, $hidden_field, $this->md5_check     );
		
		//------------------------------------------
 		// Autosizing or manual sizing?
 		//------------------------------------------
 												 
		$size_html = $ibforums->vars['disable_ipbsize'] ? $this->html->avatar_mansize() : $this->html->avatar_autosize();
 		
		//------------------------------------------
 		// Can we use a URL avatar?
 		//------------------------------------------
 		
 		if ($ibforums->vars['avatar_url'])
 		{											 
 			$this->output = str_replace( "<!--IBF.EXTERNAL_TITLE-->",  $this->html->avatar_external_title(), $this->output );
 			$this->output = str_replace( "<!--IBF.URL_AVATAR-->",  $this->html->avatar_url_field($url_avatar), $this->output );
 			$this->output = str_replace( "<!--IPB.SIZE-->", $size_html, $this->output );
 			$ibforums->lang['av_text_url'] = sprintf( $ibforums->lang['av_text_url'], $ibforums->vars['av_width'], $ibforums->vars['av_height'] );
 		}
 		else
 		{
 			$ibforums->lang['av_text_url'] = "";
 		}
 		
 		//------------------------------------------
 		// Can we use an uploaded avatar?
 		//------------------------------------------
 		
		if ($ibforums->member['g_avatar_upload'] == 1)
		{
			$this->output = str_replace( "<!--IBF.EXTERNAL_TITLE-->",  $this->html->avatar_external_title(), $this->output );
			$this->output = str_replace( "<!--IBF.UPLOAD_AVATAR-->", $this->html->avatar_upload_field($text), $this->output );
			$this->output = str_replace( "<!--IPB.SIZE-->", $size_html, $this->output );
			$ibforums->lang['av_text_upload'] = sprintf( $ibforums->lang['av_text_upload'], $ibforums->vars['avup_size_max'] );
		}
		else
		{
			$ibforums->lang['av_text_upload'] = "";
		}
		
		//------------------------------------------
 		// If yes, show little thingy at top
 		//------------------------------------------
 		
 		$ibforums->lang['av_allowed_files'] = sprintf($ibforums->lang['av_allowed_files'], implode (' .', explode( "|", $ibforums->vars['avatar_ext'] ) ) );
 		
 		if ( $ibforums->vars['allow_flash'] != 1 )
		{
			$ibforums->lang['av_allowed_files'] = str_replace( ".swf", "", $ibforums->lang['av_allowed_files'] );
		}
		
		$this->output = str_replace( "<!--IBF.LIMITS_AVATAR-->", $this->html->avatar_limits(), $this->output );
		
 			
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_avatar() {
 	
 		$this->lib->do_avatar();
 		
 	}
 	
 	//*******************************************************************/
 	//| SIGNATURE:
 	//|
 	//| Displays the signature form
 	//*******************************************************************/
 	
 	function signature()
 	{
 		global $ibforums, $DB, $std, $print;
 		
		$t_sig = $this->parser->unconvert( $this->member['signature'], $ibforums->vars['sig_allow_ibc'], $ibforums->vars['sig_allow_html'] );
		
		$ibforums->lang['the_max_length'] = $ibforums->vars['max_sig_length'] ? $ibforums->vars['max_sig_length'] : 0;
		
		if ( $ibforums->vars['sig_allow_html'] == 1 )
		{
			$this->member['signature'] = $this->parser->parse_html($this->member['signature'], 0);
		}
 		
 		$this->output .= $this->html->signature($this->member['signature'], $t_sig, $std->return_md5_check());
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 		
 	}
 	
 	function do_signature() {
 	
 		$this->lib->do_signature();
 	
 	}
 	
 	//*******************************************************************/
 	//| PERSONAL:
 	//|
 	//| Displays the personal info form
 	//*******************************************************************/
 	
 	function personal() {
 		global $ibforums, $DB, $std, $print;
 		
 		//-----------------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------------
		
		if (empty($ibforums->member['g_edit_profile']))
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
		
		//-----------------------------------------------
		// Format the birthday drop boxes..
		//-----------------------------------------------
		
		$date = getdate();
		
		$day  = "<option value='0'>--</option>";
		$mon  = "<option value='0'>--</option>";
		$year = "<option value='0'>--</option>";
		
		for ( $i = 1 ; $i < 32 ; $i++ )
		{
			$day .= "<option value='$i'";
			
			$day .= $i == $this->member['bday_day'] ? "selected>$i</option>" : ">$i</option>";
		}
		
		for ( $i = 1 ; $i < 13 ; $i++ )
		{
			$mon .= "<option value='$i'";
			
			$mon .= $i == $this->member['bday_month'] ? "selected>{$ibforums->lang['month'.$i]}</option>" : ">{$ibforums->lang['month'.$i]}</option>";
		}
		
		$i = $date['year'] - 1;
		$j = $date['year'] - 100;
		
		for ( $i ; $j < $i ; $i-- )
		{
			$year .= "<option value='$i'";
			
			$year .= $i == $this->member['bday_year'] ? "selected>$i</option>" : ">$i</option>";
		}
		
		//-----------------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------------
		
		$required_output = "";
		$optional_output = "";
		$field_data     = array();
		
		$DB->query("SELECT * from ibf_pfields_content WHERE member_id='".$ibforums->member['id']."'");
		
		while ( $content = $DB->fetch_row() )
		{
			foreach($content as $k => $v)
			{
				if ( preg_match( "/^field_(\d+)$/", $k, $match) )
				{
					$field_data[ $match[1] ] = $v;
					//break;
				}
			}
		}
		
		$DB->query("SELECT * from ibf_pfields_data WHERE fedit=1 ORDER BY forder");
		
		while( $row = $DB->fetch_row() )
		{
			$form_element = "";
			
			if ( $row['freq'] == 1 )
			{
				$ftype = 'required_output';
			}
			else
			{
				$ftype = 'optional_output';
			}
			
			if ( $row['ftype'] == 'drop' )
			{
				$carray = explode( '|', trim($row['fcontent']) );
				
				$d_content = "";
				
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
					
					$ov = trim($value[0]);
					$td = trim($value[1]);
					
					if ($ov !="" and $td !="")
					{
						$d_content .= ($field_data[$row['fid']] == $ov) ? "<option value='$ov' selected>$td</option>\n"
																		: "<option value='$ov'>$td</option>\n";
					}
				}
				
				if ($d_content != "")
				{
					$form_element = $this->html->field_dropdown( 'field_'.$row['fid'], $d_content );
				}
			}
			else if ( $row['ftype'] == 'area' )
			{
				$form_element = $this->html->field_textarea( 'field_'.$row['fid'], $field_data[$row['fid']] );
			}
			else
			{
				$form_element = $this->html->field_textinput( 'field_'.$row['fid'], $field_data[$row['fid']] );
			}
			
			${$ftype} .= $this->html->field_entry( $row['ftitle'], $row['fdesc'], $form_element );
		}
		
		//-----------------------------------------------
		// Format the interest / location boxes
		//-----------------------------------------------
		
		$this->member['location']  = $this->parser->unconvert( $this->member['location']  );
 		$this->member['interests'] = $this->parser->unconvert( $this->member['interests'] );
 		
 		$this->member['key']       = $this->md5_check;
 		
		//-----------------------------------------------
		// Suck up the HTML and swop some tags if need be
		//-----------------------------------------------
		
		$this->output .= $this->html->personal_panel($this->member);
		
		if ( ($ibforums->vars['post_titlechange']) and ($this->member['posts'] > $ibforums->vars['post_titlechange']) )
		{
			$t_html = $this->html->member_title($this->member['title']);
			$this->output = preg_replace( "/<!--\{MEMBERTITLE\}-->/", $t_html, $this->output );
		}
		
		$t_html = $this->html->birthday($day, $mon, $year);
		
		$this->output = preg_replace( "/<!--\{BIRTHDAY\}-->/", $t_html, $this->output );
		
		//-----------------------------------------------
		// Add in the custom fields if we need to.
		//-----------------------------------------------
		
		if ($required_output != "")
		{
			$this->output = str_replace( "<!--{REQUIRED.FIELDS}-->", $this->html->required_title()."\n".$required_output.$this->html->required_end(), $this->output );
		}
		
		if ($optional_output != "")
		{
			$this->output = str_replace( "<!--{OPTIONAL.FIELDS}-->", "\n".$optional_output, $this->output );
		}
		
		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
		
	}
	
	function do_personal() {
		
		// Hand it straight to our library to keep this code clean and compact.
		
		$this->lib->do_profile();
		
	}
	
	
	
 	
 	//*******************************************************************/
 	//| SPLASH:
 	//|
 	//| Displays the intro screen
 	//*******************************************************************/
 	
 	function splash() {
 		global $ibforums, $DB, $std, $print;
 		
		//-----------------------------------------------
		// Format the basic data
		//-----------------------------------------------
		
		$info['MEMBER_EMAIL']    = $this->member['email'];
		$info['DATE_REGISTERED'] = $std->get_date( $this->member['joined'], 'LONG' );
		$info['MEMBER_POSTS']    = $this->member['posts'];
		
		$info['DAILY_AVERAGE']   = $ibforums->lang['no_posts'];
		
		if ($this->member['posts'] > 0 )
		{
			$diff = time() - $this->member['joined'];
			$days = ($diff / 3600) / 24;
			$days = $days < 1 ? 1 : $days;
			$info['DAILY_AVERAGE']  = sprintf('%.2f', ($this->member['posts'] / $days) );
		}
		
		//---------------------------------------------
 		// Get the number of messages we have in total.
 		//---------------------------------------------
 		
 		$DB->query("SELECT COUNT(*) as msg_total FROM ibf_messages WHERE member_id='".$this->member['id']."'");
 		$total = $DB->fetch_row();
 		
 		//---------------------------------------------
 		// Make sure we've not exceeded our alloted allowance.
 		//---------------------------------------------
 		
 		$info['full_messenger'] = "";
 		$info['full_percent']   = "";
 		$info['space_free']     = "Unlimited";
 		$info['total_messages'] = $total['msg_total'];
 		
 		if ($ibforums->member['g_max_messages'] > 0)
 		{
 			if ($total['msg_total'] >= $ibforums->member['g_max_messages'])
 			{
 				$info['full_messenger'] = "<span class='highlight'>".$ibforums->lang['folders_full']."</span>";
 			}
 			
 			$info['full_percent'] = $total['msg_total'] ? sprintf( "%.0f", ( ($total['msg_total'] / $ibforums->member['g_max_messages']) * 100) ) : 0;
 			$info['full_percent'] = "(". $info['full_percent'] .'% '. $ibforums->lang['total_capacity'] . ")";
 			$info['space_free']   = $ibforums->member['g_max_messages'] - $total['msg_total'];
 		}
 		
		
		//-----------------------------------------------
		// Write the data..
		//-----------------------------------------------
		
		$s_array = array( 's' => 5 ,
						  'm' => 7 ,
						  'l' => 15
						);
		
		$info['NOTES'] = $this->notes ? $this->notes : $ibforums->lang['note_pad_empty'];
		
		$info['SIZE']  = $s_array[$this->size];
		
		$info['SIZE_CHOICE'] = "";
		
		//------------------------------------
		// If someone has cheated, fix it now.
		//-------------------------------------
		
		if ( empty($info['SIZE']) )
		{
			$info['SIZE'] = '5';
		}
		
		//-------------------------------------
		// Make the choice HTML.
		//-------------------------------------
		
		foreach ($s_array as $k => $v)
		{
			if ($v == $info['SIZE'])
			{
				$info['SIZE_CHOICE'] .= "<option value='$k' selected>{$ibforums->lang['ta_'.$k]}</option>";
			}
			else
			{
				$info['SIZE_CHOICE'] .= "<option value='$k'>{$ibforums->lang['ta_'.$k]}</option>";
			}
		}
 		
 		$info['NOTES'] = preg_replace( "/<br>/", "\n", $info['NOTES'] );
 		
 		$this->output .= $this->html->splash($info);
 		
 		// If no messenger, remove the links!
    	
    	if ( ! $ibforums->member['g_use_pm'])
        {
        	$this->output = preg_replace( "/<!-- MSG -->.+?<!-- END MSG -->/s", "", $this->output );
        }
 		
 		$this->page_title = $ibforums->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$ibforums->lang['t_title']."</a>" );
 	}
 	
 	
 	
 	
 	
 	
 	//*******************************************************************/
 	//| UPDATE_NOTEPAD:
 	//|
 	//| Displays the intro screen
 	//*******************************************************************/
 	
 	function update_notepad() {
 		global $ibforums, $DB, $std, $HTTP_POST_VARS;
 		
 		// Do we have an entry for this member?
 		
 		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		//+----------------------------------------
 		
 		$DB->query("SELECT id from ibf_member_extra WHERE id='".$this->member['id']."'");
 		
 		if ( $DB->get_num_rows() )
 		{ 
 			$DB->query("UPDATE ibf_member_extra SET notes='".$ibforums->input['notes']."', ta_size='".$ibforums->input['ta_size']."' WHERE id='".$this->member['id']."'");
 		}
 		else
 		{
 			$DB->query("INSERT INTO ibf_member_extra (id, notes, ta_size) ".
 			           " VALUES ('".$this->member['id']."', '".$ibforums->input['notes']."', '".$ibforums->input['ta_size']."')");
 		}
 		
 		$std->boink_it($this->base_url."act=UserCP&CODE=00");
 		exit;
 	}
 	
 	
 	function show_image()
	{
		global $ibforums, $DB, $std;
		
		
		if ( $ibforums->input['rc'] == "" )
		{
			return false;
		}
		
		// Get the info from the db
		
		$DB->query("SELECT * FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['rc']))."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			return false;
		}
		
		//--------------------------------------------
		// Using GD?
		//--------------------------------------------
		
		if ( $ibforums->vars['bot_antispam'] == 'gd' )
		{
			$std->show_gd_img($row['regcode']);
		}
		else
		{
		
			//--------------------------------------------
			// Using normal then, check for "p"
			//--------------------------------------------
			
			if ( $ibforums->input['p'] == "" )
			{
				return false;
			}
			
			$p = intval($ibforums->input['p']) - 1; //substr starts from 0, not 1 :p
			
			$this_number = substr( $row['regcode'], $p, 1 );
			
			$std->show_gif_img($this_number);
		}
		
	}
        
}

?>