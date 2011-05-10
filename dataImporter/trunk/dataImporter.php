<?php
error_reporting(E_ALL | E_STRICT);

require_once("master_detail_model.php");
require_once("commonFunctions.php");
require_once("../atim_tf_prostate/dataImporterConfig/config.php");
define("IS_XLS", $config['input type'] == "xls");
if(IS_XLS){
	require_once 'Excel/reader.php';
	$xls_reader = new Spreadsheet_Excel_Reader();
	$xls_reader->read($config['xls input file']);
	
}

//init database connection
global $connection;
$connection = @mysqli_connect($database['ip'].":".$database['port'], $database['user'], $database['pwd']) or die("Could not connect to MySQL");
if(!mysqli_set_charset($connection, $database['charset'])){
	die("Invalid charset");
}
@mysqli_select_db($connection, $database['schema']) or die("db selection failed");
mysqli_autocommit($connection, false);


//validate each file exists and prep them
foreach($tables as $ref_name => &$table){
	if(strlen($table->file) > 0){
		if(IS_XLS){
			if(!is_numeric($xls_reader->sheets[$table->file]['numRows'])){
				die("Sheet for [".$ref_name."] does not exist. [".$table->file."]\n");
			}
			$table->file_handler = $xls_reader->sheets[$table->file];
		}else{
			if(!is_file($table->file)){
				die("File for [".$ref_name."] does not exist. [".$table->file."]\n");
			}
			$table->file_handler = fopen($table->file, 'r');
			if(!$table->file_handler){
				die("fopen failed on ".$ref_name);
			}
		}
		
		$table->query_insert = " (".buildInsertQuery($table->fields);
 	 	if($config['insertRevs']){
			$table->query_insert_revs = "INSERT INTO ".$table->table."_revs".$table->query_insert.", `id`) VALUES(";
 	 	}
 	 	$table->query_insert = "INSERT INTO ".$table->table.$table->query_insert.") VALUES(";
		if(is_a($table, "MasterDetailModel")){
			if(is_array($table->detail_table)){
				//prep for multi detail tables
				foreach($table->detail_table as $key => $value){
					$table->query_detail_insert[$key] = " (".buildInsertQuery($tables[$ref_name]['detail'][$key]);
					if($config['insertRevs']){
						$table->query_detail_insert_revs[$key] = "INSERT INTO ".$table->detail_table[$key]."_revs".$table->query_detail_insert[$key].", `id`) VALUES(";
					}
					$table->query_detail_insert[$key] = "INSERT INTO ".$table->detail_table[$key].$table->query_detail_insert[$key].") VALUES(";
				}
			}else{
				//prep for single detail table
				$table->query_detail_insert = " (".buildInsertQuery(array_merge($table->detail_fields, array($table->detail_master_fkey => "@")));
				if($config['insertRevs']){
					$table->query_detail_insert_revs = "INSERT INTO ".$table->detail_table."_revs".$table->query_detail_insert.", `id`) VALUES(";
				}
				$table->query_detail_insert = "INSERT INTO ".$table->detail_table.$table->query_detail_insert.") VALUES(";
			}
		}
		if(IS_XLS){
			if($config['xls header rows'] == 1){
				$table->keys = $table->file_handler['cells'][1];
				$table->line = 1;
			}else if($config['xls header rows'] == 2){
				$table->line = 2;
				$table->keys = $table->file_handler['cells'][2];
				foreach($table->file_handler['cells'][1] as $key => $title){
					if(isset($table->keys[$key])){
						$colspan = isset($table->file_handler['cellsInfo'][1][$key]) && is_numeric($table->file_handler['cellsInfo'][1][$key]['colspan']) ? $table->file_handler['cellsInfo'][1][$key]['colspan'] : 1; 
						for($i = $colspan - 1; $i >= 0; -- $i){
							$table->keys[$key + $i] = $title." ".$table->keys[$key + $i]; 
						}
					}else{
						$table->keys[$key] = $title;
					}
				}
			}else{
				die("xls header rows config not supported");
			}
		}else{
			$table->keys = lineToArray(fgets($table->file_handler, 4096));
			$table->line = 0;
		}
		
		//check dupes
		if(count(array_unique($table->keys)) != count($table->keys)){
			die("[".$ref_name."] contains duplicate row names\n");
		}
		
		//check all non empty configured keys are found
		$missing = array();
		$tmp_fields = $table->fields;
		if(is_a($table, "MasterDetailModel")){
			$tmp_fields = array_merge($tmp_fields, $table->detail_fields);
		}

		foreach($tmp_fields as $key => $val){
			if(is_array($val)){
				if(!in_array(key($val), $table->keys)){
					$missing[] = key($val);
				}
			}else if(strlen($val) > 0 && !in_array($val, $table->keys) && strpos($val, "@") !== 0){
				$missing[] = $val;
			}
		}
		if(!empty($missing)){
			die("The following key(s) for [$ref_name] were not found: ".implode(", ", $missing)."\n");
		}
		
		if($table->parent_key != null && !isset($table->fields[$table->parent_key])){
			die("The parent key must be binded to a field for [".$table->file."]\n");
		}
		
		readLine($table);
		if(!empty($table->values) && !isset($table->values[$table->pkey])){
			print_r($table->values);
			die("Missing pkey [".$table->pkey."] in file [".$table->file."]\n");
		}
		
		$result = mysqli_query($connection, "DESC ".$table->table) or die("table desc failed for [".$table->table."]\n");
		while($row = mysqli_fetch_row($result)){
			$table->schema[$row[0]] = array("type" => $row[1], "null" => $row[2] == "YES");
		}
		if(is_a($table, "MasterDetailModel")){
			$result = mysqli_query($connection, "DESC ".$table->detail_table) or die("table desc failed for [".$table->detail_table."]\n");
			while($row = mysqli_fetch_row($result)){
				$table->detail_schema[$row[0]] = array("type" => $row[1], "null" => $row[2]);
			}
		}
	}
}
unset($table);//weird bug otherwise


