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
|   > IPChat functions
|   > Script written by Matt Mecham
|   > Date started: 29th September 2003
|
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class chat_functions
{

	var $class  = "";
	var $server = "";
	var $html   = "";
	
	function chat_functions()
	{
		global $DB, $std, $ibforums;
		
		$this->server = str_replace( 'http://', '', $ibforums->vars['chat_server_addr'] );
	}
	
	//-----------------------------------------------
	// register_class($class)
	//
	// Register a $this-> with this class 
	//
	//-----------------------------------------------
	
	function register_class(&$class)
	{
		$this->class = $class;
	}

	//-----------------------------------------------
	// Print online list
	//
	//-----------------------------------------------
	
	function get_online_list()
	{
		global $DB, $std, $ibforums;
		
		if ( ! $ibforums->vars['chat_who_on'] )
		{
			return;
		}
		
		//----------------------------------
		// Get details from the DB
		//----------------------------------
		
		$DB->query("SELECT * FROM ibf_cache_store WHERE cs_key='chatstat'");
		
		$row = $DB->fetch_row();
		
		list( $hits, $time ) = explode( '&', $row['cs_extra'] );
		
		//----------------------------------
		// Do we need to update?
		//----------------------------------
		
		$final = "";
		$time_is_running_out = time();
		$member_ids = array();
		
		if ( $time < time() - ( $ibforums->vars['chat_who_save'] * 60 ) )
		{
			$server_url = 'http://'.$this->server.'/ipc_who.pl?id='.$ibforums->vars['chat_account_no'].'&pw='.$ibforums->vars['chat_pass_md5'];
			
			if ( $data = @file( $server_url ) )
			{
				if ( count($data) > 0 )
				{
					$hits_left = array_shift($data);
				}
				
				$name_string = strtolower( implode( '","', str_replace( '"', '&quot;', str_replace( '_', ' ', $data ) ) ) );
				
				if ( count($data) > 0 )
				{
					$DB->query("SELECT m.id, m.name, g.g_id, g.prefix, g.suffix FROM ibf_members m
								 LEFT JOIN ibf_groups g ON (m.mgroup=g.g_id)
								WHERE lower(name) IN (\"".$name_string."\") ORDER BY m.name");
					
					while ( $m = $DB->fetch_row() )
					{
						$member_ids[] = "<a href=\"{$ibforums->base_url}showuser={$m['id']}\">{$m['prefix']}{$m['name']}{$m['suffix']}</a>";
					}
					
					$final = implode( ",\n", $member_ids );
					
					$final .= '|&|'.intval(count($member_ids));
				}
				
				$DB->query("UPDATE ibf_cache_store SET cs_value='".addslashes($final)."', cs_extra='{$hits_left}&{$time_is_running_out}' WHERE cs_key='chatstat'");
				
				$row['cs_value'] = $final;
			}
		}
		
		//----------------------------------
		// Any members to show?
		//----------------------------------
		
		$ibforums->vars['chat_height'] += $ibforums->vars['chat_poppad'] ? $ibforums->vars['chat_poppad'] : 50;
		$ibforums->vars['chat_width']  += $ibforums->vars['chat_poppad'] ? $ibforums->vars['chat_poppad'] : 50;
		
		$chat_link = ( $ibforums->vars['chat_display'] == 'self' )
				   ? $this->class->html->whoschatting_inline_link()
				   : $this->class->html->whoschatting_popup_link();
		
		list ($names, $count) = explode( '|&|', $row['cs_value'] );
		
		if ( $count > 0 )
		{
			$txt = sprintf( $ibforums->lang['whoschatting_delay'], $ibforums->vars['chat_who_save'] );
			$this->html = $this->class->html->whoschatting_show( intval($count), stripslashes($names), $chat_link, $txt );
		}
		else
		{
			if ( ! $ibforums->vars['chat_hide_whoschatting'] )
			{
				$this->html = $this->class->html->whoschatting_empty($chat_link);
			}
		}
		
		return $this->html;
				
	}





}



?>