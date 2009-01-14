<?php

class OrionDB_Object {
	
	private $_fieldnames = array(); // needs to be private to prevent exposure to JSON
	private $_completefieldtypes = array(); // needs to be private
	private $_fieldtypes = array(); // needs to be private to prevent exposure to JSON
	private $_fieldlimits = array(); // needs to be private to prevent exposure to JSON
	private $_tablename = ""; // needs to be private to prevent exposure to JSON
	private $_initialised = false; // needs to be private to prevent exposure to JSON
	public $type = "";
	
	// a few SC specific changes
	//        refreshURL: "/contacts?refresh=123",
   //     updateURL: "/contacts/123?update=Y",
   //     destroyURL: "/contacts/123",
	public $refreshURL = "";
	public $updateURL = "";
	public $destroyURL = "";
	
	function __construct($tablename){
		/** 
			Function to set up the class itself
			It creates the $this->_fieldnames array with all the fieldnames belonging to the table the class is initiated with
			It also creates the $this->[fieldname] properties and the $this->_tablename property
		*/		
		// first retrieve the field names of the table
		global $ORIONDB_DB;
    global $ORIONDBCFG_filter_field_names;
		
		$this->_tablename = $tablename;
		$this->type = $tablename;

    // get all the fieldnames
    $allfields = $ORIONDB_DB->tablecolumns($tablename);
    $filteredfields = array();
    // filter the fieldnames to remove the field names that should not end up in the object according to the configuration
    if(array_key_exists($tablename,$ORIONDBCFG_filter_field_names)){
      // if the filter does not exist, don't filter
      foreach($allfields as $field){
        $passfieldname = true;
        foreach($ORIONDBCFG_filter_field_names[$tablename] as $key=>$value){
          if($value){
            if($value == $field['fieldname']) $passfieldname = false; 
          } 
        } 
        if($passfieldname) array_push($filteredfields, $field);
      }      
    } else {
      // if no filtering needs to be done, pass on all fields as filtered
      $filteredfields = $allfields;
    }
    
    // walk through all fields and set up the object
    if(count($filteredfields)>0){
      foreach($filteredfields as $field){
        $fieldname=$field['fieldname'];
        $this->_fieldtypes[$fieldname] = $field['type'];
        $this->_fieldlimits[$fieldname] = $field['size'];
        $this->_fieldnames[] = $fieldname;
	      $codetoeval = "\$this->$fieldname = '';";
	      eval($codetoeval);
      }
    }
	}	

   // As the field types and limits are private to prevent exposure to JSON,
   // we still want to be able to tell what the field types and limits are to other PHP functions.
   // These functions provide this data.

   function getFieldType($fieldname){
      /// This function returns the field type of the given fieldname or returns false if the fieldname does not exist
      /// \param[in] $fieldname The name of the field
      /// \returns The fieldtype or false if the fieldname does not exist
      if(array_key_exists($fieldname,$this->_fieldtypes)){
         return $this->_fieldtypes[$fieldname];
      }
      else {
         return false;
      }
   }

   function getFieldLimit($fieldname){
      /// This function returns the field type of the given fieldname or returns false if the fieldname does not exist
      /// \param[in] $fieldname The name of the field
      /// \returns The fieldtype or false if the fieldname does not exist
      if(array_key_exists($fieldname,$this->_fieldlimits)){
         return $this->_fieldlimits[$fieldname];
      }
      else {
         return false;
      }
   }

   function fieldIsText($fieldname){
      /// Function to return whether a type of a field is textual in nature (type varchar, char, all types of Text, date and timestamp)
      /// \param[in] $fieldname the name of the field to check
      /// \return True if the field is a text field, false if the field is numerical, and null if the field does not exist
      if(array_key_exists($fieldname,$this->_fieldtypes)){
         $tmpType = $this->_fieldtypes[$fieldname];
         $charpos = strpos($tmpType,'char');
         $textpos = strpos($tmpType,'text');
         $datepos = strpos($tmpType,'date');
         //logmessage("Typename of " . $fieldname . " = " . $tmpType);
         if($charpos || $textpos || ($tmpType == 'date') || ($tmpType == 'timestamp')){
            return true;
         } 
         else {
            return false;
         }
      } 
      else {
         return null;
      }
   }

  
	function init($id){
	  global $ORIONDB_DB;
	  
	  $record = $ORIONDB_DB->getrecordbyid($this->_tablename,$id);
	  if($record){
   	  foreach($this->_fieldnames as $currentfieldname){
   				$codetoeval = "\$this->$currentfieldname = htmlentities(\$record['$currentfieldname']);";
   				eval($codetoeval);
   		}
   		$this->_initialised = true;
   			// setup the refresh, update and destroy URL's for this record
   		global $ORIONDBCFG_baseURI;
   		$uri = $ORIONDBCFG_baseURI . "/" . $this->_tablename . "/" . $id;
	    $this->refreshURL = $this->updateURL = $this->destroyURL = $uri;     
			return true;
		} else {
			return false;
		}
	}
	
