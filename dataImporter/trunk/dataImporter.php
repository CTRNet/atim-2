<?php
error_reporting(E_ALL | E_STRICT);
require_once("master_detail_model.php");
require_once("commonFunctions.php");
require_once("valueDomain.php");


//UPDATE THIS TO POINT TO YOUR CONFIG
require_once("../atim_tf_coeur/dataImporterConfig/config.php");
//require_once("config.php");
//-----------------------------------


date_default_timezone_set(Config::$timezone);

define("IS_XLS", Config::$input_type == Config::INPUT_TYPE_XLS);
if(IS_XLS){
	require_once 'Excel/reader.php';
	$xls_reader = new Spreadsheet_Excel_Reader();
	$xls_reader->read(Config::$xls_file_path);
	
}

//init database connection
$connection = @mysqli_connect(
	Config::$db_ip.":".Config::$db_port, 
	Config::$db_user, 
	Config::$db_pwd
) or die("Could not connect to MySQL");
Config::$db_connection = $connection;

if(!mysqli_set_charset($connection, Config::$db_charset)){
	die("Invalid charset");
}
@mysqli_select_db($connection, Config::$db_schema) or die("db selection failed");
mysqli_autocommit($connection, false);

//import configs
foreach(Config::$config_files as $config_file){
	require_once $config_file;
}

//validate each file exists and prep them
foreach(Config::$models as $ref_name => &$model){
	if(strlen($model->file) > 0){
		if(IS_XLS){
			if(!is_numeric($xls_reader->sheets[$model->file]['numRows'])){
				die("Sheet for [".$ref_name."] does not exist. [".$model->file."]\n");
			}
			$model->file_handler = $xls_reader->sheets[$model->file];
		}else{
			if(!is_file($model->file)){
				die("File for [".$ref_name."] does not exist. [".$model->file."]\n");
			}
			$model->file_handler = fopen($model->file, 'r');
			if(!$model->file_handler){
				die("fopen failed on ".$ref_name);
			}
		}
		
		$model->query_insert = " (".buildInsertQuery($model->fields);
 	 	if(Config::$insert_revs){
			$model->query_insert_revs = "INSERT INTO ".$model->table."_revs".$model->query_insert.", `id`) VALUES(";
 	 	}
 	 	$model->query_insert = "INSERT INTO ".$model->table.$model->query_insert.") VALUES(";
		if(is_a($model, "MasterDetailModel")){
			if(is_array($model->detail_table)){
				//prep for multi detail tables
				foreach($model->detail_table as $key => $value){
					$model->query_detail_insert[$key] = " (".buildInsertQuery($models[$ref_name]['detail'][$key]);
					if(Config::$insert_revs){
						$model->query_detail_insert_revs[$key] = "INSERT INTO ".$model->detail_table[$key]."_revs".$model->query_detail_insert[$key].", `id`) VALUES(";
					}
					$model->query_detail_insert[$key] = "INSERT INTO ".$model->detail_table[$key].$model->query_detail_insert[$key].") VALUES(";
				}
			}else{
				//prep for single detail table
				$model->query_detail_insert = " (".buildInsertQuery(array_merge($model->detail_fields, array($model->detail_master_fkey => "@")));
				if(Config::$insert_revs){
					$model->query_detail_insert_revs = "INSERT INTO ".$model->detail_table."_revs".$model->query_detail_insert.", `id`) VALUES(";
				}
				$model->query_detail_insert = "INSERT INTO ".$model->detail_table.$model->query_detail_insert.") VALUES(";
			}
		}
		if(IS_XLS){
			if(Config::$xls_header_rows == 1){
				$model->keys = $model->file_handler['cells'][1];
				$model->line = 1;
			}else if(Config::$xls_header_rows == 2){
				$model->line = 2;
				$model->keys = $model->file_handler['cells'][2];
				foreach($model->file_handler['cells'][1] as $key => $title){
					if(isset($model->keys[$key])){
						$colspan = isset($model->file_handler['cellsInfo'][1][$key]) && is_numeric($model->file_handler['cellsInfo'][1][$key]['colspan']) ? $model->file_handler['cellsInfo'][1][$key]['colspan'] : 1; 
						for($i = $colspan - 1; $i >= 0; -- $i){
							$model->keys[$key + $i] = $title." ".$model->keys[$key + $i]; 
						}
					}else{
						$model->keys[$key] = $title;
					}
				}
			}else{
				die("xls header rows config not supported");
			}
		}else{
			$model->keys = lineToArray(fgets($model->file_handler, 4096));
			$model->line = 0;
		}
		
		//check dupes
		if(count(array_unique($model->keys)) != count($model->keys)){
			die("[".$ref_name."] contains duplicate row names\n");
		}
		
		//check all non empty configured keys are found
		$missing = array();
		$tmp_fields = $model->fields;
		if(is_a($model, "MasterDetailModel")){
			$tmp_fields = array_merge($tmp_fields, $model->detail_fields);
		}

		foreach($tmp_fields as $key => $val){
			if(is_array($val)){
				if(!in_array(key($val), $model->keys)){
					$missing[] = key($val);
				}
			}else if(strlen($val) > 0 && !in_array($val, $model->keys) && strpos($val, "@") !== 0 && strpos($val, "#") !== 0){
				$missing[] = $val;
			}
		}
		if(!empty($missing)){
			die("The following key(s) for [$ref_name] were not found: [".implode("], [", $missing)."]\n");
		}
		
		if($model->parent_key != null && !isset($model->fields[$model->parent_key])){
			die("The parent key must be binded to a field for [".$model->file."]\n");
		}
		
		readLine($model);
		if(!empty($model->values) && !isset($model->values[$model->pkey])){
			print_r($model->values);
			die("Missing pkey [".$model->pkey."] in file [".$model->file."]\n");
		}
		
		$result = mysqli_query($connection, "DESC ".$model->table) or die("table desc failed for [".$model->table."]\n");
		while($row = mysqli_fetch_row($result)){
			$model->schema[$row[0]] = array("type" => $row[1], "null" => $row[2] == "YES");
		}
		if(is_a($model, "MasterDetailModel")){
			$result = mysqli_query($connection, "DESC ".$model->detail_table) or die("table desc failed for [".$model->detail_table."]\n");
			while($row = mysqli_fetch_row($result)){
				$model->detail_schema[$row[0]] = array("type" => $row[1], "null" => $row[2]);
			}
		}
	}
}
unset($model);//weird bug otherwise

