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
|   > Post core module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|   > Module Version 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Post;

class Post {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $parser    = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_groups = array();
    var $mem_titles = array();
    var $obj        = array();
    var $email      = "";
    var $can_upload = 0;
    var $md5_check  = "";
    var $module     = "";

    /***********************************************************************************/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/***********************************************************************************/

    function Post()
    {
        global $ibforums, $DB, $std, $print, $skin_universal;

        require ROOT_PATH."sources/lib/post_parser.php";

        $this->parser = new post_parser(1);

        require ROOT_PATH."sources/lib/emailer.php";

		$this->email = new emailer();

        //--------------------------------------
		// Compile the language file
		//--------------------------------------

        $ibforums->lang = $std->load_words($ibforums->lang, 'lang_post', $ibforums->lang_id);

        $this->html     = $std->load_template('skin_post');

        //--------------------------------------------
    	// Get the sync module
		//--------------------------------------------

		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";

			$this->modules = new ipb_member_sync();
		}

        //--------------------------------------
        // Check the input
        //--------------------------------------

        $this->md5_check = $std->return_md5_check();

        if ($ibforums->input['t'])
        {
        	$ibforums->input['t'] = intval($ibforums->input['t']);
        	if (! $ibforums->input['t'] )
        	{
        		$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        	}
        }

        if ($ibforums->input['p'])
        {
        	$ibforums->input['p'] = intval($ibforums->input['p']);
        	if (! $ibforums->input['p'] )
        	{
        		$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        	}
        }

