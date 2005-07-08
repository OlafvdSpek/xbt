<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.2 (Click Site)
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
|   > Admin Setting
|   > Module written by Matt Mecham
|   > Date started: 1st July 03
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new ad_settings();


class ad_settings {

	var $base_url;

	function ad_settings() {
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}

		switch($IN['code'])
		{
			case 'settings':
				$this->settings();
				break;
			case 'dosettings':
				$this->save_config( array( 'csite_on','csite_article_forum','csite_article_max','csite_article_recent_on','csite_article_recent_max','csite_article_chars','csite_discuss_max','csite_discuss_on',
	 									   'csite_discuss_len', 'csite_article_len', 'csite_pm_show', 'csite_stats_show', 'csite_search_show', 'csite_poll_show', 'csite_poll_url', 'csite_online_show','csite_skinchange_show',
	 									   'csite_nav_show', 'csite_title', 'csite_article_date','csite_fav_show' ) );
				break;
				
			
			default:
				$this->settings();
				break;
		}
		
	}
	
	//-------------------------------------------------------------
	// SETTINGS: Do Settings for the clickywickydickytavi
	//-------------------------------------------------------------- 
	 
	
	function settings()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		$this->common_header('dosettings', 'IPDynamic Lite Set-Up', 'You may adjust the settings from this control panel');
		
		// Get links
		
		$def_nav = "{board_url} [Forums]\n{board_url}act=Search&amp;CODE=getactive [Today's Active Topics]\n{board_url}act=Stats [Today's Top 10 Posters]\n{board_url}act=Stats&amp;CODE=leaders [Contact Staff]";
		
		$DB->query("SELECT * FROM ibf_cache_store WHERE cs_key IN ('csite_nav_contents', 'csite_fav_contents')");
		
		$links = array( 'csite_nav_contents' => $def_nav, 'csite_fav_contents' => "" );
		
		while ( $row = $DB->fetch_row() )
		{
			$links[ $row['cs_key'] ] = $row['cs_value'];
		}
		
		// Save default informidificationally?
		
		if ( $INFO['csite_configured'] != 1 )
		{
			$DB->query("REPLACE INTO ibf_cache_store SET cs_key='csite_nav_contents', cs_value=\"$def_nav\"");
			$DB->query("REPLACE INTO ibf_cache_store SET cs_key='csite_fav_contents', cs_value=''");
		}
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>ENABLE IPDynamic Lite?</b>" ,
										  $SKIN->form_yes_no( "csite_on", $INFO['csite_on'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Page Title?</b><br />This will appear inbetween the &lt;title&gt; elements on the page" ,
										  $SKIN->form_input( "csite_title", str_replace( "'", "&#39;", $INFO['csite_title'] ) )
								 )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Forums to export articles from...</b><br>Separate forum ids with a comma for more than one." ,
										  $SKIN->form_input( "csite_article_forum", $INFO['csite_article_forum'] )
								 )      );
 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Number of Articles to display in the main section</b>" ,
										  $SKIN->form_simple_input( "csite_article_max", $INFO['csite_article_max'] ? $INFO['csite_article_max'] : 15)
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enable Recent Articles?</b><br />Allows articles not shown in the main section to show in list form.",
										          $SKIN->form_yes_no( "csite_article_recent_on", $INFO['csite_article_recent_on'] )
										          ."<br />Max. no recent articles to show: ".$SKIN->form_simple_input( "csite_article_recent_max", $INFO['csite_article_recent_max'] ? $INFO['csite_article_recent_max'] : 5)
								 				  ."<br />Max. length of topic titles: ".$SKIN->form_simple_input( "csite_article_len", $INFO['csite_article_len'] ? $INFO['csite_article_len'] : 30)
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Date format for articles?</b><br><a href='http://www.php.net/date'>Same as PHP's date function." ,
										  $SKIN->form_input( "csite_article_date", $INFO['csite_article_date'] ? $INFO['csite_article_date'] : 'm-j-y H:i' )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Max. no of characters to show per article</b><br>Leave blank or enter 0 to disable.<br />If cut, articles may have bbcode removed." ,
										  $SKIN->form_input( "csite_article_chars", $INFO['csite_article_chars'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enable Recent Discussions?</b><br />Allows recently replied or posted topics to be listed",
										          $SKIN->form_yes_no( "csite_discuss_on", $INFO['csite_discuss_on'] )
										          ."<br />Max. no recent discussions to show: ".$SKIN->form_simple_input( "csite_discuss_max", $INFO['csite_discuss_max'] ? $INFO['csite_discuss_max'] : 10)
										          ."<br />Max. length of topic titles: ".$SKIN->form_simple_input( "csite_discuss_len", $INFO['csite_discuss_len'] ? $INFO['csite_discuss_len'] : 30)
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show User / Guest Info box?</b>" ,
										  $SKIN->form_yes_no( "csite_pm_show", $INFO['csite_pm_show'] )
								 )      );
								 
		/*$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show forum stats?</b><br />No. posts, topics, members" ,
										  $SKIN->form_yes_no( "csite_stats_show", $INFO['csite_stats_show'] )
								 )      );*/
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show online users?</b><br />" ,
										  $SKIN->form_yes_no( "csite_online_show", $INFO['csite_online_show'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show search box?</b>" ,
										  $SKIN->form_yes_no( "csite_search_show", $INFO['csite_search_show'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Enable skin selection choice dropdown?</b><br />So that visitors can change skins" ,
										  $SKIN->form_yes_no( "csite_skinchange_show", $INFO['csite_skinchange_show'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show Poll?</b>" ,
										  $SKIN->form_yes_no( "csite_poll_show", $INFO['csite_poll_show'] )
										   ."<br />Enter URL to poll topic for inclusion: ".$SKIN->form_input( "csite_poll_url", $INFO['csite_poll_url'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show Site Navigation Menu?</b>" ,
										  $SKIN->form_yes_no( "csite_nav_show", $INFO['csite_nav_show'] )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<span style='vertical-align:top'><b>Site Navigation Menu Links</b><br>One per line in this format<br>http://www.apple.com [Apple's Website]<br><br>{board_url} will convert into your board</span>" ,
										  $SKIN->form_textarea( "csite_nav_contents", preg_replace( "/&(middot|quot|copy|amp)/", "&amp;\\1", $links['csite_nav_contents'] ), 70, 20 )
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show Affiliates / Favoured Sites box?</b>" ,
										  $SKIN->form_yes_no( "csite_fav_show", $INFO['csite_fav_show'] ) 
								 )      );
								 
		$ADMIN->html .= $SKIN->add_td_row( array( "<span style='vertical-align:top'><b>Show Affiliates / Favoured Sites box content</b><br>Raw HTML enabled</span>" ,
										  $SKIN->form_textarea( "csite_fav_contents", preg_replace( "/&(middot|quot|copy|amp)/", "&amp;\\1", $links['csite_fav_contents'] ), 70, 20 )
								 )      );
								 
		$this->common_footer();
	
	
	}
	
	
	
	function save_config( $new )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		$master = array();
		
		if ( is_array($new) )
		{
			if ( count($new) > 0 )
			{
				foreach( $new as $field )
				{
					$master[ $field ] = $std->txt_stripslashes($HTTP_POST_VARS[ $field ]);
				}
				
				$master['csite_title']        = str_replace( "'", "&#39;", $master['csite_title'] );
				$master['csite_article_date'] = str_replace( "'", "&#39;", $master['csite_article_date'] );
				
				$master['csite_configured'] = 1;
				
				$ADMIN->rebuild_config($master);
			}
		}
		
		$nav_contents = preg_replace( "/&amp;(middot|quot|copy|\#|amp)/", "&\\1", $std->txt_stripslashes($HTTP_POST_VARS['csite_nav_contents']) );
		$fav_contents = preg_replace( "/&amp;(middot|quot|copy|\#|amp)/", "&\\1", $std->txt_stripslashes($HTTP_POST_VARS['csite_fav_contents']) );
		
		$DB->query("UPDATE ibf_cache_store SET cs_value='".str_replace( "'", '&#39;', $nav_contents )."' WHERE cs_key='csite_nav_contents'");
		$DB->query("UPDATE ibf_cache_store SET cs_value='".str_replace( "'", '&#39;', $fav_contents )."' WHERE cs_key='csite_fav_contents'");
		
		$ADMIN->save_log("IPDynamic Lite Configurations updated, Back Up Written");
		
		$ADMIN->done_screen("IPDynamic Lite Configurations updated", "IPDynamic Lite Settings", "act=csite" );
		
		
		
	}
	//-------------------------------------------------------------
	//
	// Common header: Saves writing the same stuff out over and over
	//
	//--------------------------------------------------------------
	
	function common_header( $formcode = "", $section = "", $extra = "" )
	{
	
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$extra = $extra ? $extra."<br>" : $extra;
		
		$ADMIN->page_detail = $extra . "Please check the data you are entering before submitting the changes";
		$ADMIN->page_title  = "Board Settings ($section)";
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $formcode ),
												  2 => array( 'act'   , 'csite'      ),
									     )      );
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "{none}"  , "40%" );
		$SKIN->td_header[] = array( "{none}"  , "60%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Settings" );
		
	}

	//-------------------------------------------------------------
	//
	// Common footer: Saves writing the same stuff out over and over
	//
	//--------------------------------------------------------------
	
	function common_footer( $button="Submit Changes" )
	{
	
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;

		$ADMIN->html .= $SKIN->end_form($button);
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
		
	}




}





?>