<?php

// === Post config initialisation

	
	// functionality that absolutely needs to be executed 
	// creating a valid log file name with path
	$lastposofslash = strrchr($ORIONDBCFG_logfilepath,"/");
	if($lastposofslash != strlen($ORIONDBCFG_logfilepath)){
		$ORIONDBCFG_logfile = $ORIONDBCFG_logfilepath . "/" . $ORIONDBCFG_logfilename;	
	} else {
		$ORIONDBCFG_logfile = $ORIONDBCFG_logfilepath . $ORIONDBCFG_logfilename;	
	}
	
  // set up the actual Database Object
  switch($ORIONDBCFG_DB_type){
    case 'mysql':
        require_once('OrionDB_MySQL.php');
        $ORIONDB_DB = new OrionDB_DB_MySQL;
      break;
    case 'postgresql':
        require_once('OrionDB_MySQL.php');
        $ORIONDB_DB = new OrionDB_DB_MySQL;
      break; 
  }

	
	setlocale (LC_ALL, $ORIONDBCFG_locale);

?>