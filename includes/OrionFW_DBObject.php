<?php

class OrionFw_DBObject {
	
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
		$tablename = cleansql($tablename);
		
		$query = "SHOW COLUMNS from " . $tablename;
		$result = mysql_query($query) or fataldberror("Error setting up the class of table " . $tablename . ": " . mysql_error(), $query);
		$this->_tablename = $tablename;
		$this->type = $tablename;

		$numberofrecords = mysql_num_rows($result);
		for($index=0;$index<$numberofrecords;$index++){
			$currentrecord = mysql_fetch_array($result);
			$fieldname = $currentrecord['Field'];
			$this->_fieldnames[] = $fieldname;
			// getting varchar(20) to varchar as fieldtypename and 20 as fieldtypelimit
			$fieldtypedef = $currentrecord['Type'];
			$parenthesis_open_pos = strpos($fieldtypedef,'(');
			if($parenthesis_open_pos){ // ( found, find ) and take name and length
   			$parenthesis_close_pos = strpos($fieldtypedef,')');
   			$fieldtypename = substr($fieldtypedef,0,$parenthesis_open_pos);
   			$fieldtypelimit = substr($fieldtypedef,($parenthesis_open_pos+1),($parenthesis_close_pos - $parenthesis_open_pos - 1));
			} 
			else { // no ( found, so just take the entire name
			   $fieldtypename = $fieldtypedef;
			   $fieldtypelimit = 0;
			}
			$this->_fieldtypes[$fieldname] = $fieldtypename;
			$this->_fieldlimits[$fieldname] = $fieldtypelimit;
			$this->_completefieldtypes[$fieldname] = $fieldtypedef;
			//echo "fieldtypename: $fieldtypename, fieldtypelimit: $fieldtypelimit <br>";
			// create the property
			$codetoeval = "\$this->$fieldname = '';";
			eval($codetoeval);
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
         logmessage("Typename of " . $fieldname . " = " . $tmpType);
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
		$tmpid = cleansql($id);
		$query = "select * from " . $this->_tablename . " where id = " . $tmpid;
		logmessage("INIT of object " . $this->_tablename . " with query: " . $query);

		$errormessage = "Error when retrieving a record from table " . $this->_tablename . " with id " . $tmpid;
		$result = mysql_query($query) or fataldberror($errormessage . ": " . mysql_error(), $query);
		
		// only accept one record
		$numofrecords = mysql_num_rows($result);
		if($numofrecords == 1){
			$currentrecord = mysql_fetch_array($result);
			for($index=0;$index<count($this->_fieldnames);$index++){
				$currentfieldname = $this->_fieldnames[$index];
				$codetoeval = "\$this->$currentfieldname = htmlentities(\$currentrecord['$currentfieldname']);";
				eval($codetoeval);
			}
			$this->_initialised = true;
			// setup the refresh, update and destroy URL's for this record
			global $ORIONCFG_baseURI;
	      $this->refreshURL = $ORIONCFG_baseURI . "/" . $this->_tablename . "/" . $id;
	      $this->updateURL = $ORIONCFG_baseURI. "/" . $this->_tablename . "/" . $id;
	      $this->destroyURL = $ORIONCFG_baseURI . "/" . $this->_tablename . "/" . $id;	      
			return true;
		} else {
			return false;
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
					//$currentvalue = $data[$currentfieldname];
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
					// no mysql protection because that has already been care of
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
				// re-init object
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