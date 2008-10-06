<?php
 /*
 	The Orion PHP Framework
 */

require_once("includes/OrionFW_DBObject.php"); // standard database object
require_once("includes/OrionFW_DBCollection.php"); // standard database collection object

class OrionFW_DBQueryInfo{
  public $tablename = "";
  public $conditions = array();  
}


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
			$codetoeval = "class $classname extends OrionFW_DBObject { function __construct(){ parent::__construct($tablename); } }";
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


         // the conditions array can contain special items like 'ids' and 'order'
         // but also fieldnames as key with a value or
         // of course a new OrionFW_DBQueryInfo object containing a subquery         
         // so we iterate through the conditions array, processing fieldnames if they fit 
         // the table we are creating the query for, and processing the specials order and ids if
         // they are not empty

function createQuery(OrionFW_DBQueryInfo $info){
   if($info->tablename != ""){
      $query = "select * from " . cleansql($info->tablename); // setup the start
      // next check for a conditions object
      if(property_exists($info,'conditions')){ 
          // we need an object of the current table to check fieldnames
          $tempTable = eval("return new " . $tablename . "_class;");
          // and we need a copy of the original query to check for changes
          $copyOfOriginalQuery = $query;
          
          foreach($info as $key=>$value){ //iterate through object
            switch($key){
               case 'order': // do nothing
                  break; 
               case 'ids': // string with comma separated ids
                  if($query == $copyOfOriginalQuery){
                    $query .= " WHERE id in (" . cleansql($value) . ") ";    
                  } 
                  else {
                     $query .= " AND id in (" . cleansql($value) . ") ";
                  }
                  break;
               default: // default means we probably have a fieldname which we can ignore
                  // if the fieldname is not a property of the object
                  if(property_exists($tempTable,$key)){ 
                      // check for the $value type, if it is a instance of OrionFW_DBQueryInfo, we need to
                      // make up the field name and make a recursive call to create the subquery
                      if($value instanceof OrionFW_DBQueryInfo){
                        // make recursive call
                        $tmpQuery = createQuery($value);
                        if($tmpQuery != false){
                            if($query == $copyOfOriginalQuery){
                               // query unchanged
                              $query .= " WHERE " . cleansql($key) . " in (" . $tmpQuery . ") ";
                            } 
                            else {
                               // query changed
                               $query .= " AND " . cleansql($key) . " in (" . $tmpQuery . ") ";
                            } // end if query unchanged
                         } 
                      }
                      else {
                        // not an object, but just a field name, so enter the key value pair
                        // check if value is an array or a single value 
                        // key cannot be empty due to property_exist check
                        
                        if($value != ""){
                            if($query == $copyOfOriginalQuery){
                              // unchanged
                              $query .= " WHERE " . $key . 
                            }
                            else {
                               // changed
                            }
                        }
                      } // end if $value instanceof 
                  } // end if property_exists($tempTable,$key) 
                  // no else clause, as we can ignore unknown fields
                  break;
               
            } 
          }
          if(property_exists($info,'order')){
            // add the order to the end of the query
          }
      }
      // if ready, return the query
      return $query;
   } else {
      return false;
   }  
}



function OrionFW_List($type,$order){
   /// Function to return a list of a specific type and order
   /// \param[in] $type The type of data (same a table name)
   /// \param[in] $order The order in which the data needs to be returned
   $list = new OrionFW_DBCollection($type,$order);
   echo json_encode($list);
   
}

function OrionFW_Refresh($type,$idsArray){
   /// Function to return one record of a specific type to refresh SC.Store
   /// for example to revert changes on one record 
   /// \param[in] $type The type of data (same as table name)
   /// \param[in] $id 
   
}

function OrionFW_Update($type,$data){
   /// Function to update one record of a specific type in the database
   /// \param[in] $type The type of data (same as table name)
   /// \param[in] $data JSON decoded PHP object containing the data
}

function OrionFW_Create($type,$data){
   /// Function to create a record of a specific type in the database
   /// it should send back the record containing both the new ID and the old _guid
   /// \param[in] $type The type of the data (same as table name)
   /// \param[in] $data JSON decoded PHP object containing the data
   
   
}

function OrionFW_Destroy(){
   

}



?>