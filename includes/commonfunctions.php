<?php
/*
 * \file commonfunctions.php
 * \brief Some common functions used throughout the PHP side of Muzorion, mainly logging and error handling functions
 *
 *
 */
require_once("config.php");
require_once("securitylib.php");

function error($errormessage){
/// Function to send a specific text to the error log
/// \param[in] $errormessage The message to be sent
	global $ORIONDBCFG_logfile;
    $t=getdate();
    $today=date('r',$t[0]);	
    error_log($today . ": Error: " . $errormessage . " \n",3,$ORIONDBCFG_logfile);	
}

function fatalerror($errormessage){
/// Function to send a specific text to the error log and end the program.
/// \param[in] $errormessage The message to send to the error log.
	global $ORIONDBCFG_logfile;
  $t=getdate();
  $today=date('r',$t[0]);	
  error_log($today . ": Fatal Error: " . $errormessage . " \n",3,$ORIONDBCFG_logfile);
	die();	
}

function logmessage($errormessage){
/// Function to send a specific text to the error log and end the program.
/// \param[in] $errormessage The message to send to the error log.	
	global $ORIONDBCFG_logfile;
  $t=getdate();
  $today=date('r',$t[0]);	
  error_log($today . ": Log Message: " . $errormessage . " \n",3,$ORIONDBCFG_logfile);
}

function fataldberror($errormessage, $query){
	global $ORIONDBCFG_logfile;
	$t=getdate();
   $today=date('r',$t[0]);	
   error_log($today . ": Database Error: " . $errormessage . " \n",3,$ORIONDBCFG_logfile);
	//error_log("\t MySQL Error Message: " . mysql_error() . "\n",3,$ORIONDBCFG_logfile);
	error_log("\t Query where error occurred: " . $query . "\n",3,$ORIONDBCFG_logfile);
	die();
}

function generateemailaddress($firstname,$inbetween,$lastname){
	/// Function to generate an e-mail address out of the names provided
	$result = strtolower($firstname . "." . $inbetween . $lastname);
	// strip characters like ' " and replace characters not allowed for e-mail	
}

function decode_json($post){
	$post = utf8_encode($post);
	return json_decode($post);	
}

function sortobjectarraybyfieldname(array $arraytosort, array $fieldnamestosortto, $preserveindex=true){
/// This function sorts an array of objects to the field name given by $fieldnametosortto.
/// The function normally retains the index or key of the arraytosort, unless $preserveindex==false
	
	$codetoexecute = "\$sortbystring = \"\";";
	foreach($fieldnamestosortto as $key=>$value){
		$codetoexecute .= "\$sortbystring .= \$value->$value . \" \";";	
	}
	$codetoexecute .= "return \$sortbystring;";
	//echo $codetoexecute . "<br>\n";
	// create an key->value array of the array to sort and the contents of the fieldname to sort to
	foreach($arraytosort as $key=>$value){
		$contentsoffield = eval($codetoexecute);
		//echo $contentsoffield . "<br>\n";
		$unsortedarray[$key] = $contentsoffield;
	}	
	//print_r($unsortedarray);
	asort($unsortedarray);
	//print_r($unsortedarray);
	$sortedarray = array();
	if($preserveindex){
		foreach($unsortedarray as $key=>$value){
			$sortedarray[$key] = $arraytosort[$key];	
		}
	} else {
		$index=0;
		foreach($unsortedarray as $key=>$value){
			$sortedarray[$index] = $arraytosort[$key];
			$index++;
		}
	}
	return $sortedarray;
}

function comparestudentobjects($student1, $student2){
	/// Function to be used with array_udiff();
	// Function compares id of a student
	$tempid1 = $student1->id;
	$tempid2 = $student2->id;
	if($tempid1 > $tempid2){
		return 1;	
	}
	if($tempid1 == $tempid2){
		return 0;	
	}
	if($tempid1 < $tempid2){
		return -1;	
	}
}


function filterobjectarraybyfield(array $arraywithallobjects, array $arraywithobjectstofilter, $fieldname){
	/// Function to filter an array with objects by another array with objects by using a common field name
	/// Essentially, this function is a wrap around for array_udiff, but one where the user function is rendered
	/// using the fieldname	
	
}




?>