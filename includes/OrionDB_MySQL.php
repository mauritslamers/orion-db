<?php

/*
  OrionDB MySQL module
  
  Contains the actual DB request functions and error handling

*/

class OrionDB_DB_MySQL {
  
  public $tablenames = array();
  
  function __construct(){
    // The constructor needs to set up the connection and read the names of all tables in the 
    // database
    global $ORIONDBCFG_DB_host, $ORIONDBCFG_DB_user, $ORIONDBCFG_DB_password, $ORIONDBCFG_DB_dbname;
      // get the configuration parameters and set up the connection
    $database = mysql_connect($ORIONDBCFG_DB_host,$ORIONDBCFG_DB_user,$ORIONDBCFG_DB_password)
       or die("Could not connect");
   
     // select the database to use
    //echo $ORIONDBCFG_MySQL_dbname;
    mysql_select_db($ORIONDBCFG_DB_dbname) or die("Could not select database");
     
    // now set up the names of the tables array
    $result = mysql_query("show tables") or fataldberror("Error checking table existance in database: " . mysql_error());
    $numrows = mysql_num_rows($result);
    if($numrows>0){
      for($index=0;$index<$numrows;$index++){
        $currentrecord = mysql_fetch_array($result);
   	    $fieldname = "Tables_in_" . $ORIONDBCFG_DB_dbname;
        array_push($this->tablenames, $currentrecord[$fieldname]);
      } 
    }
  }  // end constructor
  
  
  public function table_exists($tablename){
   // find the name of the table in the array. 
   // MySQL table names are case sensitive, which differs from other databases like PostgreSQL, so
   // do the comparison in lowercase
   $tbname = strtolower($tablename);
   $numitems = count($this->tablenames);
   if(($numitems>0) && $tbname){
     foreach($this->tablenames as $value){
       if(strtolower($value) == $tbname){
        // break if found
         return true;
       }
     }
   } 
   // if execution reaches this point nothing has been found
   return false;
  } // end function table_exists
  
  
  private function filterfieldtype($typedef){
    // Function to return the fieldtype name and constraint
    // the result is returned in an array [('type','varchar'),('size','20')]
    
	  // getting varchar(20) to varchar as fieldtypename and 20 as fieldtypelimit
		$parenthesis_open_pos = strpos($typedef,'(');
		if($parenthesis_open_pos){ // ( found, find ) and take name and length
			$parenthesis_close_pos = strpos($typedef,')');
			$fieldtypename = substr($typedef,0,$parenthesis_open_pos);
			$fieldtypelimit = substr($typedef,($parenthesis_open_pos+1),($parenthesis_close_pos - $parenthesis_open_pos - 1));
		} 
		else { // no ( found, so just take the entire name
		   $fieldtypename = $typedef;
		   $fieldtypelimit = 0;
		}
	  $result = array();
	  $result['type'] = $fieldtypename;
	  $result['size'] = $fieldtypelimit;
	  return $result;
  }
  
  
  function tablecolumns($tablename){
    // Return an array with fieldnames and types
    // clean first 
    $tablename = $this->cleansql($tablename);
		
		$query = "SHOW COLUMNS from " . $tablename;
		$result = mysql_query($query) or 
		    fataldberror("Error setting up the class of table " . $tablename . ": " . mysql_error(), $query);
		
		//process the result
		$resultarray = array();
		$numrows = mysql_num_rows($result);
		if($numrows>0){
		  for($index=0;$index<$numrows;$index++){
		    $row = mysql_fetch_array($result);
        // get type		
		    $fielddef = $this->filterfieldtype($row['Type']);
        // put everything together
 		    $tmparray = array();
		    $tmparray['fieldname'] = $row['Field'];
		    $tmparray['type'] = $fielddef['type'];
		    $tmparray['size'] = $fielddef['size'];
		    $resultarray[] = $tmparray;
		  } 
		}
		return $resultarray;
  }
  
  function cleansql($in){
  	// remove trailing spaces and other trailing rubbish
  	$in = rtrim($in);
  	
  	// escape out mysql code
  	if (get_magic_quotes_gpc()) {		  $out = mysql_real_escape_string(stripslashes($in));	  } else {		  $out = mysql_real_escape_string($in);	  }	  return $out; 	
  }
  
  function getrecordbyid($tablename,$id){
     // get a record by the db id
     // return an associative array with the content of the record
     
 		$tmpid = $this->cleansql($id);
 		$tmptablename = $this->cleansql($tablename);
		$query = "select * from " . $tmptablename . " where id = " . $tmpid;
		//logmessage("INIT of object " . $this->_tablename . " with query: " . $query);

		$errormessage = "Error when retrieving a record from table " . $tmptablename . " with id " . $tmpid;
		$result = mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);

    $numrows = mysql_num_rows($result);
    if($numrows == 1){
       return mysql_fetch_array($result);
    } 
    else return false;
  } // end function getrecordbyid
  
  function getrecordbyquery($tablename,$query){
    // get a record by query
    // return an associative array with the content of the record
    
      $errormessage="Error when retrieving a record by query from table " . $tablename;
      $result = mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);
      $numrows = mysql_num_rows($result);
      if($numrows == 1){
         // init the current record with all data in the record (the filtered fields are in the result)
         return mysql_fetch_array($result);
      }
      else return false;
  }
  
  function createrecord($tablename,stdClass $data){
    // function to create a new record in the DB
    // returns the newly created record ID 
    $tablename = $this->cleansql($tablename);
    $properties = array();
		$values = array();
		foreach($data as $key=>$value){
		  $properties[] = $this->cleansql($key);
      $resultvalue = $value ? $value: 'NULL'; // if $value evaluates false, have NULL for field value
		  $values[] = $this->cleansql($resultvalue);
		}
	
		if(count($properties)>0){
			$propertiesquery = join(",",$properties);
			$valuesquery = join(",",$values);
			$query = "INSERT into " . $tablename;
			$query .= $querystart . " (" . $propertiesquery . ") VALUES (" . $valuesquery . ")";
			logmessage("CREATE action in object " . $tablename . " with query: " . $query);
			$errormessage = "Error creating a new record in the table " . $tablename;
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);
			return mysql_insert_id();
    }
    return false;
  } // end function createrecord
  
  function updaterecord($tablename, stdClass $data){
    // function to update an existing record

  	$currentid = $data->id;
		$key_value_sets = array();
    foreach($data as $key=>$value){
       $valuetosave = $value ? $value : 'NULL';
       $key_value_sets[] = $this->cleansql($key) . '=' . $this->cleansql($valuetosave);
    }
    
    $query = "UPDATE " . $this->_tablename . " set ";
    if(count($key_value_sets)>0){
    	//logmessage("Updating " . $this->_tablename . " id " . $currentid . " with " . count($key_value_sets) . " fields");
      //logmessage("Assembling query for id " . $currentid);
			$keyvaluequery = join(",", $key_value_sets);
			$query .= $keyvaluequery . " where id=" . $currentid;
      logmessage("UPDATE action in object " . $this->_tablename . " with query: " . $query);
			$errormessage = "Error updating the existing record with id " . $currentid . " in the table " . $tablename;
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(),$query);
  }
  
  
  
} // end class OrionDB_DB_MySQL


?>