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
|   > IPS Remote Call thingy
|   > Module written by Matt Mecham
|   > Date started: 17th October 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_ips();


class ad_ips {

	var $base_url;
	
	var $colours = array();
	
	var $url = "http://www.invisionboard.com/acp/";
	
	var $version = "1.1";

	function ad_ips() {
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
		
			case 'news':
				$this->news();
				break;
				
			case 'updates':
				$this->updates();
				break;
				
			case 'docs':
				$this->docs();
				break;
				
			case 'support':
				$this->support();
				break;
			
			case 'host':
				$this->host();
				break;
				
			case 'purchase':
				$this->purchase();
				break;
				
			//-------------------------
			default:
				exit();
				break;
		}
		
	}
	


	
	function news()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		@header("Location: ".$this->url."?news");
		exit();
	}
	
	function updates()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		//@header("Location: ".$this->url."?updates&version=".$this->version);
		@header("Location: ".$this->url."?updates");
		exit();
	}
	
	function docs()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		@header("Location: ".$this->url."?docs");
		exit();
	}
	
	function support()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		@header("Location: ".$this->url."?support");
		exit();
	}
	
	function host()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		@header("Location: ".$this->url."?host");
		exit();
	}
	
	function purchase()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
	
		@header("Location: ".$this->url."?purchase");
		exit();
	}
	
	

	
	
	
	
	
	
}


?>