//create the temporary id linking table
mysqli_query($connection, "DROP TABLE IF EXISTS id_linking ") or die("DROP tmp failed");
$query = "CREATE TABLE id_linking(
	csv_id varchar(50) not null,
	csv_reference varchar(50) DEFAULT NULL,
	mysql_id int unsigned not null, 
	model varchar(50) not null
	)Engine=InnoDB";
mysqli_query($connection, $query) or die("temporary table query failed[".mysqli_errno($connection) . ": " . mysqli_error($connection)."]\n");

if(isset($addonQueries) && isset($addonQueries['start'])){
	print_r($addonQueries);
	foreach($addonQueries['start'] as $addonQuery){
		mysqli_query($connection, $addonQuery) or die("[".$addonQuery."] ".mysqli_errno($connection) . ": " . mysqli_error($connection));
		if($config['printQueries']){
			echo($addonQuery."\n");
		}
	}
}

//define the primary tables (collection links is considered to be a special table)
$primary_tables = array("participants", "collections");

//iteratover the primary tables who will, in turn, iterate over their children
foreach($primary_tables as $table_name){
	insertTable($table_name, $tables);
}

//TODO: treat special tables such as collection links
//INSERT INTO clinical_collection_links (`participant_id`, `collection_id`, `consent_master_id`) (
//SELECT p.mysql_id, coll.mysql_id, c.mysql_id  FROM `id_linking` AS p
//LEFT JOIN id_linking AS c ON substr(p.csv_id, 3)=substr(c.csv_id, 7)
//LEFT JOIN id_coll AS collt ON p.csv_id=collt.link_to
//LEFT JOIN id_linking AS coll ON collt.collection_id=coll.csv_id
//WHERE p.model='participants' AND c.model='consent_masters' AND coll.model='collections')

//validate that each file handler has reached the end of it's file so that no data is left behind
$insert = true;
foreach($tables as $ref_name => &$table){
	if(IS_XLS){
		if(isset($table->file_handler) && $table->line <= $table->file_handler["numRows"]){
			echo("ERROR: Data was not all fetched from [".$ref_name."] - Stopped at line [".$table->line."]\n");
			$insert = false;
		}
	}else if(strlen($table->file) > 0){
		if(!feof($table->file_handler)){
			echo("ERROR: Data was not all fetched from [".$ref_name."] - Stopped at line [".$tableline."]\n");
			$insert = false;
		}
	}
}

//proceed with addon querries
if(isset($addonQueries) && isset($addonQueries['end'])){
	foreach($addonQueries['end'] as $addonQuery){
		mysqli_query($connection, $addonQuery) or die("[".$addonQuery."] ".mysqli_errno($connection) . ": " . mysqli_error($connection));
		if($config['printQueries']){
			echo($addonQuery."\n");
		}
	}
}

if($insert){
	mysqli_commit($connection);
	echo("#Insertions commited\n"
		."#*************************\n"
		."#********VictWare*********\n"
		."#* Integration completed *\n"
		."#*************************\n");
}else{
	echo("#Insertions cancelled. Make sure the split occured correctly. \\n within a line can break the split procedure. Also, make sure all ids are in ascending order.\n");
}


