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
|   > Multi Moderation Module
|   > Module written by Matt Mecham
|   > Date started: 16th May 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new multi_mod;

class  multi_mod {

    var $output    = "";
    var $topic     = array();
    var $forum     = array();
    var $topic_id  = "";
    var $forum_id  = "";
    var $mm_id     = "";
    var $moderator = "";
    var $modfunc   = "";
    var $mm_data   = "";
    var $parser    = "";
    
    //------------------------------------------------------
	// @constructor (no, not bob the builder)
	//------------------------------------------------------
    
    function multi_mod()
    {
        global $ibforums, $DB, $std, $print;
        
        //-------------------------------------
        // Load modules...
        //-------------------------------------
        
        $ibforums->lang  = $std->load_words($ibforums->lang, 'lang_mod', $ibforums->lang_id);
        
        require( ROOT_PATH.'sources/lib/modfunctions.php');
        
        $this->modfunc = new modfunctions();
        
        require( ROOT_PATH.'sources/lib/post_parser.php');
        
        $this->parser  = new post_parser(1);
		
        //----------------------------------------
		// Clean the incoming
		//----------------------------------------
        
        $ibforums->input['t'] = intval($ibforums->input['t']);
        $this->mm_id          = intval($ibforums->input['mm_id']);
        
        if ($ibforums->input['t'] < 0 )
        {
            $std->Error( array( LEVEL => '1', MSG => 'missing_files') );
        }
        
        //-------------------------------------
        // Get the topic id / forum id
        //-------------------------------------
        
        $DB->query("SELECT t.*, f.*
                    FROM ibf_topics t, ibf_forums f
                    WHERE t.tid={$ibforums->input['t']} and f.id=t.forum_id");
        
        $this->topic = $DB->fetch_row();
        
        $this->forum = array( 
        					 'id'            => $this->topic['id'],
        					 'name'          => $this->topic['name'],
        					 'topic_mm_id'   => $this->topic['topic_mm_id'],
        					 'inc_postcount' => $this->topic['inc_postcount']
        					);
        					
        //-------------------------------------
        // Error out if we can not find the forum
        //-------------------------------------
        
        if (! $this->forum['id'])
        {
        	$std->Error( array( 'LEVEL' => 1,'MSG' => 'missing_files') );
        }
        
        //-------------------------------------
        // Error out if we can not find the topic
        //-------------------------------------
        
        if (! $this->topic['tid'])
        {
        	$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
        }
        
        //-------------------------------------
        // Are we a moderator?
        //-------------------------------------
		
		if ( ($ibforums->member['id']) and ($ibforums->member['g_is_supmod'] != 1) )
		{
			$DB->query("SELECT * FROM ibf_moderators
						WHERE forum_id={$this->forum['id']} AND (member_id={$ibforums->member['id']}
						  OR (is_group=1 AND group_id={$ibforums->member['mgroup']}))");
						  
			$this->moderator = $DB->fetch_row();
		}
        
        //----------------------------------------
		// Init modfunc module
		//----------------------------------------
		
		$this->modfunc->init( $this->forum, $this->topic, $this->moderator );
        
        //----------------------------------------
		// Do we have permission?
		//----------------------------------------
		
		if ( $this->modfunc->mm_authorize() != TRUE )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
		}
        
		//-------------------------------------
        // Does this forum have this mm_id
        //-------------------------------------
		
		if ( $this->modfunc->mm_check_id_in_forum( $this->forum['topic_mm_id'], $this->mm_id ) != TRUE )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//-------------------------------------
        // Still here? We're damn good to go sir!
        //-------------------------------------
        
        $DB->query("SELECT * FROM ibf_topic_mmod WHERE mm_id={$this->mm_id}");
        
        if ( ! $this->mm_data = $DB->fetch_row() )
        {
        	$std->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
        }
        
        $this->modfunc->stm_init();
        
        //-------------------------------------
        // Open close?
        //-------------------------------------
        
        if ( $this->mm_data['topic_state'] != 'leave' )
        {
        	if ( $this->mm_data['topic_state'] == 'close' )
        	{
        		$this->modfunc->stm_add_close();
        	}
        	else if ( $this->mm_data['topic_state'] == 'open' )
        	{
        		$this->modfunc->stm_add_open();
        	}
        }
        
        //-------------------------------------
        // pin no-pin?
        //-------------------------------------
        
        if ( $this->mm_data['topic_pin'] != 'leave' )
        {
        	if ( $this->mm_data['topic_pin'] == 'pin' )
        	{
        		$this->modfunc->stm_add_pin();
        	}
        	else if ( $this->mm_data['topic_pin'] == 'unpin' )
        	{
        		$this->modfunc->stm_add_unpin();
        	}
        }
        
        //-------------------------------------
        // Topic title
        //-------------------------------------
        
        $title = $this->topic['title'];
        
        if ( $this->mm_data['topic_title_st'] )
        {
        	// Tidy up...
        	
        	$title = preg_replace( "/^".preg_quote($this->mm_data['topic_title_st'], '/')."/", "", $title );
        }
        
        if ( $this->mm_data['topic_title_end'] )
        {
        	// Tidy up...
        	
        	$title = preg_replace( "/".preg_quote($this->mm_data['topic_title_end'], '/')."$/", "", $title );
        }
        
        $this->modfunc->stm_add_title($this->mm_data['topic_title_st'].$title.$this->mm_data['topic_title_end']);
        
        //-------------------------------------
        // Update what we have so far...
        //-------------------------------------
        
        $this->modfunc->stm_exec( $this->topic['tid'] );
        
        //-------------------------------------
        // Add reply?
        //-------------------------------------
        
        if ( $this->mm_data['topic_reply'] and $this->mm_data['topic_reply_content'] )
        {
       
        	$this->modfunc->auto_update = FALSE;  // Turn off auto forum re-synch, we'll manually do it at the end
        
        	$this->modfunc->topic_add_reply( 
        									 $this->parser->convert( array(
																		   'TEXT'    => $this->mm_data['topic_reply_content'],
																		   'CODE'    => 1,
																		   'SMILIES' => 1,
															       )      )
										    , array( 0 => array( $this->topic['tid'], $this->forum['id'] ) )
										    , $this->mm_data['topic_reply_postcount']
										   );
		}
		
		//-------------------------------------
        // Move topic?
        //-------------------------------------
        
        if ( $this->mm_data['topic_move'] )
        {
        	//-------------------------------------
        	// Move to forum still exist?
        	//-------------------------------------
        	
        	$DB->query("SELECT id, name, subwrap, sub_can_post FROM ibf_forums WHERE id=".$this->mm_data['topic_move']);
        	
        	if ( $r = $DB->fetch_row() )
        	{
        		if ( $r['subwrap'] == 1 AND $r['sub_can_post'] != 1 )
        		{
        			$DB->query("UPDATE ibf_topic_mmod SET topic_move=0 WHERE mm_id=".$this->mm_id);
        		}
        		else
        		{
        			if ( $r['id'] != $this->forum['id'] )
        			{
        				$this->modfunc->topic_move( $this->topic['tid'], $this->forum['id'], $r['id'], $this->mm_data['topic_move_link']);
        			
        				$this->modfunc->forum_recount( $r['id'] );
        			}
        		}
        	}
        	else
        	{
        		$DB->query("UPDATE ibf_topic_mmod SET topic_move=0 WHERE mm_id=".$this->mm_id);
        	}
        }
        
        //-------------------------------------
        // Recount root forum
        //-------------------------------------
        
        $this->modfunc->forum_recount( $this->forum['id'] );
        
        //-------------------------------------
        // Add mod log
        //-------------------------------------
        
        $this->modfunc->add_moderate_log( $this->forum['id'], $this->topic['tid'], "", $this->topic['title'], "Applied multi-mod: ".$this->mm_data['mm_title'] );
        
        //-------------------------------------
        // Redirect back with nice fluffy message
        //-------------------------------------
        
        $print->redirect_screen( sprintf($ibforums->lang['mm_applied'], $this->mm_data['mm_title'] ), "showforum=".$this->forum['id'] );
		          
	}
	
	
	
}

?>