//load the value domains
$tmp = array();
foreach(Config::$value_domains as $domain_name){
	$tmp[$domain_name] = getValueDomain($domain_name);
}
Config::$value_domains = $tmp;

//create the temporary id linking table
mysqli_query($connection, "DROP TABLE IF EXISTS id_linking ") or die("DROP tmp failed");
$query = "CREATE TABLE id_linking(
	csv_id varchar(50) not null,
	csv_reference varchar(50) DEFAULT NULL,
	mysql_id int unsigned not null, 
	model varchar(50) not null
	)Engine=InnoDB";
mysqli_query($connection, $query) or die("temporary table query failed[".mysqli_errno($connection) . ": " . mysqli_error($connection)."]\n");

foreach(Config::$addon_queries_start as $addon_query_start){
	mysqli_query($connection, $addon_query_start) or die("[".$addon_query_start."] ".mysqli_errno($connection) . ": " . mysqli_error($connection));
	if(Config::$print_queries){
		echo($addon_query_start."\n");
	}
}

//iteratover the primary tables who will, in turn, iterate over their children
foreach(Config::$parent_models as $model_name){
	insertTable($model_name);
}

//validate that each file handler has reached the end of it's file so that no data is left behind
$insert = true;
foreach(Config::$models as $ref_name => &$model){
	if(IS_XLS){
		if(isset($model->file_handler) && $model->line <= $model->file_handler["numRows"]){
			echo("ERROR: Data was not all fetched from [".$ref_name."] - Stopped at line [".$model->line."]\n");
			$insert = false;
		}
	}else if(strlen($model->file) > 0){
		if(!feof($model->file_handler)){
			echo("ERROR: Data was not all fetched from [".$ref_name."] - Stopped at line [".$model->line."]\n");
			$insert = false;
		}
	}
}

