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
|   > Registration functions
|   > Module written by Matt Mecham
|   > Date started: 16th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


$idx = new Register;

class Register {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    var $email      = "";
    var $modules    = "";
    
    function Register() {
    	global $ibforums, $DB, $std, $print;
    	
    	//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
		$ibforums->lang = $std->load_words($ibforums->lang, 'lang_register', $ibforums->lang_id );
    	
    	$this->html = $std->load_template('skin_register');
    	
    	$this->base_url        = $ibforums->base_url;
    	$this->base_url_nosess = "{$ibforums->vars['board_url']}/index.{$ibforums->vars['php_ext']}";
    	
    	//--------------------------------------------
    	// Get the emailer module
		//--------------------------------------------
		
		require ROOT_PATH."sources/lib/emailer.php";
		
		$this->email = new emailer();
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
		}
    	
    	//--------------------------------------------
    	// What to do?
    	//--------------------------------------------
    	
    	switch($ibforums->input['CODE'])
    	{
    		case '02':
    			$this->create_account();
    			break;
    			
    		case '03':
    			$this->validate_user();
    			break;

    		case '05':
    			$this->show_manual_form();
    			break;
    			
    		case '06':
    			$this->show_manual_form('lostpass');
    			break;
    			
    		case 'lostpassform':
    			$this->show_manual_form('lostpass');
    			break;
    			
    		case '07':
    			$this->show_manual_form('newemail');
    			break;
    			
    		case '10':
    			$this->lost_password_start();
    			break;
    		case '11':
    			$this->lost_password_end();
    			break;
    			
    		case '12':
    			$this->coppa_perms_form();
    			break;
    			
    		case 'coppa_two':
    			$this->coppa_two();
    			break;
    			
    		case 'image':
    			$this->show_image();
    			break;
    			
    		case 'reval':
    			$this->revalidate_one();
    			break;
    			
    		case 'reval2':
    			$this->revalidate_two();
    			break;

    		default:
    			if ($ibforums->vars['use_coppa'] == 1 and $ibforums->input['coppa_pass'] != 1)
    			{
    				$this->coppa_start();
    			}
    			else
    			{
    				$this->show_reg_form();
    			}
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$print->add_output("$this->output");
        $print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
    		
 	}
 	
 	/*****************************************************/
	// Show "check revalidate form" er.. form. thing.
	// ------------------
	//  
	/*****************************************************/
	
	function revalidate_one($errors="") {
		global $ibforums, $DB;
		
		if ($errors != "")
    	{
    		$this->output .= $this->html->errors( $ibforums->lang[$errors]);
    	}
    	
    	$name = $ibforums->member['id'] == "" ? '' : $ibforums->member['name'];
		
		$this->output     .= $this->html->show_revalidate_form($name);
		$this->page_title = $ibforums->lang['rv_title'];
		$this->nav        = array( $ibforums->lang['rv_title'] );
	
	}
	
