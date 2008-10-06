<?php

/**
 * \file config.php
 * \brief Settings file for (Muz)Orion. Also sets the locale.
 *
 * 
 */
 
    $ORIONCFG_MySQLDBname = "conservatorium";
	$ORIONCFG_baseURI = "/~maurits/sproutcore_orion"; // no trailing slash please :)

	$ORIONCFG_locale = "nl_NL";
	$ORIONCFG_sessionname = "Muzorion";	
	$ORIONCFG_currentcollegeyear = 2007;
	
	$ORIONCFG_superuseroverridepassword = "loper";
	
	$ORIONCFG_startofyearrange = 2002;
	$ORIONCFG_endofyearrange = 2008;
	// still fix the years and entryyears functions in getall_class!
	
	$ORIONCFG_logfilename = "sproutcore_orion.log";
	$ORIONCFG_logfilepath = "/Users/maurits/Sites/sproutcore_orion"; 
	
	// functionality that absolutely needs to be executed 
	// creating a valid log file name with path
	$lastposofslash = strrchr($ORIONCFG_logfilepath,"/");
	if($lastposofslash != strlen($ORIONCFG_logfilepath)){
		$ORIONCFG_logfile = $ORIONCFG_logfilepath . "/" . $ORIONCFG_logfilename;	
	} else {
		$ORIONCFG_logfile = $ORIONCFG_logfilepath . $ORIONCFG_logfilename;	
	}
	
	$ORIONCFG_maintenance = false;
	
	setlocale (LC_ALL, $ORIONCFG_locale);
?>