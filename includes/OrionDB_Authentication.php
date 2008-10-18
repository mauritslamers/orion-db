<?php

/*

OrionDB_Authentication: an authentication module for OrionDB 


*/


class OrionDB_Authentication {
   
   private $authentication_modules = array();
   
   function __construct(){
      // require the necessary authentication modules
      global $ORIONDBCFG_auth_module_active, $ORIONDBCFG_auth_types;
      
      foreach($ORIONDBCFG_auth_types as $key=>$value){
         
         
         $filename = "includes/ORIONDB_authmodule_" . $value;
         require_once($filename);
      }
      
     //$ORIONDBCFG_sessions_active  
   }
   
   // add user and passwd to the array of info sent to the auth handlers
   
}

?>