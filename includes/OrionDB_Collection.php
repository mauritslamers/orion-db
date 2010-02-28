<?php

class OrionDB_Collection {
	// collection to hold a set of objects of a specific type
	
	public $records = array();
	public $ids = array();
	
  function __construct($info = null){
	  /// function to construct the collection object
	  /// \param[in] $info An object containing at least the property tablename
	  
	  /// $info can also contain an object called conditions
	  /// The conditions object recognises the following properties
	  /// - ids : return only ids in this comma separated string
	  /// - order : sort order
	  /// - fieldnames : add a condition based on a specific field name. If the field name is not 
	  ///                a property of the object, it is ignored
	  
	  // we want to be able to call this function without parameters, so we cannot 
	  // check at the entrance. So, let's make a check here: if $info is not of the correct type, 
	  // make an empty collection object
	  global $ORIONDB_DB;
	  
    if($info instanceof OrionDB_QueryInfo){
    	  
   		// first find out whether the table name exists. We can find out by asking the DB plugin
   		// get basic information from the object
   		$tableNameExists = property_exists($info,'tablename');
   		$conditionsFieldExists = property_exists($info,'conditions');
   		$tablename = $info->tablename;
   		$table_exists = $ORIONDB_DB->table_exists($tablename);
   	  
   		if($tableNameExists && $table_exists) {
   			// even when $info->fieldnamelist is set, override it to only get all ids for this table
   			$info->fieldnamelist = "id";
   			//print_r($info);
   			$tmpQueryObject = new OrionDB_Query;
   			$query = $tmpQueryObject->createSelectQuery($info);
   			//echo $query;
   			
   			$queryresult = $ORIONDB_DB->runquery($tablename, $query);
   			$numrows = ($queryresult)? count($queryresult): 0; // count(false) returns 1 !!???
   			if($numrows>0){
   				for($index=0;$index<$numrows;$index++){
   					$currentrecord = $queryresult[$index];
   					$currentid = $currentrecord['id'];
   					$newobject = eval("return new " . $tablename . "_class;");
						// modify init of 'OrionDB_Object' to allow second, column limiting argument ('+' delineated string)
   					if(array_key_exists('returncolumns',$info->conditions)) {
						$newobject->init($currentid, $info->conditions['returncolumns']);
					} else {
						$newobject->init($currentid);
					}
   					$this->records[] = $newobject; // add the new object to the record array
   					$this->ids[] = $currentid; // idem for the id
   				}	
   			}
   		}
   		else {
   		  // this part is executed when the loaded class is not an instance of OrionDB_Object
   		  // remove the properties to show an empty object
   		  unset($this->records);
   		  unset($this->ids);
   		}   
   	} // end if $info instanceof 
  } // end constructor
} // end class	


?>