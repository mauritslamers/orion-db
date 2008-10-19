<?php

/* 

OrionDB_Session.php

OrionDB Session support

*/

function OrionDB_Session_start($validuser = false){
    // set the stuff in the config file
    global $ORIONDBCFG_session_expire_time,$ORIONDBCFG_session_name;
    global $ORIONDBCFG_cookie_host_name,$ORIONDBCFG_cookie_only_secure;
    global $ORIONDBCFG_baseURI, $ORIONDBCFG_auth_module_active;
    global $ORIONDBCFG_auth_module_only_valid_user_session;
    
    // checking whether the config settings are usable
    if($ORIONDBCFG_session_expire_time){
       $expire_time = 60 * intval($ORIONDBCFG_session_expire_time); // get the time in seconds
    } 
    if($ORIONDBCFG_session_name){
      $session_name = $ORIONDBCFG_session_name; 
    }
    
    if($ORIONDBCFG_cookie_host_name){
      $hostname = $ORIONDBCFG_cookie_host_name; 
    } else {
      // get it from the $_SERVER array
      if(array_key_exists('HTTP_HOST',$_SERVER)){
        $hostname = $_SERVER['HTTP_HOST'];  
      } else {
         $hostname = "";
      }
    }
    
    // prepare the session to start
    //void session_set_cookie_params (int $lifetime [, string $path [, string $domain [, bool $secure [, bool $httponly ]]]] )
    session_set_cookie_params($expire_time,$ORIONDBCFG_baseURI,$hostname,$ORIONDBCFG_cookie_only_secure);
    if($ORIONDBCFG_auth_module_active && $ORIONDBCFG_auth_module_only_valid_user_session){
      if(!validuser){
         return false;   
      }
    } 
    
    //start the session
    session_start($session_name);
    return true;
 
}




?>