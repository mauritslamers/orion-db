<?php

class OrionFw_DBObject {
	
	private $_fieldnames = array();
	private $_tablename = "";
	private $_initialised = false;
	public $type = "";
	
	function __construct($tablename){
		/** 
			Function to set up the class itself
			It creates the $this->_fieldnames array with all the fieldnames belonging to the table the class is initiated with
			It also creates the $this->[fieldname] properties and the $this->_tablename property
		*/		
		// first retrieve the field names of the table
		$tablename = cleansql($tablename);
		
		$query = "SHOW COLUMNS from " . $tablename;
		$result = mysql_query($query) or fataldberror("Error setting up the class of table " . $tablename . ": " . mysql_error());
		$this->_tablename = $tablename;
		$this->type = ucfirst($tablename);
		$numberofrecords = mysql_num_rows($result);
		for($index=0;$index<$numberofrecords;$index++){
			$currentrecord = mysql_fetch_array($result);
			$fieldname = $currentrecord['Field'];
			$this->_fieldnames[] = $fieldname;
			$codetoeval = "\$this->$fieldname = '';";
			eval($codetoeval);
			
			/*$fieldtype = strtolower($currentrecord['Type']);
			$varcharcomp = substr($fieldtype,0,8);
			if($varcharcomp == "varchar("){
				$propname = $fieldname . "_maxlength";
				$proplength = substr($fieldtype,8,2); 
				$codetoeval = "\$this->$propname = $proplength";
				eval($codetoeval);
			}*/
		}
	}	

	function init($id){
		$tmpid = cleansql($id);
		$query = "select * from " . $this->_tablename . " where id = " . $tmpid;

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
				$currentvalue = eval("return \$data->$currentfieldname");
				$values[] = mysql_real_escape_string($currentvalue);
			}	
		}
		if(count($properties)>0){
			$propertiesquery = join(",",$properties);
			$valuesquery = join(",",$values);
			$query = $querystart . " (" . $propertiesquery . ") VALUES (" . $valuesquery . ")";
			$errormessage = "Error creating a new record in the table " . $this->_tablename;
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error());
			$lastid = mysql_insert_id();
			$this->init($lastid);
		}
	}
		
	function update(stdClass $data){
		// function to update an existing record in the database
		// the id property needs to be present in the $data object
		$querystart = "UPDATE " . $this->_tablename . " set ";
		$key_value_sets = array();
		if(isset($data->id)){
			//$data->id MUST have value
			$currentid = $data->id;
			for($index=0;$index<count($this->_fieldnames);$index++){
				$currentfieldname = $this->_fieldnames[$index];
				if(property_exists($data,$currentfieldname)){
					$currentvalue = eval("return \$data->$currenfieldname");
					$tmpkeyvalueset = $currentfieldname . "=" . mysql_real_escape_string($currentvalue);
				}	
			}	
			if(count($key_value_sets)>0){
				$keyvaluequery = join(",", $keyvaluesets);
				$query = $querystart . $keyvaluequery . " where id=" . $currentid;
				$errormessage = "Error updating the existing record with id " . $currentid . " in the table " . $this->_tablename;
				mysql_query($query) or fataldberror($errormessage . ": " . mysql_error());
				// re-init object
				$this->init($currentid); 
			}
		}
	}
	
	function delete($data){
		// function to delete the record indicated by $data or the current record if data happens to be null.
		// If the object is not initialised, do nothing
		$query = "";
		if($data == NULL){
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
		if($query != ""){
			$errormessage = "Error deleting the record with id " . $currentid . " in the table " . $this->_tablename;
			mysql_query($query) or fataldberror($errormessage . ": " . mysql_error());
		}
	}	
}

?>