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
	public static $ymd_date_pattern	= '#^([\d]{4})[-/]([\d]{2})[-/]([\d]{2})$#';
	
	public static $uncertainty_level = array('c' => 0, 'd' => 1, 'm' => 2, 'y' => 3, 'u' => 4);
}

class Database{
	
	static private $fields_cache = array();
	
	static function getFields($table_name){
		if(!array_key_exists($table_name, self::$fields_cache)){
			$query = 'DESC '.$table_name;
			if(Config::$print_queries){
				echo $query.Config::$line_break_tag;
			}
			$result = mysqli_query(Config::$db_connection, $query) or die(__FUNCTION__." [".__LINE__."] qry failed [".$query."] ".mysqli_error(Config::$db_connection));
			$fields = array();
			while($row = mysqli_fetch_row($result)){
				$fields[] = $row[0];
			}
			self::$fields_cache[$table_name] = $fields;
		}
		
		return self::$fields_cache[$table_name];
	}
	
	static function insertRev($source_table_name, $pkey_val = null, $pkey_name = 'id'){
		if(Config::$insert_revs){
			$fields_org = self::getFields($source_table_name);
			$fields_rev = self::getFields($source_table_name.'_revs');
			$fields = implode(', ', array_intersect($fields_org, $fields_rev));
			$where = $pkey_val == null ? '' : ' WHERE '.$pkey_name.'="'.$pkey_val.'" ';
			$query = 'INSERT INTO '.$source_table_name.'_revs ('.$fields.', version_created) (SELECT '.$fields.', NOW() FROM '.$source_table_name.' '.$where.' ORDER BY '.$pkey_name.' DESC LIMIT 1)';
			if(Config::$print_queries){
				echo $query.Config::$line_break_tag;
			}
			mysqli_query(Config::$db_connection, $query) or die(__FUNCTION__." [".__LINE__."] qry failed [".$query."] ".mysqli_error(Config::$db_connection));
 		}
	}
	
	/**
	 * @deprecated Use insertRev instead
	 */
	static function insertRevForLastRow($source_table_name){
		self::insertRev($source_table_name);
	}
	