	function init_by_query(OrionDB_QueryInfo $info){  
	  /// Function to init an object by QueryInfo object. Used by the ORIONDB authentication module
	  /// to get the passwords etc. So, it does not filter out the filtered fields set in the config
	   
	  	$tmpQueryObject = new OrionDB_Query;
	  	//print_r($info);
   	$query = $tmpQueryObject->createSelectQuery($info);
      //logmessage($query);
      $errormessage="Error when retrieving a record by query from table " . $this->_tablename;
      $result = mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);
      $numrows = mysql_num_rows($result);
      if($numrows == 1){
         // init the current record with all data in the record (the filtered fields are in the result)
         $currentrecord = mysql_fetch_array($result);
         //print_r($currentrecord);
         $numfields = mysql_num_fields($result);
         for($index=0;$index<$numfields;$index++){
            $currentfieldname = mysql_field_name($result,$index);
            $currentfieldvalue = $currentrecord[$index];
            $codetoeval = "\$this->$currentfieldname = '$currentfieldvalue';";
            eval($codetoeval);
         }
         //print_r($this);
      }
	}
		
	function create(stdClass $data){
		// Function to create a new record in the database.
		// $data is a PHP object
		$querystart = "INSERT into " . $this->_tablename;
		$properties = array();
		$values = array();
		for($index=0;$index<count($this->_fieldnames);$index++){
			$currentfieldname = $this->_fieldnames[$index];
			if(property_exists($data,$currentfieldname)){
				$properties[] = $currentfieldname;
				$currentvalue = eval("return \$data->$currentfieldname;");
				if(!$currentvalue) $currentvalue = 'NULL'; // if nothing is in the $currentvalue, put in NULL
				$values[] = mysql_real_escape_string($currentvalue);
			}	
		}
		if(count($properties)>0){
			$propertiesquery = join(",",$properties);
			$valuesquery = join(",",$values);
			$query = $querystart . " (" . $propertiesquery . ") VALUES (" . $valuesquery . ")";
			logmessage("CREATE action in object " . $this->_tablename . " with query: " . $query);
			$errormessage = "Error creating a new record in the table " . $this->_tablename;
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);
			$lastid = mysql_insert_id();
			$this->init($lastid);
		}
	}
		
	function update(stdClass $data){
		// function to update an existing record in the database
		// the id property needs to be present in the $data object
		//print_r($data);
		//die();
		$querystart = "UPDATE " . $this->_tablename . " set ";
		$key_value_sets = array();
		if(property_exists($data, 'id')){
			//$data->id MUST have value
			$currentid = $data->id;
			logmessage("Updating " . $this->_tablename . " id " . $currentid . " with " . count($this->_fieldnames) . " fields");
			for($index=0;$index<count($this->_fieldnames);$index++){
				$currentfieldname = $this->_fieldnames[$index];
				if(property_exists($data,$currentfieldname) && ($currentfieldname != 'id')){ // prevent overwriting of id
					$currentvalue = eval("return \$data->$currentfieldname;");
					//logmessage("Fieldname: " . $currentfieldname . ": " . $currentvalue);
					if(is_null($currentvalue)){ // checking null
   					$currentvalue = 'NULL';
					}
					else {
					  if($this->fieldIsText($currentfieldname)){ 
					     $currentvalue = "'" . mysql_real_escape_string($currentvalue) . "'";
					  } 
					  else {
					     $currentvalue = mysql_real_escape_string($currentvalue);
					  }  
					}
					// no mysql protection because that has already been taken care of
					$key_value_sets[] = $currentfieldname . "=" . $currentvalue; 
				}	
			}	
			if(count($key_value_sets)>0){
			   logmessage("Assembling query for id" . $currentid);
				$keyvaluequery = join(",", $key_value_sets);
				$query = $querystart . $keyvaluequery . " where id=" . $currentid;
            logmessage("UPDATE action in object " . $this->_tablename . " with query: " . $query);
				$errormessage = "Error updating the existing record with id " . $currentid . " in the table " . $this->_tablename;
				mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(),$query);
				// re-init object so it will return the correct data
				$this->init($currentid); 
			}
		}
		else {
		  logmessage("No id given for update!");
		}
	}
	
	function delete($data = NULL){
		// function to delete the record indicated by $data or the current record if data happens to be null.
		// If the object is not initialised, do nothing
		$query = "";
		if(is_null($data)){
			if($this->_initialised){
				// get id
				$currentid = $this->id;
				$query = "DELETE FROM " . $this->_tablename . " WHERE id = " . $currentid;	
			}
		} else {
			// get id
			if(isset($data->id)){
				// $data->id must have a value, therefore isset instead of property_exists
				$currentid = $data->id;
				$query = "DELETE FROM " . $this->_tablename . " WHERE id = " . $currentid;	
			}
		}
		if($query){
			$errormessage = "Error deleting the record with id " . $currentid . " in the table " . $this->_tablename;
			logmessage("DELETE action in object " . $this->_tablename . " with query: " . $query);
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(),$query);
		}
	}	
}

?>