        $ibforums->input['f'] = intval($ibforums->input['f']);
        if (! $ibforums->input['f'] )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }

        $ibforums->input['st'] = $ibforums->input['st'] ?intval($ibforums->input['st']) : 0;

        // Did the user press the "preview" button?

        $this->obj['preview_post'] = $ibforums->input['preview'];


        //--------------------------------------
        // Get the forum info based on the forum ID, get the category name, ID, and get the topic details
        //--------------------------------------

        $DB->query("SELECT f.*, c.id as cat_id, c.name as cat_name from ibf_forums f, ibf_categories c WHERE f.id=".$ibforums->input[f]." and c.id=f.category");

        $this->forum = $DB->fetch_row();

        if ( $std->check_perms($this->forum['read_perms']) != TRUE )
        {
			$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }

        // Can we upload stuff?

        if ( $std->check_perms($this->forum['upload_perms']) == TRUE )
        {
        	$this->can_upload = 1;
        }

        // Is this forum switched off?

        if ( ! $this->forum['status'] )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'forum_read_only') );
        }

        //--------------------------------------
        // Is this a password protected forum?
        //--------------------------------------

        $pass = 0;

		if ($this->forum['password'] != "")
		{
			if ( ! $c_pass = $std->my_getcookie('iBForum'.$this->forum['id']) )
			{
				$pass = 0;
			}

			if ( $c_pass == $this->forum['password'] )
			{
				$pass = 1;
			}
			else
			{
			    $pass = 0;
			}
		}
		else
		{
			$pass = 1;
		}

		if ($pass == 0)
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
		}

		//--------------------------------------

        if ($this->forum['parent_id'] > 0)
        {

        	$DB->query("SELECT f.id as forum_id, f.name as forum_name, c.id, c.name FROM ibf_forums f, ibf_categories c WHERE f.id='".$this->forum['parent_id']."' AND c.id=f.category");

        	$row = $DB->fetch_row();

        	$this->forum['cat_id']   = $row['id'];
        	$this->forum['cat_name'] = $row['name'];

        }


        //--------------------------------------
        // Error out if we can not find the forum
        //--------------------------------------

        if (!$this->forum['id'])
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }

        $this->base_url = $ibforums->base_url;

        //--------------------------------------
        // Is this forum moderated?
        //--------------------------------------

        $this->obj['moderate'] = intval($this->forum['preview_posts']);

        // Can we bypass it?

        if ($ibforums->member['g_avoid_q'])
        {
        	$this->obj['moderate'] = 0;
        }

		//--------------------------------------
        // Does this member have mod_posts enabled?
		//--------------------------------------

        if ( $ibforums->member['mod_posts'] )
		{
			if ( $ibforums->member['mod_posts'] == 1 )
			{
				$this->obj['moderate'] = 1;
			}
			else
			{
				$mod_arr = $std->hdl_ban_line( $ibforums->member['mod_posts'] );

				if ( time() >= $mod_arr['date_end'] )
				{
					// Update this member's profile

					$DB->query("UPDATE ibf_members SET mod_posts=0 WHERE id=".intval($ibforums->member['id']) );

					$this->obj['moderate'] = intval($this->forum['preview_posts']);
				}
				else
				{
					$this->obj['moderate'] = 1;
				}
			}
		}

        //--------------------------------------
        // Are we allowed to post at all?
        //--------------------------------------

        if ($ibforums->member['id'])
        {
        	if ( $ibforums->member['restrict_post'] )
        	{
        		if ( $ibforums->member['restrict_post'] == 1 )
        		{
        			$std->Error( array( LEVEL => 1, MSG => 'posting_off') );
        		}

        		$post_arr = $std->hdl_ban_line( $ibforums->member['restrict_post'] );

        		if ( time() >= $post_arr['date_end'] )
        		{
        			// Update this member's profile

        			$DB->query("UPDATE ibf_members SET restrict_post=0 WHERE id=".intval($ibforums->member['id']) );
        		}
        		else
        		{
        			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'posting_off_susp', 'EXTRA' => $std->get_date($post_arr['date_end'], 'LONG') ) );
        		}

        	}

        	// Flood check..

        	if ( $ibforums->input['CODE'] != "08" and $ibforums->input['CODE'] != "09" and $ibforums->input['CODE'] != "14" and $ibforums->input['CODE'] != "15" )
        	{
				if ( $ibforums->vars['flood_control'] > 0 )
				{
					if ($ibforums->member['g_avoid_flood'] != 1)
					{
						if ( time() - $ibforums->member['last_post'] < $ibforums->vars['flood_control'] )
						{
							$std->Error( array( 'LEVEL' => 1, 'MSG' => 'flood_control' , 'EXTRA' => $ibforums->vars['flood_control'] ) );
						}
					}
				}

			}

        }
        else if ( $ibforums->is_bot == 1 )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'posting_off') );
        }


        if ($ibforums->member['id'] != 0 and $ibforums->member['g_is_supmod'] == 0)
        {
        	$DB->query("SELECT * from ibf_moderators WHERE forum_id='".$this->forum['id']."' AND (member_id='".$ibforums->member['id']."' OR (is_group=1 AND group_id='".$ibforums->member['mgroup']."'))");
        	$this->moderator = $DB->fetch_row();
        }

        //--------------------------------------
        // Convert the code ID's into something
        // use mere mortals can understand....
        //--------------------------------------

        $this->obj['action_codes'] = array ( '00'  => array( '0'  , 'new_post'     ),
        									 '01'  => array( '1'  , 'new_post'     ),
        									 '02'  => array( '0'  , 'reply_post'   ),
        									 '03'  => array( '1'  , 'reply_post'   ),
        									 '06'  => array( '0'  , 'q_reply_post' ),
        									 '07'  => array( '1'  , 'q_reply_post' ),
        									 '08'  => array( '0'  , 'edit_post'    ),
        									 '09'  => array( '1'  , 'edit_post'    ),
        									 '10'  => array( '0'  , 'poll'         ),
        									 '11'  => array( '1'  , 'poll'         ),
        									 '14'  => array( '0'  , 'poll_after'   ),
        									 '15'  => array( '1'  , 'poll_after'   ),
        								   );

        // Make sure our input CODE element is legal.

        if (! isset($this->obj['action_codes'][ $ibforums->input['CODE'] ]) )
        {
        	$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }

        // Require and run our associated library file for this action.
        // this imports an extended class for this Post class.

        require "./sources/lib/post_" . $this->obj['action_codes'][ $ibforums->input['CODE'] ][1] . ".php";

        $post_functions = new post_functions(&$this);

        // If the first CODE array bit is set to "0" - show the relevant form.
        // If it's set to "1" process the input.

        // We pass a reference to this classes object so we can manipulate this classes
        // data from our sub class.

        if ($this->obj['action_codes'][ $ibforums->input['CODE'] ][0])
        {
        	// Make sure we have a valid auth key

        	if ( $ibforums->input['auth_key'] != $this->md5_check )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
			}

        	// Make sure we have a "Guest" Name..

        	if (!$ibforums->member['id'])
        	{

        		$ibforums->input['UserName'] = trim($ibforums->input['UserName']);
        		$ibforums->input['UserName'] = str_replace( "<br>", "", $ibforums->input['UserName']);
        		$ibforums->input['UserName'] = $ibforums->input['UserName'] ? $ibforums->input['UserName'] : 'Guest';

        		if ($ibforums->input['UserName'] != 'Guest')
        		{
        			$DB->query("SELECT id FROM ibf_members WHERE LOWER(name)='".trim(strtolower($ibforums->input['UserName']))."'");

        			if ( $DB->get_num_rows() )
        			{
        				$ibforums->input['UserName'] = $ibforums->vars['guest_name_pre'].$ibforums->input['UserName'].$ibforums->vars['guest_name_suf'];
        			}
        		}

        	}

        	//-------------------------------------------------------------------------
        	// Stop the user hitting the submit button in the hope that multiple topics
        	// or replies will be added. Or if the user accidently hits the button
        	// twice.
        	//-------------------------------------------------------------------------

        	if ( $this->obj['preview_post'] == "" )
        	{

				if ( preg_match( "/Post,.*,(01|03|07|11)$/", $ibforums->location ) )
				{
					if ( time() - $ibforums->lastclick < 2 )
					{
						if ( $ibforums->input['CODE'] == '01' or $ibforums->input['CODE'] == '11' )
						{
							// Redirect to the newest topic in the forum


							$DB->query("SELECT tid from ibf_topics WHERE forum_id='".$this->forum['id']."' AND approved=1 "
									  ."ORDER BY last_post DESC LIMIT 0,1");

							$topic = $DB->fetch_row();

							$std->boink_it($ibforums->base_url."act=ST&f=".$this->forum['id']."&t=".$topic['tid']);
							exit();
						}
						else
						{
							// It's a reply, so simply show the topic...

							$std->boink_it($ibforums->base_url."act=ST&f=".$this->forum['id']."&t=".$ibforums->input['t']."&view=getlastpost");
							exit();
						}
					}
				}

        	}

        	//----------------------------------

        	$post_functions->process(&$this);
        }
        else
        {
        	$post_functions->show_form(&$this);
        }



	}

	/*****************************************************/
	// Notify new topic mod Q
	// ----------------------
	/*****************************************************/

	function notify_new_topic_approval($tid, $title, $author)
	{
		global $ibforums, $DB, $std;

		if ( $this->forum['notify_modq_emails'] == "" )
		{
			return;
		}

		$this->email->get_template("new_topic_queue_notify");

		$this->email->build_message( array(
											'TOPIC'  => $title,
											'FORUM'  => $this->forum['name'],
											'POSTER' => $author,
											'DATE'   => $std->get_date( time(), 'SHORT' ),
											'LINK'   => $ibforums->vars['board_url'].'/index.'.$ibforums->vars['php_ext'].'?act=modcp&fact=mod_topic&CODE=fchoice&f='.$this->forum['id'],
										  )
									);

		foreach( explode( ",", $this->forum['notify_modq_emails'] ) as $email )
		{
			$this->email->to = trim($email);
			$this->email->send_mail();
		}

	}



	/*****************************************************/
	// topic tracker
	// ------------------
	// Checks and sends out the emails as needed.
	/*****************************************************/

	function topic_tracker($tid="", $post="", $poster="", $last_post="" )
	{
		global $ibforums, $DB, $std;

		if ($tid == "")
		{
			return TRUE;
		}

		// Get the email addy's, topic ids and email_full stuff - oh yeah.
		// We only return rows that have a member last_activity of greater than the post itself

		$DB->query("SELECT tr.trid, tr.topic_id, m.name, m.email, m.id, m.email_full, m.language, m.last_activity, t.title, t.forum_id
				    FROM ibf_tracker tr, ibf_topics t,ibf_members m
				    WHERE tr.topic_id='$tid'
				    AND tr.member_id=m.id
				    AND m.id <> '{$ibforums->member['id']}'
				    AND t.tid=tr.topic_id
				    AND m.last_activity > '$last_post'");

		if ( $DB->get_num_rows() )
		{
			$trids = array();

			while ( $r = $DB->fetch_row() )
			{

				$r['language'] = $r['language'] ? $r['language'] : 'en';

				if ($r['email_full'] == 1)
				{
					$this->email->get_template("subs_with_post", $r['language']);

					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['name'],
														'POSTER'          => $poster,
														'POST'            => $post,
													  )
												);

					$this->email->subject = $ibforums->lang['tt_subject'];
					$this->email->to      = $r['email'];
					$this->email->send_mail();

				}
				else
				{

					$this->email->get_template("subs_no_post", $r['language']);

					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['name'],
														'POSTER'          => $poster,
													  )
												);

					$this->email->subject = $ibforums->lang['tt_subject'];
					$this->email->to      = $r['email'];

					$this->email->send_mail();

				}

				$trids[] = $r['trid'];
			}
		}

		//return TRUE;
	}



	/*****************************************************/
	// Forum tracker
	// ------------------
	// Checks and sends out the new topic notification if
	// needed
	/*****************************************************/

	function forum_tracker($fid="", $this_tid="", $title="", $forum_name="")
	{
		global $ibforums, $DB, $std;

		if ($this_tid == "")
		{
			return TRUE;
		}

		if ($fid == "")
		{
			return TRUE;
		}

		// Work out the time stamp needed to "guess" if the user is still active on the board
		// We will base this guess on a period of non activity of time_now - 30 minutes.

		$time_limit = time() - (30*60);

		// Get the email addy's, topic ids and email_full stuff - oh yeah.
		// We only return rows that have a member last_activity of greater than the post itself

		$DB->query("SELECT tr.frid, m.name, m.email, m.id, m.language, m.last_activity, m.org_perm_id, g.g_perm_id
				    FROM ibf_forum_tracker tr,ibf_members m, ibf_groups g
				    WHERE tr.forum_id='$fid'
				    AND tr.member_id=m.id
				    AND m.mgroup=g.g_id
				    AND m.id <> '{$ibforums->member['id']}'
				    AND m.last_activity < '$time_limit'");

		if (  $DB->get_num_rows() )
		{
			while ( $r = $DB->fetch_row() )
			{

				$perm_id = ( $r['org_perm_id'] ) ? $r['org_perm_id'] : $r['g_perm_id'];

				if ($this->forum['read_perms'] != '*')
				{
					if ( ! preg_match("/(^|,)".str_replace( ",", '|', $perm_id )."(,|$)/", $this->forum['read_perms'] ) )
        			{
        				continue;
       				}
				}

				$r['language'] = $r['language'] ? $r['language'] : 'en';

				$this->email->get_template("subs_new_topic", $r['language']);

				$this->email->build_message( array(
													'TOPIC_ID'        => $this_tid,
													'FORUM_ID'        => $fid,
													'TITLE'           => $title,
													'NAME'            => $r['name'],
													'POSTER'          => $ibforums->member['name'],
													'FORUM'           => $forum_name,
												  )
											);

				$this->email->subject = $ibforums->lang['ft_subject'];
				$this->email->to      = $r['email'];

				$this->email->send_mail();
			}
		}
		return TRUE;
	}


	/*****************************************************/
	// compile post
	// ------------------
	// Compiles all the incoming information into an array
	// which is returned to the accessor
	/*****************************************************/

	function compile_post()
	{
		global $ibforums, $std, $REQUEST_METHOD, $HTTP_POST_VARS;

		$ibforums->vars['max_post_length'] = $ibforums->vars['max_post_length'] ? $ibforums->vars['max_post_length'] : 2140000;

		//----------------------------------------------------------------
		// Sort out some of the form data, check for posting length, etc.
		// THIS MUST BE CALLED BEFORE CHECKING ATTACHMENTS
		//----------------------------------------------------------------

		$ibforums->input['enablesig']   = $ibforums->input['enablesig']   == 'yes' ? 1 : 0;
		$ibforums->input['enableemo']   = $ibforums->input['enableemo']   == 'yes' ? 1 : 0;
		$ibforums->input['enabletrack'] = $ibforums->input['enabletrack'] ==   1   ? 1 : 0;

		//----------------------------------------------------------------
		// Do we have a valid post?
		//----------------------------------------------------------------

		if (strlen( trim($HTTP_POST_VARS['Post']) ) < 1)
		{
			if ( ! $HTTP_POST_VARS['preview'] )
			{
				$std->Error( array( LEVEL => 1, MSG => 'no_post') );
			}
		}

		if (strlen( $HTTP_POST_VARS['Post'] ) > ($ibforums->vars['max_post_length']*1024))
		{
			$std->Error( array( LEVEL => 1, MSG => 'post_too_long') );
		}

		$post = array(
						'author_id'   => $ibforums->member['id'] ? $ibforums->member['id'] : 0,
						'use_sig'     => $ibforums->input['enablesig'],
						'use_emo'     => $ibforums->input['enableemo'],
						'ip_address'  => $ibforums->input['IP_ADDRESS'],
						'post_date'   => time(),
						'icon_id'     => $ibforums->input['iconid'],
						'post'        => $this->parser->convert( array( TEXT    => $ibforums->input['Post'],
																		SMILIES => $ibforums->input['enableemo'],
																		CODE    => $this->forum['use_ibc'],
																		HTML    => $this->forum['use_html']
																	  )
															   ),
						'author_name' => $ibforums->member['id'] ? $ibforums->member['name'] : $ibforums->input['UserName'],
						'forum_id'    => $this->forum['id'],
						'topic_id'    => "",
						'queued'      => ( $this->obj['moderate'] == 1 || $this->obj['moderate'] == 3 ) ? 1 : 0,
						'attach_id'   => "",
						'attach_hits' => "",
						'attach_type' => "",
					 );

	    // If we had any errors, parse them back to this class
	    // so we can track them later.

	    $this->obj['post_errors'] = $this->parser->error;

		return $post;
	}

    /*****************************************************/
	// process upload
	// ------------------
	// checks for an entry in the upload field, and uploads
	// the file if it meets our criteria. This also inserts
	// a new row into the attachments database if successful
	/*****************************************************/

	function process_upload() {

		global $ibforums, $std, $HTTP_POST_FILES, $DB, $FILE_UPLOAD;

		//-------------------------------------------------
		// Set up some variables to stop carpals developing
		//-------------------------------------------------

		$FILE_NAME = $HTTP_POST_FILES['FILE_UPLOAD']['name'];
		$FILE_SIZE = $HTTP_POST_FILES['FILE_UPLOAD']['size'];
		$FILE_TYPE = $HTTP_POST_FILES['FILE_UPLOAD']['type'];

		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.

		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );

		$attach_data = array( 'attach_id'   => "",
							  'attach_hits' => "",
							  'attach_type' => "",
							  'attach_file' => "",
							);

		//-------------------------------------------------
		// Return if we don't have a file to upload
		//-------------------------------------------------

		// Naughty Mozilla likes to use "none" to indicate an empty upload field.
		// I love universal languages that aren't universal.

		if ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "" or !$HTTP_POST_FILES['FILE_UPLOAD']['name'] or ($HTTP_POST_FILES['FILE_UPLOAD']['name'] == "none") ) return $attach_data;

		//-------------------------------------------------
		// Return empty handed if we don't have permission to use
		// uploads
		//-------------------------------------------------

		if ( ($this->can_upload != 1) and ($ibforums->member['g_attach_max'] < 1) ) return $attach_data;

		//-------------------------------------------------
		// Load our mime types config file.
		//-------------------------------------------------

		require "./conf_mime_types.php";

		//-------------------------------------------------
		// Are we allowing this type of file?
		//-------------------------------------------------

		if ($mime_types[ $FILE_TYPE ][0] != 1)
		{
			$this->obj['post_errors'] = 'invalid_mime_type';
			return $attach_data;
		}

		//-------------------------------------------------
		// Check the file size
		//-------------------------------------------------

		if ($FILE_SIZE > ($ibforums->member['g_attach_max']*1024))
		{
			$std->Error( array( LEVEL => 1, MSG => 'upload_to_big') );
		}

		//-------------------------------------------------
		// Make the uploaded file safe
		//-------------------------------------------------

		$FILE_NAME = preg_replace( "/[^\w\.]/", "_", $FILE_NAME );

		$real_file_name = "post-".$this->forum['id']."-".time();  // Note the lack of extension!

		if (preg_match( "/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)/", $FILE_NAME ))
		{
			$FILE_TYPE = 'text/plain';
		}

		//-------------------------------------------------
		// Add on the extension...
		//-------------------------------------------------

		$ext = '.ibf';

		switch($FILE_TYPE)
		{
			case 'image/gif':
				$ext = '.gif';
				break;
			case 'image/jpeg':
				$ext = '.jpg';
				break;
			case 'image/pjpeg':
				$ext = '.jpg';
				break;
			case 'image/x-png':
				$ext = '.png';
				break;
			case 'image/png':
				$ext = '.png';
				break;
			default:
				$ext = '.ibf';
				break;
		}

		$real_file_name .= $ext;

		//-------------------------------------------------
		// If we are previewing the post, we don't want to
		// add the attachment to the database, so we return
		// the array with the filename. We would have returned
		// earlier if there was an error
		//-------------------------------------------------

		if ($this->obj['preview_post'])
		{
			return array( 'FILE_NAME' => $FILE_NAME );
		}

		//-------------------------------------------------
		// Copy the upload to the uploads directory
		//-------------------------------------------------

		if (! @move_uploaded_file( $HTTP_POST_FILES['FILE_UPLOAD']['tmp_name'], $ibforums->vars['upload_dir']."/".$real_file_name) )
		{
			$this->obj['post_errors'] = 'upload_failed';
			return $attach_data;
		}
		else
		{
			@chmod( $ibforums->vars['upload_dir']."/".$real_file_name, 0777 );
		}

		//-------------------------------------------------
		// set the array, and enter the info into the DB
		// We don't have an extension on the file in the
		// hope that it make it more difficult to execute
		// a script on our server.
		//-------------------------------------------------

		$attach_data['attach_id']   = $real_file_name;
		$attach_data['attach_hits'] = 0;
		$attach_data['attach_type'] = $FILE_TYPE;
		$attach_data['attach_file'] = $FILE_NAME;

		if ($FILE_TYPE == "application/bittorrent"
			|| $FILE_TYPE == "application/x-bittorrent"
			|| eregi(".torrent$", $FILE_NAME))
		{
			require ROOT_PATH."sources/benc.php";
			$torrent = bdec_file($ibforums->vars['upload_dir']."/".$real_file_name, 1 << 20);
			if (!isset($torrent))
				return;
			$attach_data['bt_info_hash'] = pack("H*", sha1($torrent["value"]["info"]["string"]));
			$piece_count = $torrent["value"]["info"]["value"]["pieces"]["strlen"] / 20;
			$piece_length = $torrent["value"]["info"]["value"]["piece length"]["value"];
			$attach_data['bt_size'] = $piece_count * $piece_length;
			$attach_data['bt_tracker'] = $torrent["value"]["announce"]["value"];
		}

		return $attach_data;
	}



	/*****************************************************/
	// check_upload_ability
	// ------------------
	// checks to make sure the requesting browser can accept
	// file uploads, also checks if the member group can
	// accept uploads and returns accordingly.
	/*****************************************************/

	function check_upload_ability() {
		global $ibforums;

		if ( ($this->can_upload == 1) and $ibforums->member['g_attach_max'] > 0)
		{
			$this->obj['can_upload']   = 1;
			$this->obj['form_extra']   = " enctype='multipart/form-data'";
			$this->obj['hidden_field'] = "<input type='hidden' name='MAX_FILE_SIZE' value='".($ibforums->member['g_attach_max']*1024)."' />";
		}

	}

	/*****************************************************/
	// HTML: mod_options.
	// ------------------
	// Returns the HTML for the mod options drop down box
	/*****************************************************/

	function mod_options($is_reply=0) {
		global $ibforums, $DB;

		$can_close = 0;
		$can_pin   = 0;
		$can_move  = 0;

		$html = "<select id='forminput' name='mod_options' class='forminput'>\n<option value='nowt'>".$ibforums->lang['mod_nowt']."</option>\n";

		if ($ibforums->member['g_is_supmod'])
		{
			$can_close = 1;
			$can_pin   = 1;
			$can_move  = 1;
		}
		else if ($ibforums->member['id'] != 0)
		{
			if ($this->moderator['mid'] != "" )
			{
				if ($this->moderator['close_topic'])
				{
					$can_close = 1;
				}
				if ($this->moderator['pin_topic'])
				{
					$can_pin   = 1;
				}
				if ($this->moderator['move_topic'])
				{
					$can_move  = 1;
				}
			}
		}
		else
		{
			return "";
		}

		if ($can_pin == 0 and $can_close == 0 and $can_move == 0)
		{
			return "";
		}

		if ($can_pin)
		{
			$html .= "<option value='pin'>".$ibforums->lang['mod_pin']."</option>";
		}
		if ($can_close)
		{
			$html .= "<option value='close'>".$ibforums->lang['mod_close']."</option>";
		}

		if ($can_close and $can_pin)
		{
			$html .= "<option value='pinclose'>".$ibforums->lang['mod_pinclose']."</option>";
		}

		if ($can_move and $is_reply)
		{
			$html .= "<option value='move'>".$ibforums->lang['mod_move']."</option>";
		}

		return $this->html->mod_options($html);

	}


	/*****************************************************/
	// HTML: start form.
	// ------------------
	// Returns the HTML for the <FORM> opening tag
	/*****************************************************/

	function html_start_form($additional_tags=array()) {
		global $ibforums;

		$form = $this->html->get_javascript();

		$form .= "<form action='{$this->base_url}' method='post' name='REPLIER' onsubmit='return ValidateForm()'".$this->obj['form_extra'].">".
				"<input type='hidden' name='st' value='".$ibforums->input[st]."' />\n".
				"<input type='hidden' name='act' value='Post' />\n".
				"<input type='hidden' name='s' value='".$ibforums->session_id."' />\n".
				"<input type='hidden' name='f' value='".$this->forum['id']."' />\n".
				"<input type='hidden' name='auth_key' value='".$this->md5_check."' />\n".
				$this->obj['hidden_field'];

		// Any other tags to add?

		if (isset($additional_tags)) {
			foreach($additional_tags as $k => $v) {
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}

		return $form;
    }

	/*****************************************************/
	// HTML: name fields.
	// ------------------
	// Returns the HTML for either text inputs or membername
	// depending if the member is a guest.
	/*****************************************************/

	function html_name_field() {
		global $ibforums;

		return $ibforums->member['id'] ? $this->html->nameField_reg() : $this->html->nameField_unreg( $ibforums->input[UserName] );
	}

	/*****************************************************/
	// HTML: Post body.
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*****************************************************/

	function html_post_body($raw_post="") {
		global $ibforums;

		$ibforums->lang['the_max_length'] = $ibforums->vars['max_post_length'] * 1024;

		return $this->html->postbox_buttons($raw_post);

	}

	/*****************************************************/
	// HTML: Post Icons
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*****************************************************/

	function html_post_icons($post_icon="") {
		global $ibforums;

		if ($ibforums->input['iconid'])
		{
			$post_icon = $ibforums->input['iconid'];
		}

		$ibforums->lang['the_max_length'] = $ibforums->vars['max_post_length'] * 1024;

		$html = $this->html->PostIcons();

		if ($post_icon) {
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']$post_icon\s?[\"']/", "name='iconid' value='$post_icon' checked", $html );
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']0[\"']\s*checked=['\"]checked['\"]/i"  , "name='iconid' value='0'", $html );
		}
		return $html;
	}

	/*****************************************************/
	// HTML: checkboxes
	// ------------------
	// Returns the HTML for sig/emo/track boxes
	/*****************************************************/

	function html_checkboxes($type="", $tid="")
	{
		global $ibforums, $DB;

		$default_checked = array(
								  'sig' => 'checked="checked"',
						  		  'emo' => 'checked="checked"',
						  		  'tra' => $ibforums->member['auto_track'] ? 'checked="checked"' : ''
						        );

		// Make sure we're not previewing them and they've been unchecked!

		if ( isset( $ibforums->input['enablesig'] ) AND ( ! $ibforums->input['enablesig'] ) )
		{
			$default_checked['sig'] = "";
		}

		if ( isset( $ibforums->input['enableemo'] ) AND ( ! $ibforums->input['enableemo'] ) )
		{
			$default_checked['emo'] = "";
		}

		if ( isset( $ibforums->input['enabletrack'] ) AND ( ! $ibforums->input['enabletrack'] ) )
		{
			$default_checked['tra'] = "";
		}
		else if ( isset( $ibforums->input['enabletrack'] ) AND ( $ibforums->input['enabletrack'] == 1 ) )
		{
			$default_checked['tra'] = 'checked="checked"';
		}

		$this->output = str_replace( '<!--IBF.EMO-->'  , $this->html->get_box_enableemo( $default_checked['emo'] )  , $this->output );

		$this->output = str_replace( '<!--IBF.SIG-->'  , $this->html->get_box_enablesig( $default_checked['sig'] )  , $this->output );

		if ( $type == 'reply' )
		{
			if ( $tid and $ibforums->member['id'] )
			{
				$DB->query("SELECT trid FROM ibf_tracker WHERE topic_id=$tid AND member_id=".$ibforums->member['id']);

				if ( $DB->get_num_rows() )
				{
					$this->output = str_replace( '<!--IBF.TRACK-->',$this->html->get_box_alreadytrack(), $this->output );
				}
				else
				{
					$this->output = str_replace( '<!--IBF.TRACK-->', $this->html->get_box_enabletrack( $default_checked['tra'] ), $this->output );
				}
			}
		}
		else if ( $type != 'edit' )
		{
			$this->output = str_replace( '<!--IBF.TRACK-->', $this->html->get_box_enabletrack( $default_checked['tra'] ), $this->output );
		}
	}

    /*****************************************************/
	// HTML: add smilie box.
	// ------------------
	// Inserts the clickable smilies box
	/*****************************************************/

	function html_add_smilie_box() {
		global $ibforums, $DB;

		$show_table = 0;
		$count      = 0;
		$smilies    = "<tr align='center'>\n";

		// Get the smilies from the DB

		$DB->query("SELECT * FROM ibf_emoticons WHERE clickable='1'");

		while ($elmo = $DB->fetch_row() ) {

			$show_table++;
			$count++;

			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o

			if (strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
				$out_delim = "'";
			}
			else
			{
				$in_delim  = "'";
				$out_delim = '"';
			}

			$smilies .= "<td><a href={$out_delim}javascript:emoticon($in_delim".$elmo['typed']."$in_delim){$out_delim}><img src=\"".$ibforums->vars['EMOTICONS_URL']."/".$elmo['image']."\" alt='smilie' border='0' /></a>&nbsp;</td>\n";

			if ($count == $ibforums->vars['emo_per_row']) {
				$smilies .= "</tr>\n\n<tr align='center'>";
				$count = 0;
			}
		}

		if ($count != $ibforums->vars['emo_per_row']) {
			for ($i = $count ; $i < $ibforums->vars['emo_per_row'] ; ++$i) {
				$smilies .= "<td>&nbsp;</td>\n";
			}
			$smilies .= "</tr>";
		}

		$table = $this->html->smilie_table();

		if ($show_table != 0) {
			$table = preg_replace( "/<!--THE SMILIES-->/", $smilies, $table );
			$this->output = preg_replace( "/<!--SMILIE TABLE-->/", $table, $this->output );
		}

	}

	/*****************************************************/
	// HTML: topic summary.
	// ------------------
	// displays the last 10 replies to the topic we're
	// replying in.
	/*****************************************************/

	function html_topic_summary($topic_id) {

		global $ibforums, $std, $DB;

		if (! $topic_id ) return;

		$cached_members = array();

		$this->output .= $this->html->TopicSummary_top();

		//--------------------------------------------------------------
		// Get the posts
		// This section will probably change at some point
		//--------------------------------------------------------------

		$post_query = $DB->query("SELECT post, pid, post_date, author_id, author_name FROM ibf_posts WHERE topic_id=$topic_id and queued <> 1 ORDER BY pid DESC LIMIT 0,10");

		while ( $row = $DB->fetch_row($post_query) )
		{

		    $row['author'] = $row['author_name'];

			$row['date']   = $std->get_date( $row['post_date'], 'LONG' );

			if (!$ibforums->member['view_img'])
			{
				// unconvert smilies first, or it looks a bit crap.

				$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );

				$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>)", $row['post'] );
			}

			$row['post']   = $this->parser->post_db_parse($row['post'], $this->forum['use_html'] AND $ibforums->member['g_dohtml'] ? 1 : 0);

			//--------------------------------------------------------------
			// Do word wrap?
			//--------------------------------------------------------------

			if ( $ibforums->vars['post_wordwrap'] > 0 )
			{
				$row['post'] = $this->parser->my_wordwrap( $row['post'], $ibforums->vars['post_wordwrap']) ;
			}

			$row['post']   = str_replace( "<br>", "<br />", $row['post'] );

			$this->output .= $this->html->TopicSummary_body( $row );
		}

		$this->output .= $this->html->TopicSummary_bottom();

	}

	/*****************************************************/
	// Moderators log
	// ------------------
	// Simply adds the last action to the mod logs
	/*****************************************************/

	function moderate_log($title = 'unknown', $topic_title) {
		global $std, $ibforums, $DB, $HTTP_REFERER, $QUERY_STRING;

		$db_string = $std->compile_db_string( array (
														'forum_id'    => $ibforums->input['f'],
														'topic_id'    => $ibforums->input['t'],
														'post_id'     => $ibforums->input['p'],
														'member_id'   => $ibforums->member['id'],
														'member_name' => $ibforums->member['name'],
														'ip_address'  => $ibforums->input['IP_ADDRESS'],
														'http_referer'=> $HTTP_REFERER,
														'ctime'       => time(),
														'topic_title' => $topic_title,
														'action'      => $title,
														'query_string'=> $QUERY_STRING,
													)
										    );

		$DB->query("INSERT INTO ibf_moderator_logs (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");

	}

}

?>