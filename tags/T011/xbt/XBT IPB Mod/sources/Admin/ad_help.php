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
|   > Help Control functions
|   > Module written by Matt Mecham
|   > Date started: 2nd April 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_settings();


class ad_settings {

	var $base_url;

	function ad_settings() {
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		//---------------------------------------

		switch($IN['code'])
		{
			case 'edit':
				$this->show_form('edit');
				break;
			case 'new':
				$this->show_form('new');
				break;
			
			case 'doedit':
				$this->doedit();
				break;
				
			case 'donew':
				$this->doadd();
				break;
				
			case 'remove':
				$this->remove();
				break;
			
			//-------------------------
			default:
				$this->list_files();
				break;
		}
		
	}
	
	//-------------------------------------------------------------
	// HELP FILE FUNCTIONS
	//-------------------------------------------------------------
	
	function doedit()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must pass a valid emoticon id, silly!");
		}
		
		$text  = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['text'] ) );
		//$title = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['title'] ) );
		$desc  = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['description'] ) );
		
		$text  = preg_replace( "/\\\/", "&#092;", $text );
		
		$db_string = $DB->compile_db_update_string( array( 'title'       => $IN['title'],
														   'text'        => $text,
														   'description' => $desc,
												  )      );
												  
		$DB->query("UPDATE ibf_faq SET $db_string WHERE id='".$IN['id']."'");
		
		$ADMIN->save_log("Edited help files");
		
		$std->boink_it($SKIN->base_url."&act=help");
		exit();
			
		
	}
	
	//=====================================================
	
	
	function show_form($type='new')
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		$ADMIN->page_detail = "You may add/edit and remove help files below.";
		$ADMIN->page_title  = "Help File Management";
		
		//+-------------------------------
		
		if ($type != 'new')
		{
		
			if ($IN['id'] == "")
			{
				$ADMIN->error("You must pass a valid help file id, silly!");
			}
		
			//+-------------------------------
		
			$DB->query("SELECT * FROM ibf_faq WHERE id='".$IN['id']."'");
		
			if ( ! $r = $DB->fetch_row() )
			{
				$ADMIN->error("We could not find that help file in the database");
			}
		
			//+-------------------------------
			
			$button = 'Edit this Help File';
			$code   = 'doedit';
		}
		else
		{
			$r = array();
			$button = 'Add this Help File';
			$code   = 'donew';
		}
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'code'  , $code ),
												  2 => array( 'act'   , 'help'     ),
												  3 => array( 'id'    , $IN['id'] ),
									     )      );
		
		
		
		$SKIN->td_header[] = array( "&nbsp;"  , "20%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "80%" );
		
		$r['text'] = preg_replace( "/<br>/i", "\n", stripslashes($r['text']) );
 		
 		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( $button );
		
		
		$ADMIN->html .= $SKIN->add_td_row( array( "Help File Title",
												  $SKIN->form_input('title'  , stripslashes($r['title']) ),
										 )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array( "Help File Description",
												  $SKIN->form_textarea('description', stripslashes($r['description']) ),
										 )      );
										 
		$ADMIN->html .= $SKIN->add_td_row( array( "Help File Text",
												  $SKIN->form_textarea('text', $r['text'], "60", "10" ),
										 )      );
										 
		$ADMIN->html .= $SKIN->end_form($button);
										 
		$ADMIN->html .= $SKIN->end_table();
		
		$ADMIN->output();
	
	}
	
	//=====================================================
	
	function remove()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($IN['id'] == "")
		{
			$ADMIN->error("You must pass a valid help file id, silly!");
		}
		
		$DB->query("DELETE FROM ibf_faq WHERE id='".$IN['id']."'");
		
		$ADMIN->save_log("Removed a help file");
		
		$std->boink_it($SKIN->base_url."&act=help");
		exit();
			
		
	}
	
	//=====================================================
	
	function doadd()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS;
		
		if ($IN['title'] == "")
		{
			$ADMIN->error("You must enter a title, silly!");
		}
		
		
		
		$text  = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['text'] ) );
		$title = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['title'] ) );
		$desc  = preg_replace( "/\n/", "<br>", stripslashes($HTTP_POST_VARS['description'] ) );
		
		$text  = preg_replace( "/\\\/", "&#092;", $text );
		
		$db_string = $DB->compile_db_insert_string( array( 'title'       => $title,
														   'text'        => $text,
														   'description' => $desc,
												  )      );
												  
		$DB->query("INSERT INTO ibf_faq (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
		
		$ADMIN->save_log("Added a help file");
		
		$std->boink_it($SKIN->base_url."&act=help");
		exit();
			
		
	}
	
	//=====================================================
	
	function list_files()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		$ADMIN->page_detail = "You may add/edit and remove help files below.";
		$ADMIN->page_title  = "Help File Management";
		
		//+-------------------------------
		
		$SKIN->td_header[] = array( "Title"  , "50%" );
		$SKIN->td_header[] = array( "Edit"   , "30%" );
		$SKIN->td_header[] = array( "Remove" , "20%" );
		
		//+-------------------------------
		
		$ADMIN->html .= $SKIN->start_table( "Current Help Files" );
		
		$DB->query("SELECT * from ibf_faq ORDER BY id ASC");
		
		if ( $DB->get_num_rows() )
		{
			while ( $r = $DB->fetch_row() )
			{
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b>".stripslashes($r['title'])."</b><br>".stripslashes($r['description']),
														  "<center><a href='".$SKIN->base_url."&act=help&code=edit&id={$r['id']}'>Edit</a></center>",
														  "<center><a href='".$SKIN->base_url."&act=help&code=remove&id={$r['id']}'>Remove</a></center>",
												 )      );
												   
			
				
			}
		}
		
		$ADMIN->html .= $SKIN->add_td_basic("<a href='".$SKIN->base_url."&act=help&code=new'>Add New Help File</a>", "center", "title" );
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	
}


?>