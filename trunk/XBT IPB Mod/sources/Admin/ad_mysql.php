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
|   > mySQL Admin Stuff
|   > Module written by Matt Mecham
|   > Date started: 21st October 2002
|
|	> Module Version Number: 1.0.0
|   > Music listen to when coding this: Martin Grech - Open Heart Zoo
|   > Talk about useless information!
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

@set_time_limit(1200);

$idx = new ad_mysql();


class ad_mysql {

	var $base_url;
	var $mysql_version   = "";
	var $true_version    = "";
	var $str_gzip_header = "\x1f\x8b\x08\x00\x00\x00\x00\x00";

	function ad_mysql() {
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS, $HTTP_GET_VARS;
		
		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		//---------------------------------------
		
		// Make sure we're a root admin, or else!
		
		if ($MEMBER['mgroup'] != $INFO['admin_group'])
		{
			$ADMIN->error("Sorry, these functions are for the root admin group only");
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
   		
		switch($IN['code'])
		{
		
			case 'dotool':
				$this->run_tool();
				break;
				
			case 'runtime':
				$this->view_sql("SHOW STATUS");
				break;
				
			case 'system':
				$this->view_sql("SHOW VARIABLES");
				break;
				
			case 'processes':
				$this->view_sql("SHOW PROCESSLIST");
				break;
				
			case 'runsql':
				$q = $HTTP_POST_VARS['query'] == "" ? urldecode($HTTP_GET_VARS['query']) : $HTTP_POST_VARS['query'];
				$this->view_sql(trim(stripslashes($q)));
				break;
			
			case 'backup':
				$this->show_backup_form();
				break;
				
			case 'safebackup':
				$this->sbup_splash();
				break;
				
			case 'dosafebackup':
				$this->do_safe_backup();
				break;
				
			case 'export_tbl':
				$this->do_safe_backup(trim(urldecode(stripslashes($HTTP_GET_VARS['tbl']))));
				break;
			
			//-------------------------
			default:
				$this->list_index();
				break;
		}
		
	}
	
	//-----------------------------------------------
	// Back up baby, back up
	//-----------------------------------------------
	
	function do_safe_backup($tbl_name="")
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($tbl_name == "")
		{
			// Auto all tables
			$skip        = intval($IN['skip']);
			$create_tbl  = intval($IN['create_tbl']);
			$enable_gzip = intval($IN['enable_gzip']);
			$filename    = 'ibf_dbbackup';
		}
		else
		{
			// Man. click export
			
			$skip        = 0;
			$create_tbl  = 0;
			$enable_gzip = 1;
			$filename    = $tbl_name;
		}
		
		$output = "";
		
		@header("Pragma: no-cache");
		
		$do_gzip = 0;
		
		if( $enable_gzip )
		{
			$phpver = phpversion();

			if($phpver >= "4.0")
			{
				if(extension_loaded("zlib"))
				{
					$do_gzip = 1;
				}
			}
		}
		
		if( $do_gzip != 0 )
		{
			@ob_start();
			@ob_implicit_flush(0);
			header("Content-Type: text/x-delimtext; name=\"$filename.sql.gz\"");
			header("Content-disposition: attachment; filename=$filename.sql.gz");
		}
		else
		{
			header("Content-Type: text/x-delimtext; name=\"$filename.sql\"");
			header("Content-disposition: attachment; filename=$filename.sql");
		}
		
		//-----------------------------
		// Get tables to work on
		//-----------------------------
		
		if ($tbl_name == "")
		{
			$tmp_tbl = $DB->get_table_names();
				
			foreach($tmp_tbl as $tbl)
			{
				// Ensure that we're only peeking at IBF tables
				
				if ( preg_match( "/^".$INFO['sql_tbl_prefix']."/", $tbl ) )
				{
					// We've started our headers, so print as we go to stop
					// poss memory problems
					
					$this->get_table_sql($tbl, $create_tbl, $skip);
				}
			}
		}
		else
		{
			$this->get_table_sql($tbl_name, $create_tbl, $skip);
		}
		
		//-----------------------------
		// GZIP?
		//-----------------------------
		
		if($do_gzip)
		{
			$size     = ob_get_length();
			$crc      = crc32(ob_get_contents());
			$contents = gzcompress(ob_get_contents());
			ob_end_clean();
			echo $this->str_gzip_header
				.substr($contents, 0, strlen($contents) - 4)
				.$this->gzip_four_chars($crc)
				.$this->gzip_four_chars($size);
		}
		
		exit();
		
	}
	