/**
 * Takes an array of field and returns a string contaning those who have a non empty string value
 * and adds the default created, created_by, modified, modified_by fields at the end
 * @param array $fields 
 * @return string
 */
function buildInsertQuery(array $fields){
	$result = "";
	foreach($fields as $field => $value){
		if(is_array($value) || strlen($value) > 0){
			$result .= $field.", ";
		}
	}
	
	return $result."created, created_by, modified, modified_by";
}

/**
 * Takes the fields array and the values array in order to build the values part of the query.
 * The value fields starting with @ will be put directly into the query without beign replaced (minus the first @)
 * @param array $fields The array of the fields configuration
 * @param array $values The array of values read from the csv
 * @param array $schema The db schema going with the current insert
 * @return string
 */
function buildValuesQuery(array $fields, array $values, array $schema){
	global $created_id;
	$result = "";
	foreach($fields as $field => $value){
		if(is_array($value)){
			$tmp = $values[key($value)];
			$val_array = current($value);
			if(isset($val_array[$tmp])){
				$result .= "'".$val_array[$tmp]."', ";
			}else{
				$result .= "'', ";
				echo "WARNING: value[",$tmp,"] is unmatched for field [",$field,"]\n";
			}
		}else if(strpos($value, "@") === 0){
			$result .= "'".substr($value, 1)."', ";
		}else if(strlen($value) > 0){
			if(strlen($values[$value]) > 0){
				$result .= "'".str_replace("'", "\\'", $values[$value])."', ";
			}else if(isDbNumericType($schema[$field]['type']) && $schema[$field]){
				$result .= "NULL, ";
			}else{
				$result .= "'', ";
			}
		}
	}
	return $result."NOW(), ".$created_id.", NOW(), ".$created_id;	
}

function isDbNumericType($field_type){
	return strpos($field_type, "int(") === 0
		|| strpos($field_type, "float") === 0
		|| strpos($field_type, "tinyint(") === 0
		|| strpos($field_type, "smallint(") === 0
		|| strpos($field_type, "mediumint(") === 0
		|| strpos($field_type, "double") === 0;
}


/**
 * Inserts a given table data into the database. For each row, there is a verification to see if children exist to call this
 * function recursively
 * @param unknown_type $table_name The name of the table to work on
 * @param unknown_type $tables The full array containing every tables config
 * @param unknown_type $csv_parent_key The csv key of the parent table if it exists
 * @param unknown_type $mysql_parent_id The id (integer) of the mysql parent row
 */
