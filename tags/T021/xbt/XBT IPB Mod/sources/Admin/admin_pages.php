<?php

// Simple library that holds all the links for the admin cp

// CAT_ID => array(  PAGE_ID  => (PAGE_NAME, URL ) )

// $PAGES[ $cat_id ][$page_id][0] = Page name
// $PAGES[ $cat_id ][$page_id][1] = Url

 
$PAGES = array(

				/*0 => array (
							 1 => array( 'IPS Latest News'         , 'act=ips&code=news'   ),
							 2 => array( 'Check for updates'      , 'act=ips&code=updates'  ),
							 3 => array( 'Documentation'     , 'act=ips&code=docs'    ),
							 4 => array( 'Get Support'       , 'act=ips&code=support' ),
							 5 => array( 'IPS Hosting'  , 'act=ips&code=host'   ),
							 6 => array( 'Purchase Services'    , 'act=ips&code=purchase'     ),
						   ),*/
						   
				1 => array (
				
							1 => array( 'IP Chat'              , 'act=pin&code=ipchat'  ),
							2 => array( 'IPS Hosting'          , 'act=ips&code=host'    ),
							3 => array( 'IPB Registration'     , 'act=pin&code=reg'     ),
							4 => array( 'IPB Copyright Removal', 'act=pin&code=copy'    ),
							5 => array( 'Subscription Manager'    , 'act=msubs' ),
							6 => array( '&#0124;-- Logs'          , 'act=msubs&code=searchlog', 'modules/subsmanager' ),
							7 => array( '&#0124;-- Currency Set-up' , 'act=msubs&code=currency', 'modules/subsmanager' ),
							8 => array( '&#039;-- Transactions'   , 'act=msubs&code=dosearch', 'modules/subsmanager' ),
							
							
						   ),

				2 => array (
							 1 => array( 'General Configuration', 'act=op&code=url'   ),
							 2 => array( 'Security & Privacy'      , 'act=op&code=secure'  ),
							 3 => array( 'Topics, Posts & Polls', 'act=op&code=post'    ),
							 4 => array( 'User Profiles'       , 'act=op&code=avatars' ),
							 5 => array( 'Date & Time Formats'  , 'act=op&code=dates'   ),
							 6 => array( 'CPU Saving'    , 'act=op&code=cpu'     ),
							 7 => array( 'Cookies'       , 'act=op&code=cookie'  ),
							 8 => array( 'PM Set up'       , 'act=op&code=pm'    ),
							 9 => array( 'Board on/off'    , 'act=op&code=board' ),
							 10 =>array( 'News Set-up'    , 'act=op&code=news' ),
							 11 =>array( 'Calendar/Birthday'    , 'act=op&code=calendar' ),
							 12 =>array( 'COPPA Set-up'       , 'act=op&code=coppa' ),
							 14 =>array( 'Email Set-up'       , 'act=op&code=email' ),
							 15 =>array( 'Server Environment' , 'act=op&code=phpinfo' ),
							 16 =>array( 'Board Guidelines'   , 'act=op&code=glines' ),
							 17 =>array( 'Full Text Searching', 'act=op&code=fulltext'),
							 18 =>array( 'Search Engine Spiders', 'act=op&code=spider' ),
							 19 =>array( 'Warning Set-up'       , 'act=op&code=warn' ),
							 20 =>array( 'IPDynamic Lite Set-up'    , 'act=csite',     'sources/dynamiclite' ),
							 
							 
						   ),

				3 => array (
							 1 => array( 'New Category'        , 'act=cat&code=new'        ),
							 2 => array( 'New Forum'           , 'act=forum&code=newsp'    ),
							 3 => array( 'Manage Forums'       , 'act=cat&code=edit'       ),
							 4 => array( 'Permission Masks'    , 'act=group&code=permsplash'),
							 5 => array( 'Re-Order Categories' , 'act=cat&code=reorder'    ),
							 6 => array( 'Re-Order Forums'     , 'act=forum&code=reorder'  ),
							 7 => array( 'Moderators'          , 'act=mod'                 ),
							 //8 => array( 'Topic Multi-Moderation', 'act=multimod'          ),
						   ),
						   
				
				4 => array (
							 1 => array( 'Moderator\'s CP'       , 'act=modcp' ,   "" , 1     ),
							 2 => array( 'Topic Multi-Moderation', 'act=multimod'          ),
						   ),
						   
						   
				5 => array (
							1 => array ( 'Pre-Register'        , 'act=mem&code=add'  ),
							2 => array ( 'Find/Edit/Suspend User'      , 'act=mem&code=edit' ),
							3 => array ( 'Delete User(s)'      , 'act=mem&code=del'  ),
							4 => array ( 'List Suspended Users', 'act=mem&code=advancedsearch&showsusp=1' ),
							5 => array ( 'Ban Settings'        , 'act=mem&code=ban'  ),
							6 => array ( 'User Title/Ranks'    , 'act=mem&code=title'),
							7 => array ( 'Manage User Groups'  , 'act=group'         ),
							8 => array ( 'Manage Validating', 'act=mem&code=mod'  ),
							9 => array ( 'Custom Profile Fields', 'act=field'       ),
							10 => array ( 'Bulk Email Members'   , 'act=mem&code=mail' ),
							11 => array ( 'Member Tools'         , 'act=mtools'  ),
							
						   ),
						   
				6 => array (
							1 => array( 'Manage Word Filters', 'act=op&code=bw'    ),
							2 => array( 'Manage Emoticons', 'act=op&code=emo'   ),
							3 => array( 'Manage Help Files', 'act=help'         ),
							4 => array( 'Recount Statistics', 'act=op&code=count'    ),
							
						   ),
						   
				7 => array (
							1 => array( '<b>Manage Skin Sets</b>' , 'act=sets'        ),
							2 => array( '&#0124;-- Board Wrappers'   , 'act=wrap'        ),
							3 => array( '&#0124;-- HTML Templates'   , 'act=templ'       ),
							4 => array( '&#0124;-- Style Sheets'    , 'act=style'       ),
							5 => array( '&#039;-- Macros'           , 'act=image'       ),
							6 => array( 'Import Skin files'       , 'act=import'      ),
							7 => array( 'Skin Version Control'    , 'act=skinfix'      ),
							
						   ),
						   
				8 => array (
							1 => array( 'Manage Languages' , 'act=lang'             ),
							2 => array( 'Import a Language', 'act=lang&code=import' ),
						   ),
						   
				9 => array (
							1 => array( 'Registration Stats' , 'act=stats&code=reg'   ),
							2 => array( 'New Topic Stats'    , 'act=stats&code=topic' ),
							3 => array( 'Post Stats'         , 'act=stats&code=post'  ),
							4 => array( 'Personal Message'    , 'act=stats&code=msg'   ),
							5 => array( 'Topic Views'        , 'act=stats&code=views' ),
						   ),
						   
				10 => array (
							1 => array( 'mySQL Toolbox'   , 'act=mysql'           ),
							2 => array( 'mySQL Back Up'   , 'act=mysql&code=backup'    ),
							3 => array( 'SQL Runtime Info', 'act=mysql&code=runtime'   ),
							4 => array( 'SQL System Vars' , 'act=mysql&code=system'    ),
							5 => array( 'SQL Processes'   , 'act=mysql&code=processes' ),
						   ),
				
				11 => array(
							1 => array( 'View Moderator Logs', 'act=modlog'    ),
							2 => array( 'View Admin Logs'    , 'act=adminlog'  ),
							3 => array( 'View Email Logs'    , 'act=emaillog'  ),
							4 => array( 'View Bot Logs'      , 'act=spiderlog' ),
							5 => array( 'View Warn Logs'     , 'act=warnlog'   ),
						   ),
			   );
			   
			   
