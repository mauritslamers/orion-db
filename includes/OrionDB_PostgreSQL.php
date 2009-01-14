<?php

/*
  OrionDB MySQL module
  
  Contains the actual DB request functions and error handling

*/

class OrionDB_DB_PostgreSQL {
  
  public $dbconnection;
  
  
 function __construct(){
  
  /*   $ORIONDBCFG_DB_host = "localhost";
   $ORIONDBCFG_DB_user = "doctool";
   $ORIONDBCFG_DB_password = ".Whyareo";
   $ORIONDBCFG_DB_dbname = "toelatingsexamen"; */
  
  $connstring = "";
  if($ORIONDB_CFG_DB_host) $connstring .= "host=" . $ORIONDB_CFG_DB_host . " ";
  if($ORIONDB_CFG_DB_dbname) $connstring .= "dbname=" . $ORIONDB_CFG_DB_dbname . " ";
  if($ORIONDB_CFG_DB_user) $connstring .= "user=" . $ORIONDB_CFG_DB_user . " ";
  if($ORIONDB_CFG_DB_password) $connstring .= "password=" . $ORIONDB_CFG_DB_password . " ";

  
  //$dbconn3 = pg_connect("host=sheep port=5432 dbname=mary user=lamb password=foo");
//connect to a database named "mary" on the host "sheep" with a username and password
   $this->dbconnection = pg_connect($connstring) or die("Database connection failed") ;
  
  // get tables
      $query="SELECT NULL AS nspname, c.relname, 
					(SELECT usename FROM pg_user u WHERE u.usesysid=c.relowner) AS relowner,
					(SELECT description FROM pg_description pd WHERE c.oid=pd.objoid) AS relcomment,
					reltuples::bigint AS reltuples
				FROM pg_class c
				WHERE c.relkind='r'
					AND NOT EXISTS (SELECT 1 FROM pg_rewrite r WHERE r.ev_class = c.oid AND r.ev_type = '1')
					AND c.relname NOT LIKE 'pg@_%' ESCAPE '@' 
					AND c.relname NOT LIKE 'sql@_%' ESCAPE '@'
				ORDER BY relname";
   } 
  
}

	function cleansql($str) {
		if ($str === null) return null;
		$str = str_replace("\r\n","\n",$str);
		if (function_exists('pg_escape_string'))
			$str = pg_escape_string($str);
		else
			$str = addslashes($str);
		return $str;
	}

?>
