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
|   > ACP Prefs functions
|   > Module written by Matt Mecham
|   > Date started: 22nd May 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$idx = new ad_prefs();


class ad_prefs {

	var $base_url;

	function ad_prefs() {
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
		// Show the wee form
		//---------------------------------------
		
		if ( $IN['set'] == 1 )
		{
			$this->msg = 'Savings set';
			
			if ( $IN['tx'] == "" or $IN['ty'] == "" )
			{
				$this->msg = 'Please complete the form';
				
				print $this->get_html();
			}
			else
			{
				$std->my_setcookie( 'acpprefs', $IN['menu'] .','. $IN['tx'] .','. $IN['ty'] .','. $IN['preview']);
				
				$this->msg = 'Settings saved';
				
				$this->tx = $IN['tx'];
				$this->ty = $IN['ty'];
				
				if ( $IN['menu'] )
				{
					$this->s_yes = 'selected';
					$this->s_no  = '';
				}
				else
				{
					$this->s_yes = '';
					$this->s_no  = 'selected';
				}
				
				if ( $IN['preview'] )
				{
					$this->p_yes = 'selected';
					$this->p_no  = '';
				}
				else
				{
					$this->p_yes = '';
					$this->p_no  = 'selected';
				}
			
				print $this->get_html();
			}
		

		}
		else
		{
			$state = 0;
			$tx    = 80;
			$ty    = 40;
			
			if ( $cookie = $std->my_getcookie('acpprefs') )
			{
				list( $state, $tx, $ty, $prev_show ) = explode( ",", $cookie );
			}
			
			$this->tx = $tx;
			$this->ty = $ty;
			
			if ( $state )
			{
				$this->s_yes = 'selected';
				$this->s_no  = '';
			}
			else
			{
				$this->s_yes = '';
				$this->s_no  = 'selected';
			}
			
			if ( $prev_show )
			{
				$this->p_yes = 'selected';
				$this->p_no  = '';
			}
			else
			{
				$this->p_yes = '';
				$this->p_no  = 'selected';
			}
			
			print $this->get_html();
		
		}
		
	}
	
	function get_html()
	{
		global $SKIN;
		
$hit_muhl = <<<EOF
<html>
 <head>
   <title>IPB-ACP Prefs</title>
   <style type='text/css'>
	 BODY {
			font-size: 10px;
			font-family: Verdana, Arial, Sans-Serif;
			color:#000;
			padding:0px;
			margin:5px 5px 5px 5px;
			background-color: #F5F9FD
		  }
		  
	TABLE, TD, TR {
			font-family: Verdana,Arial, Sans-Serif;
			color:#000;
			font-size: 10px;
		  }
		  
	a:link, a:visited, a:active  { color:#000055 }
	a:hover                      { color:#333377;text-decoration:underline }
	input {vertical-align:middle}
	.textinput { background-color: #DFE6EF;; color:Ê#000; font-size:10px; font-family: Verdana,Arial, Sans-Serif; padding:2px; }
					
  </style>
  <script type='text/javascript'>
    var msg = "{$this->msg}";
    
    if ( msg != "" )
    {
    	alert( msg );
    }
  </script>
  </head>
  <body>
  <form action="{$SKIN->base_url}&act=prefs&set=1" method="post">
  <fieldset style='padding:10px'>
   <legend>Your ACP Prefs</legend>
   <strong>Save ACP Menu State</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select class='textinput' name='menu'><option value='1' {$this->s_yes}>Yes</option><option value='0' {$this->s_no}>No</option></select>
   <br /><br />
   <strong>Template's Textbox Size</strong>&nbsp;<input  class='textinput' type='text' size='3' name='tx' value='{$this->tx}' /> <strong>X</strong> <input  class='textinput' type='text' size='3' name='ty' value='{$this->ty}' />
   <br /><br />
   <strong>Show Macro Preview?</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select class='textinput' name='preview'><option value='1' {$this->p_yes}>Yes</option><option value='0' {$this->p_no}>No</option></select>
   <br /><br />
   <center><input type='submit'  class='textinput' value='Save' /></center>
  </fieldset>
  </form>
  </body>
  </html>
EOF;

	return $hit_muhl;
	}
	
	
	
}


?>