	static function sqlEmpty($value){
		return empty($value) || $value == '0000-00-00' || $value == '0000-00-00 00:00:00';
	}
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
 * accuracy (if any) accordingly. Use Model->custom_data['date_fields'] = array(date_field => accuracy_field, ...)
 * @param Model $m
 */
function excelDateFix(Model $m){
	//rearrange dates
	global $insert;
	foreach($m->custom_data['date_fields'] as $date_field => $accuracy_field){
		
		if(!array_key_exists($date_field, $m->values)){
			echo 'ERROR: excelDateFix index key not found ['.$date_field.'] for file ['.$m->file.'] on table ['.$m->table."]".Config::$line_break_tag;
			$insert = false;
			continue;
		}
		$m->values[$date_field] = trim($m->values[$date_field]);
		//echo "IN DATE: ",$m->values[$date_field]," -> ";
		$matches = array();
		if(preg_match_all('/[0-9]{2}\\.[0-9]{2}\\.[0-9]{2}/', $m->values[$date_field], $matches) === 1){
			$m->values[$date_field] = str_replace(".", "-", $m->values[$date_field]);
		}
		
		if(preg_match_all(MyTime::$full_date_pattern, $m->values[$date_field], $matches, PREG_OFFSET_CAPTURE) > 0){
			if(isset(MyTime::$months[strtolower($matches[1][0][0])])){
				$m->values[$date_field] = $matches[4][0][0]."-".MyTime::$months[strtolower($matches[1][0][0])]."-".sprintf("%02d", $matches[2][0][0]);
				if(strlen($m->values[$date_field]) != 10){
					echo "WARNING ON DATE [",$old_val,"] (A.1) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
				}
			}else{
				echo "WARNING ON DATE: unknown month [",$matches[1][0][0],"] (A.2) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
			}
		}else if(preg_match_all(MyTime::$short_date_pattern, $m->values[$date_field], $matches, PREG_OFFSET_CAPTURE) > 0){
			$old_val = $m->values[$date_field];
			$m->values[$date_field] = $matches[2][0][0]."-".MyTime::$months[strtolower($matches[1][0][0])]."-01";
			if(strlen($m->values[$date_field]) != 10){
				echo "WARNING ON DATE [",$old_val,"] month[".strtolower($matches[1][0][0])."](B) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
			}
			if($accuracy_field != null) {
				if(!isset(MyTime::$uncertainty_level[$m->values[$accuracy_field]])) {
					echo "WARNING ON DATE ACCURACY: the accuracy looks like to be missing on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
				} else if(MyTime::$uncertainty_level[$m->values[$accuracy_field]] <	MyTime::$uncertainty_level['m']){
					$m->values[$accuracy_field] = 'm';
				}
			}
		}else if(is_numeric($m->values[$date_field])){
			if($m->values[$date_field] < 2500){
				//only year
				$m->values[$date_field] = $m->values[$date_field]."-01-01";
				if(!isset($m->values[$accuracy_field])){
					echo "ERROR: Cannot set the date for field [", $date_field, "] because no accuracy fields is matched to it. See file [",$m->file, "] at line [", $m->line, "]".Config::$line_break_tag;
					global $insert;
					$insert = false;
				}
				if(!isset($m->values[$accuracy_field])) pr($date_field.' ACCURACY IS MISSING');
				if(!isset(MyTime::$uncertainty_level[$m->values[$accuracy_field]])) pr($date_field.' ACCURACY IS MISSING 2');
				if($accuracy_field != null && MyTime::$uncertainty_level[$m->values[$accuracy_field]] < MyTime::$uncertainty_level['y']){
					$m->values[$accuracy_field] = 'y';
				}
			}else{			
				//excel date integer representation
				$php_offset = 946746000;//2000-01-01 (12h00 to avoid daylight problems)
				$xls_offset = Config::$use_windows_xls_offset? 36526 : 35064; //2000-01-01 = 36526 for windows & 2000-01-01 = 36526 for mac 35064
				$m->values[$date_field] = date("Y-m-d", $php_offset + (($m->values[$date_field] - $xls_offset) * 86400));
			}
			
		}else if(preg_match_all('/^([A-Za-z]{3})\1{1,3}\/([0-9]{4})\2$/', $m->values[$date_field], $matches) && isset(MyTime::$months[strtolower($matches[1][0])]) && $accuracy_field != null){
			$m->values[$accuracy_field] = "m";
			$m->values[$date_field] = $matches[2][0]."-".MyTime::$months[strtolower($matches[1][0])]."-01";
			
		}else if(strlen($m->values[$date_field]) > 0 && preg_match_all('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $m->values[$date_field], $matches) === 0){
			//not a standard date, consider unknown
			if($accuracy_field = NULL){
				$m->values[$accuracy_field] = "u";
				if(strlen($m->values[$date_field]) != 10){
					echo "WARNING ON DATE [",$m->values[$date_field],"] (C) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
				}
			}else{
				global $insert;
				$insert = false;
				echo "ERROR ON DATE [",$m->values[$date_field],"] (D) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]".Config::$line_break_tag;
			}
		}else if(preg_match(MyTime::$ymd_date_pattern, $m->values[$date_field], $matches) > 0){
			//classic date
		}else{
			//empty date, turn into null
			if($m->values[$date_field] != ''){
				echo "WARNING ON DATE [",$m->values[$date_field],"] (E) on sheet [".$m->file."] at line [".$m->line."] on field [".$date_field."]\n";
			}
			$m->values[$date_field] = null;
		}
	}
	
	return true;
}

/**
 * Fetches a structure_value_domain
 * @param string $domain_name The name of the domain to fetch
 * @return array The domain name with the values as both array keys and values
 */
function getValueDomain($domain_name){
	$tmp = array();
	
	$query = 'SELECT source FROM structure_value_domains WHERE domain_name="'.$domain_name.'" AND source LIKE "StructurePermissibleValuesCustom::getCustomDropdown(\'%\')"';
	$result = mysqli_query(Config::$db_connection, $query) or die("reading value domains failed 1 ".$domain_name);
	if($row = $result->fetch_assoc()){
		$control_name = substr($row['source'], 53, (strlen($row['source']) -55));
		mysqli_free_result($result);
		$query = "SELECT value
			FROM structure_permissible_values_customs AS spvc
			INNER JOIN structure_permissible_values_custom_controls AS spvcc ON spvc.control_id=spvcc.id AND spvcc.name='".$control_name."'";
		
	}else{
		mysqli_free_result($result);
		$query = "SELECT value
			FROM structure_value_domains AS svd
			INNER JOIN structure_value_domains_permissible_values AS svdpv ON svd.id=svdpv.structure_value_domain_id
			INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id
			WHERE svd.domain_name='".$domain_name."'";
		
	}
	$result = mysqli_query(Config::$db_connection, $query) or die("reading value domains failed 2");
	while($row = $result->fetch_assoc()){
		$tmp[$row['value']] = $row['value'];
	}
	mysqli_free_result($result);
	return $tmp;
}
?>