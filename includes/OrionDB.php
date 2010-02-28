<?php
 /*
 	The Orion PHP Framework
 */

require_once("includes/OrionDB_Object.php"); // standard database object
require_once("includes/OrionDB_Collection.php"); // standard database collection object
require_once('includes/OrionDB_Query.php'); // standard query object classes OrionDB_Query and OrionDB_QueryInfo
require_once('includes/OrionDB_MySQL.php');



function __autoload($classname){
	// generate classes on the fly using the $classname and tablename
	// Classname need to be of the form tablename_class, such as student_class etc
	// If the table does not exist in the DB, the include directory is checked for extra classes
	
	// get table name from $classname
	$lastunderscorepos = strrpos($classname,"_");
	$tablename = substr($classname,0,$lastunderscorepos);
	//logmessage("Searching for table " . $tablename);
	
	// first check whether a table exists with the name of the class
	global $ORIONDB_DB;
	$table_exists = $ORIONDB_DB->table_exists($tablename);
	if($table_exists){
	  // setup the class
	  	$codetoeval = "class " . $classname . " extends OrionDB_Object {";
			$codetoeval .= "function __construct(){ parent::__construct('" . $tablename . "'); } }";
			eval($codetoeval);
			//logmessage("Generated class " . $classname);
	 }
   else {
 			// check for external PHP files to include
			// before requiring the file, check whether it exists	
			$filename = "includes/" . $classname . ".php";
			if(file_exists($filename)){
				require_once $filename;
				//logmessage("Loaded external PHP file " . $filename);
			} else {
			   // make a log note
			   logmessage('Autoload did not succeed in finding a decent source to create a class with. Classname:' . $classname);
				//return false;// do nothing for else, unless this breaks things	
			}
   }
} // end autoload

	



function OrionDB_List(OrionDB_QueryInfo $info){
   /// Function to return a list of a specific type and order
   /// \param[in] $info The data of the request
      
   $list = new OrionDB_Collection($info);
   //print_r($list);
   if(property_exists($list,'records')){
      // catch if a OrionDB_Collection object returns empty, because it could not create OrionDB_Object class objects
      //echo json_encode($list);
	   return $list;
   }
   else {
      return false;
   }   
}

function OrionDB_Update(OrionDB_QueryInfo $info){
   /// Function to update one record of a specific type in the database
   /// \param[in] $type The type of data (same as table name)
   /// \param[in] $data JSON decoded PHP object containing the data
   
}

function OrionDB_Create($requestedResource){
   /// Function to create a record of a specific type in the database - to search for an old one.
   /// If it's to create new records, it should send back the record containing both the new ID and the old _guid.
   /// If it's to search, it should send back an array of matching SC GUIDs.

		// find out if you have a search or not...
		if ($_POST['search_string']) {
			// you have a search...
			// build the query, then run it
			$tmpInfo = new OrionDB_QueryInfo;
			// iterate through $_POST
			$tmpInfo->tablename = $requestedResource;
			$tmpInfo->fieldnamelist = 'guid';
			$searchColumnsAry = explode(',',$_POST['search_columns']);	
			foreach($searchColumnsAry as $columnName){
				// give the search string wildcard ears:
				$tmpInfo->conditions[$columnName] = '%'.$_POST['search_string'].'%';
			}
			$tmpQueryObject = new OrionDB_Query;
			$query = $tmpQueryObject->createSelectQuery($tmpInfo);
			
			global $ORIONDB_DB;
			$outgoingArray = $ORIONDB_DB->runquery($requestedResource,$query);
			
			// ready? respond!
			$testing = array();
			foreach ($outgoingArray as $returnedRecord) {
				$testing[] = $returnedRecord['guid'];
			}
			echo json_encode($testing);
		} else {
			// you don't have a search...
			// all records to create are in the $_POST
			  $recordContents = urldecode($_POST["records"]);
			  //logmessage("Posted records: " . $recordContents);
	      $incomingRecordsToCreate = json_decode($_POST['records']);
	      //logmessage("json decoding trial one: " . $incomingRecordsToCreate);
	      if(!$incomingRecordsToCreate) {
	        $incomingRecordsToCreate = json_decode( stripslashes($_POST['records']) );
	      }
	      // if malformed JSON, it'd better die here :)
	      //print_r($incomingRecordsToCreate);
	      if($incomingRecordsToCreate){
		      // $incomingRecordsToCreate is an array so iterate
		      // but first get ourselves an empty OrionDB_Collection object to send data back
		      $outgoingRecords = array();
		      // create working object
		      $workingObject = eval("return new " . $requestedResource . "_class;");
		      foreach($incomingRecordsToCreate as $key=>$value){
		         // we need to save the id so SC will know what record to update
		         // it is sent along in both the id property as the _guid property
		         // so remove both from the object we pass along, but keep 'em here  
		         $SC_guid = $value->_guid;
		         unset($value->_guid);
		         unset($value->id);
		         // next create a new record
		         $workingObject->create($value);
		         // now put back the SC temp guid
		         $workingObject->_guid = $SC_guid;

		         // put the record in the collection
		         $outgoingRecords[] = clone $workingObject;
		      }
		      // ready? send back the new record(s)
		      echo json_encode($outgoingRecords);
		      //echo json_encode($workingObject);
			  }
      }
}

function OrionDB_Destroy(){
   

}



?>