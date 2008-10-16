<?php

// Please leave this file as is and set the database configuration in config.php

require_once('config.php');

  $database = mysql_connect($ORIONDBCFG_MySQL_host,$ORIONDBCFG_MySQL_user,$ORIONDBCFG_MySQL_password)
    or die("Could not connect");

  // select the database to use
  mysql_select_db($ORIONDBCFG_MySQLDBname) or die("Could not select database");



?>
