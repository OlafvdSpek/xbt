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
|   > mySQL DB abstraction module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/



class db_driver {

    var $obj = array ( "sql_database"   => ""         ,
                       "sql_user"       => "root"     ,
                       "sql_pass"       => ""         ,
                       "sql_host"       => "localhost",
                       "sql_port"       => ""         ,
                       "persistent"     => "0"         ,
                       "sql_tbl_prefix"        => "ibf_"      ,
                       "cached_queries" => array(),
                       'debug'          => 0,
                     );
                     
     var $query_id      = "";
     var $connection_id = "";
     var $query_count   = 0;
     var $record_row    = array();
     var $return_die    = 0;
     var $error         = "";
     var $failed        = 0;
                  
    /*========================================================================*/
    // Connect to the database                 
    /*========================================================================*/  
                   
    function connect() {
    
    	if ($this->obj['persistent'])
    	{
    	    $this->connection_id = mysql_pconnect( $this->obj['sql_host'] ,
												   $this->obj['sql_user'] ,
												   $this->obj['sql_pass'] 
												);
        }
        else
        {
			$this->connection_id = mysql_connect( $this->obj['sql_host'] ,
												  $this->obj['sql_user'] ,
												  $this->obj['sql_pass'] 
												);
		}
		
        if ( !mysql_select_db($this->obj['sql_database'], $this->connection_id) )
        {
            echo ("ERROR: Cannot find database ".$this->obj['sql_database']);
        }
    }
    
    
    
    /*========================================================================*/
    // Process a query
    /*========================================================================*/
    
    function query($the_query, $bypass=0) {
    	
    	//--------------------------------------
        // Change the table prefix if needed
        //--------------------------------------
        
        if ($bypass != 1)
        {
			if ($this->obj['sql_tbl_prefix'] != "ibf_")
			{
			   $the_query = preg_replace("/ibf_(\S+?)([\s\.,]|$)/", $this->obj['sql_tbl_prefix']."\\1\\2", $the_query);
			}
        }
        
        if ($this->obj['debug'])
        {
    		global $Debug, $ibforums;
    		
    		$Debug->startTimer();
    	}
    	
        $this->query_id = mysql_query($the_query, $this->connection_id);
      
        if (! $this->query_id )
        {
            $this->fatal_error("mySQL query error: $the_query");
        }
        
        if ($this->obj['debug'])
        {
        	$endtime = $Debug->endTimer();
        	
        	if ( preg_match( "/^select/i", $the_query ) )
        	{
        		$eid = mysql_query("EXPLAIN $the_query", $this->connection_id);
        		$ibforums->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FFE8F3' align='center'>
										   <tr>
										   	 <td colspan='8' style='font-size:14px' bgcolor='#FFC5Cb'><b>Select Query</b></td>
										   </tr>
										   <tr>
										    <td colspan='8' style='font-family:courier, monaco, arial;font-size:14px;color:black'>$the_query</td>
										   </tr>
										   <tr bgcolor='#FFC5Cb'>
											 <td><b>table</b></td><td><b>type</b></td><td><b>possible_keys</b></td>
											 <td><b>key</b></td><td><b>key_len</b></td><td><b>ref</b></td>
											 <td><b>rows</b></td><td><b>Extra</b></td>
										   </tr>\n";
				while( $array = mysql_fetch_array($eid) )
				{
					$type_col = '#FFFFFF';
					
					if ($array['type'] == 'ref' or $array['type'] == 'eq_ref' or $array['type'] == 'const')
					{
						$type_col = '#D8FFD4';
					}
					else if ($array['type'] == 'ALL')
					{
						$type_col = '#FFEEBA';
					}
					
					$ibforums->debug_html .= "<tr bgcolor='#FFFFFF'>
											 <td>$array[table]&nbsp;</td>
											 <td bgcolor='$type_col'>$array[type]&nbsp;</td>
											 <td>$array[possible_keys]&nbsp;</td>
											 <td>$array[key]&nbsp;</td>
											 <td>$array[key_len]&nbsp;</td>
											 <td>$array[ref]&nbsp;</td>
											 <td>$array[rows]&nbsp;</td>
											 <td>$array[Extra]&nbsp;</td>
										   </tr>\n";
				}
				
				if ($endtime > 0.1)
				{
					$endtime = "<span style='color:red'><b>$endtime</b></span>";
				}
				
				$ibforums->debug_html .= "<tr>
										  <td colspan='8' bgcolor='#FFD6DC' style='font-size:14px'><b>mySQL time</b>: $endtime</b></td>
										  </tr>
										  </table>\n<br />\n";
			}
			else
			{
			  $ibforums->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FEFEFE'  align='center'>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>Non Select Query</b></td>
										 </tr>
										 <tr>
										  <td style='font-family:courier, monaco, arial;font-size:14px'>$the_query</td>
										 </tr>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>mySQL time</b>: $endtime</span></td>
										 </tr>
										</table><br />\n\n";
			}
		}
		
		$this->query_count++;
        
        $this->obj['cached_queries'][] = $the_query;
        
        return $this->query_id;
    }
    
    
    /*========================================================================*/
    // Fetch a row based on the last query
    /*========================================================================*/
    
    function fetch_row($query_id = "") {
    
    	if ($query_id == "")
    	{
    		$query_id = $this->query_id;
    	}
    	
        $this->record_row = mysql_fetch_array($query_id, MYSQL_ASSOC);
        
        return $this->record_row;
        
    }

	/*========================================================================*/
    // Fetch the number of rows affected by the last query
    /*========================================================================*/
    
