<?php

/*

OrionDB_Authentication: an authentication module for OrionDB 


*/


class OrionDB_Authentication_class {
   
   /*
     The model for the server display only needs the following code:
     
     properties: ['id','name']
   
     The model for the return data needs to have
     
     properties: ['authServerId','username','passwd']
   */
      
   function auth(stdObject $JSONdata){
      // in the stdObject the following properties need to be present:
      // the user name
      // the password (encrypted or not)
      // the authentication type (id of the $ORIONDBCFG_authserver array in the config file)
      global $ORIONDBCFG_authserver;
      
      $authserverpresent = property_exists($JSONdata,'authServerId');
      $usernamepresent = property_exists($JSONdata,'username');
      $passwordpresent = property_exists($JSONdata,'passwd');
      
      if($authserverpresent && $usernamepresent && $passwordpresent){
         // get the type of the server
         $authserver = $ORIONDBCFG_authserver[$JSONdata->authServerId];
         $type = $authserver->type;
         $tmpObject = eval("return new OrionDB_authmodule_" . $type . "_class;");
         if($tmpObject){
            $authserver["username"] = $JSONdata->username;
            $authserver["passwd"] = $JSONdata->passwd;
            $authresult = $tmpObject->auth($authserver);
            return $authresult; 
         }
      }
   }
   
   function return_server_collection(){
     // this function creates a collection for a SC login client with all authentication servers in the 
     // config file
     global $ORIONDBCFG_authserver;
     $servers = array();
     
     foreach($ORIONDBCFG_authserver as $key=>$value){
       if($value["active"]){
         // server active
         $servers[] = $value;
       }
     }
     if(count($servers)>1){
       // active authentication servers found
       // create an empty collection object
       $tmpCol = new OrionDB_Collection();
       foreach($servers as $key => $value){
         $newRecord = new stdClass;
         $newRecord->id = $value['id'];
         $newRecord->name = $value['name'];
         $tmpCol->records[] = clone $newRecord;
         $tmpCol->ids[] = $value['id'];
       }
       echo json_encode($tmpCol);
     }
     else {
       if(count($servers) == 1){
         $newRecord = new stdClass;
         $newRecord->id = $servers[0]['id'];
         $newRecord->name = $servers[0]['name']; 
         echo json_encode($newRecord);
       }
     }
   }
  
   
   // add user and passwd to the array of info sent to the auth handlers
   
}

?>