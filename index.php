<?php

/**
 *  Index.php
 *  Access point to Orion REST
 *
 *
 */ 

//require_once("dbconnect.php"); // dbconnect.php will automatically require_once config.php
require_once('includes/OrionDB_initialise.php'); // preconfig initialisation
require_once("config.php"); // load the config 
require_once("includes/OrionDB_postconfig.php"); // initialise database connection and localisation
require_once("includes/securitylib.php");
require_once("includes/commonfunctions.php"); // library containing common functions like error logging

require_once("includes/OrionDB.php"); // load the framework


/* 
 * MAIN PROCESS 
 */

// first check whether SC is talking to us:
// $_SERVER must have both these parameters
//     [HTTP_X_REQUESTED_WITH] => XMLHttpRequest
//     [HTTP_X_SPROUTCORE_VERSION] => 1.0
//print_r($_SERVER);

$OrionDB_SessionPresent = false;

if(!$ORIONDBCFG_allow_non_sc_clients){  
   $xmlHttpRequestPresent = array_key_exists('HTTP_X_REQUESTED_WITH',$_SERVER);
   $SCPresent = array_key_exists('HTTP_X_SPROUTCORE_VERSION',$_SERVER);
   if(!($xmlHttpRequestPresent) && !($SCPresent)){
      echo "You do not have permission to access this resource!";
      logmessage('Attempted access to OrionDB from a non-SC client while this is not allowed in the configuration');
      die();
   }
}


//check whether session support is turned on or authorisation is activated.
if($ORIONDBCFG_sessions_active || $ORIONDBCFG_auth_module_active){
  // load session support
  require_once('includes/OrionDB_Session.php');
  // start session (will fail if authentication is turned on and the user has not authenticated yet)
  logmessage("Trying to start session");
  $result = OrionDB_Session_start();
  if($result){
      $OrionDB_SessionPresent = true;
  }  
  logmessage("session start trial result: " . $result);
}