    function get_affected_rows() {
        return mysql_affected_rows($this->connection_id);
    }
    
    /*========================================================================*/
    // Fetch the number of rows in a result set
    /*========================================================================*/
    
    function get_num_rows() {
        return mysql_num_rows($this->query_id);
    }
    
    /*========================================================================*/
    // Fetch the last insert id from an sql autoincrement
    /*========================================================================*/
    
    function get_insert_id() {
        return mysql_insert_id($this->connection_id);
    }  
    
    /*========================================================================*/
    // Return the amount of queries used
    /*========================================================================*/
    
    function get_query_cnt() {
        return $this->query_count;
    }
    
    /*========================================================================*/
    // Free the result set from mySQLs memory
    /*========================================================================*/
    
    function free_result($query_id="") {
    
   		if ($query_id == "") {
    		$query_id = $this->query_id;
    	}
    	
    	@mysql_free_result($query_id);
    }
    
    /*========================================================================*/
    // Shut down the database
    /*========================================================================*/
    
    function close_db() { 
        return mysql_close($this->connection_id);
    }
    
    /*========================================================================*/
    // Return an array of tables
    /*========================================================================*/
    
    function get_table_names() {
    
		$result     = mysql_list_tables($this->obj['sql_database']);
		$num_tables = @mysql_numrows($result);
		for ($i = 0; $i < $num_tables; $i++)
		{
			$tables[] = mysql_tablename($result, $i);
		}
		
		mysql_free_result($result);
		
		return $tables;
   	}
   	
   	/*========================================================================*/
    // Return an array of fields
    /*========================================================================*/
    
    function get_result_fields($query_id="") {
    
   		if ($query_id == "")
   		{
    		$query_id = $this->query_id;
    	}
    
		while ($field = mysql_fetch_field($query_id))
		{
            $Fields[] = $field;
		}
		
		//mysql_free_result($query_id);
		
		return $Fields;
   	}
    
    /*========================================================================*/
    // Basic error handler
    /*========================================================================*/
    
    function fatal_error($the_error) {
    	global $INFO;
    	
    	
    	// Are we simply returning the error?
    	
    	if ($this->return_die == 1)
    	{
    		$this->error    = mysql_error();
    		$this->error_no = mysql_errno();
    		$this->failed   = 1;
    		return;
    	}
    	
    	$the_error .= "\n\nmySQL error: ".mysql_error()."\n";
    	$the_error .= "mySQL error code: ".$this->error_no."\n";
    	$the_error .= "Date: ".date("l dS of F Y h:i:s A");
    	
    	$out = "<html><head><title>Invision Power Board Database Error</title>
    		   <style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
    		   &nbsp;<br><br><blockquote><b>There appears to be an error with the {$INFO['board_name']} database.</b><br>
    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>, if this
    		   does not fix the error, you can contact the board administrator by clicking <a href='mailto:{$INFO['email_in']}?subject=SQL+Error'>here</a>
    		   <br><br><b>Error Returned</b><br>
    		   <form name='mysql'><textarea rows=\"15\" cols=\"60\">".htmlspecialchars($the_error)."</textarea></form><br>We apologise for any inconvenience</blockquote></body></html>";
    		   
    
        echo($out);
        die("");
    }
    
    /*========================================================================*/
    // Create an array from a multidimensional array returning formatted
    // strings ready to use in an INSERT query, saves having to manually format
    // the (INSERT INTO table) ('field', 'field', 'field') VALUES ('val', 'val')
    /*========================================================================*/
    
    function compile_db_insert_string($data) {
    
    	$field_names  = "";
		$field_values = "";
		
		foreach ($data as $k => $v)
		{
			$v = preg_replace( "/'/", "\\'", $v );
			//$v = preg_replace( "/#/", "\\#", $v );
			$field_names  .= "$k,";
			$field_values .= "'$v',";
		}
		
		$field_names  = preg_replace( "/,$/" , "" , $field_names  );
		$field_values = preg_replace( "/,$/" , "" , $field_values );
		
		return array( 'FIELD_NAMES'  => $field_names,
					  'FIELD_VALUES' => $field_values,
					);
	}
	
	/*========================================================================*/
    // Create an array from a multidimensional array returning a formatted
    // string ready to use in an UPDATE query, saves having to manually format
    // the FIELD='val', FIELD='val', FIELD='val'
    /*========================================================================*/
    
    function compile_db_update_string($data) {
		
		$return_string = "";
		
		foreach ($data as $k => $v)
		{
			$v = preg_replace( "/'/", "\\'", $v );
			$return_string .= $k . "='".$v."',";
		}
		
		$return_string = preg_replace( "/,$/" , "" , $return_string );
		
		return $return_string;
	}
	
	/*========================================================================*/
    // Test to see if a field exists by forcing and trapping an error.
    // It ain't pretty, but it do the job don't it, eh?
    // Posh my ass.
    // Return 1 for exists, 0 for not exists and jello for the naked guy
    // Fun fact: The number of times I spelt 'field' as 'feild'in this part: 104
    /*========================================================================*/
    
    function field_exists($field, $table) {
		
		$this->return_die = 1;
		$this->error = "";
		
		$this->query("SELECT COUNT($field) as count FROM $table");
		
		$return = 1;
		
		if ( $this->failed )
		{
			$return = 0;
		}
		
		$this->error = "";
		$this->return_die = 0;
		$this->error_no   = 0;
		$this->failed     = 0;
		
		return $return;
	}
    
} // end class


?>