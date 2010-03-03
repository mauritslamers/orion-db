<?php

 /**
  * \file securitylib.php
  * \brief Collection of security related functions, such as the check_sql function
  *
  */

  function check_sql($instring,$maxlen)
  {
    // Handling of quotes
    if(!get_magic_quotes_gpc()) $outstring = addslashes($instring);
    else $outstring = $instring;

    // Filter out potentially dangerous constructs
    $outstring = ereg_replace("&","\&",$outstring);
    $outstring = ereg_replace(";|\|","#",$outstring);

    // and/or at start of string and followed by spaces
    //$outstring = ereg_replace("^[ ]*and[ ]+|^[ ]*or[ ]+","# ",$outstring);

    // and/or preceded and followed by spaces
    //$outstring = ereg_replace("[ ]+and[ ]+|[ ]+or[ ]+"," # ",$outstring);

    $outstring = ereg_replace("^[ ]*where","#",$outstring);

    if($maxlen>0) $outstring=substr($outstring,0,$maxlen);

    return $outstring;
  } // check_sql()
  
  function cleansql($in){
  	// remove trailing spaces and other trailing rubbish
  	$in = rtrim($in);
  	
  	// escape out mysql code
  	if (get_magic_quotes_gpc()) {		$out = mysql_real_escape_string(stripslashes($in));	} else {		$out = mysql_real_escape_string($in);	}	return $out; 	
  }
  

  function htmlprepare($incoming){
  	$returnvalue = htmlentities($incoming, ENT_QUOTES);
  	//$returnvalue = html_entity_decode($returnvalue);
  	//$returnvalue = html_entity_decode($incoming);
  	//$returnvalue = htmlspecialchars($incoming);
  	//$returnvalue = utf8_encode($incoming);
  	//$returnvalue = $incoming;
  	return($returnvalue);
  }
?>
