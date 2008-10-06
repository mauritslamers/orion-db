<?php

/**
 *  Index.php
 *  Access point to Orion REST
 *
 *
 */ 

require_once("includes/dbconnect.php");
require_once("includes/securitylib.php");
require_once("includes/commonfunctions.php"); // library containing common functions like error logging
require_once("includes/OrionFW.php"); // load the framework


/* 
Before anything goes, the session needs to be created
The sessions work in a different way from what is normal.

The session will be created as soon as the login page is loaded.
A class member called authenticated will contain whether the user of the current session is actually authenticated.
If he is not, no access can be given to any useful data, except for three resources:
- authenticationserver -> the list of servers to authenticate against
- logindata -> the action of logging in
- systemstate -> the action of retrieving the current state of the session.


*/


/* 
 * MAIN PROCESS 
 */

// process the call 
if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])) {
	
	global $ORIONCFG_baseURI;
	$tmpbaseURI = $ORIONCFG_baseURI . "/";
	$ORION_actualRequest = substr($_SERVER['REQUEST_URI'],strlen($tmpbaseURI));
	//echo $ORION_actualRequest;
	
	// now we have our actual request 
	// Before we look at 
	// next find out whether a specific item is being called for, say : student/25 which would be student with id 25
	// it could also be that a different request has been made, for example student/order=id.
	// this is a collection retrieval and so idpresent should not be set
	
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
                    //let's get the $_GET;
                    if(array_key_exists('ids',$_GET)){
                       // we will get an comma separated list and let's keep in that way
                       // easy to include in queries
                       $idsList = $_GET['ids'];
                       
                       
                    }
                    else {
                      if(array_key_exists('order',$_GET)){
                        
                      }
                    }
                    
                    break;
                case 2:
                    // we have a refresh, get the id
                    // take the id and feed it to the refresh function
                default:
                    // not good, die
                    die();
                    break;
            } 
    		   break;
    	   case 'POST':
            //create
    	      
    		   break;
    	   case 'PUT':
    	      //update existing record, so having /id
    	       //first get the post data
            $putstream = fopen("php://input", "r");
            $putdata = fread($putstream,16384);
            fclose($putstream);
            
            // check whether we have proper JSON data
            $testJSONObject = json_decode($putdata);
            if($testJSONObject == null){
                //now create proper JSON data
                $putdata = urldecode($putdata);
                $recordsText = substr($putdata,0,strlen('records='));
                if($recordsText != 'records='){
                    // someone is playing with us, so die
                    die();  
                }
                $recordJSON = substr($putdata,strlen('records='));
                //echo $recordJSON;
                print_r(json_decode($recordJSON));
            }
            
            
    	    break;
    	 case 'DELETE':
    	   //delete so no post body, only an id
    	   
    	    break;
    	 default:
    		// no action?? do nothing :)
    	    break;
        } 
   }
}



	  
	  //$recordType = $request[0];
	  //$verbAndCondition = $request[1];
	  // verb and condition can be key value pair, so let's 

	
	//$slashinactualrequest = strpos($ORION_actualrequest,"/");
/*	$ORION_idpresent = false;
	$ORION_orderpresent	= false;
	if($slashinactualrequest != false){
		// a verb have been sent and maybe even an id	
		// retrieve it
		
		
		$ORION_tablename = substr($ORION_actualrequest,0,$slashinactualrequest);
		$rawrequestid = substr($ORION_actualrequest,$slashinactualrequest+1);
		$equal_sign_in_raw_request = strpos($rawrequestid,"=");
		if($equal_sign_in_raw_request != false){
			// equal sign found, so retrieve the two halves
			$elementaction = substr($rawrequestid,0,$equal_sign_in_raw_request);
			$elementvalue = substr($rawrequestid,$equal_sign_in_raw_request+1);
			if($elementaction == "order"){
				$ORION_orderpresent	= true;
				$ORION_order = $elementvalue;
			}
			//echo "orderpresent: " . $ORION_orderpresent . " tablename: " . $ORION_tablename . " idpresent: " . $ORION_idpresent;
		} else {	
			$ORION_requestid = $rawrequestid;
			$ORION_idpresent = true;
		}
	} else {
		$ORION_tablename = $ORION_actualrequest;	
	}
	
	// now we have to look at the action performed.
	// GET is just for retrieving data, returning a group when an id is not provided and send back the record if it is
	// POST is for creating a new record when id is not provided and for updating if an id is sent along
	// DELETE is for removing a record from the database. An id is compulsary.
	
	//echo "Actual request: <b> " . $ORION_actualrequest . "</b><br>";
	//echo "Request id: <b>" . $ORION_requestid . "</b><br>";

	switch($_SERVER['REQUEST_METHOD']){
		case 'GET':
			// two cases possible: $ORION_idpresent true or false
			if($ORION_idpresent){
				// if idpresent, collect only one record
				$newobject = eval("return new " . $ORION_tablename . "_class;");
				$newobject->init($ORION_requestid);
				//print_r($newobject);
			} else {
				// if id not present, send the lot (a collection that is :) ) or handle the exceptions
				// before creating a new collection object, check whether the table actually exists
				$checkdbobject = eval("return new " . $ORION_tablename . "_class;");
				if(is_object($checkdbobject) && is_a($checkdbobject,'OrionFW_DBObject')){
					$codeto_eval = "return new OrionFW_DBCollection(" . $ORION_tablename . ");";
					$newcollection = eval($codeto_eval);
					if(is_object($newcollection)){
						// send off if collection is valid
						echo json_encode($newcollection);
						//print_r($newcollection);
					}						
				} else {
					if(is_object($checkdbobject)){
						echo json_encode($checkdbobject);
					}
				}
			}
			break;
		case 'POST':
			break;
		case 'DELETE':
			break;
		default:
			// no action?? do nothing :)
			break;
	} */
	
	

?>