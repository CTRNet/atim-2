<?php
class MyTime{
	public static $months = array(
		"january"	=> "01",
		"janvier"	=> "01",
		"jan"		=> "01",
		"february"	=> "02",
		"février"	=> "02",
		"fevrier"	=> "02",
		"fev"		=> "02",
		"fév"		=> "02",
		"feb"		=> "02",
		"march"		=> "03",
		"mars"		=> "03",
		"mar"		=> "03",
		"april"		=> "04",
		"avril"		=> "04",
		"apr"		=> "04",
		"avr"		=> "04",
		"may"		=> "05",
		"mai"		=> "05",
		"june"		=> "06",
		"juin"		=> "06",
		"jun"		=> "06",
		"july"		=> "07",
		"jul"		=> "07",
		"juillet"	=> "07",
		"august"	=> "08",
		"août"		=> "08",
		"aout"		=> "08",
		"aug"		=> "08",
		"aou"		=> "08",
		"september"	=> "09",
		"septembre"	=> "09",
		"sep"		=> "09",
		"sept"		=> "09",
		"october"	=> "10",
		"octobre"	=> "10",
		"oct"		=> "10",
		"november"	=> "11",
		"novembre"	=> "11",
		"nov"		=> "11",
		"december"	=> "12",
		"décembre"	=> "12",
		"decembre"	=> "12",
		"dec"		=> "12",
		"déc"		=> "12"
	);
	
	public static $full_date_pattern 	= '/^([^ \t0-9]+)[ \t]*([0-9]{1,2})(th)?[ \t]*[\,| \t][ \t]*([0-9]{4})$/';
	public static $short_date_pattern	= '/^([^ \t0-9\,]+)[ \t]*[\,]?[ \t]*([0-9]{4})$/';
	
	public static $uncertainty_level = array('c' => 0, 'd' => 1, 'm' => 2, 'y' => 3, 'u' => 4);
}


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
		$values[$v] = isset($values[$i]) ? $values[$i] : "";
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

/**
 * Tries to rewrite an excel date to year-month-day format. Will fix the 
 * accuracy (if any) accordingly. Use Model->custom_data['date_fields] = array(date_field => accuracy_field)
 * @param Model $m
 */
function excelDateFix(Model $m){
	//rearrange dates
	foreach($m->custom_data['date_fields'] as $date_field => $accuracy_field){
		$m->values[$date_field] = trim($m->values[$date_field]);
		//echo "IN DATE: ",$m->values[$date_field]," -> ";
		$matches = array();
		if(preg_match_all(MyTime::$full_date_pattern, $m->values[$date_field], $matches, PREG_OFFSET_CAPTURE) > 0){
			if(isset(MyTime::$months[strtolower($matches[1][0][0])])){
				$m->values[$date_field] = $matches[4][0][0]."-".MyTime::$months[strtolower($matches[1][0][0])]."-".sprintf("%02d", $matches[2][0][0]);
				if(strlen($m->values[$date_field]) != 10){
					echo "WARNING ON DATE [",$old_val,"] (A.1) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]\n";
				}
			}else{
				echo "WARNING ON DATE: unknown month [",$matches[1][0][0],"] (A.2) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]\n";
			}
		}else if(preg_match_all(MyTime::$short_date_pattern, $m->values[$date_field], $matches, PREG_OFFSET_CAPTURE) > 0){
			$old_val = $m->values[$date_field];
			$m->values[$date_field] = $matches[2][0][0]."-".MyTime::$months[strtolower($matches[1][0][0])]."-01";
			if(strlen($m->values[$date_field]) != 10){
				echo "WARNING ON DATE [",$old_val,"] month[".strtolower($matches[1][0][0])."](B) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]\n";
			}
			if($accuracy_field != null && MyTime::$uncertainty_level[$m->values[$accuracy_field]] < MyTime::$uncertainty_level['m']){
				$m->values[$accuracy_field] = 'm';
			}
		}else if(is_numeric($m->values[$date_field])){
			if($m->values[$date_field] < 2500){
				//only year
				$m->values[$date_field] = $m->values[$date_field]."-01-01";
				if($accuracy_field != null && MyTime::$uncertainty_level[$m->values[$accuracy_field]] < MyTime::$uncertainty_level['y']){
					$m->values[$accuracy_field] = 'y';
				}
			}else{
				//excel date integer representation
				$php_offset = 946746000;//2000-01-01 (12h00 to avoid daylight problems)
				$xls_offset = 36526;//2000-01-01
				$m->values[$date_field] = date("Y-m-d", $php_offset + (($m->values[$date_field] - $xls_offset) * 86400));
			}
			
		}else if(preg_match_all('/^([A-Za-z]{3})\1{1,3}\/([0-9]{4})\2$/', $m->values[$date_field], $matches) && isset(MyTime::$months[strtolower($matches[1][0])])){
			$m->values[$accuracy_field] = "m";
			$m->values[$date_field] = $matches[2][0]."-".MyTime::$months[strtolower($matches[1][0])]."-01";
			
		}else if(strlen($m->values[$date_field]) > 0 && preg_match_all('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $m->values[$date_field], $matches) === 0 && $accuracy_field != NULL){
			//not a standard date, consider unknown
			$m->values[$accuracy_field] = "u";
			if(strlen($m->values[$date_field]) != 10){
				echo "WARNING ON DATE [",$m->values[$date_field],"] (C) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]\n";
			}
		}
		//echo $m->values[$date_field],"\n";
	}
}

/**
 * Fetches a structure_value_domain
 * @param string $domain_name The name of the domain to fetch
 * @return array The domain name with the values as both array keys and values
 */
function getValueDomain($domain_name){
	$tmp = array();

	$query = "SELECT value
		FROM structure_value_domains AS svd
		INNER JOIN structure_value_domains_permissible_values AS svdpv ON svd.id=svdpv.structure_value_domain_id
		INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id
		WHERE svd.domain_name='".$domain_name."'";
	$result = mysqli_query(Config::$db_connection, $query) or die("reading value domains failed ");
	while($row = $result->fetch_assoc()){
		$tmp[$row['value']] = $row['value'];
	}
	mysqli_free_result($result);
	
	return $tmp;
}
?>