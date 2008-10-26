<?php

/* 

OrionDB_Session.php

OrionDB Session support

*/
class OrionDB_SystemState{
  public $id;
  public $user_name;
  // no password :)
  public $login_status;
  public $preferred_client;
  public $type = "SystemState";
  public $logout = false;
}


function OrionDB_Session_set_information(OrionDB_SystemState $state_info){
   // hardcoding session names
   
   $_SESSION['system_state_id'] = $state_info->id;
   $_SESSION['system_state_user_name'] = $state_info->user_name;
   $_SESSION['system_state_login_status'] = $state_info->login_status;      
   $_SESSION['system_state_preferred_client'] = $state_info->preferred_client;
   //echo "info after setting info <br>\n";
   //print_r($_SESSION);
}

function OrionDB_Session_get_information(){
  // returns an object of type OrionDB_SystemState
  //echo "info before getting info <br>\n";
  //print_r($_SESSION);
  $tmpObject = new OrionDB_SystemState;
  $tmpObject->id = $_SESSION['system_state_id'];
  $tmpObject->user_name = $_SESSION['system_state_user_name'];
  $tmpObject->login_status = $_SESSION['system_state_login_status'];
  $tmpObject->preferred_client = $_SESSION['system_state_preferred_client'];
  return $tmpObject;
}

function OrionDB_Check_cookie(){
  // function to check for the presence of a cookie with the session name configured
  // returns false when a session name has not been set
  global $ORIONDBCFG_session_name;
  
  if($ORIONDBCFG_session_name){
     if(array_key_exists('HTTP_COOKIE',$_SERVER)){
       // check whether the session name 
       $sessions = explode('; ',$_SERVER['HTTP_COOKIE']);
       $OrionDBSession_found = false;
       foreach($sessions as $key => $value){
         $sesname = substr($value,0,strlen($ORIONDBCFG_session_name));
         //logmessage("checking session name in cookie: " . $sesname . " to configured: " . $ORIONDBCFG_session_name);
         if($sesname == $ORIONDBCFG_session_name){
           $OrionDBSession_found = true; 
         }
       }
       if($OrionDBSession_found){
         return true;
       }
       else {
         return false;
       }
     } 
     else {
       return false; // no cookie found
     } 
  }
  else {
    return false;
  }
}



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
    
    //print_r($_SERVER);
    // prepare the session to start
    //void session_set_cookie_params (int $lifetime [, string $path [, string $domain [, bool $secure [, bool $httponly ]]]] )
    if($ORIONDBCFG_auth_module_active && $ORIONDBCFG_auth_module_only_valid_user_session){
      if(!$validuser){
         // check whether a session key of OrionDB already is present
         if(!(OrionDB_Check_cookie())){
            return false;
         }
      }
    } 
    
    
    //start the session
    if(!(OrionDB_Check_cookie())){
      // only set this stuff if no cookie has been found
       session_set_cookie_params($expire_time,$ORIONDBCFG_baseURI,$hostname,$ORIONDBCFG_cookie_only_secure);
       session_name($session_name);
    }
    
    session_start();
    return true;
 
}






?>