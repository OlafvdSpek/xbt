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
|   > Show all emo's / BB Tags module
|   > Module written by Matt Mecham
|   > Date started: 18th April 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new legends;

class legends {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";

    function legends() {
    
    	//------------------------------------------------------
    	// $is_sub is a boolean operator.
    	// If set to 1, we don't show the "topic subscribed" page
    	// we simply end the subroutine and let the caller finish
    	// up for us.
    	//------------------------------------------------------
    
        global $ibforums, $DB, $std, $print, $skin_universal;
        
        $ibforums->lang    = $std->load_words($ibforums->lang, 'lang_legends', $ibforums->lang_id );

    	$this->html = $std->load_template('skin_legends');
    	
    	$this->base_url        = $ibforums->base_url;
    	
    	
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	switch($ibforums->input['CODE'])
    	{
    		case 'emoticons':
    			$this->show_emoticons();
    			break;
    			
    		case 'finduser_one':
    			$this->find_user_one();
    			break;
    			
    		case 'finduser_two':
    			$this->find_user_two();
    			break;
    			
    		case 'bbcode':
    			$this->show_bbcode();
    			break;
    			
    		default:
    			$this->show_emoticons();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
        $print->pop_up_window( $this->page_title, $this->output );
    		
 	}
 	
 	//--------------------------------------------------------------
 	
 	function find_user_one()
 	{
 		global $ibforums, $DB, $std;
 		
 		// entry=textarea&name=carbon_copy&sep=comma
 		
 		$entry = (isset($ibforums->input['entry'])) ? $ibforums->input['entry'] : 'textarea';
 		$name  = (isset($ibforums->input['name']))  ? $ibforums->input['name']  : 'carbon_copy';
 		$sep   = (isset($ibforums->input['sep']))   ? $ibforums->input['sep']   : 'line';
 		
 		$this->output .= $this->html->find_user_one($entry, $name, $sep);
 		
 		$this->page_title = $ibforums->lang['fu_title'];
 		
 	}
 	
 	//--------------------------------------------------------------
 	
 	function find_user_two()
 	{
 		global $ibforums, $DB, $std;
 		
 		$entry = (isset($ibforums->input['entry'])) ? $ibforums->input['entry'] : 'textarea';
 		$name  = (isset($ibforums->input['name']))  ? $ibforums->input['name']  : 'carbon_copy';
 		$sep   = (isset($ibforums->input['sep']))   ? $ibforums->input['sep']   : 'line';
 		
 		//-----------------------------------------
 		// Check for input, etc
 		//-----------------------------------------
 		
 		$ibforums->input['username'] = strtolower(trim($ibforums->input['username']));
 		
 		if ($ibforums->input['username'] == "")
 		{
 			$this->find_user_error('fu_no_data');
 			return;
 		}
 		
 		//-----------------------------------------
 		// Attempt a match
 		//-----------------------------------------
 		
 		$DB->query("SELECT id, name FROM ibf_members WHERE LOWER(name) LIKE '".$ibforums->input['username']."%' LIMIT 0,101");
 		
 		if ( ! $DB->get_num_rows() )
 		{
 			$this->find_user_error('fu_no_match');
 			return;
 		}
 		else if ( $DB->get_num_rows() > 99 )
 		{
 			$this->find_user_error('fu_kc_loads');
 			return;
 		}
 		else
 		{
 			$select_box = "";
 			
 			while ( $row = $DB->fetch_row() )
 			{
 				if ($row['id'] > 0)
 				{
 					$select_box .= "<option value='{$row['name']}'>{$row['name']}</option>\n";
 				}
 			}
 		
 		
 			$this->output .= $this->html->find_user_final($select_box, $entry, $name, $sep);
 		
 			$this->page_title = $ibforums->lang['fu_title'];
 		}
 		
 	}
 	
 	
 	//--------------------------------------------------------------
 	
 	function find_user_error($error)
 	{
 		global $ibforums, $DB, $std;
 		
 		$this->page_title = $ibforums->lang['fu_title'];
 		
 		$this->output = $this->html->find_user_error($ibforums->lang[$error]);
 		
 		return;
 		
 	}
 	
 	
 	//--------------------------------------------------------------
 	
 	function show_emoticons()
 	{
 		global $ibforums, $DB, $std;
 		
 		$this->page_title = $ibforums->lang['emo_title'];
 		
 		$this->output .= $this->html->emoticon_javascript();
 		
 		$this->output .= $this->html->page_header( $ibforums->lang['emo_title'], $ibforums->lang['emo_type'], $ibforums->lang['emo_img'] );
 		
 		$DB->query("SELECT typed, image from ibf_emoticons");
			
		if ( $DB->get_num_rows() )
		{
			while ( $r = $DB->fetch_row() )
			{
				if (strstr( $r['typed'], "&quot;" ) )
				{
					$in_delim  = "'";
					$out_delim = '"';
				}
				else
				{
					$in_delim  = '"';
					$out_delim = "'";
				}
			
				$this->output .= $this->html->emoticons_row( stripslashes($r['typed']), stripslashes($r['image']), $in_delim, $out_delim );
											
			}
		}
		
		$this->output .= $this->html->page_footer();
    	
 	}
 	
 	//--------------------------------------------------------------
 	// Show BBCode Helpy file
 	//--------------------------------------------------------------
 	
 	function show_bbcode()
 	{
 		global $ibforums, $DB, $std;
 		
 		require './sources/lib/post_parser.php';
 		
 		$this->parser = new post_parser();
 		
 		//-------------------------------------------
 		// Array out or stuff here
 		//-------------------------------------------
 		
 		$bbcode = array(
 		
 		0  => array( '[b]', '[/b]', $ibforums->lang['bbc_ex1'] ),
 		1  => array('[s]', '[/s]', $ibforums->lang['bbc_ex1'] ),
 		2  => array('[i]', '[/i]', $ibforums->lang['bbc_ex1'] ),
 		3  => array('[u]', '[/u]', $ibforums->lang['bbc_ex1'] ),
 		4  => array('[email]', '[/email]', 'user@domain.com' ),
 		5  => array('[email=user@domain.com]', '[/email]', $ibforums->lang['bbc_ex2'] ),
 		6  => array('[url]', '[/url]', 'http://www.domain.com' ),
 		7  => array('[url=http://www.domain.com]', '[/url]', $ibforums->lang['bbc_ex2'] ),
 		8  => array('[size=7]', '[/size]'    , $ibforums->lang['bbc_ex1'] ),
 		9  => array('[font=times]', '[/font]', $ibforums->lang['bbc_ex1'] ),
 		10 => array('[color=red]', '[/color]', $ibforums->lang['bbc_ex1'] ),
 		11 => array('[img]', '[/img]', $ibforums->vars['board_url'].'/'.$ibforums->vars['img_url'].'/icon11.gif' ),
 		12 => array('[list]', '[/list]', '[*]List Item [*]List Item' ),
 		13 => array('[list=1]', '[/list]', '[*]List Item [*]List Item' ),
 		14 => array('[list=a]', '[/list]', '[*]List Item [*]List Item' ),
 		15 => array('[list=i]', '[/list]', '[*]List Item [*]List Item' ),
 		16 => array('[quote]', '[/quote]', $ibforums->lang['bbc_ex1'] ),
 		17 => array('[code]', '[/code]', '$this_var = "Hello World!";' ),
 		18 => array('[sql]', '[/sql]', 'SELECT t.tid FROM a_table t WHERE t.val="This Value"' ),
 		19 => array('[html]', '[/html]', '&lt;a href=&quot;test/page.html&quot;&gt;A Test Page&lt;/a&gt;' ),
 		
 		);
 		
 		$this->page_title = $ibforums->lang['bbc_title'];
 		
 		$this->output .= $this->html->bbcode_header();
 		
 		$this->output .= $this->html->page_header( $ibforums->lang['bbc_title'], $ibforums->lang['bbc_before'], $ibforums->lang['bbc_after'] );
 		
		foreach( $bbcode as $bbc )
		{
			$open    = $bbc[0];
			$close   = $bbc[1];
			$content = $bbc[2];
		
			$before = $this->html->wrap_tag($open) . $content . $this->html->wrap_tag($close);
			
			$after = $this->parser->convert( array( 'TEXT' => $open.$content.$close, 'CODE' => 1 ) );
		
			$this->output .= $this->html->bbcode_row( $before, stripslashes($after) );
										
		}
		
		$this->output .= $this->html->page_footer();
    	
 	}
 	
 	
        
}

?>





