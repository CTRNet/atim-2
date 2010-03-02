<?php

/**
 * Used as an array map, removes the " from a string
 * @param unknown_type $element
 * @return unknown_type
 */
function clean($element){
	return str_replace('"', '', $element);
}

/**
 * Associate the csv values to the csv keys
 * @param unknown_type $keys The csv keys
 * @param unknown_type $values The csv values
 * @return unknown_type The values array is updated with the associations
 */
function associate($keys, &$values){
	foreach($keys as $i => $v){
		$values[$v] = $values[$i];
//		echo($keys[$i]." -> ".$values[$i]."\n");
	}
}

/**
 * Takes a line and builds an array with it by using ; as a separator
 * @param unknown_type $line The line with the termination character
 * @return array
 */
function lineToArray($line){
	$result = explode(";", substr($line, 0, strlen($line) - 1));
	return array_map("clean", $result);
	
}
?>