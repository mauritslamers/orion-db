<?php

class OrionFW_DBCollection {
	// collection to hold a set of objects of a specific type
	
	public $records = array();
	public $ids = array();
	
	function __construct(OrionFW_DBQueryInfo $info){
	  /// function to construct the collection object
	  /// \param[in] $info An object containing at least the property tablename
	  
	  /// $info can also contain an object called conditions
	  /// The conditions object recognises the following properties
	  /// - ids : return only ids in this comma separated string
	  /// - order : sort order
	  /// - fieldnames : add a condition based on a specific field name. If the field name is not 
	  ///                a property of the object, it is ignored
	  
	  
		// first find out whether the table name exists.
		// This can be done by initialising one object as the autoload function creating the intended class 
		// errors out (does not create the class) when a false table is encountered
		
		// get basic information from the object
		$tableNameExists = property_exists($info,'tablename');
		$conditionsFieldExists = property_exists($info,'conditions');
		
		if($tableNameExists) {
   		$tablename = cleansql($info->tablename);        
    		// maybe a check whether $tablename contains php code, which seems unlikely as it would violate the URL
    		$tmpobject = eval("return new " . $tablename . "_class;");
    		// if the class does not exist, PHP dies here.
    		
    		if(is_object($tmpobject)){
    			// even when $info->fieldnamelist is set, override it to only get all ids for this table
    			$info->fieldnamelist = "id";
    			//print_r($info);
    			$tmpQueryObject = new OrionFW_DBQuery;
    			$query = $tmpQueryObject->createSelectQuery($info);
    			//echo $query;
    		/*	$query = "select id from " . cleansql($tablename);
    			
    			
    			
    			//hack
    			if($tablename == "module"){
    				$query .= " where collegeyear=2007";	
    			}
    			// hack 2
    			if($tablename == "mod_edu"){
    				$query .= " where module_id in (select id from module where collegeyear=2007)";	
    			}
    			if($orderFieldExists){
    			   $query .= " ORDER BY " . $orderField;
    			}*/
    			$errormessage = "Error while retrieving a collection from table " . $tablename;
    			$result = mysql_query($query) or fataldberror($query, $errormessage . ": " . mysql_error());
    			$numrows = mysql_num_rows($result);
    			if($numrows>0){
    				for($index=0;$index<$numrows;$index++){
    					$currentrecord = mysql_fetch_array($result);
    					$currentid = $currentrecord['id'];
    					$newobject = eval("return new " . $tablename . "_class;");
    					$newobject->init($currentid);
    					$this->records[] = $newobject;
    					$this->ids[] = $currentid;
    				}	
    			}
    		}
		}
		else {
         // create an empty collection object, so do nothing
		}
	}
	
}

?>