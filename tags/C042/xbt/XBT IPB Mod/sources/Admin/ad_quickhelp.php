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
|   > Admin Quick Help System
|   > Module written by Matt Mecham
|   > Date started: 1st march 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new quick_help();


class quick_help {

	var $help_text = array();
	
	function init_help_array()
	{
	
		return array(	'mg_dohtml' => array( 'title' => "[doHTML] Tag Usage",
											  'body'  => "The [doHTML] tag will allow all members of the group to post pure HTML in forums that have the ability to parse the [doHTML] tag. You can turn this on and off
											  			  for each forum via the edit forum settings function.<br />
											  			  <br /><b>Warning!</b><br />
											  			  Allowing a member to post HTML is a very dangerous thing, you should not enable this functionality on any group that will use it maliciously. Although IPB tries
											  			  to filter some of the more harmful content, it is NOT foolproof and this tag CAN be used to steal session cookies, redirect members and destroy the topic view layout.
											  			  <br />Invision Power Board and Invision Power Services will not be held responsible for any misfortune that occurs from the usage of this tag.
											  			  <br /><br /><b>Use it wisely!</b>
														 ",
											 ),
											 
						'mod_mmod' =>  array( 'title' => "Moderations and Topic Multi-Moderation",
											  'body'  => "If you allow your moderators access to the forums' multi-moderation then you must be aware that they have full use.
											  			  <br />For example, if one of your multi-moderation actions allowed the topic to be moved and you do not allow move permissions the
											  			  moderator will still be able to use the topic multi-moderation and move the topic.
														 ",
											 ),
											 
						'set_spider' => array( 'title' => "What are Search Engine Spiders?",
											  'body'  => "Search engines such as Google 'spider' the web by using special programs to find and add links to the search engine
											  			  database.<br />Invision Power can take advantage of separating the bots from the real users and you can then
											  			  ensure that they are getting adequate information to help with search engine ranking.
											  			  <br />
											  			  <br />
											  			  <b>Warning!</b>
											  			  <br />
											  			  Invision Power Board recognises the search engine spiders and crawlers by their user_agent. Please keep in mind
											  			  that it's not impossible to fake this and a malicious user could pose as a harmless search engine spider.
											  			  <br />This is not a problem as by default the spider will have guest permissions, but it's something that
											  			  you should keep in mind when allowing permissions.
											  			  <br /><br />Also keep in mind that most search engines do NOT search dynamically generated website for fear
											  			  of crashing the server with the constant reading of topics.
														 ",
											 ),
		
		
		
						'mg_upload' => array( 'title' => "Upload Permissions",
											  'body'  => "If you wish to allow this group to attach files (upload) when posting, you will have to ensure you have completed the following:
											  			  <ul>
											  			  <li>You have entered a reasonable numerical figure in the groups 'Max upload file size' field.
											  			  <li>You have edited the forum permissions for this group and checked the 'Upload' checkbox.
											  			  </ul>
											  			  This allows you to control in which forums this group can upload to.
														  <br><br><b>Warning!</b><br>If, when posting, no post is made and you are returned back to the board index, disable this groups upload by entering 0 into this field. This will turn off the mutli-part form the uploads use.
														 ",
											 ),
		
		
						'mg_promote' => array( 'title' => "Group Promotion",
											   'body'  => "If enabled (by choosing a member group to promote your members to and by entering a number of posts to achieve this)
											    		   when your members meet or exceed the number of posts set they will be 'promoted' to the specified group.
											    		   <br><br>
											    		   Many administrators use this feature to set up a 'Senior Members' group with more functionality (such as a longer edit time, larger post uploads) and even
											    		   allow access to otherwise hidden forums - when your members have made enough posts, they are promoted to this group allowing you to intice more posting and
											    		   allow for a more restrictive set of permissions for newcomers.
											    		   <br><br><b>Warning!</b><br>Use this feature carefully and always check the information before proceeding.<br>It is possible to advance to an Admin group - you have been warned.
											   			  ",
											 ),
						's_reg_antispam' => array ( 'title' => "Registration AntiSpam",
													'body'  => "To prevent robots from registering (such as a malicious denial of service attack registering thousands of new accounts and forcing thousands of emails to be sent from your server)
													            you can enable this option.
													            <br><br>When enabled, a random 6 digit numerical string is generated and shown in a graphical format (to prevent advanced bots from reading the source page). The user must enter
													            this string exactly when registering or the account will not be created.",
											 ),
											 
						'm_bulkemail'    => array ( 'title' => "Bulk Emailing",
												    'body' => "<b>Overview</b><br>Bulk emailing allows you to target a specific section of your community or email all your registered members.
												    <br><br><b>Settings</b><br>You can choose which user groups will receive the email and elect to override the user set 'Allow Admin Emails' function. It is NOT recommended that you do override this
												    however.<hr>
												    <b>Allowed Tags</b><br>Although the email system sends the mail via BCC to preserve system resources, you can add in dynamic content with the following tags.
												    <br>{board_name} will return the name of your board
													<br>{reg_total} will return the total number of registered members
													<br>{total_posts} will return the total number of posts
													<br>{busy_count} will return the most number of online users
													<br>{busy_time} will return the date of the most online users
													<br>{board_url} will return the URL to the board
													<br><br>As the email is sent via BCC, it is not possible to include the members username, password or other user profile data.",
												),
					);
	
	}

	function quick_help() {
		global $DB, $IN, $INFO, $ADMIN, $SKIN, $std;
		
		$id = $IN['id'];
		
		$this->help_text = $this->init_help_array();
		
		if ($this->help_text[$id]['title'] == "")
		{
			$ADMIN->error("No help information is available for this function at present");
		}
		
		print "<html>
				<head>
				 <title>Quick Help</title>
				</head>
				<body leftmargin='0' topmargin='0' bgcolor='#F5F9FD'>
				 <table width='95%' align='center' border='0' cellpadding='6'>
				 <tr>
				  <td style='font-family:verdana, arial, tahoma;color:#4C77B6;font-size:16px;letter-spacing:-1px;font-weight:bold'>{$this->help_text[$id]['title']}</td>
				 </tr>
				 <tr>
				  <td style='font-family:verdana, arial, tahoma;color:black;font-size:9pt'>{$this->help_text[$id]['body']}</td>
				 </tr>
				 </table>
				</body>
				</html>";
		
		
		exit();
		
	}
	
	
	
}


?>