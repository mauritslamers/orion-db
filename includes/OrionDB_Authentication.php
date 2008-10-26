<?php

/*

OrionDB_Authentication: an authentication module for OrionDB 


*/



class OrionDB_Authentication {
   
   /*
   OrionDB Authentication has two actions: GET and POST
   
     The model for the server display only needs the following code:
     
     properties: ['id','name']
   
     The model for the POST data needs to have
     
     properties: ['id','authServerId','user_name','passwd','loginStatus','preferredClient']
     
     of which the authServerId,username and password need to be set.
     OrionDB will return the entire model except the password (of course).
     preferredclient will only have a value when loggedin is true and only the first time it is returned
     
   */
      
   function auth(stdClass $JSONdata){
      // in the stdObject the following properties need to be present:
      // the user name
      // the password (encrypted or not)
      // the authentication type (id of the $ORIONDBCFG_authserver array in the config file)
      if(is_object($JSONdata)){
         //print_r($JSONdata);
         //logmessage("processing auth action");
         global $ORIONDBCFG_auth_server;
         global $OrionDB_SessionPresent;
         
         $authserverpresent = property_exists($JSONdata,'auth_server_id');
         $usernamepresent = property_exists($JSONdata,'user_name');
         $passwordpresent = property_exists($JSONdata,'passwd');
         
         if($authserverpresent && $usernamepresent && $passwordpresent){
            // get the type of the server
            $authserver = $ORIONDBCFG_auth_server[$JSONdata->auth_server_id];
            $type = $authserver["type"];
            // no further checking of $type needed, as this data comes from the config file.
            $tmpObject = eval("return new OrionDB_authmodule_" . $type . ";"); 
            if($tmpObject){
               $authserver["user_name"] = $JSONdata->user_name;
               $authserver["passwd"] = $JSONdata->passwd;
               $authresult = $tmpObject->auth($authserver);
               //return $authresult; 
               if($authresult){
                  if(!$OrionDB_SessionPresent){
                    require_once('includes/OrionDB_Session.php');
                    OrionDB_Session_start($authresult);  
                  }
                 // now return the proper data
                 // first get the temp guid for the posted record as we need to return it
                 $tmpSystemState = new OrionDB_SystemState;
                 $tmpSystemState->id = 1;
                 $tmpSystemState->user_name = $JSONdata->user_name;
                 $tmpSystemState->login_status = true;
                 $tmpSystemState->preferred_client = 'admissionexam'; // hardcoding the client for the moment
                 echo json_encode($tmpSystemState);
                 
                 // setting the session data
                 OrionDB_Session_set_information($tmpSystemState);
                 
                 return true;
               }
            }
         }
      }
   }
   
   function return_server_collection(){
     // this function creates a collection for a SC login client with all authentication servers in the 
     // config file
     global $ORIONDBCFG_auth_server;
     global $ORIONDBCFG_auth_server_resource_name;
     $servers = array();
     
     foreach($ORIONDBCFG_auth_server as $key => $value){
       if($value["active"]){
         // server active
         $servers[] = $value;
       }
     }
     if(count($servers)>0){
       // active authentication servers found
       // create an empty collection object
       $tmpCol = new OrionDB_Collection;
       foreach($servers as $key => $value){
         $newRecord = new stdClass;
         $newRecord->id = $value['id'];
         $newRecord->name = $value['name'];
         $newRecord->type = $ORIONDBCFG_auth_server_resource_name;
         $tmpCol->records[] = clone $newRecord;
         $tmpCol->ids[] = $value['id'];
       }
       echo json_encode($tmpCol);
     }
   }
  
   function return_logged_out_system_state(){
      $tmpState = new stdClass;
      $tmpState->guid = 1;
      //$tmpState->preferred_client = 'login';
      $tmpState->login_status = false;
      $tmpObject = new OrionDB_Collection;
      $tmpObject->records[] = $tmpState;
      $tmpObject->ids[] = 1;
      echo json_encode($tmpObject);  
   }
   
   function return_system_state(){
      //print_r($_SESSION);
      logmessage("Returning system state in PHP session array");
      $tmpState = OrionDB_Session_get_information();
      unset($tmpState->preferred_client); // remove the preferred client to prevent reloading
      $tmpObject = new OrionDB_Collection;
      $tmpObject->records[] = $tmpState;
      $tmpObject->ids[] = 1;
      echo json_encode($tmpObject);      
   }
  
   function process_get_request($requestname = ""){
      // this function returns either true or false to allow continuation of the program
      // it checks whether the request is intended for system state requests or login stuff
      // and also checks whether the user is authorised to load this data
      // it returns false when it has handled the stuff itself, or when the user is not allowed to have the stuff
 
      global $ORIONDBCFG_return_system_state_info;
      global $ORIONDBCFG_auth_server_resource_name;
      global $ORIONDBCFG_system_state_resource_name;
      global $OrionDB_SessionPresent;
      
      // no request? no action!
      if($requestname){
         // getting the server list is the only thing an unauthenticated user is allowed to do
         // so before checking whether the current user or authentication is valid.
         // check whether performing the check is really necessary (speed optimisation :) )
         if($requestname == $ORIONDBCFG_auth_server_resource_name){
               logmessage("Returning server list");
               $this->return_server_collection();
               return false; // end execution of this function and return false to indicate a no continue
         }
         // now check whether the current user has a valid authentication 
         
         /* 
          TODO: create a system to check whether the user actually is allowed to perform this request.
          this also needs a second function parameter for the contents (more or less, as it also can be retrieved 
          directly from the $_GET array
          
          Although it is possible to create this in PHP of course, it may be even easier to do it in SQL
         */
         // check the session data whether the user already logged in
         
         // check whether the system state is requested
         if($requestname == $ORIONDBCFG_system_state_resource_name){
            // this get request is a bit different, as it becomes important whether the user is authenticated or not
            // as this requires differen
            if($OrionDB_SessionPresent){
               $this->return_system_state();
               return false; // end execution and return false to indicate a no continue
            }
            else {
               $this->return_logged_out_system_state();
               return false; // end execution
            }
         }
         
         // other 
         
         return true; 
      }
      
   }


} // end authentication class

?>