<?php

/*

OrionDB LDAP authentication module

*/

class OrionDB_authmodule_LDAP_class {
   
   function auth(array $data_array){
      // function to check whether an LDAP authentication can be made
      //first check whether the $data_array contains the right elements
      
      /*
      $ORIONDBCFG_auth_server[0]["name"] = "name"; // name to show on screen (send back to SC)
      $ORIONDBCFG_auth_server[0]["type"] = "LDAP"; // authentication type
   	$ORIONDBCFG_auth_server[0]["host"] = "subdomain.domain.ext"; // hostname to authenticate against (non-ORIONDB auth only)

      // the OrionDB_Authentication host adds the username and password to the array
      */
      
      $host_exists = array_key_exists("host",$data_array);
      $user_exists = array_key_exists("user",$data_array);
      $passwd_exists = array_key_exists("passwd", $data_array);
      if($host_exists && $user_exists && $passwd_exists){
         // the array seems to be complete
         $user = $data_array["user"];
         $passwd = $data_array["passwd"];
         $host = $data_array["host"];
         if($user && $passwd && $host){
            // try the ldap auth
            $ldapconn = ldap_connect($host);
            if ($ldapconn) {
               // binding to ldap server
               $ldapbind = ldap_bind($ldapconn, $user, $passwd);
               // verify binding
               if ($ldapbind) {
                  return true;
               } else {
                  return false;
               }
            }
         }
      }  
   } // end function auth
   
}


?>