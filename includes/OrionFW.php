<?php
 /*
 	The Orion PHP Framework
 */

require_once("includes/OrionFW_DBObject.php"); // standard database object
require_once("includes/OrionFW_DBCollection.php"); // standard database collection object
require_once('includes/OrionFW_DBQuery.php'); // standard query object classes OrionFW_DBQuery and OrionFW_DBQueryInfo

function __autoload($classname){
	// generate classes on the fly using the $classname and tablename
	// Classname need to be of the form tablename_class, such as student_class etc
	// If the table does not exist in the DB, the include directory is checked for extra classes
	
	// get table name from $classname
	$lastunderscorepos = strrpos($classname,"_");
	$tablename = substr($classname,0,$lastunderscorepos);
	
	// check whether table actually exists in a way that prevents SQL injection
	$query = "SHOW tables";
	$result = mysql_query($query) or fataldberror("Error checking table existance in database: " . mysql_error());
	$numrows = mysql_num_rows($result);
	$tablefound = false;
	// get the db name
	global $ORIONCFG_MySQLDBname;
	// compare the class name against the table names in the DB and set $tablefound to true if a match is found
	if($numrows>0){
		for($index=0;$index<$numrows;$index++){
			$currentrecord = mysql_fetch_array($result);
			$fieldname = "Tables_in_" . $ORIONCFG_MySQLDBname;
			$currenttablename = $currentrecord[$fieldname];
			if($currenttablename == $tablename){
				$tablefound = true;	
			}
		}	
		if($tablefound){
			//match found, create new class
			$codetoeval = "class $classname extends OrionFW_DBObject { function __construct(){ parent::__construct('$tablename'); } }";
			eval($codetoeval);
		} else {
			// check for external PHP files to include
			// before requiring the file, check whether it exists
			$filename = "includes/" . $classname . ".php";
			if(file_exists($filename)){
				require_once "includes/" . $classname . '.php';
			} else {
				return false;// do nothing for else, unless this breaks things	
			}
		}
	}
}



function OrionFW_List(OrionFW_DBQueryInfo $info){
   /// Function to return a list of a specific type and order
   /// \param[in] $info The data of the request
      
   $list = new OrionFW_DBCollection($info);
   echo json_encode($list);
   
}

function OrionFW_Update(OrionFW_DBQueryInfo $info){
   /// Function to update one record of a specific type in the database
   /// \param[in] $type The type of data (same as table name)
   /// \param[in] $data JSON decoded PHP object containing the data
   
}

function OrionFW_Create($type,$data){
   /// Function to create a record of a specific type in the database
   /// it should send back the record containing both the new ID and the old _guid
   /// \param[in] $info The type of the data (same as table name)
   /// \param[in] $data JSON decoded PHP object containing the data
   /// \return The created object
   
}

function OrionFW_Destroy(){
   

}



?>