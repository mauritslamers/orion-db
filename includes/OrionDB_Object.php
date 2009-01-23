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
	  global $ORIONDB_DB; 
	  $tmpQueryObject = new OrionDB_Query;
   	$query = $tmpQueryObject->createSelectQuery($info);
    $tmprecord = $ORIONDB_DB->getrecordbyquery($this->_tablename,$query);
    
    if($tmprecord){
      foreach($tmprecord as $key=>$value){
        if(is_string($key)){ 
              // for some strange reason foreach runs every item twice, first with string association and 
              // second with the index number itself. Only the association is of any interest here.
          $codetoeval="\$this->$key = '$value';"; 
          //logmessage($codetoeval);
          eval($codetoeval);
        }
      }
      return true; 
    }
    else return false;
    
	} // end init_by_query

  private function filterfieldnames(stdClass $data, $filter_id = false){
    $resultdata = new stdClass;
    
    if($filter_id){
      // filter out the 'id' field
      $fieldnames_to_allow = array();
      foreach($this->_fieldnames as $value){
         if($value != 'id'){
           $fieldnames_to_allow[] = $value;
         }
      }
    } 
    else $fieldnames_to_allow = $this->_fieldnames;
    
    foreach($data as $key=>$value){
      if(in_array($key, $fieldnames_to_allow)){
         $codetoeval = "\$resultdata->$key = '$value';";
         eval($codetoeval);
      }
    } 
    return $resultdata;
  }
		
	function create(stdClass $data){
		// Function to create a new record in the database.
		// $data is a PHP object
    global $ORIONDB_DB;
    // run through the data to filter out any fields not in $this->_fieldnames
    $filtereddata = $this->filterfieldnames($data, true);
		$newid = $ORIONDB_DB->createrecord($this->_tablename,$filtereddata, $this);
		logmessage("New record created with id $newid");
		if($newid) $this->init($newid);
	}
		
	function update(stdClass $data){
		// function to update an existing record in the database
		// the id property needs to be present in the $data object
    global $ORIONDB_DB;
    
		// do nothing if an id property doesn't exist on $data
    if(property_exists($data,'id')){
      $filtereddata = $this->filterfieldnames($data,true);
		  $ORIONDB_DB->updaterecord($this->_tablename, $data, $this);
		  $this->init($data->id);
    }
    else {
      logmessage("No id given for update!");
      return false;
    }
	}
	
	function delete($data = NULL){
		// function to delete the record indicated by $data or the current record if data happens to be null.
		// If the object is not initialised, do nothing
		global $ORIONDB_DB;
		
		if(is_null($data)){
			if($this->_initialised) $currentid = $this->id;
		} else {
			// get id
				// $data->id must have a value, therefore isset instead of property_exists
			if(isset($data->id)) $currentid = $data->id;
		}
	  if($currentid) $ORIONDB_DB->deleterecord($this->_tablename,$currentid);
	}	
}

?>