	function revalidate_two()
	{
		global $ibforums, $DB, $std, $HTTP_POST_VARS;
		
		//------------------------------------------
		// Check in the DB for entered member name
		//------------------------------------------
		
		if ( $HTTP_POST_VARS['username'] == "" )
		{
			$this->revalidate_one('err_no_username');
			return;
		}
		
		$DB->query("SELECT * FROM ibf_members WHERE LOWER(name)='".strtolower($ibforums->input['username'])."'");
		
		if ( ! $member = $DB->fetch_row() )
		{
			$this->revalidate_one('err_no_username');
			return;
		}
		
		//------------------------------------------
		// Check in the DB for any validations
		//------------------------------------------
		
		$DB->query("SELECT * FROM ibf_validating WHERE member_id=".intval($member['id']));
		
		if ( ! $val = $DB->fetch_row() )
		{
			$this->revalidate_one('err_no_validations');
			return;
		}
		
		//------------------------------------------
		// Which type is it then?
		//------------------------------------------
		
		if ( $val['lost_pass'] == 1 )
		{
			$this->email->get_template("lost_pass");
				
			$this->email->build_message( array(
												'NAME'         => $member['name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform&uid=".$member['id']."&aid=".$val['vid'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $val['vid'],
												'IP_ADDRESS'   => $ibforums->input['IP_ADDRESS'],
											  )
										);
										
			$this->email->subject = $ibforums->lang['lp_subject'].' '.$ibforums->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
		}
		else if ( $val['new_reg'] == 1 )
		{
			$this->email->get_template("reg_validate");
					
			$this->email->build_message( array(
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&uid=".$member['id']."&aid=".$val['vid'],
												'NAME'         => $member['name'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=05",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $val['vid'],
											  )
										);
										
			$this->email->subject = $ibforums->lang['email_reg_subj']." ".$ibforums->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
		}
		else if ( $val['email_chg'] == 1 )
		{
			$this->email->get_template("newemail");
				
			$this->email->build_message( array(
												'NAME'         => $member['name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&type=newemail&uid=".$member['id']."&aid=".$val['vid'],
												'ID'           => $member['id'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=07",
												'CODE'         => $val['vid'],
											  )
										);
										
			$this->email->subject = $ibforums->lang['ne_subject'].' '.$ibforums->vars['board_name'];
			$this->email->to      = $email_one;
		}
		else
		{
			$this->revalidate_one('err_no_validations');
			return;
		}
		
		$this->output .= $this->html->show_revalidated();
		
		$this->page_title = $ibforums->lang['rv_title'];
		$this->nav        = array( $ibforums->lang['rv_title'] );
	}
 	
 	
 	/*****************************************************/
	// Coppa Start
	// ------------------
	// Asks the registree if they are an old git or not
	/*****************************************************/
	
	function coppa_perms_form()
	{
		global $ibforums, $DB, $std;
		
		echo($this->html->coppa_form());
		exit();
	}
	
	
	
	function coppa_start()
	{
		global $ibforums, $DB, $std;
		
		$coppa_date = date( 'j-F y', mktime(0,0,0,date("m"),date("d"),date("Y")-13) );
		
		$ibforums->lang['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$ibforums->base_url}act=Reg&amp;CODE=12'>{$ibforums->lang['coppa_link_form']}</a>", $ibforums->lang['coppa_form_text']);
		
		$this->output .= $this->html->coppa_start($coppa_date);
		
		$this->page_title = $ibforums->lang['coppa_title'];
		
    	$this->nav        = array( $ibforums->lang['coppa_title'] );
 	
 	}
 	
 	function coppa_two()
	{
		global $ibforums, $DB, $std;
		
		$ibforums->lang['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$ibforums->base_url}act=Reg&amp;CODE=12'>{$ibforums->lang['coppa_link_form']}</a>", $ibforums->lang['coppa_form_text']);
		
		$this->output .= $this->html->coppa_two();
		
		$this->page_title = $ibforums->lang['coppa_title'];
		
    	$this->nav        = array( $ibforums->lang['coppa_title'] );
 	
 	}
 	
 	/*****************************************************/
	// lost_password_start
	// ------------------
	// Simply shows the lostpassword form
	// What do you want? Blood?
	/*****************************************************/
	
	function lost_password_start($errors="")
	{
		global $ibforums, $DB, $std;
		
		if ($ibforums->vars['bot_antispam'])
		{
			// Sort out the security code
				
			$r_date = time() - (60*60*6);
			
			// Remove old reg requests from the DB
			
			$DB->query("DELETE FROM ibf_reg_antispam WHERE ctime < '$r_date'");
			
			// Set a new ID for this reg request...
			
			$regid = md5( uniqid(microtime()) );
			
			// Set a new 6 character numerical string
			
			mt_srand ((double) microtime() * 1000000);
			
			$reg_code = mt_rand(100000,999999);
			
			// Insert into the DB
			
			$str = $DB->compile_db_insert_string( array (
															'regid'      => $regid,
															'regcode'    => $reg_code,
															'ip_address' => $ibforums->input['IP_ADDRESS'],
															'ctime'      => time(),
												)       );
												
			$DB->query("INSERT INTO ibf_reg_antispam ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		}
		
		$this->page_title = $ibforums->lang['lost_pass_form'];
		
    	$this->nav        = array( $ibforums->lang['lost_pass_form'] );
    	
    	if ($errors != "")
    	{
    		$this->output .= $this->html->errors( $ibforums->lang[$errors]);
    	}

    	$this->output    .= $this->html->lost_pass_form($regid);
    	
    	if ($ibforums->vars['bot_antispam'] == 'gd')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->html->bot_antispam_gd( $regid ), $this->output );
		}
		else if ($ibforums->vars['bot_antispam'] == 'gif')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->html->bot_antispam( $regid ), $this->output );
		}
    }
    
    
    
    
    function lost_password_end()
    {
    	global $ibforums, $DB, $std, $HTTP_POST_VARS, $print;
    	
    	if ($ibforums->vars['bot_antispam'])
		{
			//--------------------------------------
			// Security code stuff
			//--------------------------------------
			
			if ($ibforums->input['regid'] == "")
			{
				$this->lost_password_start('err_reg_code');
				return;
			}
			
			$DB->query("SELECT * FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['regid']))."'");
			
			if ( ! $row = $DB->fetch_row() )
			{
				$this->show_reg_form('err_reg_code');
				return;
			}
			
			if ( trim( intval($ibforums->input['reg_code']) ) != $row['regcode'] )
			{
				$this->lost_password_start('err_reg_code');
				return;
			}
		}
		
    	//--------------------------------------
    	// Back to the usual programming! :o
    	//--------------------------------------
    	
    	if ($HTTP_POST_VARS['member_name'] == "")
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
    	}
    	
    	//------------------------------------------------------------
		// Check for input and it's in a valid format.
		//------------------------------------------------------------
		
		$member_name = trim(strtolower($ibforums->input['member_name']));
		
		if ($member_name == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
		}
    	
    	//------------------------------------------------------------
		// Attempt to get the user details from the DB
		//------------------------------------------------------------
		
		$DB->query("SELECT name, id, email, mgroup FROM ibf_members WHERE LOWER(name)='$member_name'");
		
		if ( ! $DB->get_num_rows() )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		else
		{
			$member = $DB->fetch_row();
			
			//------------------------------------------------------------
			// Is there a validation key? If so, we'd better not touch it
			//------------------------------------------------------------
			
			if ($member['id'] == "")
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
			}
			
			$validate_key = md5( $std->make_password() . time() );
			
			//------------------------------------------------------------
			// Update the DB for this member.
			//------------------------------------------------------------
			
			$db_str = $DB->compile_db_insert_string( array (
															 'vid'         => $validate_key,
															 'member_id'   => $member['id'],
															 'real_group'  => $member['mgroup'],
															 'temp_group'  => $member['mgroup'],
															 'entry_date'  => time(),
															 'coppa_user'  => 0,
															 'lost_pass'   => 1,
															 'ip_address'  => $ibforums->input['IP_ADDRESS']
												   )       );
			
			$DB->query("INSERT INTO ibf_validating ({$db_str['FIELD_NAMES']}) VALUES({$db_str['FIELD_VALUES']})");
			
			//------------------------------------------------------------
			// Send out the email.
			//------------------------------------------------------------
			
    		$this->email->get_template("lost_pass");
				
			$this->email->build_message( array(
												'NAME'         => $member['name'],
												'PASSWORD'     => $new_pass,
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform&uid=".$member['id']."&aid=".$validate_key,
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $validate_key,
												'IP_ADDRESS'   => $ibforums->input['IP_ADDRESS'],
											  )
										);
										
			$this->email->subject = $ibforums->lang['lp_subject'].' '.$ibforums->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
			
			$this->output = $this->html->show_lostpasswait( $member );
		}
    	
    	$this->page_title = $ibforums->lang['lost_pass_form'];
    }
 	
 	/*****************************************************/
	// show_reg_form
	// ------------------
	// Simply shows the registration form, no - really! Thats
	// all it does. It doesn't make the tea or anything.
	// Just the registration form, no more - no less.
	// Unless your server went down, then it's just useless.
	/*****************************************************/   
    
    function show_reg_form($errors = "") {
    	global $ibforums, $DB, $std;
    	
    	if ($ibforums->vars['no_reg'] == 1)
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_off' ) );
    	}
    	
    	if ($ibforums->vars['reg_auth_type'])
    	{
    		$ibforums->lang['std_text'] .= "<br>" . $ibforums->lang['email_validate_text'];
    	}
    	
    	$this->bash_dead_validations();
    	
    	//-----------------------------------------------
		// Clean out anti-spam stuffy
		//-----------------------------------------------
		
		if ($ibforums->vars['bot_antispam'])
		{
		
			// Get a time roughly 6 hours ago...
			
			$r_date = time() - (60*60*6);
			
			// Remove old reg requests from the DB
			
			$DB->query("DELETE FROM ibf_reg_antispam WHERE ctime < '$r_date'");
			
			// Set a new ID for this reg request...
			
			$regid = md5( uniqid(microtime()) );
			
			// Set a new 6 character numerical string
			
			mt_srand ((double) microtime() * 1000000);
			
			$reg_code = mt_rand(100000,999999);
			
			// Insert into the DB
			
			$str = $DB->compile_db_insert_string( array (
															'regid'      => $regid,
															'regcode'    => $reg_code,
															'ip_address' => $ibforums->input['IP_ADDRESS'],
															'ctime'      => time(),
												)       );
												
			$DB->query("INSERT INTO ibf_reg_antispam ({$str['FIELD_NAMES']}) VALUES ({$str['FIELD_VALUES']})");
		
		}
    	
    	//-----------------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------------
		
		$required_output = "";
		$optional_output = "";
		$field_data     = array();
		
		$DB->query("SELECT * from ibf_pfields_data WHERE fedit=1 AND fshowreg=1 ORDER BY forder");
		
		while( $row = $DB->fetch_row() )
		{
			$form_element = "";
			
			if ( $row['freq'] == 1 )
			{
				$ftype = 'required_output';
			}
			else
			{
				$ftype = 'optional_output';
			}
			
			if ( $row['ftype'] == 'drop' )
			{
				$carray = explode( '|', trim($row['fcontent']) );
				
				$d_content = "";
				
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
					
					$ov = trim($value[0]);
					$td = trim($value[1]);
					
					if ($ov !="" and $td !="")
					{
						$d_content .= "<option value='$ov'>$td</option>\n";
					}
				}
				
				if ($d_content != "")
				{
					$form_element = $this->html->field_dropdown( 'field_'.$row['fid'], $d_content );
				}
			}
			else if ( $row['ftype'] == 'area' )
			{
				$form_element = $this->html->field_textarea( 'field_'.$row['fid'], $ibforums->input['field_'.$row['fid']] );
			}
			else
			{
				$form_element = $this->html->field_textinput( 'field_'.$row['fid'], $ibforums->input['field_'.$row['fid']] );
			}
			
			${$ftype} .= $this->html->field_entry( $row['ftitle'], $row['fdesc'], $form_element );
		}
    	
    	$this->page_title = $ibforums->lang['registration_form'];
    	$this->nav        = array( $ibforums->lang['registration_form'] );
    	
    	$coppa = ($ibforums->input['coppa_user'] == 1) ? 1 : 0;
    	
    	if ($errors != "")
    	{
    		$this->output .= $this->html->errors( $ibforums->lang[$errors]);
    	}

    	$this->output    .= $this->html->ShowForm( array( 'TEXT'        => $ibforums->lang['std_text'],
    												      'RULES'       => $ibforums->lang['click_wrap'],
    												      'coppa_user'  => $coppa,
    											 )      );
    											 
    	if ($ibforums->vars['bot_antispam'] == 'gd')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->html->bot_antispam_gd( $regid ), $this->output );
		}
		else if ($ibforums->vars['bot_antispam'] == 'gif')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->html->bot_antispam( $regid ), $this->output );
		}
    	
    	if ($required_output != "")
		{
			$this->output = str_replace( "<!--{REQUIRED.FIELDS}-->", "\n".$required_output, $this->output );
		}
		
		if ($optional_output != "")
		{
			$this->output = str_replace( "<!--{OPTIONAL.FIELDS}-->", $this->html->optional_title()."\n".$optional_output, $this->output );
		}
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class(&$this);
    		$this->modules->on_register_form();
   		}
   	}
    
   	/*****************************************************/
	// create_account
	// ------------------
	// Now this is a really good subroutine. It adds the member
	// to the members table in the database. Yes, really fancy
	// this one. It also finds the time to see if we need to
	// check any email verification type malarky before we
	// can use this brand new account. It's like buying a new
	// car and getting it towed home and being told the keys
	// will be posted later. Although you can't polish this
	// routine while you're waiting.
	/*****************************************************/ 
	
	function create_account()
	{
		global $ibforums, $std, $DB, $print, $HTTP_POST_VARS;
		
		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		if ($ibforums->vars['no_reg'] == 1)
    	{
    		$std->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_off' ) );
    	}
    	
    	$coppa = ($ibforums->input['coppa_user'] == 1) ? 1 : 0;
    	
    	//----------------------------------
		// Custom profile field stuff
		//----------------------------------
		
		$custom_fields = array();
		
		$DB->query("SELECT * from ibf_pfields_data WHERE fedit=1");
		
		while ( $row = $DB->fetch_row() )
		{
			if ($row['freq'] == 1)
			{
				if ($HTTP_POST_VARS[ 'field_'.$row['fid'] ] == "")
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
				}
			}
			
			if ($row['fmaxinput'] > 0)
			{
				if (strlen($HTTP_POST_VARS[ 'field_'.$row['fid'] ]) > $row['fmaxinput'])
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'cf_to_long', 'EXTRA' => $row['ftitle'] ) );
				}
			}
			
			$custom_fields[ 'field_'.$row['fid'] ] = str_replace( '<br>', "\n", $ibforums->input[ 'field_'.$row['fid'] ] );
		}
		
		//---------------------------------------
		// Trim off the username and password
		
		$in_username = trim( str_replace( '|', '&#124;' , $ibforums->input['UserName'] ) );
		$in_password = trim($ibforums->input['PassWord']);
		$in_email    = strtolower( trim($ibforums->input['EmailAddress']) );
		
		$ibforums->input['EmailAddress_two'] = strtolower( trim($ibforums->input['EmailAddress_two']) );
		
		if ($ibforums->input['EmailAddress_two'] != $in_email)
		{
			$this->show_reg_form('err_email_address_match');
			return;
		}
		
		// Remove multiple spaces in the username
		
		$in_username = preg_replace( "/\s{2,}/", " ", $in_username );
		
		//-------------------------------------------------
		// More unicode..
		//-------------------------------------------------
		
		$len_u = $in_username;
		
		$len_u = preg_replace("/&#([0-9]+);/", "-", $len_u );
		
		$len_p = $in_password;
		
		$len_p = preg_replace("/&#([0-9]+);/", "-", $len_p );
		
		//+--------------------------------------------
		//| Check for errors in the input.
		//+--------------------------------------------
		
		if (empty($in_username))
		{
			$this->show_reg_form('err_no_username');
			return;
		}
		if (strlen($len_u) < 3)
		{
			$this->show_reg_form('err_no_username');
			return;
		}
		if (strlen($len_u) > 32) 
		{
			$this->show_reg_form('err_no_username');
			return;
		}
		if (empty($in_password))
		{
			$this->show_reg_form('err_no_password');
			return;
		}
		if (strlen($len_p) < 3)
		{
			$this->show_reg_form('err_no_password');
			return;
		}
		if (strlen($len_p) > 32) 
		{
			$this->show_reg_form('err_no_password');
			return;
		}
		if ($ibforums->input['PassWord_Check'] != $in_password)
		{
			$this->show_reg_form('err_pass_match');
			return;
		}
		if (strlen($in_email) < 6)
		{
			$this->show_reg_form('err_invalid_email');
			return;
		}
		
		//+--------------------------------------------
		//| Check the email address
		//+--------------------------------------------
		
		$in_email = $std->clean_email($in_email);
		
		if (! $in_email )
		{
			$this->show_reg_form('err_invalid_email');
			return;
		}
		
		//+--------------------------------------------
		//| Is this name already taken?
		//+--------------------------------------------
		
		$DB->query("SELECT id FROM ibf_members WHERE LOWER(name)='".strtolower($in_username)."'");
		$name_check = $DB->fetch_row();
		
		if ($name_check['id'])
		{
			$this->show_reg_form('err_user_exists');
			return;
		}
		
		if (strtolower($in_username) == 'guest')
		{
			$this->show_reg_form('err_user_exists');
			return;
		}
		
		//+--------------------------------------------
		//| Is this email addy taken?
		//+--------------------------------------------
		
		if (! $ibforums->vars['allow_dup_email'] )
		{
			$DB->query("SELECT id FROM ibf_members WHERE email='".$in_email."'");
			$email_check = $DB->fetch_row();
			if ($email_check['id'])
			{
				$this->show_reg_form('err_email_exists');
				return;
			}
		}
		
		//+--------------------------------------------
		//| Are they in the reserved names list?
		//+--------------------------------------------
		
		if ($ibforums->vars['ban_names'])
		{
			$names = explode( "|" , $ibforums->vars['ban_names'] );
			foreach ($names as $n)
			{
				if ( $n == "" )
				{
					continue;
				}
				
				if (preg_match( "/".preg_quote($n, '/' )."/i", $in_username ))
				{
					$this->show_reg_form('err_user_exists');
					return;
				}
			}
		}	
		
		//+--------------------------------------------
		//| Are they banned?
		//+--------------------------------------------
		
		if ($ibforums->vars['ban_ip'])
		{
			$ips = explode( "|", $ibforums->vars['ban_ip'] );
			
			foreach ($ips as $ip)
			{
				$ip = preg_replace( "/\*/", '.*' , $ip );
				
				if ( $ip == "" )
				{
					continue;
				}
				
				if (preg_match( "/^$ip/", $ibforums->input['IP_ADDRESS'] ))
				{
					$std->Error( array( LEVEL => 1, MSG => 'you_are_banned' ) );
				}
			}
		}
		
		if ($ibforums->vars['ban_email'])
		{
			$ips = explode( "|", $ibforums->vars['ban_email'] );
			foreach ($ips as $ip)
			{
				$ip = preg_replace( "/\*/", '.*' , $ip );
				if (preg_match( "/$ip/", $in_email ))
				{
					$std->Error( array( LEVEL => 1, MSG => 'you_are_banned' ) );
				}
			}
		}
		
		//+--------------------------------------------
		//| Check the reg_code
		//+--------------------------------------------
		
		if ($ibforums->vars['bot_antispam'])
		{
			if ($ibforums->input['regid'] == "")
			{
				$this->show_reg_form('err_reg_code');
				return;
			}
			
			$DB->query("SELECT * FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['regid']))."'");
			
			if ( ! $row = $DB->fetch_row() )
			{
				$this->show_reg_form('err_reg_code');
				return;
			}
			
			if ( trim( intval($ibforums->input['reg_code']) ) != $row['regcode'] )
			{
				$this->show_reg_form('err_reg_code');
				return;
			}
			
			$DB->query("DELETE FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['regid']))."'");
		}
		
		//+--------------------------------------------
		//| Build up the hashes
		//+--------------------------------------------
		
		$mem_group = $ibforums->vars['member_group'];
		
		//+--------------------------------------------
		//| Are we asking the member or admin to preview?
		//+--------------------------------------------
		
		if ($ibforums->vars['reg_auth_type'])
		{
			$mem_group = $ibforums->vars['auth_group'];
		}
		else if ($coppa == 1)
		{
			$mem_group = $ibforums->vars['auth_group'];
		}
		
		//+--------------------------------------------
		//| Find the highest member id, and increment it
		//| auto_increment not used for guest id 0 val.
		//+--------------------------------------------
		
		$DB->query("SELECT MAX(id) as new_id FROM ibf_members");
		$r = $DB->fetch_row();
		
		$member_id = $r['new_id'] + 1;
		
		$member = array(
						 'id'              => $member_id,
						 'name'            => $in_username,
						 'password'        => $in_password,
						 'email'           => $in_email,
						 'mgroup'          => $mem_group,
						 'posts'           => 0,
						 'avatar'          => 'noavatar',
						 'joined'          => time(),
						 'ip_address'      => $ibforums->input['IP_ADDRESS'],
						 'time_offset'     => $ibforums->vars['time_offset'],
						 'view_sigs'       => 1,
						 'email_pm'        => 1,
						 'view_img'        => 1,
						 'view_avs'        => 1,
						 'restrict_post'   => 0,
						 'view_pop'        => 1,
						 'vdirs'           => "in:Inbox|sent:Sent Items",
						 'msg_total'       => 0,
						 'new_msg'         => 0,
						 'coppa_user'      => $coppa,
						 'language'        => $ibforums->vars['default_language'],
					   );
					   
		
					   
		//+--------------------------------------------
		//| Insert into the DB
		//+--------------------------------------------
		
		$member['password'] = md5( $member['password'] );
		
		$db_string = $std->compile_db_string( $member );
		
		$DB->query("INSERT INTO ibf_members (" .$db_string['FIELD_NAMES']. ") VALUES (". $db_string['FIELD_VALUES'] .")");
		
		$DB->query("INSERT INTO ibf_member_extra (id) VALUES ($member_id)");
		
		unset($db_string);
		
		//+--------------------------------------------
		//| Insert into the custom profile fields DB
		//+--------------------------------------------
		
		// Ensure deleted members profile fields are removed.
		
		$DB->query("DELETE FROM ibf_pfields_content WHERE member_id=".$member['id']);
		
		$custom_fields['member_id'] = $member['id'];
			
		$db_string = $DB->compile_db_insert_string($custom_fields);
			
		$DB->query("INSERT INTO ibf_pfields_content (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
		
		unset($db_string);
		
		//+--------------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class(&$this);
    		$this->modules->on_create_account($member);
    		
    		if ( $this->modules->error == 1 )
    		{
    			return;
    		}
   		}
		
		//+--------------------------------------------
		
		$validate_key = md5( $std->make_password() . time() );
		$time         = time();
		
		
		if ($coppa != 1)
		{
			if ( ($ibforums->vars['reg_auth_type'] == 'user') or ($ibforums->vars['reg_auth_type'] == 'admin') ) {
			
				// We want to validate all reg's via email, after email verificiation has taken place,
				// we restore their previous group and remove the validate_key
				
				$db_str = $DB->compile_db_insert_string( array (
																 'vid'         => $validate_key,
																 'member_id'   => $member['id'],
																 'real_group'  => $ibforums->vars['member_group'],
																 'temp_group'  => $ibforums->vars['auth_group'],
																 'entry_date'  => $time,
																 'coppa_user'  => $coppa,
																 'new_reg'     => 1,
																 'ip_address'  => $member['ip_address']
													   )       );
				
				$DB->query("INSERT INTO ibf_validating ({$db_str['FIELD_NAMES']}) VALUES({$db_str['FIELD_VALUES']})");
				
				
				if ( $ibforums->vars['reg_auth_type'] == 'user' )
				{
				
					$this->email->get_template("reg_validate");
					
					$this->email->build_message( array(
														'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&uid=".urlencode($member_id)."&aid=".urlencode($validate_key),
														'NAME'         => $member['name'],
														'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=05",
														'EMAIL'        => $member['email'],
														'ID'           => $member_id,
														'CODE'         => $validate_key,
													  )
												);
												
					$this->email->subject = "Registration at ".$ibforums->vars['board_name'];
					$this->email->to      = $member['email'];
					
					$this->email->send_mail();
					
					$this->output     = $this->html->show_authorise( $member );
					
				}
				else if ( $ibforums->vars['reg_auth_type'] == 'admin' )
				{
					$this->output     = $this->html->show_preview( $member );
				}
				
				if ($ibforums->vars['new_reg_notify']) {
					
					$date = $std->get_date( time(), 'LONG' );
					
					$this->email->get_template("admin_newuser");
				
					$this->email->build_message( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['name'],
													  )
												);
												
					$this->email->subject = "New Registration at ".$ibforums->vars['board_name'];
					$this->email->to      = $ibforums->vars['email_in'];
					$this->email->send_mail();
				}
				
				$this->page_title = $ibforums->lang['reg_success'];
				
				$this->nav        = array( $ibforums->lang['nav_reg'] );
			}
	
			else
			{
				
				// We don't want to preview, or get them to validate via email.
				
				$DB->query("UPDATE ibf_stats SET ".
							 "MEM_COUNT=MEM_COUNT+1, ".
							 "LAST_MEM_NAME='" . $member['name'] . "', ".
							 "LAST_MEM_ID='"   . $member['id']   . "'");
							 
				if ($ibforums->vars['new_reg_notify']) {
					
					$date = $std->get_date( time(), 'LONG' );
					
					$this->email->get_template("admin_newuser");
				
					$this->email->build_message( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['name'],
													  )
												);
												
					$this->email->subject = "New Registration at ".$ibforums->vars['board_name'];
					$this->email->to      = $ibforums->vars['email_in'];
					$this->email->send_mail();
				}
				
				$std->my_setcookie("member_id"   , $member['id']      , 1);
				$std->my_setcookie("pass_hash"   , $member['password'], 1);
					
				$std->boink_it($ibforums->base_url.'&act=Login&CODE=autologin&fromreg=1');
			}
		}
		else
		{
			// This is a COPPA user, so lets tell them they registered OK and redirect to the form.
			
			$db_str = $DB->compile_db_insert_string( array (
															 'vid'         => $validate_key,
															 'member_id'   => $member['id'],
															 'real_group'  => $ibforums->vars['member_group'],
															 'temp_group'  => $ibforums->vars['auth_group'],
															 'entry_date'  => $time,
															 'coppa_user'  => $coppa,
															 'new_reg'     => 1,
															 'ip_address'  => $member['ip_address']
												   )       );
			
			$DB->query("INSERT INTO ibf_validating ({$db_str['FIELD_NAMES']}) VALUES({$db_str['FIELD_VALUES']})");
			
			$print->redirect_screen( $ibforums->lang['cp_success'], 'act=Reg&CODE=12' );
		}
				
	} 
    
    /*****************************************************/
	// validate_user
	// ------------------
	// Leave a message after the tone, and I'll amuse myself
	// by pulling faces when hearing the message later.
	/*****************************************************/
	
	function validate_user() {
		global $ibforums, $std, $DB, $print, $HTTP_POST_VARS;
		
		//------------------------------------------------------------
		// Check for input and it's in a valid format.
		//------------------------------------------------------------
		
		$in_user_id      = intval(trim(urldecode($ibforums->input['uid'])));
		$in_validate_key = trim(urldecode($ibforums->input['aid']));
		$in_type         = trim($ibforums->input['type']);
		
		if ($in_type == "")
		{
			$in_type = 'reg';
		}
		
		//------------------------------------------------------------
		
		if (! preg_match( "/^(?:[\d\w]){32}$/", $in_validate_key ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
		}
		
		//------------------------------------------------------------
		
		if (! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
		}
		
		//------------------------------------------------------------
		// Attempt to get the profile of the requesting user
		//------------------------------------------------------------
		
		$DB->query("SELECT * FROM ibf_members WHERE id=$in_user_id");
		
		if ( ! $member = $DB->fetch_row() )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_mem' ) );
		}
		
		//------------------------------------------------------------
		// Get validating info..
		//------------------------------------------------------------
		
		$DB->query("SELECT * FROM ibf_validating WHERE member_id=$in_user_id");
		
		if ( ! $validate = $DB->fetch_row() )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
		}
		
		if (($validate['new_reg'] == 1) && ($ibforums->vars['reg_auth_type'] == "admin")) 
		{ 
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key_not_allow' ) ); 
		} 
		
		if ($validate['vid'] != $in_validate_key)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_key_wrong' ) );
		}
		else
		{
			//------------------------------------------------------------
			// REGISTER VALIDATE
			//------------------------------------------------------------
			
			if ($in_type == 'reg')
			{
				if ( $validate['new_reg'] != 1 )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				if (empty($validate['real_group']))
				{
					$validate['real_group'] = $ibforums->vars['member_group'];
				}
				
				$DB->query("UPDATE ibf_members SET mgroup=".intval($validate['real_group'])." WHERE id=".intval($member['id']));
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class(&$this);
    				$this->modules->on_group_change($member['id'], $validate['real_group']);
    			}
    			
				//------------------------------------------------------------
				// Update the stats...
				//------------------------------------------------------------
			
				$DB->query("UPDATE ibf_stats SET ".
							 "MEM_COUNT=MEM_COUNT+1, ".
							 "LAST_MEM_NAME='" . $member['name'] . "', ".
							 "LAST_MEM_ID='"   . $member['id']   . "'");
							 
							 
				$std->my_setcookie("member_id"   , $member['id']      , 1);
				$std->my_setcookie("pass_hash"   , $member['password'], 1);
				
				//------------------------------------------------------------
				// Remove "dead" validation
				//------------------------------------------------------------
				
				$DB->query("DELETE FROM ibf_validating WHERE vid='".$validate['vid']."' OR (member_id={$member['id']} AND new_reg=1)");
				
				$this->bash_dead_validations();
					
				$std->boink_it($ibforums->base_url.'&act=Login&CODE=autologin&fromreg=1');
							 
			}
			
			//------------------------------------------------------------
			// LOST PASS VALIDATE
			//------------------------------------------------------------
			
			else if ($in_type == 'lostpass')
			{
				if ($validate['lost_pass'] != 1)
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'lp_no_pass' ) );
				}
				
				if ( $HTTP_POST_VARS['pass1'] == "" )
				{
					$std->Error( array( LEVEL => 1, MSG => 'pass_blank' ) );
				}
				
				if ( $HTTP_POST_VARS['pass2'] == "" )
				{
					$std->Error( array( LEVEL => 1, MSG => 'pass_blank' ) );
				}
				
				$pass_a = trim($ibforums->input['pass1']);
				$pass_b = trim($ibforums->input['pass2']);
				
				if ( strlen($pass_a) < 3 )
				{
					$std->Error( array( LEVEL => 1, MSG => 'pass_too_short' ) );
				}
				
				if ( $pass_a != $pass_b )
				{
					$std->Error( array( LEVEL => 1, MSG => 'pass_no_match' ) );
				}
				
				$new_pass = md5($pass_a);
				
				$DB->query("UPDATE ibf_members SET password='$new_pass' WHERE id=".intval($member['id']));
				
				$std->my_setcookie("member_id"   , $member['id']  , 1);
				$std->my_setcookie("pass_hash"   , $new_pass      , 1);
				
				//------------------------------------------------------------
				// Remove "dead" validation
				//------------------------------------------------------------
				
				$DB->query("DELETE FROM ibf_validating WHERE vid='".$validate['vid']."' OR (member_id={$member['id']} AND lost_pass=1)");
				
				$this->bash_dead_validations();
					
				$std->boink_it($ibforums->base_url.'&act=Login&CODE=autologin&frompass=1');
			
			}
			
			//------------------------------------------------------------
			// EMAIL ADDY CHANGE
			//------------------------------------------------------------
			
			else if ($in_type == 'newemail')
			{
				if ( $validate['email_chg'] != 1 )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				if (empty($validate['real_group']))
				{
					$validate['real_group'] = $ibforums->vars['member_group'];
				}
				
				$DB->query("UPDATE ibf_members SET mgroup=".intval($validate['real_group'])." WHERE id=".intval($member['id']));
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class(&$this);
    				$this->modules->on_group_change($member['id'], $validate['real_group']);
    			}
    			
				$std->my_setcookie("member_id"   , $member['id']      , 1);
				$std->my_setcookie("pass_hash"   , $member['password'], 1);
				
				//------------------------------------------------------------
				// Remove "dead" validation
				//------------------------------------------------------------
				
				$DB->query("DELETE FROM ibf_validating WHERE vid='".$validate['vid']."' OR (member_id={$member['id']} AND email_chg=1)");
				
				$this->bash_dead_validations();
					
				$std->boink_it($ibforums->base_url.'&act=Login&CODE=autologin&fromemail=1');
			}
			
		} 
		
	} 
    
    /*****************************************************/
	// show_board_rules
	// ------------------
	// o_O  ^^
	/*****************************************************/
	
	function show_board_rules() {
		global $ibforums, $DB;
		
		$DB->query("SELECT RULES_TEXT from ib_forum_rules WHERE ID='00'");
		$rules = $DB->fetch_row();
		
		$this->output     = $this->html->show_rules($rules);
		$this->page_title = $ibforums->lang['board_rules'];
		$this->nav        = array( $ibforums->lang['board_rules'] );
	
	}
	
	/*****************************************************/
	// show_manual_form
	// ------------------
	// This feature is not available in an auto option
	/*****************************************************/
	
	function show_manual_form($type='reg') {
		global $ibforums, $std, $DB;
		
		if ( $type == 'lostpass' )
		{
		
			$this->output = $this->html->show_lostpass_form();
			
			//------------------------------------------------------------
			// Check for input and it's in a valid format.
			//------------------------------------------------------------
			
			if ( $ibforums->input['uid'] AND $ibforums->input['aid'] )
			{ 
			
				$in_user_id      = intval(trim(urldecode($ibforums->input['uid'])));
				$in_validate_key = trim(urldecode($ibforums->input['aid']));
				$in_type         = trim($ibforums->input['type']);
				
				if ($in_type == "")
				{
					$in_type = 'reg';
				}
				
				//------------------------------------------------------------
				
				if (! preg_match( "/^(?:[\d\w]){32}$/", $in_validate_key ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
				}
				
				//------------------------------------------------------------
				
				if (! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
				}
				
				//------------------------------------------------------------
				// Attempt to get the profile of the requesting user
				//------------------------------------------------------------
				
				$DB->query("SELECT id, name, password, email FROM ibf_members WHERE id=$in_user_id");
				
				if ( ! $member = $DB->fetch_row() )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_mem' ) );
				}
				
				//------------------------------------------------------------
				// Get validating info..
				//------------------------------------------------------------
				
				$DB->query("SELECT * FROM ibf_validating WHERE member_id=$in_user_id and vid='$in_validate_key' and lost_pass=1");
				
				if ( ! $validate = $DB->fetch_row() )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->html->show_lostpass_form_auto($in_validate_key, $in_user_id), $this->output );
			}
			else
			{
				$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->html->show_lostpass_form_manual(), $this->output );
			}
		}
		else
		{
			$this->output     = $this->html->show_dumb_form($type);
		}
		
		$this->page_title = $ibforums->lang['activation_form'];
		$this->nav        = array( $ibforums->lang['activation_form'] );
	
	}
	
	function show_image()
	{
		global $ibforums, $DB, $std;
		
		
		if ( $ibforums->input['rc'] == "" )
		{
			return false;
		}
		
		// Get the info from the db
		
		$DB->query("SELECT * FROM ibf_reg_antispam WHERE regid='".trim(addslashes($ibforums->input['rc']))."'");
		
		if ( ! $row = $DB->fetch_row() )
		{
			return false;
		}
		
		//--------------------------------------------
		// Using GD?
		//--------------------------------------------
		
		if ( $ibforums->vars['bot_antispam'] == 'gd' )
		{
			$std->show_gd_img($row['regcode']);
		}
		else
		{
		
			//--------------------------------------------
			// Using normal then, check for "p"
			//--------------------------------------------
			
			if ( $ibforums->input['p'] == "" )
			{
				return false;
			}
			
			$p = intval($ibforums->input['p']) - 1; //substr starts from 0, not 1 :p
			
			$this_number = substr( $row['regcode'], $p, 1 );
			
			$std->show_gif_img($this_number);
		}
		
	}
	
	
	
	function bash_dead_validations()
	{
		global $ibforums, $std, $DB;
		
		$mids = array();
		$vids = array();
		
		// If enabled, remove validating new_reg members & entries from members table
		
		if ( intval($ibforums->vars['validate_day_prune']) > 0 )
		{
			$less_than = time() - $ibforums->vars['validate_day_prune']*86400;
			
			$DB->query("SELECT v.vid, v.member_id, m.posts
			               FROM ibf_validating v
			             LEFT JOIN ibf_members m ON (v.member_id=m.id)
					    WHERE v.new_reg=1
					    AND v.coppa_user <> 1
					    AND v.entry_date < $less_than
					    AND lost_pass <> 1");
			
			while( $i = $DB->fetch_row() )
			{
				if ( intval($i['posts']) < 1 )
				{
					$mids[] = $i['member_id'];
					$vids[] = "'".$i['vid']."'";
				}
			}
			
			// Remove non-posted validating members
			
			if ( count($mids) > 0 )
			{
				$DB->query("DELETE FROM ibf_members WHERE id IN(".implode(",",$mids).")");
				$DB->query("DELETE FROM ibf_member_extra WHERE id IN(".implode(",",$mids).")");
				$DB->query("DELETE FROM ibf_pfields_content WHERE member_id IN(".implode(",",$mids).")");
				$DB->query("DELETE FROM ibf_validating WHERE vid IN(".implode(",",$vids).")");
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class(&$this);
					$this->modules->on_delete($mids);
				}
			}
		}
	}
	
        
}

?>