// process the call 
if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])) {
	
	//global $ORIONDBCFG_baseURI;
	$tmpbaseURI = $ORIONDBCFG_baseURI . "/";
	$ORION_actualRequest = substr($_SERVER['REQUEST_URI'],strlen($tmpbaseURI));
	//logmessage("Getting request: " . $ORION_actualRequest);
	
	// now we have our actual request 
	// next find out whether a specific item is being called for, say : student/25 which would be student with id 25
	// it could also be that a different request has been made, for example student?order=id.
	// this is a collection retrieval 
		
	$request = explode("/",$ORION_actualRequest);
	//print_r($request);
	$numberOfRequestItems = count($request);
   if(($numberOfRequestItems == 1) || ($numberOfRequestItems == 2)){
      $requestedResource = $request[0];
    
      switch($_SERVER['REQUEST_METHOD']){
         case 'GET':
            // list and refresh
            //with /id for one (refresh)
            // with ?order=x for list
                        
            $parameters = array();
            switch($numberOfRequestItems){
                case 1:
                    //we have a list or a refresh with a list of id's
                    // it means that we need to separate the table name from the $GET
                    $tmpExplodedResource = explode('?',$requestedResource);
                    $tablename = $tmpExplodedResource[0];
                    //let's get the $_GET;
                    // before getting the information from the database,
                    // check whether this request is meant for the authentication module
                    if($ORIONDBCFG_auth_module_active){
                      // get the auth object and feed it the table name
                      // the function returns either true or false to allow continuation of the program
                      // it checks whether the request is intended for system state requests or login stuff
                      // and also checks whether the user is authorised to load this data
                      // it returns false when it has handled the stuff itself, or when the user is not allowed to have the stuff
                      $tmpAuthObject = new OrionDB_Authentication;
                      $authResult = $tmpAuthObject->process_get_request($tablename);
                      if(!$authResult){
                        // not allowed to get anything or it has handled the request itself
                        // so terminate
                        die();
                      }
                      // 
                    }
                    // if we get here, everything is ok.
                    // handle the request 
                    $tmpInfo = new OrionDB_QueryInfo;
                    // iterate through $_GET
                    $tmpInfo->tablename = $tablename;
                    foreach($_GET as $key=>$value){
                       $tmpInfo->conditions[$key] = $value;
                    }
                    OrionDB_List($tmpInfo);
                    break;
                case 2:
                    // we have a refresh
                    // when we have a refresh or get request for only one record, don't return
                    // a collection object, but only the record requested.
                    if($ORIONDBCFG_auth_module_active){
                      // get the auth object and feed it the table name
                      // the function returns either true or false to allow continuation of the program
                      // it checks whether the request is intended for system state requests or login stuff
                      // and also checks whether the user is authorised to load this data
                      // it returns false when it has handled the stuff itself, or when the user is not allowed to have the stuff
                      $tmpAuthObject = new OrionDB_Authentication;
                      $authResult = $tmpAuthObject->process_get_request($tablename);
                      if(!$authResult){
                        // not allowed to get anything or it has handled the request itself
                        // so terminate
                        die();
                      }
                      // 
                    }
                    // if we get here, everything is ok.
                    $workingObject = eval("return new " . $requestedResource . "_class;");
                    $tmpId=intval($request[1]);
                    $workingObject->init($tmpId);
                    echo json_encode($workingObject);
                    break;
                default:
                    // not good, die
                    die();
                    break;
            } 
    		   break;
    	   case 'POST':
            //create
             OrionDB_Create($requestedResource); // function will get the post data itself
    		   break;
    	   case 'PUT':
    	      //update existing record, so having either /id or a set of records
    	      // even if id is given, ignore, as it is in the JSON too
    	      // todo check for consistency?
    	       //first get the post data
            $putstream = fopen("php://input", "r");
            $putdata = fread($putstream,16384);
            fclose($putstream);
            //logmessage("read input stream: $putdata");
            // check whether we have proper JSON data
            $JSONObject = json_decode($putdata);
            if(!is_object($JSONObject)){
                //logmessage("Now creating proper JSONData");
                //now create proper JSON data
                $putdata = urldecode($putdata);
                $recordsText = substr($putdata,0,strlen('records='));
                if($recordsText != 'records='){
                    // someone is playing with us, so die
                    die();  
                }
                $recordJSON = substr($putdata,strlen('records='));
                if(substr($recordJSON,strlen($recordJSON) - 3) == '&_='){
                  // talking to safari which attaches &_= to every post for some strange reason
                  // so let's remove the last three stray items...
                  $recordJSON = substr($recordJSON,0,strlen($recordJSON) - 3);
                }
                logmessage($recordJSON);
                //print_r(json_decode($recordJSON));
                $JSONObject = json_decode($recordJSON);
            }
            // a valid JSON object
            // The object is an array so iterate through it
            // create a working object of the correct type
            if($JSONObject){
               //catch auth request
                //logmessage("Authentication module active: " . $ORIONDBCFG_auth_module_active);
               //logmessage("Table name = " . $requestedResource);
               //logmessage("Auth server resource = " . $ORIONDBCFG_auth_server_resource_name);
               if($ORIONDBCFG_auth_module_active){
                  $tmpObject = new OrionDB_Authentication;
                  $result = $tmpObject->process_put_request($requestedResource, $JSONObject);
                  // as with the get, the process function will return true when allowed to continue
                  if(!$result){
                    // no continue allowed or the process function has handled it 
                    die();  
                  }
               }       
               $output = array();
               $workingObject = eval("return new ". $requestedResource ."_class;");
               if($workingObject){
                  foreach($JSONObject as $key=>$value){
                  //logmessage("Processing PUT object array item $key");
                  $workingObject->update($value);
                  $output[] = clone $workingObject;
                  }
                  echo json_encode($output);                  
               }
               else {
                  logmessage('No proper working model for update');  
               }
            }
            else {
              logmessage('No proper JSON data!');
            }   
    	    break;
    	 case 'DELETE':
    	    //delete so no post body, only an id
    	    $tmpId=intval($request[1]);
    	    $workingObject = eval("return new " . $requestedResource . "_class;");
    	    $workingObject->init($tmpId);
    	    $workingObject->delete();
    	    break;
    	 default:
    		// no action?? do nothing :)
    	    break;
        } 
   }
}



	

?>