//proceed with addon querries
foreach(Config::$addon_queries_end as $addon_query_end){
	mysqli_query($connection, $addon_query_end) or die("[".$addon_query_end."] ".mysqli_errno($connection) . ": " . mysqli_error($connection));
	if(Config::$print_queries){
		echo($addon_query_end."\n");
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
 * @param Model $model The model to build the values query on
 * @param array $fields The fields key to use
 * @param array $schema The schema into which the insert will occur
 * @return string
 */
function buildValuesQuery(Model $model, array $fields, array $schema){
	$result = "";
	foreach($fields as $field => $value){
		if(is_array($value)){
			$possible_values = current($value);
			$tmp = $model->values[key($value)];
			if(is_a($possible_values, 'ValueDomain')){
				if($possible_values->case_sensitive == ValueDomain::CASE_INSENSITIVE){
					$tmp = strtolower($tmp);
				}
				$possible_values = $possible_values->values;
			}
			if(isset($possible_values[$tmp])){
				$result .= "'".$possible_values[$tmp]."', ";
			}else{
				$result .= "'', ";
				echo "WARNING: value [",$tmp,"] is unmatched for field [",$field,"] in file [",$model->file,"] at line [".$model->line."]\n";
			}
		}else if(strpos($value, "@") === 0){
			$result .= "'".substr($value, 1)."', ";
		}else if(strpos($value, "#") === 0){
			$tmp = substr($value, 1);
			if(isset($model->values[$tmp])){
				$result .= "'".$model->values[$tmp]."', ";
			}else{
				$result .= "'', ";
				echo "WARNING: custom value [",$tmp,"] is unmatched for field [",$field,"] in file [",$model->file,"] at line [".$model->line."]\n";
			}	
		}else if(strlen($value) > 0){
			if(strlen($model->values[$value]) > 0){
				$result .= "'".str_replace("'", "\\'", $model->values[$value])."', ";
			}else if(isDbNumericType($schema[$field]['type']) && $schema[$field]){
				$result .= "NULL, ";
			}else{
				$result .= "'', ";
			}
		}
	}
	return $result."NOW(), ".Config::$db_created_id.", NOW(), ".Config::$db_created_id;	
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
 * @param unknown_type $ref_name The name of the table to work on
 * @param unknown_type $csv_parent_key The csv key of the parent table if it exists
 * @param unknown_type $mysql_parent_id The id (integer) of the mysql parent row
 * @param array $parent_data The data of the parent
 */
//table_name -> ref_name
//table -> Config::$models
function insertTable($ref_name, $csv_parent_key = null, $mysql_parent_id = null, $parent_data = null){
	$connection = Config::$db_connection;
	if(!isset(Config::$models[$ref_name])){
		echo "WARNING: model [".$ref_name."] not found\n";
		return ;
	}
	$current_model = &Config::$models[$ref_name];
	$i = 0;
	//debug info
//	echo($ref_name."\n");
//	if($ref_name == "collections"){
//		echo("Size: ".sizeof($current_model->values)."\n");
//		print_r($current_model->values);
//		echo($current_model->parent_key." -> ".$current_model->fields[$current_model->parent_key]."\n");
//		echo($current_model->values[$current_model->fields[$current_model->parent_key]]."  -  ".$csv_parent_key."\n");
//		echo($current_model->values[$current_model->fields[$current_model->parent_key]]."\n");
//		exit;
//	}
	while(sizeof($current_model->values) > 0 && 
	($csv_parent_key == null || $current_model->values[$current_model->fields[$current_model->parent_key]] == $csv_parent_key)
	){
			//replace parent value.
		if($mysql_parent_id != null){
			$current_model->values[$current_model->fields[$current_model->parent_key]] = $mysql_parent_id;
		}
		if(isset($parent_data)){
			//put answers in place
			foreach($parent_data as $question => $answer){
				$current_model->values[$question] = $answer;
			}
		}
		
		//master main
		$queryValues = buildValuesQuery($current_model, $current_model->fields, $current_model->schema);
		$query = $current_model->query_insert.$queryValues.")";
		mysqli_query($connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
		$last_id = mysqli_insert_id($connection);
		if(Config::$print_queries){
			echo $query.";\n";
		}
		
		if(Config::$insert_revs){
			//master revs
			$query = $current_model->query_insert_revs.$queryValues.", '".$last_id."')";
			mysqli_query($connection, $query) or die("query failed[".$ref_name."_revs][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
			if(Config::$print_queries){
				echo $query.";\n";
			}
		}
		if(is_a($current_model, "MasterDetailModel")){
			//detail level
			if(is_array($current_model->query_detail_insert)){
				//insert into multi detail tables
				foreach($current_model->query_detail_insert as $key => $value){
					//detail main
					$current_model->detail_fields[$key][$current_model->detail_master_fkey] = "@".$last_id;
					echo $current_model->detail_master_fkey,"\n";
					
					$queryValues = buildValuesQuery($current_model, $current_model->detail_fields[$key], $current_model->detail_schema);
					$query = $current_model->query_detail_insert[$key].$queryValues.")";
					mysqli_query($connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
					$last_detail_id = mysqli_insert_id($connection);
					if(Config::$print_queries){
						echo $query.";\n";
					}
					
					if(Config::$insert_revs){
						//detail revs
						$query = $current_model->query_detail_insert_revs[$key].$queryValues.", '".$last_detail_id."')";
						mysqli_query($connection, $query) or die("query failed[".$ref_name."_revs][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
						if(Config::$print_queries){
							echo $query.";\n";
						}
					}
				}
			}else{
				
				//insert insto single detail table
				//detail main
				$current_model->detail_fields[$current_model->detail_master_fkey] = "@".$last_id;
				$queryValues = buildValuesQuery($current_model, $current_model->detail_fields, $current_model->detail_schema);
				$query = $current_model->query_detail_insert.$queryValues.")";
				mysqli_query($connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
				$last_detail_id = mysqli_insert_id($connection);
				if(Config::$print_queries){
					echo $query.";\n";
				}
				
				if(Config::$insert_revs){
					//detail revs
					$query = $current_model->query_detail_insert_revs.$queryValues.", '".$last_detail_id."')";
					mysqli_query($connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");
					if(Config::$print_queries){
						echo $query.";\n";
					}
				}
				
			}
		}

		if($current_model->post_write_function != NULL){
			$func = $current_model->post_write_function;
			$func($current_model, $last_id);
		}
		
		//saving id if required
		if($current_model->save_id){
			$query = "INSERT INTO id_linking (csv_id, csv_reference, mysql_id, model) VALUES('"
					.$current_model->values[$current_model->pkey]."', "
					."'".$ref_name."', " 
					.$last_id.", '"
					.$current_model->table."')";
			if(Config::$print_queries){
				echo $query.";\n";
			}
			mysqli_query($connection, $query) or die("tmp id query failed[".$ref_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_model)."\n");	
		}
		
		if(is_array($current_model->child)){
			//treat child
			foreach($current_model->child as $child_model_ref){
				$child_required_data = array();
				if(isset($tables[$child_model_ref]->ask_parent)){
					foreach($tables[$child_model_ref]->ask_parent as $question => $where_to_answer){
						if($question == "id"){
							$child_required_data[$where_to_answer] = $last_id;
						}else{
							$child_required_data[$where_to_answer] = $current_model->values[$current_model->fields[$question]];
						}
					}
				}
				
				insertTable($child_model_ref, 
								$current_model->values[$current_model->pkey], 
								$last_id, 
								$child_required_data);
			}
		}
		flush();
		readLine($current_model);
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


