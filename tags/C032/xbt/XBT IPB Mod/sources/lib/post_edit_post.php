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
|   > Edit post library
|   > Module written by Matt Mecham
|   > Date started: 19th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/



class post_functions extends Post {

	var $nav               = array();
	var $title             = "";
	var $post              = array();
	var $topic             = array();
	var $upload            = array();
	var $moderator         = array( 'member_id' => 0, 'member_name' => "", 'edit_post' => 0 );
	var $orig_post         = array();
	var $edit_title        = 0;

	function post_functions($class) {
	
		global $ibforums, $std, $DB;
		
		//-------------------------------------------------
		// Lets load the topic from the database before we do anything else.
		//-------------------------------------------------
		
		$DB->query("SELECT * FROM ibf_topics WHERE tid=".intval($ibforums->input['t']));
		$this->topic = $DB->fetch_row();
		
		//-------------------------------------------------
		// Is it legitimate?
		//-------------------------------------------------
		
		if (! $this->topic['tid'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-------------------------------------------------
		// Load the old post
		//-------------------------------------------------
		
		$DB->query("SELECT * FROM ibf_posts WHERE pid=".intval($ibforums->input['p']));
		$this->orig_post = $DB->fetch_row();
		
		if (! $this->orig_post['pid'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-------------------------------------------------
		// Load the moderator
		//-------------------------------------------------
		
		if ($ibforums->member['id'])
		{
			$DB->query("SELECT member_id, member_name, mid, edit_post, edit_topic FROM ibf_moderators WHERE forum_id=".$class->forum['id']." AND (member_id=".$ibforums->member['id']." OR (is_group=1 AND group_id='".$ibforums->member['mgroup']."'))");
			$this->moderator = $DB->fetch_row();
		}
		
		//-------------------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to edit this topic
		//-------------------------------------------------
		
		$can_edit = 0;
		
		if ($ibforums->member['g_is_supmod'])
		{
			$can_edit = 1;
		}
		if ($this->moderator['edit_post'])
		{
			$can_edit = 1;
		}
		if ( ($this->orig_post['author_id'] == $ibforums->member['id']) and ($ibforums->member['g_edit_posts']) )
		{
			// Have we set a time limit?
			
			if ($ibforums->member['g_edit_cutoff'] > 0)
			{
				if ( $this->orig_post['post_date'] > ( time() - ( intval($ibforums->member['g_edit_cutoff']) * 60 ) ) )
				{
					$can_edit = 1;
				}
			}
			else
			{
				$can_edit = 1;
			}
		}
		
		if ($can_edit != 1)
		{
			$std->Error( array( LEVEL => 1, MSG => 'not_op') );
		}
		
		//-----------------------------
		// Is the topic locked?
		//-----------------------------
		
		if (($this->topic['state'] != 'open') and (!$ibforums->member['g_is_supmod']))
		{
			if ($ibforums->member['g_post_closed'] != 1)
			{
				$std->Error( array( LEVEL => 1, MSG => 'locked_topic') );
			}
		}
		
		//-----------------------------
		// // Do we have edit topic abilities?
		//-----------------------------
		
		if ( $this->orig_post['new_topic'] == 1 )
		{
			if ($ibforums->member['g_is_supmod'] == 1)
			{
				$this->edit_title = 1;
			}
			
			else if ($this->moderator['edit_topic'] == 1)
			{
				if ( $ibforums->member['id'] == $this->topic['starter_id'] )
				{
					$this->edit_title = 1;
				}
			}
			else if ($ibforums->member['g_edit_topic'] == 1 )
			{
				$this->edit_title = 1;
			}
		}

	}
	
	function process($class) {
	
		global $ibforums, $std, $DB, $print;
		
		//-------------------------------------------------
		// Parse the post, and check for any errors.
		// overwrites saved post intentionally
		//-------------------------------------------------
		
		$this->post   = $class->compile_post();
		
		if ( ($class->obj['post_errors'] != "") or ($class->obj['preview_post'] != "") )
		{
			// Show the form again
			$this->show_form($class);
		}
		else
		{
			$this->complete_edit($class);
		}
	}
	
	
	
	
	
	function complete_edit($class) {
		
		global $ibforums, $std, $DB, $print, $HTTP_POST_FILES;
		
		$time = $std->get_date( time(), 'LONG' );
				
		//-------------------------------------------------
		// Reset some data
		//-------------------------------------------------
		
		// Do we have to adjust the attachments?
		
		if ($this->orig_post['attach_id'])
		{
			if ( isset($ibforums->input['editupload']) AND ($ibforums->input['editupload'] != 'keep') )
			{
				// We're either uploading a new attachment, or deleting one, so lets
				// remove the old attachment first eh?
				
				if (is_file($ibforums->vars['upload_dir']."/".$this->orig_post['attach_id']))
				{
					@unlink($ibforums->vars['upload_dir']."/".$this->orig_post['attach_id']);
				}
				
				if ($ibforums->input['editupload'] == 'new')
				{
					// w00t, we're uploading a new ..um.. upload

					$new_upload = $class->process_upload();
					
					if ( $class->obj['post_errors'] != "") {
			
						$this->show_form($class);
					}
					   
					$this->post['attach_id']   = $new_upload['attach_id'];
					$this->post['attach_type'] = $new_upload['attach_type'];
					$this->post['attach_hits'] = $new_upload['attach_hits'];
					$this->post['attach_file'] = $new_upload['attach_file'];
				}
				else if ($ibforums->input['editupload'] == 'delete')
				{
					// Simply remove the DB data as we've already removed the file
					
					$this->post['attach_id']   = "";
					$this->post['attach_type'] = "";
					$this->post['attach_hits'] = "";
					$this->post['attach_file'] = "";
				}
			}
			else
			{
				// We are keeping the old attachment
				$this->post['attach_id']   = $this->orig_post['attach_id'];
				$this->post['attach_type'] = $this->orig_post['attach_type'];
				$this->post['attach_hits'] = $this->orig_post['attach_hits'];
				$this->post['attach_file'] = $this->orig_post['attach_file'];
			}
		}
		else
		{
			// Is there a new attachment?
			
			$new_upload = $class->process_upload();
					
			if ( $class->obj['post_errors'] != "") {
	
				$this->show_form($class);
			}
			
			if ( $new_upload['attach_id'] )
			{
				$this->post['attach_id']   = $new_upload['attach_id'];
				$this->post['attach_type'] = $new_upload['attach_type'];
				$this->post['attach_hits'] = $new_upload['attach_hits'];
				$this->post['attach_file'] = $new_upload['attach_file'];
			}
		}
		
		$this->post['ip_address']  = $this->orig_post['ip_address'];
		$this->post['topic_id']    = $this->orig_post['topic_id'];
		$this->post['author_id']   = $this->orig_post['author_id'];
		$this->post['pid']         = $this->orig_post['pid'];
		$this->post['post_date']   = $this->orig_post['post_date'];
		$this->post['author_name'] = $this->orig_post['author_name'];
		$this->post['queued']      = $this->orig_post['queued'];
		$this->post['edit_time']   = time();
		$this->post['edit_name']   = $ibforums->member['name'];
		
		//-------------------------------------------------
		// If the post icon has changed, update the topic post icon
		//-------------------------------------------------
		
		if ($this->orig_post['new_topic'] == 1)
		{
			if ($this->post['icon_id'] != $this->orig_post['icon_id'])
			{
				$DB->query("UPDATE ibf_topics SET icon_id='".$this->post['icon_id']."' WHERE tid=".$this->topic['tid']);
			}
		}
		
		//-------------------------------------------------
		// Update topic title?
		//-------------------------------------------------
		
		if ( $this->edit_title == 1 )
		{
			if ($ibforums->vars['etfilter_punct'])
			{
				$ibforums->input['TopicTitle']	= preg_replace( "/\?{1,}/"      , "?"    , $ibforums->input['TopicTitle'] );		
				$ibforums->input['TopicTitle']	= preg_replace( "/(&#33;){1,}/" , "&#33;", $ibforums->input['TopicTitle'] );
			}
			
			if ($ibforums->vars['etfilter_shout'])
			{
				$ibforums->input['TopicTitle'] = ucwords(strtolower($ibforums->input['TopicTitle']));
			}
			
			$ibforums->input['TopicTitle'] = trim( $class->parser->bad_words( $ibforums->input['TopicTitle'] ) );
			$ibforums->input['TopicDesc']  = trim( $class->parser->bad_words( $ibforums->input['TopicDesc']  ) );
			
			if ( $ibforums->input['TopicTitle'] != "" )
			{
				if ( ($ibforums->input['TopicTitle'] != $this->topic['title']) or ($ibforums->input['TopicDesc'] != $this->topic['description'])  )
				{
					$dbs = $DB->compile_db_update_string( array( 
																 'title'       => $ibforums->input['TopicTitle'],
																 'description' => $ibforums->input['TopicDesc']
														)      );
														
					$DB->query("UPDATE ibf_topics SET $dbs WHERE tid=".$this->topic['tid']);
					
					if ($this->topic['tid'] == $class->forum['last_id'])
					{
						$DB->query("UPDATE ibf_forums SET last_title='{$ibforums->input['TopicTitle']}' WHERE id='".$class->forum['id']."'");
					}
					
					if ( ($this->moderator['edit_topic'] == 1) OR ( $ibforums->member['g_is_supmod'] == 1 ) )
					{
						$db_string = $std->compile_db_string( array (
																'forum_id'    => $class->forum['id'],
																'topic_id'    => $this->topic['tid'],
																'post_id'     => $this->post['pid'],
																'member_id'   => $ibforums->member['id'],
																'member_name' => $ibforums->member['name'],
																'ip_address'  => $ibforums->input['IP_ADDRESS'],
																'http_referer'=> $HTTP_REFERER,
																'ctime'       => time(),
																'topic_title' => $this->topic['title'],
																'action'      => "Edited topic title or description '{$this->topic['title']}' to '{$ibforums->input['TopicTitle']}' via post form",
																'query_string'=> $QUERY_STRING,
															)
													);
				
						$DB->query("INSERT INTO ibf_moderator_logs (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
					}
				}
			}
		}
		
		//-------------------------------------------------
		// Update the database (ib_forum_post)
		//-------------------------------------------------
		
		$this->post['append_edit'] = 1;
		
		if ($ibforums->member['g_append_edit'])
		{
			if ($ibforums->input['add_edit'] != 1)
			{
				$this->post['append_edit'] = 0;
			}
		}
		
		
		$db_string = $DB->compile_db_update_string( $this->post );
		
		$DB->query("UPDATE ibf_posts SET $db_string WHERE pid='".$this->post['pid']."'");

		
		//-------------------------------------------------
		// Redirect them back to the topic
		//-------------------------------------------------
		
		$print->redirect_screen( $ibforums->lang['post_edited'], "act=ST&f={$class->forum['id']}&t={$this->topic['tid']}&st={$ibforums->input['st']}#entry{$this->post['pid']}");
		
	}






	function show_form($class) {
	
		global $ibforums, $std, $DB, $print, $HTTP_POST_VARS;
		
		//-------------------------------------------------
		// Sort out the "raw" textarea input and make it safe incase
		// we have a <textarea> tag in the raw post var.
		//-------------------------------------------------
		
		$raw_post = isset($HTTP_POST_VARS['Post'])  ? $std->txt_htmlspecialchars($HTTP_POST_VARS['Post']) : $class->parser->unconvert($this->orig_post['post'], $class->forum['use_ibc'], $class->forum['use_html']);

		if (isset($raw_post))
		{
			$raw_post = $std->txt_raw2form($raw_post);
		}
		
		//-------------------------------------------------
		// Is this the first post in the topic?
		//-------------------------------------------------
		
		if ( $this->edit_title == 1 )
		{
			$topic_title = isset($HTTP_POST_VARS['TopicTitle']) ? $ibforums->input['TopicTitle'] : $this->topic['title'];
			$topic_desc  = isset($HTTP_POST_VARS['TopicDesc'])  ? $ibforums->input['TopicDesc']  : $this->topic['description'];
			
			$topic_title = $class->html->topictitle_fields( array( 'TITLE' => $topic_title, 'DESC' => $topic_desc ) );
		}
		
		//-------------------------------------------------
		// Do we have any posting errors?
		//-------------------------------------------------
		
		if ($class->obj['post_errors'])
		{
			$class->output .= $class->html->errors( $ibforums->lang[ $class->obj['post_errors'] ]);
		}
		
		if ($class->obj['preview_post'])
		{
			$this->post['post'] = $class->parser->post_db_parse(
															     $class->parser->convert( array(
															     								 'TEXT'    => $this->post['post'],
															     								 'CODE'    => $class->forum['use_ibc'],
															     								 'SMILIES' => $ibforums->input['enableemo'],
															     								 'HTML'    => $class->forum['use_html']
															     						)      ) ,
															     $class->forum['use_html'] AND $ibforums->member['g_dohtml'] ? 1 : 0);
			$class->output .= $class->html->preview( $this->post['post'] );
		}
		
		$class->check_upload_ability();
		
		$class->output .= $class->html_start_form( array( 1 => array( 'CODE', '09' ),
														  2 => array( 't'   , $this->topic['tid']),
														  3 => array( 'p'   , $ibforums->input['p'] ),
														  4 => array( 'st'  , $ibforums->input['st'] ),
														) );
														
		//---------------------------------------
		// START TABLE
		//---------------------------------------
		
		$class->output .= $class->html->table_structure();
		
		//---------------------------------------
		
		$start_table = $class->html->table_top( "{$ibforums->lang['top_txt_edit']} {$this->topic['title']}");
		
		$name_fields = $class->html_name_field();
		
		$post_box    = $class->html_post_body( $raw_post );
		
		$end_form    = $class->html->EndForm( $ibforums->lang['submit_edit'] );
		
		$post_icons  = $class->html_post_icons($this->orig_post['icon_id']);
		
		if ( $class->obj['can_upload'] )
		{
			if ($this->orig_post['attach_id'] != "")
			{
				$upload_field = $class->html->edit_upload_field( $std->size_format( $ibforums->member['g_attach_max'] * 1024 ), $this->orig_post['attach_file'] );
			}
			else
			{
				$upload_field = $class->html->Upload_field( $std->size_format( $ibforums->member['g_attach_max'] * 1024 ) );
			}
		}
		
		if ($ibforums->member['g_append_edit'])
		{
			$checked = "";
			
			if ($this->orig_post['append_edit'])
			{
				$checked = "checked";
			}
			
			$edit_option = $class->html->add_edit_box($checked);
		}
		
		//---------------------------------------
		
		$class->output = str_replace( "<!--START TABLE-->" , $start_table  , $class->output );
		$class->output = str_replace( "<!--NAME FIELDS-->" , $name_fields  , $class->output );
		$class->output = str_replace( "<!--POST BOX-->"    , $post_box     , $class->output );
		$class->output = str_replace( "<!--POST ICONS-->"  , $post_icons   , $class->output );
		$class->output = str_replace( "<!--END TABLE-->"   , $end_form     , $class->output );
		$class->output = str_replace( "<!--UPLOAD FIELD-->", $upload_field , $class->output );
		$class->output = str_replace( "<!--MOD OPTIONS-->" , $edit_option  , $class->output );
		$class->output = str_replace( "<!--FORUM RULES-->" , $std->print_forum_rules($class->forum), $class->output );
		$class->output = str_replace( "<!--TOPIC TITLE-->" , $topic_title  , $class->output );
		
		//---------------------------------------
		
		$class->html_add_smilie_box();
		
		//---------------------------------------
		// Add in siggy buttons and such
		//---------------------------------------
		
		$class->html_checkboxes('edit');
		
		//---------------------------------------
		
		$class->html_topic_summary($this->topic['tid']);
		
		$this->nav = array( "<a href='{$class->base_url}&act=SC&c={$class->forum['cat_id']}'>{$class->forum['cat_name']}</a>",
							"<a href='{$class->base_url}&act=SF&f={$class->forum['id']}'>{$class->forum['name']}</a>",
							"<a href='{$class->base_url}&act=ST&f={$class->forum['id']}&t={$this->topic['tid']}'>{$this->topic['title']}</a>",
						  );
						  
		$this->title = $ibforums->lang['editing_post'].' '.$this->topic['title'];
		
		$print->add_output("$class->output");
		
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->title,
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->nav,
        					  ) );
		
	}
	

}

?>