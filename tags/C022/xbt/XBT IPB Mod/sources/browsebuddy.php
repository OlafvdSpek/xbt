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
|   > Browse Buddy Module
|   > Module written by Matt Mecham
|   > Date started: 2nd July 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new buddy;

class buddy {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";


    
    function buddy() {
    	global $ibforums, $DB, $std, $print;
    	
    	//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_buddy', $ibforums->lang_id );
    	
    	$this->html = $std->load_template('skin_buddy');
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	switch($ibforums->input['code']) {
    		
    		default:
    			$this->splash();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$this->output = str_replace( "<!--CLOSE.LINK-->", $this->html->closelink(), $this->output );
    	
    	$print->pop_up_window($ibforums->lang['page_title'], $this->html->buddy_js().$this->output);
       
    		
 	}
 	
 	function splash() {
 		global $ibforums, $DB, $std;
 		
 		//--------------------------------------------
 		// Is this a guest? If so, get 'em to log in.
 		//--------------------------------------------
 		
 		if ( ! $ibforums->member['id'] )
 		{
 			$this->output = $this->html->login();
 			return;
 		}
 		else
 		{
 		
 			//--------------------------------------------
 			// Get the forums we're allowed to search in
 			//--------------------------------------------
 			
 			$allow_forums   = array();
 			
 			$allow_forums[] = '0';
 			
 			$DB->query("SELECT id, read_perms, password FROM ibf_forums");
 			
 			while( $i = $DB->fetch_row() )
 			{
 				$pass = 1;
				
				if ($i['password'] != "")
				{
					if ( ! $c_pass = $std->my_getcookie('iBForum'.$i['id']) )
					{
						$pass = 0;
					}
				
					if ( $c_pass == $i['password'] )
					{
						$pass = 1;
					}
					else
					{
						$pass = 0;
					}
				}
				
				if ($pass == 1)
				{
					if ( $std->check_perms($i['read_perms']) == TRUE )
					{
						$allow_forums[] = $i['id'];
					}
				}
 			}
 			
 			$forum_string = implode( ",", $allow_forums );
 			
 			//--------------------------------------------
 			// Get the number of posts since the last visit.
 			//--------------------------------------------
 			
 			if (! $ibforums->member['last_visit'] )
 			{
 				$ibforums->member['last_visit'] = time() - 3600;
 			}
 			
 			$DB->query("SELECT COUNT(pid) as posts FROM ibf_posts WHERE post_date > '".$ibforums->member['last_visit']."' AND queued <> 1 AND forum_id IN($forum_string)");
 			
 			$posts = $DB->fetch_row();
 			
 			$posts_total = ($posts['posts'] < 1) ? 0 : $posts['posts'];
 			
 			//-----------------------------------------------------------------------
 			// Get the number of posts since the last visit to topics we've started.
 			//-----------------------------------------------------------------------
 			
 			$DB->query("SELECT COUNT(tid) as replies
 						FROM ibf_topics WHERE last_post > '".$ibforums->member['last_visit']."'
 						AND approved=1 AND forum_id IN($forum_string)
 						AND posts > 0
 						AND starter_id='".$ibforums->member['id']."'");
 			
 			$topic = $DB->fetch_row();
 			
 			$topics_total = ($topic['replies'] < 1) ? ucfirst($ibforums->lang['none']) : $topic['replies'];
 			
 			$text = $ibforums->lang['no_new_posts'];
 			
 			if ($posts_total > 0)
 			{
 				$ibforums->lang['new_posts']  = sprintf($ibforums->lang['new_posts'] , $posts_total  );
 				$ibforums->lang['my_replies'] = sprintf($ibforums->lang['my_replies'], $topics_total );
 				
 				$ibforums->lang['new_posts'] .= $this->html->append_view("&act=Search&CODE=getnew");
 				
 				if ($topic['replies'] > 0)
 				{
 					$ibforums->lang['my_replies'] .= $this->html->append_view("&act=Search&CODE=getreplied");
 				}
 				
 				$text = $this->html->build_away_msg();
 			}
 			
 			
 			$this->output = $this->html->main($text);
 		}
 		
 		
 	}
	 
 	
        
}

?>