	//-----------------------------------------------
	// Internal handler to return content from table
	//-----------------------------------------------
	
	function get_table_sql($tbl, $create_tbl, $skip=0)
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		if ($create_tbl)
		{
			// Generate table structure
			
			if ( $IN['addticks'] )
			{
				$DB->query("SHOW CREATE TABLE `".$INFO['sql_database'].".".$tbl."`");
			}
			else
			{
				$DB->query("SHOW CREATE TABLE ".$INFO['sql_database'].".".$tbl);
			}
			
			$ctable = $DB->fetch_row();
			
			echo $this->sql_strip_ticks($ctable['Create Table']).";\n";
		}
		
		// Are we skipping? Woohoo, where's me rope?!
		
		if ($skip == 1)
		{
			if ($tbl == $INFO['sql_tbl_prefix'].'admin_sessions'
				OR $tbl == $INFO['sql_tbl_prefix'].'sessions'
				OR $tbl == $INFO['sql_tbl_prefix'].'reg_anti_spam'
				OR $tbl == $INFO['sql_tbl_prefix'].'search_results'
			   )
			{
				return $ret;
			}
		}
		
		// Get the data
		
		$DB->query("SELECT * FROM $tbl");
		
		// Check to make sure rows are in this
		// table, if not return.
		
		$row_count = $DB->get_num_rows();
		
		if ($row_count < 1)
		{
			return TRUE;
		}
		
		//---------------------------
		// Get col names
		//---------------------------
		
		$f_list = "";
	
		$fields = $DB->get_result_fields();
		
		$cnt = count($fields);
		
		for( $i = 0; $i < $cnt; $i++ )
		{
			$f_list .= $fields[$i]->name . ", ";
		}
		
		$f_list = preg_replace( "/, $/", "", $f_list );
		
		while ( $row = $DB->fetch_row() )
		{
			//---------------------------
			// Get col data
			//---------------------------
			
			$d_list = "";
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				if ( ! isset($row[ $fields[$i]->name ]) )
				{
					$d_list .= "NULL,";
				}
				elseif ( $row[ $fields[$i]->name ] != '' )
				{
					$d_list .= "'".$this->sql_add_slashes($row[ $fields[$i]->name ]). "',";
				}
				else
				{
					$d_list .= "'',";
				}
			}
			
			$d_list = preg_replace( "/,$/", "", $d_list );
			
