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
|
|   > Module Loader File
|   > Module written by Matt Mecham
|   > Date started: 7th July 2003
|
+--------------------------------------------------------------------------
|
| USAGE:
| ------
|
| This is a module loader file
| example: index.php?act=module&module=register&var=foo
| 
| Looks for a file called "mod_register.php" and runs it
|
| $DB, $ibforums, $std are all available and any thing in
| the URL will be in the standard $ibforums->input['var'] format
|
+--------------------------------------------------------------------------
*/

class module_loader
{
	var $class  = "";
	var $module = "";
	
	function module_loader()
	{
		global $ibforums, $DB, $std;
		
		$this->module = $this->_name_cleaner($ibforums->input['module']);
			
		if ( $this->module == "" )
		{
			$this->_return_dead();
		}
		
		//----------------------------------
		// Does module file exist?
		//----------------------------------
		
		if ( ! @file_exists( ROOT_PATH.'modules/mod_'.$this->module.'.php' ) )
		{
			$this->_return_dead();
		}
		
		//----------------------------------
		// Require and run
		//----------------------------------
		
		require_once( ROOT_PATH.'modules/mod_'.$this->module.'.php' );
		
		$mod_run = new module();
		
		exit();
	}
	
	
	
	//------------------------------------------
	// _name_cleaner
	// 
	// Remove everything bar a - z, 0 - 9  _ -
	//
	//------------------------------------------
	
	function _name_cleaner($name)
	{
		return preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $name );
	}
	
	//------------------------------------------
	// _return_dead
	// 
	// Return to board index
	//
	//------------------------------------------
	
	function _return_dead()
	{
		global $ibforums;
		
		header("Location: ".$ibforums->base_url);
		
		exit();
	}
	
}


?>