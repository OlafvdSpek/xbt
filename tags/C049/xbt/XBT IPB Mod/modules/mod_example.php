<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.2 Module File
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > Example Module Loader File
|   > Module written by Matt Mecham
|   > Date started: 7th July 2003
|
+--------------------------------------------------------------------------
*/

//=====================================
// Define class, this must be the same
// in all modules
//=====================================

class module extends module_loader
{

	//=====================================
	// Define vars if required
	//=====================================
	
	var $class  = "";
	var $module = "";
	var $html   = "";
	
	var $result = "";
	
	//=====================================
	// Constructer, called and run by IPB
	//=====================================
	
	function module()
	{
		global $ibforums, $DB, $std;
		
		//=====================================
		// Do any set up here, like load lang
		// skin files, etc
		//=====================================
		
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_boards', $ibforums->lang_id);
        $this->html     = $std->load_template('skin_boards');
		
		//=====================================
		// Set up structure
		//=====================================
		
		switch( $ibforums->input['cmd'] )
		{
			case 'dosomething':
				$this->do_something();
				break;
				
			default:
				$this->do_something();
				break;
		}
		
		print $this->result;
		
		exit();
		
	}
	
	
	
	//------------------------------------------
	// do_something
	// 
	// Test sub, show if admin or not..
	//
	//------------------------------------------
	
	function do_something()
	{
		global $ibforums, $DB, $std;
		
		if ( $ibforums->member['mgroup'] == $ibforums->vars['admin_group'] )
		{
			$this->result = "You're an admin!";
		}
		else
		{
			$this->result = "You're not an admin!";
		}
	}
	
	
}


?>