			echo "INSERT INTO $tbl ($f_list) VALUES($d_list);\n";
		}
		
		return TRUE;
		
	}
	
	//-----------------------------------------------
	// sql_strip_ticks from field names
	//-----------------------------------------------
	
	function sql_strip_ticks($data)
	{
		return str_replace( "`", "", $data );
	}
	
	//-----------------------------------------------
	// Add slashes to single quotes to stop sql breaks
	//-----------------------------------------------
	
	function sql_add_slashes($data)
	{
		$data = str_replace('\\', '\\\\', $data);
        $data = str_replace('\'', '\\\'', $data);
        $data = str_replace("\r", '\r'  , $data);
        $data = str_replace("\n", '\n'  , $data);
        
        return $data;
	}
	
	//-----------------------------------------------
	// Almost there!
	//-----------------------------------------------
	
	function sbup_splash()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->page_detail = "This section allows you to backup your database.";

		$ADMIN->page_title  = "mySQL ".$this->true_version." Back Up";
		
		// Check for mySQL version..
		// Might change at some point..
		
		if ( $this->mysql_version < 32321 )
		{
			$ADMIN->error("Sorry, mySQL version of less than 3.23.21 are not support by this backup utility");
		}
		
		$SKIN->td_header[] = array( "&nbsp;" , "100%" );

		$ADMIN->html .= $SKIN->start_table( "Simple Back Up" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Back Up mySQL Database</b><br><br>Once you have clicked the link below, please wait
													until your browser prompts you with a dialogue box. This may take some time depending on
													the size of the database you are backing up.
													<br><br>
													<b><a href='{$ADMIN->base_url}&act=mysql&code=dosafebackup&create_tbl={$IN['create_tbl']}&addticks={$IN['addticks']}&skip={$IN['skip']}&enable_gzip={$IN['enable_gzip']}'>Click here to start the backup</a></b>"
									     )      );
									     
												 
		$ADMIN->html .= $SKIN->end_table();
		
		
		$ADMIN->output();
		
	
	}
	
	
	function show_backup_form()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->page_detail = "This section allows you to backup your database.
							  <br><br><b>Simple Backup</b>
							  <br>This function compiles a single back up file and prompts a browser dialogue box for you to save 
							  the file. This is beneficial for PHP safe mode enabled hosts, but can only be used on small databases.
							  <!--<br><br>
							  <b>Advanced Backup</b>
							  <br>This function allows you to split the backup into smaller sections and saves the backup to disk.
							  <br>Note, this can only be used if you do not have PHP safe mode enabled.-->";

		$ADMIN->page_title  = "mySQL ".$this->true_version." Back Up";
		
		// Check for mySQL version..
		// Might change at some point..
		
		if ( $this->mysql_version < 32321 )
		{
			$ADMIN->error("Sorry, mySQL version of less than 3.23.21 are not support by this backup utility");
		}
		
		$SKIN->td_header[] = array( "&nbsp;" , "60%" );
		$SKIN->td_header[] = array( "&nbsp;" , "40%" );
			
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'act'  , 'mysql' ),
											  	  2 => array( 'code' , 'safebackup'),
									 	 )      );
		
		$ADMIN->html .= $SKIN->start_table( "Simple Back Up" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Add 'CREATE TABLE' statements?</b><br>Add backticks around the table name?<br>(if you get a mySQL error, enable this) <input type='checkbox' name='addticks' value=1>",
													$SKIN->form_yes_no( 'create_tbl', 1),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>Skip non essential data?</b><br>Will not produce insert rows for ibf_sessions, ibf_admin_sessions, ibf_search_results, ibf_reg_anti_spam.",
													$SKIN->form_yes_no( 'skip', 1),
									     )      );
									     
		$ADMIN->html .= $SKIN->add_td_row( array( 
													"<b>GZIP Content?</b><br>Will produce a smaller file if GZIP is enabled.",
													$SKIN->form_yes_no( 'enable_gzip', 1),
									     )      );
												 
		$ADMIN->html .= $SKIN->end_form("Start Back Up");
		$ADMIN->html .= $SKIN->end_table();
		
		
		$ADMIN->output();
		
	
	}
	
	
	
	//-------------------------
	// Run mySQL queries
	//-------------------------
	
	
	function view_sql($sql)
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$limit = 50;
		$start = intval($IN['st']) == "" ? 0 : intval($IN['st']);
		$pages = "";
		
		$ADMIN->page_detail = "This section allows you to administrate your mySQL database.$extra";
		$ADMIN->page_title  = "mySQL ".$this->true_version." Tool Box";
		
		$map = array( 'processes' => "SQL Processes",
					  'runtime'   => "SQL Runtime Information",
					  'system'    => "SQL System Variables",
					);
					
		if ($map[ $IN['code'] ] != "")
		{
			$tbl_title = $map[ $IN['code'] ];
			$man_query = 0;
		}
		else
		{
			$tbl_title = "Manual Query";
			$man_query = 1;
		}
		
		//------------------------------------------------
		
		if ($man_query == 1)
		{
			$SKIN->td_header[] = array( "&nbsp;" , "100%" );
			
			$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'act'  , 'mysql' ),
											      2 => array( 'code' , 'runsql'),
										 )      );
			
			$ADMIN->html .= $SKIN->start_table( "Run Query" );
			
			$ADMIN->html .= $SKIN->add_td_row( array( "<center>".$SKIN->form_textarea("query", $sql )."</center>" ) );
													 
			$ADMIN->html .= $SKIN->end_form("Run a New Query");
			$ADMIN->html .= $SKIN->end_table();
			
			
			// Check for drop, create and flush
			
			if ( preg_match( "/^DROP|CREATE|FLUSH/i", trim($sql) ) )
			{
				$ADMIN->error = "Sorry, those queries are not allowed for your safety";
			}
		}
		
		//------------------------------------------------
		
		$DB->return_die = 1;
		
		$DB->query($sql,1);
		
		// Check for errors..
		
		if ( $DB->error != "")
		{
			$SKIN->td_header[] = array( "&nbsp;" , "100%" );
			
			$ADMIN->html .= $SKIN->start_table( "SQL Error" );
			
			$ADMIN->html .= $SKIN->add_td_row( array($DB->error) );
		
			$ADMIN->html .= $SKIN->end_table();
			
			$ADMIN->output(); // End output and script
			
		}
		
		if ( preg_match( "/^INSERT|UPDATE|DELETE|ALTER/i", trim($sql) ) )
		{
			// We can't show any info, and if we're here, there isn't
			// an error, so we're good to go.
			
			$SKIN->td_header[] = array( "&nbsp;" , "100%" );
			
			$ADMIN->html .= $SKIN->start_table( "SQL Query Completed" );
			
			$ADMIN->html .= $SKIN->add_td_row( array("Query: $sql<br>Executed Successfully") );
		
			$ADMIN->html .= $SKIN->end_table();
			
			$ADMIN->output(); // End output and script
			
		}
		else if ( preg_match( "/^SELECT/i", $sql ) )
		{
			// Sort out the pages and stuff
			// auto limit if need be
			
			if ( ! preg_match( "/LIMIT[ 0-9,]+$/i", $sql ) )
			{
				$rows_returned = $DB->get_num_rows();
			
				if ($rows_returned > $limit)
				{
					// Get tbl name
			
					//$tbl_name = preg_replace( "/(".$INFO['sql_tbl_prefix']."\S+?)([\s\.,]|$)/i", "\\1", $sql );
			
					// Set up pages.
					
					$links = $std->build_pagelinks( array( 'TOTAL_POSS'  => $rows_returned,
														   'PER_PAGE'    => $limit,
														   'CUR_ST_VAL'  => $start,
														   'L_SINGLE'    => "Single Page",
														   'L_MULTI'     => "Pages: ",
														   'BASE_URL'    => $ADMIN->base_url."&act=mysql&code=runsql&query=".urlencode($sql),
														 )
												  );
												  
					$sql .= " LIMIT $start, $limit";
				
					// Re-run with limit
					
					$DB->query($sql, 1); /// bypass table swapping
				}
			}
			
		}
		
		$fields = $DB->get_result_fields();
		
		$cnt = count($fields);
		
		// Print the headers - we don't what or how many so...
		
		for( $i = 0; $i < $cnt; $i++ )
		{
			$SKIN->td_header[] = array( $fields[$i]->name , "*" );
		}
		
		$ADMIN->html .= $SKIN->start_table( "Result: ".$tbl_title );
		
		if ($links != "")
		{
			$pages = $SKIN->add_td_basic( $links, 'left', 'tdrow2' );
		
			$ADMIN->html .= $pages;
		}
		
		while( $r = $DB->fetch_row() )
		{
		
			// Grab the rows - we don't what or how many so...
			
			$rows = array();
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				if ($man_query == 1)
				{
					// Limit output
					if ( strlen($r[ $fields[$i]->name ]) > 200 )
					{
						$r[ $fields[$i]->name ] = substr($r[ $fields[$i]->name ], 0, 200) .'...';
					}
				}
				
				$rows[] = wordwrap( htmlspecialchars(nl2br($r[ $fields[$i]->name ])) , 50, "<br>", 1 );
			}
			
			$ADMIN->html .= $SKIN->add_td_row( $rows );
		
		}
		
		$ADMIN->html .= $SKIN->end_table();
		
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	//-------------------------------------------------------------
	// I'm A TOOL!
	//-------------------------------------------------------------
	
	function run_tool()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$ADMIN->page_detail = "This section allows you to administrate your mySQL database.$extra";
		$ADMIN->page_title  = "mySQL ".$this->true_version." Tool Box";
		
		//-------------------------------------------------------------
		// have we got some there tables me laddo?
		//-------------------------------------------------------------
		
		$tables = array();
 		
 		foreach ($IN as $key => $value)
 		{
 			if ( preg_match( "/^tbl_(\S+)$/", $key, $match ) )
 			{
 				if ($IN[$match[0]])
 				{
 					$tables[] = $match[1];
 				}
 			}
 		}
 		
 		if ( count($tables) < 1 )
 		{
 			$ADMIN->error("You must choose some tables to run this tool on or it's just plain outright silly");
 		}
 		
 		//-------------------------------------------------------------
		// What tool is one running?
		// optimize analyze check repair
		//-------------------------------------------------------------
		
		if (strtoupper($IN['tool']) == 'DROP' || strtoupper($IN['tool']) == 'CREATE' || strtoupper($IN['tool']) == 'FLUSH')
		{
			$ADMIN->error("You can't do that, sorry");
		}
		
		foreach($tables as $table)
		{
			$DB->query(strtoupper($IN['tool'])." TABLE $table");
			
			$fields = $DB->get_result_fields();
			
			$data = $DB->fetch_row();
			
			$cnt = count($fields);
			
			// Print the headers - we don't what or how many so...
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				$SKIN->td_header[] = array( $fields[$i]->name , "*" );
			}
			
			$ADMIN->html .= $SKIN->start_table( "Result: ".$IN['tool']." ".$table );
			
			// Grab the rows - we don't what or how many so...
			
			$rows = array();
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				$rows[] = $data[ $fields[$i]->name ];
			}
			
			$ADMIN->html .= $SKIN->add_td_row( $rows );
			
			$ADMIN->html .= $SKIN->end_table();
		}
		
		//+-------------------------------
		
		$ADMIN->output();
		
		
	}
	
	
	//-------------------------------------------------------------
	// SHOW ALL TABLES AND STUFF!
	// 5 hours ago this seemed like a damned good idea.
	// Now it's late and I want to replace all of this code with
	// header("Location: http://yoursite/phpmyadmin");
	//-------------------------------------------------------------
	
	function list_index()
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;
		
		$form_array = array();
		
		if ( $this->mysql_version < 32322 )
		{
			$extra = "<br><b>Note: your version of mySQL has a limited feature set and some tools have been removed</b>";
		}
	
		$ADMIN->page_detail = "This section allows you to administrate your mySQL database.$extra";
		$ADMIN->page_title  = "mySQL ".$this->true_version." Tool Box";
		
		//+-------------------------------
		// Show advanced stuff for mySQL > 3.23.03
		//+-------------------------------
		
		$idx_size = 0;
		$tbl_size = 0;
		
		
		$ADMIN->html .= "
				     <script language='Javascript'>
                     <!--
                     function CheckAll(cb) {
                         var fmobj = document.theForm;
                         for (var i=0;i<fmobj.elements.length;i++) {
                             var e = fmobj.elements[i];
                             if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
                                 e.checked = fmobj.allbox.checked;
                             }
                         }
                     }
                     function CheckCheckAll(cb) {	
                         var fmobj = document.theForm;
                         var TotalBoxes = 0;
                         var TotalOn = 0;
                         for (var i=0;i<fmobj.elements.length;i++) {
                             var e = fmobj.elements[i];
                             if ((e.name != 'allbox') && (e.type=='checkbox')) {
                                 TotalBoxes++;
                                 if (e.checked) {
                                     TotalOn++;
                                 }
                             }
                         }
                         if (TotalBoxes==TotalOn) {fmobj.allbox.checked=true;}
                         else {fmobj.allbox.checked=false;}
                     }
                     //-->
                     </script>
                     ";
						  
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'act'  , 'mysql' ),
											      2 => array( 'code' , 'dotool'),
										 ) , "theForm"     );
		
		if ( $this->mysql_version >= 32303 )
		{
		
			$SKIN->td_header[] = array( "Table"      , "20%" );
			$SKIN->td_header[] = array( "Rows"       , "10%" );
			$SKIN->td_header[] = array( "TBL Size"   , "20%" );
			$SKIN->td_header[] = array( "Index Size" , "20%" );
			$SKIN->td_header[] = array( "Export"     , "10%" );
			$SKIN->td_header[] = array( '<input name="allbox" type="checkbox" value="Check All" onClick="CheckAll();">'     , "10%" );
			
			$ADMIN->html .= $SKIN->start_table( "Invision Power Board Tables" );
			
			$DB->query("SHOW TABLE STATUS FROM `".$INFO['sql_database']."`");
			
			while ( $r = $DB->fetch_row() )
			{
				// Check to ensure it's a table for this install...
				
				if ( ! preg_match( "/^".$INFO['sql_tbl_prefix']."/", $r['Name'] ) )
				{
					continue;
				}
				
				$idx_size += $r['Index_length'];
				$tbl_size += $r['Data_length'];
				
				$iBit = ($r['Index_length'] > 0) ? 1 : 0;
				$tBit = ($r['Data_length'] > 0)  ? 1 : 0;
				
				$idx = $this->gen_size( $r['Index_length'], 3, $iBit );
				$tbl = $this->gen_size( $r['Data_length'] , 3, $tBit );
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b><span style='font-size:12px'><a href='{$SKIN->base_url}&act=mysql&code=runsql&query=".urlencode("SELECT * FROM {$r['Name']}")."'>{$r['Name']}</a></span></b>",
														  "<center>{$r['Rows']}</center>",
														  "<div align='right'><span style='color:blue;font-size:12px'>{$tbl[0]} {$tbl[1]}</span></div>",
														  "<div align='right'>{$idx[0]} {$idx[1]}</div>",
														  "<center><a href='{$SKIN->base_url}&act=mysql&code=export_tbl&tbl={$r['Name']}'>Export</a></center></b>",
														  "<center><input name=\"tbl_{$r['Name']}\" value=1 type='checkbox' onClick=\"CheckCheckAll();\"></center>",
												 )      );
			}
			
			$total = $idx_size + $tbl_size;
			
			$iBit = ($idx_size > 0) ? 1 : 0;
			$tBit = ($tbl_size > 0)  ? 1 : 0;
			$oBit = ($total    > 0)  ? 1 : 0;
			
			$idx = $this->gen_size( $idx_size , 3, $iBit );
			$tbl = $this->gen_size( $tbl_size , 3, $tBit );
			$tot = $this->gen_size( $total    , 3, $oBit );
			
			$ADMIN->html .= $SKIN->add_td_row( array ("&nbsp;",
													  "&nbsp;",
													  "<div align='right'><b>{$tbl[0]} {$tbl[1]}</b></div>",
													  "<div align='right'><b>{$idx[0]} {$idx[1]}</b></div>",
													  "<div align='right'>Totals (<b>{$tot[0]} {$tot[1]}</b>)</div>",
													  
											 )       );
											 
		}
		else
		{
			// display a basic information table
			
			$SKIN->td_header[] = array( "Table"      , "60%" );
			$SKIN->td_header[] = array( "Rows"       , "30%" );
			$SKIN->td_header[] = array( '<input name="allbox" type="checkbox" value="Check All" onClick="CheckAll();">'     , "10%" );
			
			$ADMIN->html .= $SKIN->start_table( "Invision Power Board Tables" );
			
			$tables = $DB->get_table_names();
			
			foreach($tables as $tbl)
			{
				// Ensure that we're only peeking at IBF tables
				
				if ( ! preg_match( "/^".$INFO['sql_tbl_prefix']."/", $tbl ) )
				{
					continue;
				}
				
				$DB->query("SELECT COUNT(*) AS Rows FROM $tbl");
				
				$cnt = $DB->fetch_row();
				
				$ADMIN->html .= $SKIN->add_td_row( array( "<b><span style='font-size:12px'>$tbl</span></b>",
														  "<center>{$cnt['Rows']}</center>",
														  "<center><input name='tbl_$tbl' type='checkbox' onClick=\"CheckCheckAll(this);\"></center>",
												 )      );
												 
			}
			
		}
		
		//----------------------------
		// Add in the bottom stuff
		//----------------------------
											 
		if ( $this->mysql_version < 32322 )
		{
			$ADMIN->html .= $SKIN->add_td_basic( "<select id='button' name='tool'>
													<option value='optimize'>Optimize Selected Tables</option>
												  </select>
												 <input type='submit' value='Go!' id='button'></form>", "center", "tdrow2" );
		}
		else
		{
										 
			$ADMIN->html .= $SKIN->add_td_basic( "<select id='button' name='tool'>
													<option value='optimize'>Optimize Selected Tables</option>
													<option value='repair'>Repair Selected Tables</option>
													<option value='check'>Check Selected Tables</option>
													<option value='analyze'>Analyze Selected Tables</option>
												  </select>
												 <input type='submit' value='Go!' id='button'></form>", "center", "tdrow2" );
		}
			
		$ADMIN->html .= $SKIN->end_table();
		
		
		//----------------------------
		
		$ADMIN->html .= $SKIN->start_form( array( 1 => array( 'act'  , 'mysql' ),
											      2 => array( 'code' , 'runsql'),
										 )      );
		
		$SKIN->td_header[] = array( "Table"      , "30%" );
		$SKIN->td_header[] = array( "Rows"       , "70%" );
		
		$ADMIN->html .= $SKIN->start_table( "Run a Query" );
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Manual Query</b><br>Advanced Users Only",
												  $SKIN->form_textarea("query", "" ),
												 )      );
												 
		$ADMIN->html .= $SKIN->end_form("Run Query");
		$ADMIN->html .= $SKIN->end_table();
		
		
		//+-------------------------------
		
		$ADMIN->output();
	
	}
	
	//-------------------------------------------------------------
	// Convert chars into byte sizes
	// (Based on a similar function in phpmyadmin)
	// "Based on?" my ass!
	// Ok, so I pretty much cut n' pasted it - but it's late and I
	// have to get this done.
	// I did make it pretty though - does that count?
	// Oh CMON! A zillion lines of new code and you can't get over
	// a few lines "borrowed" from another script?
	//-------------------------------------------------------------
	
	function gen_size($val, $li, $sepa )
	{
		$sep     = pow(10, $sepa);
		$li      = pow(10, $li);
		$retval  = $val;
		$unit    = 'Bytes';
	
		if ($val >= $li * 1000000)
		{
			$val = round( $val / (1073741824/$sep) ) / $sep;
			$unit  = 'GB';
		}
		else if ($val >= $li*1000)
		{
			$val = round( $val / (1048576/$sep) ) / $sep;
			$unit  = 'MB';
		}
		else if ($val >= $li)
		{
			$val = round( $val / (1024/$sep) ) / $sep;
			$unit  = 'KB';
		}
		if ($unit != 'Bytes')
		{
			$retval = number_format($val, $sepa, '.', ',');
		}
		else
		{
			$retval = number_format($val, 0, '.', ',');
		}
		
		return array($retval, $unit);
    }
    
    function gzip_four_chars($val)
	{
		for ($i = 0; $i < 4; $i ++)
		{
			$return .= chr($val % 256);
			$val     = floor($val / 256);
		}
		return $return;
	} 
	
	
	
}


?>