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
|   > User Profile functions
|   > Module written by Matt Mecham
|   > Date started: 28th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Profile;

class Profile {

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
    
    var $show_photo = "";
    var $show_width = "";
    var $show_height = "";
    var $show_name  = "";
    
    var $photo_member = "";
    
    var $has_photo   = FALSE;
    
    var $lib;
    
    function Profile() {
    	global $ibforums, $DB, $std, $print;
    
    	require "./sources/lib/post_parser.php";
        
        $this->parser = new post_parser();
    	
    	//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
    	$ibforums->lang = $std->load_words($ibforums->lang, 'lang_profile'  , $ibforums->lang_id );
    	
    	$this->html = $std->load_template('skin_profile');
    	
    	$this->base_url        = $ibforums->base_url;
    	$this->base_url_nosess = "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}";
    	
    	//--------------------------------------------
    	// Check viewing permissions, etc
		//--------------------------------------------
		
		$this->member  = $ibforums->member;
		$this->m_group = $ibforums->member;
		
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	
    	switch($ibforums->input['CODE']) {
    		case '03':
    			$this->view_profile();
    			break;
    			
    		case 'showphoto':
    			$this->show_photo();
    			break;
    			
    		case 'showcard':
    			$this->show_card();
    		
    		//------------------------------
    		default:
    			$this->view_profile();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, NAV => $this->nav ) );
    		
 	}
 	
 	//---------------------------------------------------------------------------
 	//
 	// VIEW CONTACT CARD:
 	//
 	//---------------------------------------------------------------------------
 	
 	function show_card()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$info = array();
 		
 		if ($ibforums->member['g_mem_info'] != 1)
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//--------------------------------------------
    	// Check input..
    	//--------------------------------------------
    	
    	$id = intval($ibforums->input['MID']);
    	
    	if ( empty($id) )
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	$DB->query("SELECT * FROM ibf_members WHERE id=$id");
    	
    	$member = $DB->fetch_row();
    	
    	$member['password'] = '';
    	
    	$info['aim_name']    = $member['aim_name']   ? $member['aim_name']   : $ibforums->lang['no_info'];
    	$info['icq_number']  = $member['icq_number'] ? $member['icq_number'] : $ibforums->lang['no_info'];
    	$info['yahoo']       = $member['yahoo']      ? $member['yahoo']      : $ibforums->lang['no_info'];
    	$info['location']    = $member['location']   ? $member['location']   : $ibforums->lang['no_info'];
    	$info['interests']   = $member['interests']  ? $member['interests']  : $ibforums->lang['no_info'];
    	$info['msn_name']    = $member['msnname']    ? $member['msnname']    : $ibforums->lang['no_info'];
    	$info['integ_msg']   = $member['integ_msg']  ? $member['integ_msg']  : $ibforums->lang['no_info'];
    	$info['mid']         = $member['id'];
    	
    	if (!$member['hide_email'])
    	{
			$info['email'] = "<a href='javascript:redirect_to(\"&amp;act=Mail&amp;CODE=00&amp;MID={$member['id']}\",1);'>{$ibforums->lang['click_here']}</a>";
		}
		else
		{
			$info['email'] = $ibforums->lang['private'];
		}
    	
    	$this->load_photo($id);
    	
    	if ( $this->has_photo == TRUE )
    	{
    		$photo = $this->html->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	}
    	else
    	{
    		$photo = "<{NO_PHOTO}>";
    	}
    	
    	if ($ibforums->input['download'] == 1)
    	{
    		$photo = str_replace( "<{NO_PHOTO}>", "No Photo Available", $photo );
    		$html  = $this->html->show_card_download( $member['name'], $photo, $info );
    		
    		@flush();
			@header("Content-type: unknown/unknown");
			@header("Content-Disposition: attachment; filename={$member['name']}.html");
			print $html;
			exit();
    	}
    	else
    	{
			$html  = $this->html->show_card( $member['name'], $photo, $info );
			
			$print->pop_up_window( $ibforums->lang['photo_title'], $html );
    	}
    	
    }
 	
 	//---------------------------------------------------------------------------
 	//
 	// VIEW PHOTO:
 	//
 	//---------------------------------------------------------------------------
 	
 	function show_photo()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$info = array();
 		
 		if ($ibforums->member['g_mem_info'] != 1)
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//--------------------------------------------
    	// Check input..
    	//--------------------------------------------
    	
    	$id = intval($ibforums->input['MID']);
    	
    	if ( empty($id) )
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	$this->load_photo($id);
    	
    	if ( $this->has_photo == TRUE )
    	{
    		$photo = $this->html->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	}
    	else
    	{
    		$photo = "<{NO_PHOTO}>";
    	}
    	
    	$html  = $this->html->show_photo( $this->photo_member['name'], $photo );
    	
    	$print->pop_up_window( $ibforums->lang['photo_title'], $html );
    	
    }
    
    
    //---------------------------------------------------------------------------
 	//
 	// FUNC: RETURN PHOTO
 	//
 	//---------------------------------------------------------------------------
 	
    
    function load_photo($id)
    {
    	global $ibforums, $DB, $std, $print;
    	
    	$this->show_photo  = "";
    	$this->show_height = "";
    	$this->show_width  = "";
    	
    	$DB->query("SELECT m.id, m.name, me.photo_type, me.photo_location, me.photo_dimensions FROM ibf_member_extra me
    	              LEFT JOIN ibf_members m ON me.id=m.id
    			    WHERE m.id=$id");
    	
    	$this->photo_member = $DB->fetch_row();
    	
    	if ( $this->photo_member['photo_type'] and $this->photo_member['photo_location'] )
    	{
    		$this->has_photo = TRUE;
    		
    		list( $show_width, $show_height ) = explode( ",", $this->photo_member['photo_dimensions'] );
    		
    		if ($this->photo_member['photo_type'] == 'url')
    		{
    			$this->show_photo = $this->photo_member['photo_location'];
    		}
    		else
    		{
    			$this->show_photo = $ibforums->vars['upload_url']."/".$this->photo_member['photo_location'];
    		}
    		
    		if ( $show_width > 0 )
    		{
    			$this->show_width = "width='$show_width'";
    		}
    		
    		if ( $show_height > 0 )
    		{
    			$this->show_height = "height='$show_height'";
    		}
    	}
    }
    
 	
 	//---------------------------------------------------------------------------
 	//
 	// VIEW MAIN PROFILE:
 	//
 	//---------------------------------------------------------------------------
 	
 	function view_profile()
 	{
 		global $ibforums, $DB, $std, $print;
 		
 		$info = array();
 		
 		if ($ibforums->member['g_mem_info'] != 1)
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//--------------------------------------------
    	// Check input..
    	//--------------------------------------------
    	
    	$id = intval($ibforums->input['MID']);
    	
    	if ( empty($id) )
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	//--------------------------------------------
    	// Prepare Query...
    	//--------------------------------------------
    	
    	$DB->query("SELECT m.*, g.g_id, g.g_title as group_title FROM ibf_members m, ibf_groups g WHERE m.id='$id' and m.mgroup=g.g_id");
    	$member = $DB->fetch_row();
    	
    	if (empty($member['id']))
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	// Play it safe
    	
    	$member['password'] = "";
    	
    	//--------------------------------------------
    	// Find the most posted in forum that the viewing
    	// member has access to by this members profile
    	//--------------------------------------------
    	
    	$DB->query("SELECT id, read_perms FROM ibf_forums");
    	
    	$forum_ids = array('0');
    	
    	while ( $r = $DB->fetch_row() )
    	{
    		if ( $std->check_perms($r['read_perms']) == TRUE )
    		{
    			$forum_ids[] = $r['id'];
    		}
    	}
    	
    	$forum_id_str = implode( ",", $forum_ids );
    	
    	$percent = 0;
    	
    	$DB->query("SELECT DISTINCT(p.forum_id), f.name, COUNT(p.author_id) as f_posts FROM ibf_posts p, ibf_forums f ".
    			   "WHERE p.forum_id IN ($forum_id_str) AND p.author_id='".$member['id']."' AND p.forum_id=f.id GROUP BY p.forum_id ORDER BY f_posts DESC");
    			   
    	$favourite   = $DB->fetch_row();
    	
    	$DB->query("SELECT COUNT(pid) as total_posts FROM ibf_posts WHERE author_id='".$member['id']."'");
    	
    	$total_posts = $DB->fetch_row();
    	
    	$DB->query("SELECT TOTAL_TOPICS, TOTAL_REPLIES FROM ibf_stats");
    	
    	$stats = $DB->fetch_row();
    	
    	$board_posts = $stats['TOTAL_TOPICS'] + $stats['TOTAL_REPLIES'];
    	
    	if ($total_posts['total_posts'] > 0)
    	{
    		$percent = round( $favourite['f_posts'] / $total_posts['total_posts'] * 100 );
    	}
    	
    	if ($member['posts'] and $board_posts)
    	{
    		$info['posts_day'] = round( $member['posts'] / (((time() - $member['joined']) / 86400)), 1);
    		$info['total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
    	}
    	
    	if ($info['posts_day'] > $member['posts'])
    	{
    		$info['posts_day'] = $member['posts'];
    	}
    	
    	$info['posts']       = $member['posts'] ? $member['posts'] : 0;
    	$info['name']        = $member['name'];
    	$info['mid']         = $member['id'];
    	$info['fav_forum']   = $favourite['name'];
    	$info['fav_id']      = $favourite['forum_id'];
    	$info['fav_posts']   = $favourite['f_posts'];
    	$info['percent']     = $percent;
    	$info['group_title'] = $member['group_title'];
    	$info['board_posts'] = $board_posts;
    	$info['joined']      = $std->get_date( $member['joined'], 'JOINED' );
    	
    	$info['member_title'] = $member['title']     ? $member['title']      : $ibforums->lang['no_info'];
    	
    	$info['aim_name']    = $member['aim_name']   ? $member['aim_name']   : $ibforums->lang['no_info'];
    	$info['icq_number']  = $member['icq_number'] ? $member['icq_number'] : $ibforums->lang['no_info'];
    	$info['yahoo']       = $member['yahoo']      ? $member['yahoo']      : $ibforums->lang['no_info'];
    	$info['location']    = $member['location']   ? $member['location']   : $ibforums->lang['no_info'];
    	$info['interests']   = $member['interests']  ? $member['interests']  : $ibforums->lang['no_info'];
    	$info['msn_name']    = $member['msnname']    ? $member['msnname']    : $ibforums->lang['no_info'];
    	$info['integ_msg']   = $member['integ_msg']  ? $member['integ_msg']  : $ibforums->lang['no_info'];
    	
    	$ibforums->vars['time_adjust'] = $ibforums->vars['time_adjust'] == "" ? 0 : $ibforums->vars['time_adjust'];
    	
    	if ($member['dst_in_use'] == 1)
    	{
    		$member['time_offset'] += 1;
    	}
    	
    	// This is a useless comment. Completely void of any useful information
    	
    	$info['local_time']  = $member['time_offset'] != "" ? gmdate( $ibforums->vars['clock_long'], time() + ($member['time_offset']*3600) + ($ibforums->vars['time_adjust'] * 60) ) : $ibforums->lang['no_info'];
    	
    	$info['avatar']      = $std->get_avatar( $member['avatar'] , 1, $member['avatar_size'] );
    	
    	$info['signature']   = $member['signature'];
    	
    	if ( $ibforums->vars['sig_allow_html'] == 1 )
		{
			$info['signature'] = $this->parser->parse_html($info['signature'], 0);
		}
    	
    	if ( $member['website'] and preg_match( "/^http:\/\/\S+$/", $member['website'] ) )
    	{
			$info['homepage'] = "<a href='{$member['website']}' target='_blank'>{$member['website']}</a>";
		}
		else
		{
			$info['homepage'] = $ibforums->lang['no_info'];
		}
		
    	
    	if ($member['bday_month'])
    	{
    		$info['birthday'] = $member['bday_day']." ".$ibforums->lang[ 'M_'.$member['bday_month'] ]." ".$member['bday_year'];
    	}
    	else
    	{
    		$info['birthday'] = $ibforums->lang['no_info'];
    	}
    	
    	
    	if (!$member['hide_email']) {
			$info['email'] = "<a href='{$this->base_url}act=Mail&amp;CODE=00&amp;MID={$member['id']}'>{$ibforums->lang['click_here']}</a>";
		}
		else
		{
			$info['email'] = $ibforums->lang['private'];
		}
		
		//---------------------------------------------------
		// Get photo and show profile:
		//---------------------------------------------------
		
		$this->load_photo($id);
		
		if ( $this->has_photo == TRUE )
    	{
    		$info['photo'] = $this->html->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	}
    	else
    	{
    		$info['photo'] = "";
    	}
    	
    	$info['base_url'] = $this->base_url;
    	
    	$info['posts'] = $std->do_number_format($info['posts']);
    	
    	//---------------------------------------------------
    	// Output
    	//---------------------------------------------------
    	
    	$this->output .= $this->html->show_profile( $info );
    	
    	//---------------------------------------------------
    	// Is this our profile?
    	//---------------------------------------------------
    	
    	if ($member['id'] == $this->member['id'])
    	{
    		$this->output = preg_replace( "/<!--MEM OPTIONS-->/e", "\$this->html->user_edit(\$info)", $this->output );
    	}
    	
    	//---------------------------------------------------
    	// Can mods see the hidden parts of this profile?
    	//---------------------------------------------------
    	
    	$query_extra = 'WHERE fedit=1 AND fhide <> 1';
    	$custom_out  = "";
    	$field_data  = array();
    	
    	if ($ibforums->member['id'])
        {
        	if ($ibforums->member['g_is_supmod'] == 1)
        	{
        		$query_extra = "";
        	}
        	else if ($ibforums->member['mgroup'] == $ibforums->vars['admin_group'])
        	{
        		$query_extra = "";
        	}
        }
        
        $DB->query("SELECT * from ibf_pfields_content WHERE member_id='".$member['id']."'");
		
		while ( $content = $DB->fetch_row() )
		{
			foreach($content as $k => $v)
			{
				if ( preg_match( "/^field_(\d+)$/", $k, $match) )
				{
					$field_data[ $match[1] ] = $v;
				}
			}
		}
		
		$DB->query("SELECT * from ibf_pfields_data $query_extra ORDER BY forder");
		
		while( $row = $DB->fetch_row() )
		{
			if ($row['ftype'] == 'drop')
			{
				$carray = explode( '|', trim($row['fcontent']) );
				
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
					
					$ov = trim($value[0]);
					$td = trim($value[1]);
					
					if ($field_data[ $row['fid'] ] == $ov)
					{
						$field_data[ $row['fid'] ] = $td;
					}
				}
			}
			else
			{
				$field_data[ $row['fid'] ] = ($field_data[ $row['fid'] ] == "") ? $ibforums->lang['no_info'] : nl2br($field_data[ $row['fid'] ]);
			}
			
    		$custom_out .= $this->html->custom_field($row['ftitle'], $field_data[ $row['fid'] ] );
    	}
    	
    	if ($custom_out != "")
    	{
    		$this->output = str_replace( "<!--{CUSTOM.FIELDS}-->", $custom_out, $this->output );
    	}
    	
    	//---------------------------------------------------
    	// Warning stuff!!
    	//---------------------------------------------------
    	
    	$pass = 0;
    	$mod  = 0;
    	
    	if ( $ibforums->vars['warn_on'] and ( ! stristr( $ibforums->vars['warn_protected'], ','.$member['mgroup'].',' ) ) )
		{
			if ($ibforums->member['id'])
			{
				if ( $ibforums->member['g_is_supmod'] == 1 )
				{
					$pass = 1;
					$mod  = 1;
				}
				else
				{
					$DB->query("SELECT * FROM ibf_moderators WHERE (member_id=".$ibforums->member['id']." OR (is_group=1 AND group_id=".$ibforums->member['mgroup']."))");
					$this->moderator = $DB->fetch_row();
					
					if ( $this->moderator['mid'] AND $this->moderator['allow_warn'] == 1 )
					{
						$pass = 1;
						$mod  = 1;
					}
				}
				
				if ( $pass == 0 and ( $ibforums->vars['warn_show_own'] and ( $member['id'] == $ibforums->member['id'] ) ) )
				{
					$pass = 1;
				}
				
				if ( $pass == 1 )
				{
					// Work out which image to show.
					
					if ( ! $ibforums->vars['warn_show_rating'] )
					{
						if ( $member['warn_level'] < 1 )
						{
							$member['warn_img'] = '<{WARN_0}>';
						}
						else if ( $member['warn_level'] >= $ibforums->vars['warn_max'] )
						{
							$member['warn_img']     = '<{WARN_5}>';
							$member['warn_percent'] = 100;
						}
						else
						{
							$member['warn_percent'] = $member['warn_level'] ? sprintf( "%.0f", ( ($member['warn_level'] / $ibforums->vars['warn_max']) * 100) ) : 0;
							
							if ( $member['warn_percent'] > 100 )
							{
								$member['warn_percent'] = 100;
							}
							
							if ( $member['warn_percent'] >= 81 )
							{
								$member['warn_img'] = '<{WARN_5}>';
							}
							else if ( $member['warn_percent'] >= 61 )
							{
								$member['warn_img'] = '<{WARN_4}>';
							}
							else if ( $member['warn_percent'] >= 41 )
							{
								$member['warn_img'] = '<{WARN_3}>';
							}
							else if ( $member['warn_percent'] >= 21 )
							{
								$member['warn_img'] = '<{WARN_2}>';
							}
							else if ( $member['warn_percent'] >= 1 )
							{
								$member['warn_img'] = '<{WARN_1}>';
							}
							else
							{
								$member['warn_img'] = '<{WARN_0}>';
							}
						}
						
						if ( $member['warn_percent'] < 1 )
						{
							$member['warn_percent'] = 0;
						}
						
						if ( $mod == 1 )
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->html->warn_level($member['id'], $member['warn_img'], $member['warn_percent']), $this->output );
						}
						else
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->html->warn_level_no_mod($member['id'], $member['warn_img'], $member['warn_percent']), $this->output );
						}
					}
					else
					{
						// Rating mode:
						
						if ( $mod == 1 )
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->html->warn_level_rating($member['id'], $member['warn_level'], $ibforums->vars['warn_min'], $ibforums->vars['warn_max']), $this->output );
						}
						else
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->html->warn_level_rating_no_mod($member['id'], $member['warn_level'], $ibforums->vars['warn_min'], $ibforums->vars['warn_max']), $this->output );
						}
					}	
				}
			}
    	}
    	
 		$this->page_title = $ibforums->lang['page_title'];
 		$this->nav        = array( $ibforums->lang['page_title'] );
 		
 	}
 	
 	
 	
}

?>