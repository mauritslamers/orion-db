<?php

/*

OrionDB_Authentication: an authentication module for OrionDB 


*/


class OrionDB_Authentication {
   
   /*
   OrionDB Authentication has two actions: GET and POST
   
     The model for the server display only needs the following code:
     
     properties: ['id','name']
   
     The model for the return data needs to have
     
     properties: ['authServerId','user_name','passwd']
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
         
         $authserverpresent = property_exists($JSONdata,'auth_server_id');
         $usernamepresent = property_exists($JSONdata,'user_name');
         $passwordpresent = property_exists($JSONdata,'passwd');
         
         if($authserverpresent && $usernamepresent && $passwordpresent){
            // get the type of the server
            $authserver = $ORIONDBCFG_auth_server[$JSONdata->auth_server_id];
            $type = $authserver["type"];
            $tmpObject = eval("return new OrionDB_authmodule_" . $type . ";");
            if($tmpObject){
               $authserver["user_name"] = $JSONdata->user_name;
               $authserver["passwd"] = $JSONdata->passwd;
               $authresult = $tmpObject->auth($authserver);
               return $authresult; 
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
       $tmpCol = new OrionDB_Collection();
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
  
   
   // add user and passwd to the array of info sent to the auth handlers
   
}

?>