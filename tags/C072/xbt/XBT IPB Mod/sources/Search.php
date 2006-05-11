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
|   > Searching procedures
|   > Module written by Matt Mecham
|   > Date started: 24th February 2002
|
|	> Module Version Number: 1.1.0
+--------------------------------------------------------------------------
*/

$idx = new Search;

class Search {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    
    var $first      = 0;
    
    var $search_type = 'posts';
    var $sort_order  = 'desc';
    var $sort_key    = 'last_post';
    var $search_in   = 'posts';
    var $prune       = '30';
    var $st_time     = array();
    var $end_time    = array();
    var $st_stamp    = "";
    var $end_stamp   = "";
    var $result_type = "topics";
    var $parser      = "";
    var $load_lib    = 'search_mysql_man';
    var $lib         = "";
    
    var $mysql_version   = "";
	var $true_version    = "";
    
    function Search()
    {
    	global $ibforums, $DB, $std, $print;
    	
    	if (! $ibforums->vars['allow_search'])
    	{
    		$std->Error( array( LEVEL => 1, MSG => 'search_off') );
    	}
    	
    	if ($ibforums->member['g_use_search'] != 1)
 		{
 			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
    	
    	if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));
        }
    	
    	//---------------------------------------
		// Get the mySQL version.
		// Adapted from phpMyAdmin
		//---------------------------------------
		
		$DB->query("SELECT VERSION() AS version");
		
		if ( ! $row = $DB->fetch_row() )
		{
			$DB->query("SHOW VARIABLES LIKE 'version'");
			$row = $DB->fetch_row();
		}
		
		$this->true_version = $row['version'];
		
		$no_array = explode( '.', preg_replace( "/^(.+?)[-_]?/", "\\1", $row['version']) );
		
		$one   = (!isset($no_array) || !isset($no_array[0])) ? 3  : $no_array[0];
		$two   = (!isset($no_array[1]))                      ? 21 : $no_array[1];
		$three = (!isset($no_array[2]))                      ? 0  : $no_array[2];
		
   		$this->mysql_version = (int)sprintf('%d%02d%02d', $one, $two, intval($three));
    	
    	
    	if ($ibforums->input['CODE'] == "") $ibforums->input['CODE'] = '00';
    	
    	//--------------------------------------------
    	// Sort out the required search library
    	//--------------------------------------------
    	
    	$method = isset($ibforums->vars['search_sql_method']) ? $ibforums->vars['search_sql_method'] : 'man';
    	$sql    = isset($ibforums->vars['sql_driver'])        ? $ibforums->vars['sql_driver']        : 'mysql';
    	
    	$this->load_lib = 'search_'.strtolower($sql).'_'.$method.'.php';
    	
    	require ( "./sources/lib/".$this->load_lib );
    	
    	//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_search', $ibforums->lang_id );
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_forum' , $ibforums->lang_id );
    	
    	$this->html = $std->load_template('skin_search'); 
    	
    	$this->base_url = $ibforums->base_url;
    	
    	//--------------------------------------------
    	// Suck in libby
    	//--------------------------------------------
    	
    	$this->lib = new search_lib(&$this);
    	
    	$ibforums->input['st'] = intval($ibforums->input['st']);
    	
    	if ( $ibforums->input['st'] )
    	{
    		$this->first = $ibforums->input['st'];
    	}
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	if (! isset($ibforums->member['g_use_search']) )
    	{
    		$std->Error( array( LEVEL => 1, MSG => 'cant_use_feature') );
    	}
    	
    	switch($ibforums->input['CODE']) {
    		case '01':
    			$this->do_search();
    			break;
    		case 'getnew':
    			$this->get_new_posts();
    			break;
    		case 'getactive':
    			$this->get_active();
    			break;
    		case 'show':
    			$this->show_results();
    			break;
    		case 'getreplied':
    			$this->get_replies();
    			break;
    		case 'lastten':
    			$this->get_last_ten();
    			break;
    		case 'getalluser':
    			$this->get_all_user();
    			break;
    		case 'simpleresults':
    			$this->show_simple_results();
    			break;
    		case 'explain':
    			$this->show_boolean_explain();
    			break;
    		default:
    			$this->show_form();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
    		
 	}
 	
 	//-----------------------------------------------------
	// Do simple search
	//-----------------------------------------------------
	
	function show_simple_results()
	{
    	global $ibforums, $DB, $std, $print;
    	
    	$result = $this->lib->do_simple_search();
    }
    
    //-----------------------------------------------------
	// Get all posts by a member
	//-----------------------------------------------------
 	
 	function get_all_user() {
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		//------------------------------------
		// Do we have flood control enabled?
		//------------------------------------
		
		if ($ibforums->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $ibforums->member['g_search_flood'];
			
			// Get any old search results..
			
			$DB->query("SELECT id FROM ibf_search_results WHERE (member_id='".$ibforums->member['id']."' OR ip_address='".$ibforums->input['IP_ADDRESS']."') AND search_date > '$flood_time'");
			
			if ( $DB->get_num_rows() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $ibforums->member['g_search_flood']) );
			}
		}
		
		$ibforums->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		$mid    = intval($ibforums->input['mid']);
		
		//------------------------------------
		// Do we have any forums to search in?
		//------------------------------------
		
		if ($forums == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		if ($mid == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
	
		//------------------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//------------------------------------------------
		
		$DB->query("SELECT pid FROM ibf_posts WHERE queued <> 1 AND forum_id IN($forums) AND author_id=$mid");
	
		$max_hits = $DB->get_num_rows();
		
		$posts  = "";
		
		while ($row = $DB->fetch_row() )
		{
			$posts .= $row['pid'].",";
		}
	
		$DB->free_result();
		
		$posts  = preg_replace( "/,$/", "", $posts );
		
		//------------------------------------------------
		// Do we have any results?
		//------------------------------------------------
		
		if ($posts == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------------------
		// If we are still here, store the data into the database...
		//------------------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$str = $DB->compile_db_insert_string( array (
														'id'         => $unique_id,
														'search_date'=> time(),
														'post_id'    => $posts,
														'post_max'   => $max_hits,
														'sort_key'   => $this->sort_key,
														'sort_order' => $this->sort_order,
														'member_id'  => $ibforums->member['id'],
														'ip_address' => $ibforums->input['IP_ADDRESS'],
											   )        );
		
		$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		$print->redirect_screen( $ibforums->lang['search_redirect'] , "act=Search&nav=au&CODE=show&searchid=$unique_id&search_in=posts&result_type=posts" );
		exit();
		
	}
 	
 	//---------------------------------
 	
 	function get_new_posts()
 	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		if ( ! $ibforums->member['id'] )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------
		// Do we have flood control enabled?
		//------------------------------------
		
		if ($ibforums->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $ibforums->member['g_search_flood'];
			
			// Get any old search results..
			
			$DB->query("SELECT id FROM ibf_search_results WHERE (member_id='".$ibforums->member['id']."' OR ip_address='".$ibforums->input['IP_ADDRESS']."') AND search_date > '$flood_time'");
			
			if ( $DB->get_num_rows() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $ibforums->member['g_search_flood']) );
			}
		}
		
		$ibforums->input['forums'] = 'all';
		$ibforums->input['nav']    = 'lv';
		
		$forums = $this->get_searchable_forums();
		
		//------------------------------------
		// Do we have any forums to search in?
		//------------------------------------
		
		if ($forums == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
	
		//------------------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//------------------------------------------------
		
		$DB->query("SELECT t.tid FROM ibf_topics t
					WHERE t.approved=1 AND t.forum_id IN($forums) AND t.last_post > '".$ibforums->member['last_visit']."'");
	
		$max_hits = $DB->get_num_rows();
		
		$posts  = "";
		
		while ($row = $DB->fetch_row() )
		{
			$posts .= $row['tid'].",";
		}
	
		$DB->free_result();
		
		$posts  = preg_replace( "/,$/", "", $posts );
		
		//------------------------------------------------
		// Do we have any results?
		//------------------------------------------------
		
		if ($posts == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------------------
		// If we are still here, store the data into the database...
		//------------------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$str = $DB->compile_db_insert_string( array (
														'id'         => $unique_id,
														'search_date'=> time(),
														'topic_id'    => $posts,
														'topic_max'   => $max_hits,
														'sort_key'   => $this->sort_key,
														'sort_order' => $this->sort_order,
														'member_id'  => $ibforums->member['id'],
														'ip_address' => $ibforums->input['IP_ADDRESS'],
											   )        );
		
		$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		$print->redirect_screen( $ibforums->lang['search_redirect'] , "act=Search&nav=lv&CODE=show&searchid=$unique_id&search_in=topics&result_type=topics" );
		exit();
		
	}
 	
 	
 	//--------------------------------------------------------
 	
 	function get_last_ten()
 	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		$ibforums->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		//------------------------------------
		// Do we have any forums to search in?
		//------------------------------------
		
		if ($forums == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		if ( $read = $std->my_getcookie('topicsread') )
        {
        	$this->read_array = unserialize(stripslashes($read));
        }
	
		//------------------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//------------------------------------------------
		
		$DB->query("SELECT p.*, t.*, f.id as forum_id, f.name as forum_name FROM ibf_forums f, ibf_posts p, ibf_topics t WHERE p.queued <> 1 AND p.forum_id IN($forums) AND p.author_id='".$ibforums->member['id']."' AND t.tid=p.topic_id AND f.id=p.forum_id ORDER BY p.post_date DESC LIMIT 0,10");
	
		if ( $DB->get_num_rows() )
		{
		
			require "./sources/lib/post_parser.php";
       		$this->parser = new post_parser();
       		
			$this->output .= $this->html->start_as_post( array( 'SHOW_PAGES' => $links ) );
			
			while ($row = $DB->fetch_row() )
			{
				$row['keywords'] = $url_words;
				$row['post_date'] = $std->get_date( $row['post_date'],'LONG' );
				
				if ( $ibforums->vars['post_wordwrap'] > 0 )
				{
					$row['post'] = $this->parser->my_wordwrap( $row['post'], $ibforums->vars['post_wordwrap']) ;
				}
			
				$this->output .= $this->html->RenderPostRow( $this->parse_entry($row, 1) );
			}
			
			$this->output .= $this->html->end_as_post(array( 'SHOW_PAGES' => $links ));
		}
		else
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
	
		$DB->free_result();
		
		$this->page_title = $ibforums->lang['nav_lt'];
		
		$this->nav = array( $ibforums->lang['nav_lt'] );
		
	}
 	
 	//--------------------------------------------------------
 	
 	function get_replies()
 	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		//------------------------------------
		// Do we have flood control enabled?
		//------------------------------------
		
		if ($ibforums->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $ibforums->member['g_search_flood'];
			
			// Get any old search results..
			
			$DB->query("SELECT id FROM ibf_search_results WHERE (member_id='".$ibforums->member['id']."' OR ip_address='".$ibforums->input['IP_ADDRESS']."') AND search_date > '$flood_time'");
			
			if ( $DB->get_num_rows() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $ibforums->member['g_search_flood']) );
			}
		}
		
		$ibforums->input['forums'] = 'all';
		$ibforums->input['nav']    = 'lv';
		
		$forums = $this->get_searchable_forums();
		
		//------------------------------------
		// Do we have any forums to search in?
		//------------------------------------
		
		if ($forums == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
	
		//------------------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//------------------------------------------------
		
		$DB->query("SELECT tid FROM ibf_topics WHERE starter_id='".$ibforums->member['id']."'
		            AND last_post > ".$ibforums->member['last_visit']." AND forum_id IN($forums) AND approved=1");
	
		$max_hits = $DB->get_num_rows();
		
		$topics  = "";
		
		while ($row = $DB->fetch_row() )
		{
			$topics .= $row['tid'].",";
		}
	
		$DB->free_result();
		
		$topics  = preg_replace( "/,$/", "", $topics );
		
		//------------------------------------------------
		// Do we have any results?
		//------------------------------------------------
		
		if ($topics == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------------------
		// If we are still here, store the data into the database...
		//------------------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$str = $DB->compile_db_insert_string( array (
														'id'         => $unique_id,
														'search_date'=> time(),
														'topic_id'   => $topics,
														'topic_max'  => $max_hits,
														'sort_key'   => $this->sort_key,
														'sort_order' => $this->sort_order,
														'member_id'  => $ibforums->member['id'],
														'ip_address' => $ibforums->input['IP_ADDRESS'],
											   )        );
		
		$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		$print->redirect_screen( $ibforums->lang['search_redirect'] , "act=Search&nav=gr&CODE=show&searchid=$unique_id&search_in=posts&result_type=topics" );
		exit();
		
	}
	
	//------------------------------------------------
	// Show pop-up window
	//------------------------------------------------
	
	function show_boolean_explain()
 	{
 		global $DB, $std, $ibforums, $print;
 		
 		$print->pop_up_window( $ibforums->lang['be_link'], $this->html->boolean_explain_page() );
		
 	}
	
	//------------------------------------------------
	// Show main form
	//------------------------------------------------
 	
 	
 	function show_form()
 	{
 		global $DB, $std, $ibforums;
 		
 		$last_cat_id = -1;
 		
 		$the_hiddens = "";
		
		$DB->query("SELECT f.id as forum_id, f.parent_id, f.subwrap, f.sub_can_post, f.name as forum_name, f.position, f.read_perms, c.id as cat_id, c.name as cat_name
		            FROM ibf_forums f
		              LEFT JOIN ibf_categories c ON (c.id=f.category)
		              WHERE c.state <> 0
		            ORDER BY c.position, f.position");
		
		$forum_keys = array();
		$cat_keys   = array();
		$children   = array();
		$subs       = array();
		$subwrap    = array();
			
		while ( $i = $DB->fetch_row() )
		{
			$selected = '';
			
			if ($ibforums->input['f'] and $ibforums->input['f'] == $i['forum_id'])
			{
				$selected = ' selected="selected"';
			}
			
			if ( $i['subwrap'] == 1 )
			{
				$is_sub  = $ibforums->lang['is_sub'];
				$sub_css = " class='sub' ";
				$subwrap[ $i['forum_id'] ] = 1;
				
			}
			
			if ($i['subwrap'] == 1 and $i['sub_can_post'] != 1)
			{
				$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "<option value=\"{$i['forum_id']}\"".$sub_css."$selected>&middot;&middot;&nbsp;{$i['forum_name']}$is_sub</option>\n";
			}
			else
			{
				if ( $std->check_perms($i['read_perms']) == TRUE )
				{
					if ($i['parent_id'] > 0)
					{
						$children[ $i['parent_id'] ][] = "<option value=\"{$i['forum_id']}\"$selected>&middot;&middot;&middot;&middot;&nbsp;{$i['forum_name']}</option>\n";
					}
					else
					{
						$forum_keys[ $i['cat_id'] ][$i['forum_id']] = "<option value=\"{$i['forum_id']}\"".$sub_css."$selected>&middot;&middot;&nbsp;{$i['forum_name']}$is_sub</option>\n";
					}
				}
				else
				{
					continue;
				}
			}
			
			if ($last_cat_id != $i['cat_id'])
			{
				$cat_keys[ $i['cat_id'] ] = "<option value=\"c_{$i['cat_id']}\" class='cat'>{$i['cat_name']}</option>\n";
				$last_cat_id = $i['cat_id'];
			}
			
			unset($is_sub);
			unset($sub_css);
		}
		
		foreach($cat_keys as $cat_id => $cat_text)
		{
			if ( is_array( $forum_keys[$cat_id] ) && count( $forum_keys[$cat_id] ) > 0 )
			{
				$the_html .= $cat_text;
				
				foreach($forum_keys[$cat_id] as $idx => $forum_text)
				{
					if ( $subwrap[$idx] != 1 )
					{
						$the_html .= $forum_text;
					}
					else
					{
						if (count($children[$idx]) > 0)
						{
							$the_html .= $forum_text;
							
							foreach($children[$idx] as $ii => $tt)
							{
								$the_html .= $tt;
							}
						}
					}
				}
			}
		}
		
		$init_sel = "";
		
		if ( $ibforums->input['f'] == "" )
		{
			$init_sel = ' selected="selected"';
		}
		
		$forums   = "<select name='forums[]' class='forminput' size='10' multiple='multiple'>\n"
		           ."<option value='all'".$init_sel.">".$ibforums->lang['all_forums']."</option>"
		           . $the_html
		           . "</select>";
		
		if ( $ibforums->input['mode'] == 'simple' )
		{
			if ( $ibforums->vars['search_sql_method'] == 'ftext' )
			{
				$this->output = $this->html->simple_form($forums);
			}
			else
			{
				$this->output = $this->html->Form($forums);
			}
		}
		else if ( $ibforums->input['mode'] == 'adv' )
		{
			$this->output = $this->html->Form($forums);
			
			if ( $ibforums->vars['search_sql_method'] == 'ftext' )
			{
				$this->output = str_replace( "<!--IBF.SIMPLE_BUTTON-->", $this->html->form_simple_button(), $this->output );
			}
		}
		else
		{
			// No mode specified..
			
			if ( $ibforums->vars['search_default_method'] == 'simple' )
			{
				if ( $ibforums->vars['search_sql_method'] == 'ftext' )
				{
					$this->output = $this->html->simple_form($forums);
				}
				else
				{
					$this->output = $this->html->Form($forums);
				}
			}
			else
			{
				// Default..
				
				$this->output = $this->html->Form($forums);
				
				if ( $ibforums->vars['search_sql_method'] == 'ftext' )
				{
					$this->output = str_replace( "<!--IBF.SIMPLE_BUTTON-->", $this->html->form_simple_button(), $this->output );
				}
			}
		}
		
		if ( $this->mysql_version >= 40010 AND $ibforums->vars['search_sql_method'] == 'ftext' )
		{
			$this->output = str_replace( "<!--IBF.BOOLEAN_EXPLAIN-->", $this->html->boolean_explain_link(), $this->output );
		}
		
		$this->page_title = $ibforums->lang['search_title'];
		$this->nav        = array( $ibforums->lang['search_form'] );
		
 	}
 	
 	

	function do_search()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
		
		//------------------------------------
		// Do we have flood control enabled?
		//------------------------------------
		
		if ($ibforums->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $ibforums->member['g_search_flood'];
			
			// Get any old search results..
			
			$DB->query("SELECT id FROM ibf_search_results WHERE (member_id='".$ibforums->member['id']."' OR ip_address='".$ibforums->input['IP_ADDRESS']."') AND search_date > '$flood_time'");
			
			if ( $DB->get_num_rows() )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $ibforums->member['g_search_flood']) );
			}
		}
		
		//------------------------------------------------
		// init main search
		//------------------------------------------------
		
		$result = $this->lib->do_main_search();
		
		//------------------------------------------------
		// Do we have any results?
		//------------------------------------------------
		
		if ($result['topic_id'] == "" and $result['post_id'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//------------------------------------------------
		// If we are still here, store the data into the database...
		//------------------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$str = $DB->compile_db_insert_string( array (
														'id'         => $unique_id,
														'search_date'=> time(),
														'topic_id'   => $result['topic_id'],
														'topic_max'  => $result['topic_max'],
														'sort_key'   => $this->sort_key,
														'sort_order' => $this->sort_order,
														'member_id'  => $ibforums->member['id'],
														'ip_address' => $ibforums->input['IP_ADDRESS'],
														'post_id'    => $result['post_id'],
														'post_max'   => $result['post_max'],
											)        );
		
		$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		$print->redirect_screen( $ibforums->lang['search_redirect'] , "act=Search&CODE=show&searchid=$unique_id&search_in=".$this->search_in."&result_type=".$this->result_type."&highlite=".urlencode(trim($result['keywords'])) );

	}
	
	/******************************************************/
	// Show Results
	// Shows the results of the search
	/******************************************************/
	
	function show_results()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS;
		
        $this->result_type = $ibforums->input['result_type'];
        $this->search_in   = $ibforums->input['search_in'];
		
		//------------------------------------------------
		// We have a search ID, so lets get the parsed results.
		// Delete old search queries (older than 24 hours)
		//------------------------------------------------
		
		$t_time = time() - (60*60*24);
		
		$DB->query("DELETE FROM ibf_search_results WHERE search_date < '$t_time'");
		
		$this->unique_id = $ibforums->input['searchid'];
		
		if ($this->unique_id == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		$DB->query("SELECT * FROM ibf_search_results WHERE id='{$this->unique_id}'");
		$sr = $DB->fetch_row();
		
		$tmp_topics     = $sr['topic_id'];
		$topic_max_hits = "";//$sr['topic_max'];
		$tmp_posts      = $sr['post_id'];
		$post_max_hits  = "";//$sr['post_max'];
		
		$this->sort_order = $sr['sort_order'];
		$this->sort_key   = $sr['sort_key'];
		
		//------------------------------------------------
		// Remove duplicates from the topic_id and post_id
		//------------------------------------------------
		
		$topics = ",";
		$posts  = ",";
		
		foreach( explode( ",", $tmp_topics) as $tid )
		{
			if ( ! preg_match( "/,$tid,/", $topics ) )
			{
				$topics .= "$tid,";
				$topic_max_hits++;
			}
		}
		
		//-------------------------------------
		
		foreach( explode( ",", $tmp_posts) as $pid )
		{
			if ( ! preg_match( "/,$pid,/", $posts ) )
			{
				$posts .= "$pid,";
				$post_max_hits++;
			}
		}
		
		$topics = str_replace( ",,", ",", $topics );
		$posts  = str_replace( ",,", ",", $posts  );
		
		//-------------------------------------
		
		if ($topics == "," and $posts == ",")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		$url_words = $this->convert_highlite_words($ibforums->input['highlite']);
		
		
									  
		if ($this->result_type == 'topics')
		{
			if ($this->search_in == 'titles')
			{
				$this->output .= $this->start_page($topic_max_hits);
				
				$DB->query("SELECT t.*, f.id as forum_id, f.name as forum_name
							FROM ibf_topics t, ibf_forums f
							 WHERE t.tid IN(0{$topics}-1) and f.id=t.forum_id
							 ORDER BY t.pinned DESC, ".$this->sort_key." ".$this->sort_order."
							LIMIT ".$this->first.",25");
			}
			else
			{
				//--------------------------------------------
				// we have tid and pid to sort out, woohoo NOT
				//--------------------------------------------
				
				if ($posts != ",")
				{
					$DB->query("SELECT topic_id FROM ibf_posts WHERE pid IN(0{$posts}0)");
					
					while ( $pr = $DB->fetch_row() )
					{
						if ( ! preg_match( "/,".$pr['topic_id'].",/", $topics ) )
						{
							$topics .= $pr['topic_id'].",";
							$topic_max_hits++;
						}
					}
					
					$topics = str_replace( ",,", ",", $topics );
				}
				
				$this->output .= $this->start_page($topic_max_hits);
							
				$DB->query("SELECT t.*, f.id as forum_id, f.name as forum_name
							FROM ibf_topics t
							 LEFT JOIN ibf_forums f ON (f.id=t.forum_id)
							 WHERE t.tid IN(0{$topics}0)
							 ORDER BY t.pinned DESC, t.".$this->sort_key." ".$this->sort_order."
							LIMIT ".$this->first.",25");
				
			}
			
			//--------------------------------------------
			
			if ( $DB->get_num_rows() )
			{
				while ( $row = $DB->fetch_row() )
				{
					$row['keywords'] = $url_words;
					$this->output .= $this->html->RenderRow( $this->parse_entry($row) );
				
				}
			}
			else
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
			
			//--------------------------------------------
			
			$this->output .= $this->html->end(array( 'SHOW_PAGES' => $this->links ));
		
		}
		else //--------------------------------------------
		{
		
			
			require "./sources/lib/post_parser.php";
       		$this->parser = new post_parser();
       		
			if ($this->search_in == 'titles')
			{
				$this->output .= $this->start_page($topic_max_hits, 1);
				            
				$DB->query("SELECT t.*, p.pid, p.author_id, p.author_name, p.post_date, p.post, f.id as forum_id, f.name as forum_name
				            FROM ibf_topics t
				              LEFT JOIN ibf_posts p ON (t.tid=p.topic_id AND p.new_topic=1)
				              LEFT JOIN ibf_forums f ON (f.id=t.forum_id)
				            WHERE t.tid IN(0{$topics}-1)
				            ORDER BY p.post_date DESC
				            LIMIT ".$this->first.",25");
			}
			else
			{
				if ($topics != ",")
				{
					$DB->query("SELECT pid FROM ibf_posts WHERE topic_id IN(0{$topics}0) AND new_topic=1");
					
					while ( $pr = $DB->fetch_row() )
					{
						if ( ! preg_match( "/,".$pr['pid'].",/", $posts ) )
						{
							$posts .= $pr['pid'].",";
							$post_max_hits++;
						}
					}
					
					$posts = str_replace( ",,", ",", $posts );
				}
				
				$this->output .= $this->start_page($post_max_hits, 1);
				
				$DB->query("SELECT t.*, p.pid, p.author_id, p.author_name, p.post_date, p.post, f.id as forum_id, f.name as forum_name, f.use_html, g.g_dohtml
							FROM ibf_posts p
							  LEFT JOIN ibf_topics t ON (t.tid=p.topic_id)
							  LEFT JOIN ibf_forums f ON (f.id=p.forum_id)
							  LEFT JOIN ibf_members m ON (m.id=p.author_id)
							  LEFT JOIN ibf_groups g ON (m.mgroup=g.g_id)
							WHERE p.pid IN(0{$posts}0)
							ORDER BY p.post_date DESC
							LIMIT ".$this->first.",25");
			}
			
			while ( $row = $DB->fetch_row() )
			{
				$row['keywords']  = $url_words;
				$row['post_date'] = $std->get_date( $row['post_date'],'LONG' );
				
				//--------------------------------------------------------------
				// Parse HTML tag on the fly
				//--------------------------------------------------------------
				
				if ( $row['use_html'] == 1 )
				{
					// So far, so good..
					
					if ( stristr( $row['post'], '[dohtml]' ) )
					{
						// [doHTML] tag found..
						
						$parse = ($row['use_html'] AND $row['g_dohtml']) ? 1 : 0;
						
						$row['post'] = $this->parser->post_db_parse($row['post'], $parse );
					}
				}
				
				//--------------------------------------------------------------
				// Do word wrap?
				//--------------------------------------------------------------
				
				if ( $ibforums->vars['post_wordwrap'] > 0 )
				{
					$row['post'] = $this->parser->my_wordwrap( $row['post'], $ibforums->vars['post_wordwrap']) ;
				}
				
				$this->output .= $this->html->RenderPostRow( $this->parse_entry($row, 1) );
			
			}
			
			$this->output .= $this->html->end_as_post(array( 'SHOW_PAGES' => $this->links ));
		}
		
		$this->page_title = $ibforums->lang['search_results'];
		
		if ( $ibforums->input['nav'] == 'lv' )
		{
			$this->nav = array( $ibforums->lang['nav_since_lv'] );
		}
		else if ( $ibforums->input['nav'] == 'lt' )
		{
			$this->nav = array( $ibforums->lang['nav_lt'] );
		}
		else if ( $ibforums->input['nav'] == 'au' )
		{
			$this->nav = array( $ibforums->lang['nav_au'] );
		}
		else
		{
			$this->nav = array( "<a href='{$this->base_url}&act=Search'>{$ibforums->lang['search_form']}</a>", $ibforums->lang['search_title'] );
		}
		
		
	}
	
	function start_page($amount, $is_post = 0)
	{
		global $ibforums, $DB, $std;
		
		$url_words = $this->convert_highlite_words($ibforums->input['highlite']);
		
		$this->links = $std->build_pagelinks( array( TOTAL_POSS  => $amount,
											   PER_PAGE    => 25,
											   CUR_ST_VAL  => $this->first,
											   L_SINGLE    => "",
											   L_MULTI     => $ibforums->lang['search_pages'],
											   BASE_URL    => $this->base_url."act=Search&nav=".$ibforums->input['nav']."&CODE=show&searchid=".$this->unique_id."&search_in=".$this->search_in."&result_type=".$this->result_type."&hl=".$url_words,
											 )
									  );
									  
		if ($is_post == 0)
		{
			return $this->html->start( array( 'SHOW_PAGES' => $this->links ) );
		}
		else
		{
			return $this->html->start_as_post( array( 'SHOW_PAGES' => $this->links ) );
		}
			
	}

	/******************************************************/
	// Get active
	// Show all topics posted in / created between a user
	// definable amount of days..
	/******************************************************/
	
	function get_active() {
		global $ibforums, $DB, $std, $HTTP_POST_VARS;
		
		
		//------------------------------------
		// If we don't have a search ID (searchid)
		// then it's a fresh query.
		//
		//------------------------------------
		
		if (! isset($ibforums->input['searchid']) )
		{
		
			//------------------------------------
			// Do we have any start date input?
			//------------------------------------
			
			if ($ibforums->input['st_day'] == "")
			{
				// No? Lets work out the start date as 24hrs ago
				$ibforums->input['st_day'] = 1;
				$this->st_stamp = time() - (60*60*24);
				
			}
			else
			{
				$ibforums->input['st_day'] = preg_replace( "/s/", "", $ibforums->input['st_day']);
				$this->st_stamp = time() - (60*60*24*$ibforums->input['st_day']);
			}
			
			
			//------------------------------------
			// Do we have any END date input?
			//------------------------------------
			
			if ($ibforums->input['end_day'] == "")
			{
				// No? Lets work out the end date as now
				
				$this->end_stamp = time();
				$ibforums->input['end_day'] = 0;
				
			}
			else
			{
				$ibforums->input['end_day'] = preg_replace( "/e/", "", $ibforums->input['end_day']);
				$this->end_stamp = time() - (60*60*24*$ibforums->input['end_day']);
			}
			
			
			//------------------------------------
			// Synchronise our input data
			//------------------------------------
			
			$ibforums->input['forums'] = 'all';
			
			$forums = $this->get_searchable_forums();
			
			//------------------------------------
			// Do we have any forums to search in?
			//------------------------------------
			
			if ($forums == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
			}
		
			
			$query = "SELECT DISTINCT(t.tid)
					  FROM ibf_posts p
					    LEFT JOIN ibf_topics t ON ( t.approved=1 and p.topic_id=t.tid)
					  WHERE p.post_date BETWEEN ".$this->st_stamp." AND ".$this->end_stamp."
					    AND p.forum_id IN($forums)
					    AND p.queued <> 1
					  ORDER BY t.last_post DESC
					  LIMIT 0,200";
					  
			//------------------------------------------------
			// Get the topic ID's to serialize and store into
			// the database
			//------------------------------------------------
			
			$DB->query($query);
		
			$max_hits = $DB->get_num_rows();
		
			$topics = "";
			
			while ($row = $DB->fetch_row() )
			{
				$topics .= $row['tid'].",";
			}
		
			$DB->free_result();
			
			$topics = preg_replace( "/,$/", "", $topics );
			
			//------------------------------------------------
			// Do we have any results?
			//------------------------------------------------
			
			if ($topics == "")
			{
				$this->output .= $this->html->active_start( array( 'SHOW_PAGES' => "" ) );
				$this->output .= $this->html->active_none();
				$this->output .= $this->html->end("");
				$this->page_title = $ibforums->lang['search_results'];
				$this->nav        = array( "<a href='{$this->base_url}&act=Search'>{$ibforums->lang['search_form']}</a>", $ibforums->lang['search_title'] );
				return ""; // return empty handed
			}
			
			//------------------------------------------------
			// If we are still here, store the data into the database...
			//------------------------------------------------
			
			$unique_id = md5(uniqid(microtime(),1));
			
			$str = $DB->compile_db_insert_string( array (
														'id'         => $unique_id,
														'search_date'=> time(),
														'topic_id'   => $topics,
														'topic_max'  => $max_hits,
														'sort_key'   => $this->sort_key,
														'sort_order' => $this->sort_order,
														'member_id'  => $ibforums->member['id'],
														'ip_address' => $ibforums->input['IP_ADDRESS'],
											   )        );
		
			$DB->query("INSERT INTO ibf_search_results ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
						
		}
		else 
		{
			//------------------------------------------------
			// We have a search ID, so lets get the parsed results.
			// Delete old search queries (older than 24 hours)
			//------------------------------------------------
			
			$t_time = time() - (60*60*24);
			
			$this->first = intval($ibforums->input['st']) != "" ? intval($ibforums->input['st']) : 0;
			
			$DB->query("DELETE FROM ibf_search_results WHERE search_date < '$t_time'");
			
			$unique_id = $ibforums->input['searchid'];
			
			$DB->query("SELECT * FROM ibf_search_results WHERE id='$unique_id'");
			$sr = $DB->fetch_row();
			
			$topics   = $sr['topic_id'];
			$max_hits = $sr['topic_max'];
			
			$this->sort_order = $sr['sort_order'];
			$this->sort_key   = $sr['sort_key'];
			
			if ($topics == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
		}
		
		// Our variables are centralised, lets get the array slice depending on our $this->first
		// position.
		
		$topic_string = implode( "," , array_slice( explode(",",$topics), $this->first, 25 ) );
		
		$topic_string = str_replace(  " "   , "", $topic_string );
		$topic_string = preg_replace( "/,$/", "", $topic_string );
			
		$url_words = urlencode(trim($keywords));
			
		$links = $std->build_pagelinks( array( TOTAL_POSS  => $max_hits,
											   PER_PAGE    => 25,
											   CUR_ST_VAL  => $this->first,
											   L_SINGLE    => "",
											   L_MULTI     => $ibforums->lang['search_pages'],
											   BASE_URL    => $this->base_url."act=Search&CODE=getactive&searchid=$unique_id",
											 )
									  );
									  
		
		
		$this->output .= $this->html->active_start( array( 'SHOW_PAGES' => $links ) );
		
		// Regex in our selected values.
		
		$this->output = preg_replace( "/(<option value='s".$ibforums->input['st_day']."')/" , "\\1 selected", $this->output );
		$this->output = preg_replace( "/(<option value='e".$ibforums->input['end_day']."')/", "\\1 selected", $this->output );
		
		$DB->query("SELECT t.*, f.id as forum_id, f.name as forum_name
		             FROM ibf_topics t, ibf_forums f
		            WHERE t.tid IN($topic_string) and f.id=t.forum_id
		            ORDER BY ".$this->sort_key." ".$this->sort_order." LIMIT 0,25");
		
		while ( $row = $DB->fetch_row() )
		{
			$row['keywords'] = $url_words;
			$this->output .= $this->html->RenderRow( $this->parse_entry($row) );
		
		}
		
		$this->page_title = $ibforums->lang['search_results'];
		$this->nav        = array( "<a href='{$this->base_url}act=Search'>{$ibforums->lang['search_form']}</a>", $ibforums->lang['search_title'] );
		
		$this->output .= $this->html->end( array( 'SHOW_PAGES' => $links ) );
		
	}
    
    
    
    
    
	function parse_entry($topic, $view_as_post=0) {
		global $DB, $std, $ibforums;
		
		$topic['last_text']   = $ibforums->lang[last_post_by];
		
		$topic['last_poster'] = ($topic['last_poster_id'] != 0)
								? "<b><a href='{$this->base_url}showuser={$topic['last_poster_id']}'>{$topic['last_poster_name']}</a></b>"
								: "-".$topic['last_poster_name']."-";
								
		$topic['starter']     = ($topic['starter_id']     != 0)
								? "<a href='{$this->base_url}showuser={$topic['starter_id']}'>{$topic['starter_name']}</a>"
								: "-".$topic['starter_name']."-";
	 
		if ($topic['poll_state'])
		{
			$topic['prefix']     = $ibforums->vars['pre_polls'].' ';
		}
	
		$topic['folder_img']     = $std->folder_icon($topic, "", $this->read_array[$topic['tid']]);
		
		$topic['topic_icon']     = $topic['icon_id']  ? '<img src="'.$ibforums->vars['img_url'] . '/icon' . $topic['icon_id'] . '.gif" border="0" alt="">'
													  : '&nbsp;';
															  
		if ($topic['pinned'])
		{
			$topic['topic_icon'] = "<{B_PIN}>";
			$topic['prefix']     = $ibforums->vars['pre_pinned'];
		}
		
		$topic['topic_start_date'] = $std->get_date( $topic['start_date'], 'LONG' );
	
	
		$pages = 1;
		
		if ($topic['posts'])
		{
			if ( (($topic['posts'] + 1) % $ibforums->vars['display_max_posts']) == 0 )
			{
				$pages = ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'];
			}
			else
			{
				$number = ( ($topic['posts'] + 1) / $ibforums->vars['display_max_posts'] );
				$pages = ceil( $number);
			}
			
		}
		
		if ($pages > 1)
		{
			$topic['PAGES'] = "<span class='small'>({$ibforums->lang['topic_sp_pages']} ";
			for ($i = 0 ; $i < $pages ; ++$i ) {
				$real_no = $i * $ibforums->vars['display_max_posts'];
				$page_no = $i + 1;
				if ($page_no == 4) {
					$topic['PAGES'] .= "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;st=" . ($pages - 1) * $ibforums->vars['display_max_posts'] . "&hl={$topic['keywords']}'>...$pages </a>";
					break;
				} else {
					$topic['PAGES'] .= "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;st=$real_no&amp;hl={$topic['keywords']}'>$page_no </a>";
				}
			}
			$topic['PAGES'] .= ")</span>";
		}
		
		if ($topic['posts'] < 0) $topic['posts'] = 0;
		
		$last_time = $this->read_array[ $topic['tid'] ] > $ibforums->input['last_visit'] ? $this->read_array[ $topic['tid'] ] : $ibforums->input['last_visit'];
		
		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			$topic['go_last_page'] = "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;view=getlastpost'><{GO_LAST_ON}></a>";
			$topic['go_new_post']  = "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;view=getnewpost'><{NEW_POST}></a>";
		
		}
		else
		{
			$topic['go_last_page'] = "<a href='{$this->base_url}showtopic={$topic['tid']}&amp;view=getlastpost'><{GO_LAST_OFF}></a>";
			$topic['go_new_post']  = "";
		}
		
		// Do the quick goto last page icon stuff
		
		$maxpages = ($pages - 1) * $ibforums->vars['display_max_posts'];
		if ($maxpages < 0) $maxpages = 0;
		
		$topic['last_post']  = $std->get_date($topic['last_post'], 'LONG');
			
		if ($topic['state'] == 'link')
		{
			$t_array = explode("&", $topic['moved_to']);
			$topic['tid']       = $t_array[0];
			$topic['forum_id']  = $t_array[1];
			$topic['title']     = $topic['title'];
			$topic['views']     = '--';
			$topic['posts']     = '--';
			$topic['prefix']    = $ibforums->vars['pre_moved']." ";
			$topic['go_new_post'] = "";
		}
		
		if ($topic['pinned'] == 1)
		{
			$topic['prefix']     = $ibforums->vars['pre_pinned'];
			$topic['topic_icon'] = "<{B_PIN}>";
			
		}
		
		if ($view_as_post == 1)
		{
			if ( $ibforums->vars['search_post_cut'] )
			{
				$topic['post'] = substr( $this->parser->unconvert($topic['post'] ), 0, $ibforums->vars['search_post_cut']) . '...';
				$topic['post'] = str_replace( "\n", "<br />", $topic['post'] );
			}
			
			if ($topic['author_id'])
			{
				$topic['author_name'] = "<b><a href='{$this->base_url}showuser={$topic['author_id']}'>{$topic['author_name']}</a></b>";
			}
			
			// Highlighting?
			
			if ($topic['keywords'])
			{
			
				$keywords = str_replace( "+", " ", $topic['keywords'] );
				
				if ( preg_match("/,(and|or),/i", $keywords) )
				{
					while ( preg_match("/,(and|or),/i", $keywords, $match) )
					{
						$word_array = explode( ",".$match[1].",", $keywords );
						
						if (is_array($word_array))
						{
							foreach ($word_array as $keywords)
							{
								$topic['post'] = preg_replace( "/(^|\s)(".preg_quote($keywords, "/").")(\s|$)/i", "\\1<span class='searchlite'>\\2</span>\\3", $topic['post'] );
							}
						}
					}
				}
				else
				{
					$topic['post'] = preg_replace( "/(^|\s)(".preg_quote($keywords, "/").")(\s|,|$)/i", "\\1<span class='searchlite'>\\2</span>\\3", $topic['post'] );
				}
			}
		}
		
		$topic['posts'] = $std->do_number_format($topic['posts']);
		$topic['views'] = $std->do_number_format($topic['views']);
		return $topic;
	}
        
     
        
    function filter_keywords($words="", $name=0) {
    
    	// force to lowercase and swop % into a safer version
    	
    	$words = trim( strtolower( str_replace( "%", "\\%", $words) ) );
    	
    	// Remove trailing boolean operators
    	
    	$words = preg_replace( "/\s+(and|or)$/" , "" , $words );
    	
    	// Swop wildcard into *SQL percent
    	
    	//$words = str_replace( "*", "%", $words );
    	
    	// Make safe underscores
    	
    	$words = str_replace( "_", "\\_", $words );
    	
    	$words = str_replace( '|', "&#124;", $words );
    	
    	// Remove crap
    	
    	if ($name == 0)
    	{
    		$words = preg_replace( "/[\|\[\]\{\}\(\)\,:\\\\\/\"']|&quot;/", "", $words );
    	}
    	
    	// Remove common words..
    	
    	$words = preg_replace( "/^(?:img|quote|code|html|javascript|a href|color|span|div)$/", "", $words );
    	
    	return " ".preg_quote($words)." ";
    }
    
    
    function filter_ftext_keywords($words="") {
    
    	// force to lowercase and swop % into a safer version
    	
    	$words = trim($words);
    	$words = str_replace( '|', "&#124;", $words );
    	
    	// Remove crap
    	
    	$words = str_replace( "&quot;", '"', $words );
    	//$words = str_replace( "&lt;"  , "<", $words );
    	$words = str_replace( "&gt;"  , ">", $words );
    	$words = str_replace( "%"     , "" , $words );
    	
    	// Remove common words..
    	
    	$words = preg_replace( "/^(?:img|quote|code|html|javascript|a href|color|span|div)$/", "", $words );
    	
    	return $words;
    
    }
    
    //------------------------------------------------------
    // Make the hl words nice and stuff
    //------------------------------------------------------
    
    function convert_highlite_words($words="")
    {
    	$words = trim(urldecode($words));
    	
    	// Convert booleans to something easy to match next time around
    	
    	$words = preg_replace("/\s+(and|or)(\s+|$)/i", ",\\1,", $words);
    	
    	// Convert spaces to plus signs
    	
    	$words = preg_replace("/\s/", "+", $words);
    	
    	return $words;
    }
        
    //------------------------------------------------------
    // Get the searchable forums
    //------------------------------------------------------    
        
    function get_searchable_forums()
    {
    	global $ibforums, $DB, $std, $HTTP_POST_VARS;
    	
    	$forum_array  = array();
    	$forum_string = "";
    	$sql_query    = "";
    	$check_sub    = 0;
    	
    	$cats         = array();
    	$forums       = array();
    	
    	// If we have an array of "forums", loop
    	// through and build our *SQL IN( ) statement.
    	
    	//------------------------------------------------
    	// Check for an array
    	//------------------------------------------------
    	
    	if ( is_array( $HTTP_POST_VARS['forums'] )  )
    	{
    	
    		if ( in_array( 'all', $HTTP_POST_VARS['forums'] ) )
    		{
    			//--------------------------------------------
    			// Searching all forums..
    			//--------------------------------------------
    			
    			$sql_query = "SELECT c.state, f.id, f.read_perms, f.password FROM ibf_forums f, ibf_categories c WHERE c.id=f.category AND c.state <> 0";
    		
    		}
    		else
    		{
				//--------------------------------------------
				// Go loopy loo
				//--------------------------------------------
				
				foreach( $HTTP_POST_VARS['forums'] as $l )
				{
					if ( preg_match( "/^c_/", $l ) )
					{
						$cats[] = intval( str_replace( "c_", "", $l ) );
					}
					else
					{
						$forums[] = intval($l);
					}
				}
				
				//--------------------------------------------
				// Do we have cats? Give 'em to Charles!
				//--------------------------------------------
				
				if ( count( $cats ) )
				{
					$sql_query = "SELECT id, read_perms, password, subwrap from ibf_forums WHERE category IN(".implode(",",$cats).")";
					$boolean   = "OR";
				}
				else
				{
					$sql_query = "SELECT id, read_perms, password, subwrap from ibf_forums";
					$boolean   = "WHERE";
				}
				
				if ( count( $forums ) )
				{
					if ($ibforums->input['searchsubs'] == 1)
					{
						$sql_query .= " $boolean (id IN(".implode(",",$forums).") or parent_id IN(".implode(",",$forums).") )";
					}
					else
					{
						$sql_query .= " $boolean id IN(".implode(",",$forums).")";
					}
				}
				
				if ( $sql_query == "" )
				{
					// Return empty..
					
					return;
				}
    		}
    		
    		//--------------------------------------------
    		// Run query and finish up..
    		//--------------------------------------------
			
			$DB->query( $sql_query );
				
			while ($i = $DB->fetch_row())
			{
				if ( $this->check_access($i) )
				{
					$forum_array[] = $i['id'];
				}
			}
		}
		else
		{
			//--------------------------------------------
			// Not an array...
			//--------------------------------------------
			
			if ( $ibforums->input['forums'] == 'all' )
			{
				$DB->query("SELECT c.state, f.id, f.read_perms, f.password FROM ibf_forums f, ibf_categories c WHERE c.id=f.category AND c.state <> 0");
				
				while ($i = $DB->fetch_row())
				{
					if ( $this->check_access($i) )
					{
						$forum_array[] = $i['id'];
					}
				}
			}
			else
			{
				if ( $ibforums->input['forums'] != "" )
				{
					$l = $ibforums->input['forums'];
					
					//--------------------------------------------
					// Single  Cat
					//--------------------------------------------
					
					if ( preg_match( "/^c_/", $l ) )
					{
						$c = intval( str_replace( "c_", "", $l ) );
						
						if ($c)
						{
							$DB->query("SELECT id, read_perms, password FROM ibf_forums WHERE category=$c");
							
							while ($i = $DB->fetch_row())
							{
								if ( $this->check_access($i) )
								{
									$forum_array[] = $i['id'];
								}
							}
						}
					}
					else
					{
						//--------------------------------------------
						// Single forum
						//--------------------------------------------
					
						$f = intval($l);
						
						if ($f)
						{
							$qe = ($ibforums->input['searchsubs'] == 1) ? " OR parent_id=$f " : "";
							
							$DB->query("SELECT id, read_perms, password FROM ibf_forums WHERE id=$f".$qe);
							
							while ($i = $DB->fetch_row())
							{
								if ( $this->check_access($i) )
								{
									$forum_array[] = $i['id'];
								}
							}
						}
					}
				}
			}
		}
    					
    	$forum_string = implode( "," , $forum_array );
    	
    	return $forum_string;
    	
    }
        
    
    function check_access($i)
    {
    	global $std, $ibforums;
    	
    	$can_read = TRUE;
        
        if ($i['password'] != "")
		{
			if ( ! $c_pass = $std->my_getcookie('iBForum'.$i['id']) )
			{
				$can_read = FALSE;
			}
		
			if ( $c_pass == $i['password'] )
			{
				$can_read = TRUE;
			}
			else
			{
				$can_read = FALSE;
			}
		}
		
		if ($can_read == TRUE)
		{
			if ( $std->check_perms($i['read_perms']) == TRUE )
			{
				$can_read = TRUE;
			}
			else
			{
				$can_read = FALSE;
			}
		}
		
		return $can_read;
	}
        
}

?>
