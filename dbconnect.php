<?php

  /* This PHP module is to be included by all PHP modules wanting to
     connect to the database server.

     On HKU, this module should reside in a protected directory
       /usr/people/schriftj/.php_include (Silicon Graphics style)
     which is owned by and only accessible by the web server

  */

require_once('config.php');

  $database = mysql_connect('ORIONDBCFG_MySQL_host','ORIONDBCFG_MySQL_user','.ORIONDBCFG_MySQL_password')
    or die("Could not connect");

  // select the database to use
  mysql_select_db($ORIONDBCFG_MySQLDBname) or die("Could not select database");



?>
