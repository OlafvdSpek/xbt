<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.3.1 Final
|   ========================================
|   by Matthew Mecham
|   (c) 2001,2002 Invision Power Services
|   http://www.ibforums.com
|   ========================================
|   Web: http://www.ibforums.com
|   Email: phpboards@ibforums.com
|   Licence Info: phpib-licence@ibforums.com
+---------------------------------------------------------------------------
|
|   > Admin: Warning Functions
|   > Module written by Matt Mecham
|   > Date started: 23rd April 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$idx = new ad_warning();

$root_path = "";

class ad_warning {

	var $base_url;

	function ad_warning()
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
		
		$ADMIN->nav[] = array( 'act=warn', 'Warning Set-up' );
		
		$ADMIN->page_detail = "This section will allow you to modify your warning parameters";
		$ADMIN->page_title  = "Member Warning Set-up";
		
		//---------------------------------------

		switch($IN['code'])
		{
			
			//---------------------
			default:
				$this->overview();
				break;
		}
		
	}
	
	
	//---------------------------------------------------------------
	//
	// Overview: show um.. overview.
	//
	//---------------------------------------------------------------
	
	function overview()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $ibforums;
		
		$unit_map = array(
						   'd'  => 'Day(s)' ,
						   'h'  => 'Hour(s)',
						 );
		
		$SKIN->td_header[] = array( "Warn Level"   , "20%" );
		$SKIN->td_header[] = array( "Details"      , "80%" );
		
		$ADMIN->html .= $SKIN->start_table( "Currently active Auto tasks" );
		
		$warns = array();
		
		$DB->query("SELECT * from ibf_warn_settings ORDER BY warn_level");
		
		while ( $r = $DB->fetch_row() )
		{
			$warns[ $r['warn_level'] ][] = $r;
		}
			
		if ( count($warns) > 0 )
		{
			foreach( $warns as $id => $data )
			{
				$tmp = "";
				
				$ban = "";
				$mod = "";
				$nop = "";
				
				if ( $data['warn_ban'] != "" )
				{
					if ( $data['warn_ban'] == 'p' )
					{
						$ban = "Permanent Ban";
					}
					else
					{
						list ($val, $unit) = explode( ',', $data['warn_ban'] );
						
						$ban = "Temporary suspension for ".$val." ".$unit_map[$unit];
					}
				}
				
				if ( $data['warn_modq'] != "" )
				{
					if ( $data['warn_modq'] == 'p' )
					{
						$mod = "Permanent post moderation";
					}
					else
					{
						list ($val, $unit) = explode( ',', $data['warn_modq'] );
						
						$mod = "Require post moderation for ".$val." ".$unit_map[$unit];
					}
				}
				
				if ( $data['warn_nopost'] != "" )
				{
					if ( $data['warn_nopost'] == 'p' )
					{
						$nop = "Permanent post banning";
					}
					else
					{
						list ($val, $unit) = explode( ',', $data['warn_nopost'] );
						
						$nop = "Post ban for ".$val." ".$unit_map[$unit];
					}
				}
				
				$html = "<table width='100%' cellpadding='4'>";
				
				if ( $ban != "" )
				{
					$html .= "<tr>
								<td width='20%'><strong>Ban</strong></td>
								<td width='30%'>$ban</td>
								<td width='25%'>Edit</td>
								<td width='25%'>Remove</td>
							  </tr>";
				}
				
				if ( $mod != "" )
				{
					$html .= "<tr>
								<td width='20%'><strong>Post Moderation</strong></td>
								<td width='30%'>$mod</td>
								<td width='25%'>Edit</td>
								<td width='25%'>Remove</td>
							  </tr>";
				}
				
				if ( $nop != "" )
				{
					$html .= "<tr>
								<td width='20%'><strong>Post Ban</strong></td>
								<td width='30%'>$nop</td>
								<td width='25%'>Edit</td>
								<td width='25%'>Remove</td>
							  </tr>";
				}
				
				$html .= "</table>";
				
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>{$warns['warn_level']}</b>",
														  $html
										 )      );
			}
		}
		
		$ADMIN->html .= $SKIN->add_td_basic( 'Add new auto task', 'center', 'pformstrip' );
										 
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		//+-------------------------------
		
		$ADMIN->output();
	

		
		
	}
	
	
	
	
	
	
	
}

?>