function insertTable($table_name, &$tables, $csv_parent_key = null, $mysql_parent_id = null, $parent_data = null){
	global $connection;
	global $config;
	if(!isset($tables[$table_name])){
		return ;
	}
	$current_table = &$tables[$table_name];
	$i = 0;
	//debug info
//	echo($table_name."\n");
//	if($table_name == "collections"){
//		echo("Size: ".sizeof($current_table->values)."\n");
//		print_r($current_table->values);
//		echo($current_table->parent_key." -> ".$current_table->fields[$current_table->parent_key]."\n");
//		echo($current_table->values[$current_table->fields[$current_table->parent_key]]."  -  ".$csv_parent_key."\n");
//		echo($current_table->values[$current_table->fields[$current_table->parent_key]]."\n");
//		exit;
//	}
	while(sizeof($current_table->values) > 0 && 
	($csv_parent_key == null || $current_table->values[$current_table->fields[$current_table->parent_key]] == $csv_parent_key)
	){
			//replace parent value.
		if($mysql_parent_id != null){
			$current_table->values[$current_table->fields[$current_table->parent_key]] = $mysql_parent_id;
		}
		if(isset($parent_data)){
			//put answers in place
			foreach($parent_data as $question => $answer){
				$current_table->values[$question] = $answer;
			}
		}
		
		//master main
		$queryValues = buildValuesQuery($current_table->fields, $current_table->values, $current_table->schema);
		$query = $current_table->query_insert.$queryValues.")";
		mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
		$last_id = mysqli_insert_id($connection);
		if($config['printQueries']){
			echo $query.";\n";
		}
		
		if($config['insertRevs']){
			//master revs
			$query = $current_table->query_insert_revs.$queryValues.", '".$last_id."')";
			mysqli_query($connection, $query) or die("query failed[".$table_name."_revs][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
			if($config['printQueries']){
				echo $query.";\n";
			}
		}
		if(is_a($current_table, "MasterDetailModel")){
			//detail level
			if(is_array($current_table->query_detail_insert)){
				//insert into multi detail tables
				foreach($current_table->query_detail_insert as $key => $value){
					//detail main
					$current_table->detail_fields[$key][$current_table->detail_master_fkey] = "@".$last_id;
					echo $current_table->detail_master_fkey,"\n";
					
					$queryValues = buildValuesQuery($current_table->detail_fields[$key], $current_table->values, $current_table->detail_schema);
					$query = $current_table->query_detail_insert[$key].$queryValues.")";
					mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
					$last_detail_id = mysqli_insert_id($connection);
					if($config['printQueries']){
						echo $query.";\n";
					}
					
					if($config['insertRevs']){
						//detail revs
						$query = $current_table->query_detail_insert_revs[$key].$queryValues.", '".$last_detail_id."')";
						mysqli_query($connection, $query) or die("query failed[".$table_name."_revs][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
						if($config['printQueries']){
							echo $query.";\n";
						}
					}
				}
			}else{
				
				//insert insto single detail table
				//detail main
				$current_table->detail_fields[$current_table->detail_master_fkey] = "@".$last_id;
				$queryValues = buildValuesQuery($current_table->detail_fields, $current_table->values, $current_table->detail_schema);
				$query = $current_table->query_detail_insert.$queryValues.")";
				mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
				$last_detail_id = mysqli_insert_id($connection);
				if($config['printQueries']){
					echo $query.";\n";
				}
				
				if($config['insertRevs']){
					//detail revs
					$query = $current_table->query_detail_insert_revs.$queryValues.", '".$last_detail_id."')";
					mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
					if($config['printQueries']){
						echo $query.";\n";
					}
				}
				
			}
		}

		if($current_table->post_write_function != NULL){
			$func = $current_table->post_write_function;
			$func($current_table, $last_id);
		}
		
		//saving id if required
		if($current_table->save_id){
			$query = "INSERT INTO id_linking (csv_id, csv_reference, mysql_id, model) VALUES('"
					.$current_table->values[$current_table->pkey]."', "
					."'".$table_name."', " 
					.$last_id.", '"
					.$current_table->table."')";
			if($config['printQueries']){
				echo $query.";\n";
			}
			mysqli_query($connection, $query) or die("tmp id query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");	
		}
		
		if(is_array($current_table->child)){
			//treat child
			foreach($current_table->child as $child_table_name){
				$child_required_data = array();
				if(isset($tables[$child_table_name]->ask_parent)){
					foreach($tables[$child_table_name]->ask_parent as $question => $where_to_answer){
						if($question == "id"){
							$child_required_data[$where_to_answer] = $last_id;
						}else{
							$child_required_data[$where_to_answer] = $current_table->values[$current_table->fields[$question]];
						}
					}
				}
				
				insertTable($child_table_name, 
								$tables, 
								$current_table->values[$current_table->pkey], 
								$last_id, 
								$child_required_data);
			}
		}
		flush();
		readLine($current_table);
	}
}

function readLine(&$current_table){
	$proceed = true;
	$end_of_file_eval = NULL;
	if(IS_XLS){
		$end_of_file_eval = 'return $current_table->line > $current_table->file_handler["numRows"];';
	}else if(feof($current_table->file_handler)){
		$end_of_file_eval = 'return feof($current_table->file_handler);';
	}
	if(eval($end_of_file_eval)){
		$current_table->values = array();
	}else{
		do{
			//read line, skip empty lines
			$current_table->line ++;
			if(IS_XLS){
				$current_table->values = isset($current_table->file_handler['cells'][$current_table->line]) ? $current_table->file_handler['cells'][$current_table->line] : array();
			}else{
				$line = fgets($current_table->file_handler, 4096);
				$current_table->values = lineToArray($line);
			}
			associate($current_table->keys, $current_table->values);
		}while(!eval($end_of_file_eval) && (sizeof($current_table->values) <= (sizeof($current_table->keys) + 1) ||
		(strlen($current_table->pkey) > 0 && strlen($current_table->values[$current_table->pkey]) == 0)));
		
		if(eval($end_of_file_eval)){
			$current_table->values = array();
		}else{
			if($current_table->parent_key != null){
				if($current_table->parent_key_value != null
				&& strlen($current_table->values[$current_table->parent_key]) != 0 
				&& $current_table->parent_key_value > $current_table->values[$current_table->parent_key]){
					echo("WARNING: parent_key ".$current_table->parent_key." is not in ascending order at line ".$current_table->line." of file ".$current_table->file
						.". Previous: ".$current_table->parent_key_value." Current: ".$current_table->values[$current_table->parent_key]."\n");
				}
				if(isset($current_table->values[$current_table->parent_key])){
					$current_table->parent_key_value = $current_table->values[$current_table->parent_key];
				}
			}
			if($current_table->post_read_function != null){
				$func = $current_table->post_read_function;
				$func($current_table);
			}
		}
	}
}


