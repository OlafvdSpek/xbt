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
|   > New Post module
|   > Module written by Matt Mecham
|
+--------------------------------------------------------------------------
*/




class post_functions extends Post {

	var $nav = array();
	var $title = "";
	var $post  = array();
	var $topic = array();
	var $upload = array();
	var $mod_topic = array();
	var $poll_count = 0;
	var $poll_choices = "";

	var $m_group = "";
	
	function post_functions($class) {
	
		global $ibforums, $std, $DB;
		
		//-------------------------------------------------------------------------
		// Sort out maximum number of poll choices allowed
		//-------------------------------------------------------------------------
		
		$ibforums->vars['max_poll_choices'] = $ibforums->vars['max_poll_choices'] ? $ibforums->vars['max_poll_choices'] : 10;
		
		$ibforums->lang['poll_choices']      = sprintf( $ibforums->lang['poll_choices'], $ibforums->vars['max_poll_choices'] );
		
		//-------------------------------------------------------------------------
		// Lets do some tests to make sure that we are allowed to start a new topic
		//-------------------------------------------------------------------------
		
		if (! $ibforums->member['g_post_polls'])
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_start_polls') );
		}
		
		if ( ! $class->forum['allow_poll'] )
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_start_polls') );
		}
		
		if ( $std->check_perms($class->forum['start_perms']) == FALSE )
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_start_polls') );
		}
		
		if ( ! intval($ibforums->input['t']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
		}
		else
		{
			$tid = intval($ibforums->input['t']);
			
			$DB->query("SELECT * from ibf_topics WHERE tid=$tid");
			
			if ( ! $this->topic = $DB->fetch_row() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
			}
		}
		
		$pass = 0;
		
		if ( $ibforums->member['id'] )
		{
			if ( $ibforums->member['g_is_supmod'] == 1 )
			{
				$pass = 1;
			}
			else if ( $this->topic['starter_id'] == $ibforums->member['id'] )
			{
				if ( ($ibforums->vars['startpoll_cutoff'] > 0) AND ( $this->topic['start_date'] + ($ibforums->vars['startpoll_cutoff'] * 3600) > time() ) )
				{
					$pass = 1;
				}
			}
		}
		
		if ( $pass != 1 )
		{
			$std->Error( array( LEVEL => 1, MSG => 'no_start_polls') );
		}
	}
	
	function process($class) {
	
		global $ibforums, $std, $DB, $print;
		
		//-------------------------------------------------
		// check to make sure we have a correct # of choices
		//-------------------------------------------------
		
		$this->poll_choices = $ibforums->input['PollAnswers'];
		
		$this->poll_choices = preg_replace( "/<br><br>/" , ""                              , $this->poll_choices );
		
		$this->poll_choices = preg_replace( "/<br>/e"    , "\$this->regex_count_choices()" , $this->poll_choices );
		
		if ($this->poll_count > $ibforums->vars['max_poll_choices'])
		{
			$class->obj['post_errors'] = 'poll_to_many';
		}
		
		if ($this->poll_count < 1)
		{
			$class->obj['post_errors'] = 'poll_not_enough';
		}

		
		
		if ($class->obj['post_errors'] != "")
		{
			// Show the form again
			$this->show_form($class);
		}
		else
		{
			$this->add_new_poll($class);
		}
	}
	
	
	
	
	
	function add_new_poll($class) {
		
		global $ibforums, $std, $DB, $print;
		
		//-------------------------------------------------
		// Sort out the poll stuff
		// This is somewhat contrived, but it has to be
		// compatible with the current perl version.
		//-------------------------------------------------
		
		$poll_array  = array();
		$count       = 0;
		
		$polls       = explode( "<br>", $this->poll_choices );
		
		foreach ($polls as $polling)
		{
			if ( $polling == "" )
			{
				continue;
			}
			$poll_array[] = array( $count , $class->parser->bad_words($polling), 0 );
			$count++;
		}
		
		
		//-------------------------------------------------
		// Add the poll to the forum_polls table
		// if we are moderating this post
		//-------------------------------------------------
		
		$db_string = $std->compile_db_string(
											  array (
											  			'tid'           => $this->topic['tid'],
											  			'forum_id'      => $class->forum['id'],
											  			'start_date'    => time(),
											  			'choices'       => addslashes(serialize($poll_array)),
											  			'starter_id'    => $ibforums->member['id'],
											  			'votes'         => 0,
											  			'poll_question' => $class->parser->bad_words($ibforums->input['pollq']),
											  		)
											  );
											  
		$DB->query("INSERT INTO ibf_polls (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
		
		$DB->query("UPDATE ibf_topics SET poll_state='open' WHERE tid={$this->topic['tid']}");
								    
		//-------------------------------------------------
		// Redirect them back to the topic
		//-------------------------------------------------
		
		$std->boink_it($class->base_url."act=ST&f={$class->forum['id']}&t={$this->topic['tid']}");
		
	}






	function show_form($class)
	{
	
		global $ibforums, $std, $DB, $print, $HTTP_POST_VARS;
		
		// Sort out the "raw" textarea input and make it safe incase
		// we have a <textarea> tag in the raw post var.
		
		$poll = isset($HTTP_POST_VARS['PollAnswers']) ? $std->txt_htmlspecialchars($HTTP_POST_VARS['PollAnswers']) : "";
		
		$extra = "";
		
		if ($ibforums->vars['poll_tags'])
		{
			$extra = $ibforums->lang['poll_tag_allowed'];
		}
		
		$class->output .= $class->html_start_form( array( 1 => array( 'CODE', '15' ), 2 => array( 'f', $class->forum['id']), 2 => array( 't', $this->topic['tid']) ) );
		
		//---------------------------------------
		// START TABLE
		//---------------------------------------
		
		$class->output .= $class->html->table_structure();
		
		//---------------------------------------
				
		$start_table = $class->html->table_top( "{$ibforums->lang['top_txt_poll']}: {$class->forum['name']} -> ".$this->topic['title']);
						
		$poll_box    = $class->html->poll_box($poll, $extra);
		
		$end_form    = $class->html->poll_end_form( $ibforums->lang['submit_poll'] );
			
		//---------------------------------------
		
		$class->output = preg_replace( "/<!--START TABLE-->/" , "$start_table"  , $class->output );
		//$class->output = preg_replace( "/<!--NAME FIELDS-->/" , "$name_fields"  , $class->output );
		//$class->output = preg_replace( "/<!--POST BOX-->/"    , "$post_box"     , $class->output );
		//$class->output = preg_replace( "/<!--POST ICONS-->/"  , "$post_icons"   , $class->output );
		//$class->output = preg_replace( "/<!--UPLOAD FIELD-->/", "$upload_field" , $class->output );
		//$class->output = preg_replace( "/<!--MOD OPTIONS-->/" , "$mod_options"  , $class->output );
		$class->output = preg_replace( "/<!--END TABLE-->/"   , "$end_form"     , $class->output );
		//$class->output = preg_replace( "/<!--TOPIC TITLE-->/" , "$topic_title"  , $class->output );
		$class->output = preg_replace( "/<!--POLL BOX-->/"    , "$poll_box"     , $class->output );
		$class->output = str_replace("<!--FORUM RULES-->", $std->print_forum_rules($class->forum), $class->output );
		
		//---------------------------------------
		
		
		$class->html_add_smilie_box();
		
		$this->nav = array( "<a href='{$class->base_url}act=SC&amp;c={$class->forum['cat_id']}'>{$class->forum['cat_name']}</a>",
							"<a href='{$class->base_url}act=SF&amp;f={$class->forum['id']}'>{$class->forum['name']}</a>",
							"<a href='{$class->base_url}act=ST&amp;f={$class->forum['id']}&amp;t={$this->topic['tid']}'>{$this->topic['title']}</a>",
						  );
		$this->title = $ibforums->lang['posting_poll'];
		
		$print->add_output("$class->output");
        $print->do_output( array( 'TITLE'    => $ibforums->vars['board_name']." -> ".$this->title,
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->nav,
        					  ) );
		
	}
	
	function regex_count_choices() {
		
		++$this->poll_count;
		
		return "<br>";
		
	}
	

}

?>