$CATS = array (   
				  //0 => "IPS Services",
				  1 => "IPB Enhancements",
				  2 => "System Settings",
			      3 => 'Forum Control',
			      4 => 'Forum Moderation',
				  5 => 'Users and Groups',
				  6 => 'Administration',
				  7 => 'Skins & Templates',
				  8 => 'Languages',
				  9 => 'Statistic Center',
				  10 => 'SQL Management',
				  11 => 'Board Logs',
			  );
			  
$DESC = array (
				 // 0 => "Get IPS latest news, documentation, request support, purchase extra services and more...",
				  1 => "Set up and manage your IPB plug in services",
				  2 => "Edit forum settings such as cookie paths, security features, posting abilities, etc",
				  3 => "Create, edit, remove and re-order categories, forums and moderators",
				  4 => "Access the moderators CP and manage topic multi-moderation",
				  5 => "Edit, register, remove and ban members. Set up member titles and ranks. Manage User Groups and moderated registrations",
				  6 => "Manage Help Files, Bad Word Filters and Emoticons",
				  7 => "Manage templates, skins, colours and images.",
				  8 => "Manage language sets",
				  9 => "Get registration and posting statistics",
				  10 => "Manage your SQL database; repair, optimize and export data",
				  11 => "View admin, moderator and email logs (Root admin only)",
			  );
			  
			  
?>