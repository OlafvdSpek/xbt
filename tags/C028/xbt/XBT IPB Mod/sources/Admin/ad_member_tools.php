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
|   > Admin Member Tool functions
|   > Module written by Matt Mecham
|   > Date started: 17th September 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$idx = new ad_member_tools();

$root_path = "";

class ad_member_tools {

	var $base_url;
	var $modules = "";

	function ad_member_tools()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $ibforums;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		$ADMIN->nav[] = array( 'act=mtools', 'Member Tools Home' );
		
		//---------------------------------------
		
		$ADMIN->page_title  = "Member Tool Box";
		$ADMIN->page_detail = 'You can use the tools below to search for IP address.';

		switch($IN['code'])
		{
			
			case 'showallips':
				$this->show_ips();
				break;
				
			case 'learnip':
				$this->learn_ip();
				break;
			
			//---------------------
			default:
				$this->show_index();
				break;
		}
		
	}
	
	
	//+---------------------------------------------------------------------------------
	//
	// LEARN ABOUT THE IP. It's very good.
	//
	//+---------------------------------------------------------------------------------
	
	
	function learn_ip()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ( $IN['ip'] == "" )
		{
			$this->show_index("You did not enter an IP address to search by");
		}
		
		$ip = $IN['ip'];
		
		$resolved = 'N/A - Partial IP Address';
		$exact    = 0;
		
		if ( substr_count( $ip, '.' ) == 3 )
		{
			$exact = 1;
		}
		
		if ( strstr( $ip, '*' ) )
		{
			$exact = 0;
			$ip    = str_replace( "*", "", $ip );
		}
			
		if ( $exact != 0 )
		{
			$resolved = gethostbyaddr($ip);
			$query    = "='".$ip."'";
		}
		else
		{
			$query    = " LIKE '".$ip."%'";
		}
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Host Address for {$IN['ip']}" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>IP address resolves to</b>" ,
												  $resolved
										  )      );
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+--------------------------------------------------
		// Find registered members
		//+--------------------------------------------------
		
		$SKIN->td_header[] = array( "Name"       , "30%" );
		$SKIN->td_header[] = array( "Email"      , "20%" );
		$SKIN->td_header[] = array( "Posts"      , "10%" );
		$SKIN->td_header[] = array( "IP"         , "20%" );
		$SKIN->td_header[] = array( "Registered" , "20%" );
		
		$ADMIN->html .= $SKIN->start_table( "Members using that IP when REGISTERING" );
		
		$DB->query("SELECT id, name, email, posts, ip_address, joined FROM ibf_members WHERE ip_address{$query} ORDER BY joined DESC LIMIT 250");
		
		if ( ! $DB->get_num_rows() )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $DB->fetch_row() )
			{
				
				$ADMIN->html .= $SKIN->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['posts'],
														  $m['ip_address'],
														  $std->get_date( $m['joined'], 'SHORT' )
												 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+--------------------------------------------------
		// Find Names posted under
		//+--------------------------------------------------
		
		$SKIN->td_header[] = array( "Name"       , "20%" );
		$SKIN->td_header[] = array( "Email"      , "20%" );
		$SKIN->td_header[] = array( "IP"         , "15%" );
		$SKIN->td_header[] = array( "First Used"  , "20%" );
		$SKIN->td_header[] = array( "View Post"  , "15%" );
		
		$ADMIN->html .= $SKIN->start_table( "Members using that IP when POSTING" );
		
		$DB->query("SELECT m.id, m.name, m.email, m.posts, m.joined, p.pid, p.author_id, p.post_date, p.ip_address, p.topic_id
					FROM ibf_posts p
					 LEFT JOIN ibf_members m ON ( p.author_id=m.id)
					WHERE p.ip_address{$query} GROUP BY p.author_id ORDER BY p.post_date DESC LIMIT 250");
		
		if ( ! $DB->get_num_rows() )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $DB->fetch_row() )
			{
				$ADMIN->html .= $SKIN->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['ip_address'],
														  $std->get_date( $m['post_date'], 'SHORT' ),
														  "<center><a href='index.php?showtopic={$m['topic_id']}&view=findpost&p={$m['pid']}' target='_blank'>View Post</a></center>",
												 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+--------------------------------------------------
		// Find Names VOTED under
		//+--------------------------------------------------
		
		$SKIN->td_header[] = array( "Name"       , "20%" );
		$SKIN->td_header[] = array( "Email"      , "20%" );
		$SKIN->td_header[] = array( "IP"         , "15%" );
		$SKIN->td_header[] = array( "First Used" , "20%" );
		$SKIN->td_header[] = array( "View Poll" , "15%" );
		
		$ADMIN->html .= $SKIN->start_table( "Members using that IP when VOTING" );
		
		$DB->query("SELECT m.id, m.name, m.email, m.posts, m.joined, p.vote_date, p.ip_address, p.tid
					FROM ibf_voters p
					 LEFT JOIN ibf_members m ON ( p.member_id=m.id)
					WHERE p.ip_address{$query} GROUP BY p.member_id ORDER BY p.vote_date DESC LIMIT 250");
		
		if ( ! $DB->get_num_rows() )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $DB->fetch_row() )
			{
				$ADMIN->html .= $SKIN->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['ip_address'],
														  $std->get_date( $m['vote_date'], 'SHORT' ),
														  "<center><a href='index.php?showtopic={$m['tid']}' target='_blank'>View Poll</a></center>",
												 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+--------------------------------------------------
		// Find Names EMAILING under
		//+--------------------------------------------------
		
		$SKIN->td_header[] = array( "Name"       , "20%" );
		$SKIN->td_header[] = array( "Email"      , "20%" );
		$SKIN->td_header[] = array( "IP"         , "15%" );
		$SKIN->td_header[] = array( "First Used"    , "20%" );
		
		$ADMIN->html .= $SKIN->start_table( "Members using that IP when EMAILING other members" );
		
		$DB->query("SELECT m.id, m.name, m.email, m.posts, m.joined, p.email_date, p.from_ip_address
					FROM ibf_email_logs p
					 LEFT JOIN ibf_members m ON ( p.from_member_id=m.id)
					WHERE p.from_ip_address{$query} GROUP BY p.from_member_id ORDER BY p.email_date DESC LIMIT 250");
		
		if ( ! $DB->get_num_rows() )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $DB->fetch_row() )
			{
				$ADMIN->html .= $SKIN->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['from_ip_address'],
														  $std->get_date( $m['email_date'], 'SHORT' ),
												 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		
		//+--------------------------------------------------
		// Find Names VALIDATING under
		//+--------------------------------------------------
		
		$SKIN->td_header[] = array( "Name"       , "20%" );
		$SKIN->td_header[] = array( "Email"      , "20%" );
		$SKIN->td_header[] = array( "IP"         , "15%" );
		$SKIN->td_header[] = array( "First Used" , "20%" );
		
		$ADMIN->html .= $SKIN->start_table( "Members using that IP while VALIDATING their accounts" );
		
		$DB->query("SELECT m.id, m.name, m.email, m.posts, m.joined, p.entry_date, p.ip_address
					FROM ibf_validating p
					 LEFT JOIN ibf_members m ON ( p.member_id=m.id)
					WHERE p.ip_address{$query} GROUP BY p.member_id ORDER BY p.entry_date DESC LIMIT 250");
		
		if ( ! $DB->get_num_rows() )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $DB->fetch_row() )
			{
				$ADMIN->html .= $SKIN->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['ip_address'],
														  $std->get_date( $m['entry_date'], 'SHORT' ),
												 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
	}
	
	//+---------------------------------------------------------------------------------
	//
	// SHOW ALL IPs
	//
	//+---------------------------------------------------------------------------------
	
	
	function show_ips()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ( $IN['name'] == "" and $IN['member_id'] == "" )
		{
			$this->show_index("You did not enter a name to search by");
		}
		
		if ( $IN['member_id'] )
		{
			$id = intval($IN['member_id']);
			
			$DB->query("SELECT id, name, email, ip_address FROM ibf_members WHERE id=$id");
			
			if ( ! $member = $DB->fetch_row() )
			{
				$this->show_index("Could not locate a member with the id of '$id'");
			}
		}
		else
		{
			$name = addslashes($IN['name']);
			
			$DB->query("SELECT id, name, email, ip_address FROM ibf_members WHERE name='$name'");
			
			if ( ! $member = $DB->fetch_row() )
			{
				$this->show_index( "We could not find an exact match for that member name, some choices will be shown below", $name );
			}
		}
		
		$DB->query("SELECT count(distinct(ip_address)) as cnt FROM ibf_posts WHERE author_id={$member['id']}");
		
		$count = $DB->fetch_row();
		
		$st  = intval($IN['st']);
		$end = 50;
		
		$links = $std->build_pagelinks( array( 'TOTAL_POSS'  => $count['cnt'],
											   'PER_PAGE'    => $end,
											   'CUR_ST_VAL'  => $st,
											   'L_SINGLE'    => "Single Page",
											   'L_MULTI'     => "Multiple Pages",
											   'BASE_URL'    => $SKIN->base_url."&act=mtools&code=showallips&member_id={$member['id']}",
									  )      );
		
		$master = array();
		$ips    = array();
		
		$DB->query("SELECT count(ip_address) as ip, ip_address, pid, topic_id, post_date FROM ibf_posts WHERE author_id={$member['id']} GROUP BY ip_address ORDER BY ip DESC LIMIT $st, $end");
		
		while ( $r = $DB->fetch_row() )
		{
			$master[] = $r;
			$ips[]    = '"'.$r['ip_address'].'"';
		}
		
		$reg = array();
		
		if ( count($ips) > 0 )
		{
			$DB->query("SELECT id, name, ip_address FROM ibf_members WHERE ip_address IN (".implode(",",$ips).") AND id != {$member['id']}");
			
			while ( $i = $DB->fetch_row() )
			{
				$reg[ $i['ip_address'] ][] = $i;
			}
		}
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "IP Address"          , "20%" );
		$SKIN->td_header[] = array( "Times Used"          , "10%" );
		$SKIN->td_header[] = array( "Date Used"           , "25%" );
		$SKIN->td_header[] = array( "Used for other Reg." , "20%" );
		$SKIN->td_header[] = array( "IP Tool"             , "25%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "{$member['name']}'s IP addresses ({$count['cnt']}) matches" );
		
		foreach( $master as $idx => $r )
		{
			$ADMIN->html .= $SKIN->add_td_row( array( $r['ip_address'] ,
													  $r['ip'] ,
													  $std->get_date( $r['post_date'], 'SHORT' ),
													  "<center>". intval( count($reg[ $r['ip_address'] ]) ). "</center>",
													  "<center><a href='{$ADMIN->base_url}&act=mtools&code=learnip&ip={$r['ip_address']}'>Learn about this IP</a></center>"
											 )      );
		}
		
		$ADMIN->html .= $SKIN->add_td_basic( "$links", "center", "catrow2");
									     							     
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
	}
	
	//+---------------------------------------------------------------------------------
	//
	// Default Screen
	//
	//+---------------------------------------------------------------------------------
	
	
	function show_index($msg="", $membername="")
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		
		if ($msg != "")
		{
			$SKIN->td_header[] = array( "&nbsp;"  , "100%" );
			
			$ADMIN->html .= $SKIN->start_table( "Message" );
			
			$ADMIN->html .= $SKIN->add_td_row( array( $msg ) );
			
			$ADMIN->html .= $SKIN->end_table();
		}
		
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'showallips'  ),
												  2 => array( 'act'   , 'mtools'     ),
									     )      );
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Show all IP Addresses a member has posted with" );
		
		if ( $membername == "" )
		{
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enter the member's name</b>" ,
													  $SKIN->form_input( "name", $std->txt_stripslashes($HTTP_POST_VARS['name']) )
											 )      );
		}
		else
		{
			$DB->query("SELECT id, name FROM ibf_members WHERE lower(name) LIKE '{$membername}%'");
			
			if ( ! $DB->get_num_rows() )
			{
				$this->show_index("There are no members with names that start with '$membername'");
			}
			
			$mem_array = array();
			
			while ( $m = $DB->fetch_row() )
			{
				$mem_array[] = array( $m['id'], $m['name'] );
			}
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<b>Choose the member from the selection</b>" ,
													  $SKIN->form_dropdown( "member_id", $mem_array )
											 )      );
		}
		
		$ADMIN->html .= $SKIN->end_form("Get IP Addresses");
									     							     
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , 'learnip'  ),
												  2 => array( 'act'   , 'mtools'     ),
									     )      );
		
		$SKIN->td_header[] = array( "&nbsp;"  , "40%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "IP Multi-Tool" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show me everything you know about this IP...</b>" ,
												   $SKIN->form_input( "ip", $std->txt_stripslashes($HTTP_POST_VARS['ip']) )
										  )      );
										  
		$ADMIN->html .= $SKIN->end_form("Show me!");
									     							     
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
	}
	
	
	
	
	
	
	
}

?>