<?php
error_reporting(E_ALL | E_STRICT);
require_once("master_detail_model.php");
require_once("commonFunctions.php");
require_once("valueDomain.php");


//UPDATE THIS TO POINT TO YOUR CONFIG

if(!isset($_GET['project'])) {
	die('http://localhost/dataImporter/dataImporter.php?project=xxx&step=xxx with xxx being folder name');
} else {
	$project_name = $_GET['project'];
}

$step_name = '';
if(isset($_GET['step'])) {
	$step_name .= '/'.$_GET['step'];
}

//require_once("config.php");
require_once("C:/_NicolasLuc/Server/www/$project_name/dataImporterConfig$step_name/config.php");
// require_once("../atim_tf_prostate/dataImporterConfig/config.php");
// require_once("../atim_chuq_ovaire/dataImporterConfig/config.php");

//-----------------------------------


date_default_timezone_set(Config::$timezone);

define("IS_XLS", Config::$input_type == Config::INPUT_TYPE_XLS);
if(IS_XLS){
	require_once 'Excel/reader.php';
	$xls_reader = new Spreadsheet_Excel_Reader();
	$xls_reader->read(Config::$xls_file_path);
	
}

//init database connection
Config::$db_connection = @mysqli_connect(
	Config::$db_ip.":".Config::$db_port, 
	Config::$db_user, 
	Config::$db_pwd
) or die("Could not connect to MySQL");

if(!mysqli_set_charset(Config::$db_connection, Config::$db_charset)){
	die("Invalid charset");
}
@mysqli_select_db(Config::$db_connection, Config::$db_schema) or die("db selection failed");
mysqli_autocommit(Config::$db_connection, false);

//import configs
foreach(Config::$config_files as $config_file){
	require_once $config_file;
}

//validate each file exists and prep them
foreach(Config::$models as $ref_name => &$model){
	if(strlen($model->file) > 0){
		if(IS_XLS){
			if(!is_numeric($xls_reader->sheets[$model->file]['numRows'])){
				die("Sheet for [".$ref_name."] does not exist. [".$model->file."]".Config::$line_break_tag);
			}
			$model->file_handler = $xls_reader->sheets[$model->file];
		}else{
			if(!is_file($model->file)){
				die("File for [".$ref_name."] does not exist. [".$model->file."]".Config::$line_break_tag);
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
					$model->query_detail_insert[$key] = " (".buildInsertQuery($models[$ref_name]['detail'][$key],true);
					if(Config::$insert_revs){					
						$model->query_detail_insert_revs[$key] = "INSERT INTO ".$model->detail_table[$key]."_revs".$model->query_detail_insert[$key].") VALUES(";
					}
					$model->query_detail_insert[$key] = "INSERT INTO ".$model->detail_table[$key].$model->query_detail_insert[$key].") VALUES(";
				}
			}else{
				//prep for single detail table
				$model->query_detail_insert = " (".buildInsertQuery(array_merge($model->detail_fields, array($model->detail_master_fkey => "@")),true);
				if(Config::$insert_revs){
					$model->query_detail_insert_revs = "INSERT INTO ".$model->detail_table."_revs".$model->query_detail_insert.") VALUES(";
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
			die("[".$ref_name."] contains duplicate column names".Config::$line_break_tag);
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
		if(!in_array($model->csv_pkey, $model->keys) && !in_array($model->csv_pkey, $missing)){
			$missing[] = $model->csv_pkey;
		}
		
		if(!empty($missing)){
			pr("The following key(s) for [$ref_name] were not found into the ".($model->file - 1)."th worksheet: [".implode("], [", $missing)."]".Config::$line_break_tag);
			pr('=================================================================================================================='.Config::$line_break_tag);
			pr('- Model Fields (Model in tablesMapping directory) -------------------------------------------------------------'.Config::$line_break_tag);
			pr($tmp_fields);
			pr('- CSV worksheet key -------------------------------------------------------------'.Config::$line_break_tag);
			pr($model->file);
			pr('- CSV fields -------------------------------------------------------------'.Config::$line_break_tag);
			pr($model->keys);
			pr('- CSV primary key field-------------------------------------------------------------'.Config::$line_break_tag);
			pr($model->csv_pkey);
			pr('--------------------------------------------------------------').Config::$line_break_tag;
			die();
		}
		
		readFileLine($model, false);
		if(!empty($model->values)){
			-- $model->line;
		}
		if(!empty($model->values) && !isset($model->values[$model->csv_pkey])){
			print_r($model->values);
			die("Missing csv_pkey [".$model->csv_pkey."] in file [".$model->file."]".Config::$line_break_tag);
		}
		
		$result = mysqli_query(Config::$db_connection, "DESC ".$model->table) or die("table desc failed for [".$model->table."]".Config::$line_break_tag);
		while($row = mysqli_fetch_row($result)){
			$model->schema[$row[0]] = array("type" => $row[1], "null" => $row[2] == "YES");
		}
		if(is_a($model, "MasterDetailModel")){
			$result = mysqli_query(Config::$db_connection, "DESC ".$model->detail_table) or die("table desc failed for [".$model->detail_table."]".Config::$line_break_tag);
			while($row = mysqli_fetch_row($result)){
				$model->detail_schema[$row[0]] = array("type" => $row[1], "null" => $row[2]);
			}
		}
	}
}
unset($model);//weird bug otherwise

//initiate the value domains
foreach(Config::$value_domains as $value_domain){
	$value_domain->initiateValueDomain();
}

//create the temporary id linking table
mysqli_query(Config::$db_connection, "DROP TABLE IF EXISTS id_linking ") or die("DROP tmp failed");
$query = "CREATE TABLE id_linking(
	csv_id varchar(50) not null,
	csv_reference varchar(50) DEFAULT NULL,
	mysql_id int unsigned not null, 
	model varchar(50) not null
	)Engine=InnoDB";
mysqli_query(Config::$db_connection, $query) or die("temporary table query failed[".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".Config::$line_break_tag);

foreach(Config::$addon_queries_start as $addon_query_start){
	mysqli_query(Config::$db_connection, $addon_query_start) or die("[".$addon_query_start."] ".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection));
	if(Config::$print_queries){
		echo($addon_query_start.Config::$line_break_tag);
	}
}

global $insert;
$insert = true;

if(Config::$addon_function_start != null){
	$func = Config::$addon_function_start;
	$func();
}

//iteratover the primary tables who will, in turn, iterate over their children
foreach(Config::$parent_models as $model_name){
	insertTable($model_name);
}

//validate that each file handler has reached the end of it's file so that no data is left behind
foreach(Config::$models as $ref_name => &$model){
	if(IS_XLS){
		if(isset($model->file_handler) && $model->line <= $model->file_handler["numRows"]){
			echo("ERROR1: Data was not all fetched from [".$ref_name."] - Stopped at line [".$model->line." <= ".$model->file_handler["numRows"]."]".Config::$line_break_tag);
			$insert = false;
		}
	}else if(strlen($model->file) > 0){
		if(!feof($model->file_handler)){
			echo("ERROR2: Data was not all fetched from [".$ref_name."] - Stopped at line [".$model->line."]".Config::$line_break_tag);
			$insert = false;
		}
	}
}

//proceed with addon querries
foreach(Config::$addon_queries_end as $addon_query_end){
	mysqli_query(Config::$db_connection, $addon_query_end) or die("[".$addon_query_end."] ".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection));
	if(Config::$print_queries){
		echo($addon_query_end.Config::$line_break_tag);
	}
}

if(Config::$addon_function_end != null){
	$func = Config::$addon_function_end;
	$func();
}

if($insert){
	mysqli_commit(Config::$db_connection);
	echo("#Insertions commited".Config::$line_break_tag
		."#*************************".Config::$line_break_tag
		."#********VictWare*********".Config::$line_break_tag
		."#* Integration completed *".Config::$line_break_tag
		."#*************************".Config::$line_break_tag);
}else{
	mysqli_rollback(Config::$db_connection);
	echo "#~~~~~~~~~~~~~~Fail~~~~~~~~~~~~~~~~~~~~~~~".Config::$line_break_tag
		."#~ Insertions cancelled due to error(s). ~".Config::$line_break_tag
		."#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~".Config::$line_break_tag;
}


/**
 * Takes an array of field and returns a string contaning those who have a non empty string value
 * and adds the default created, created_by, modified, modified_by fields at the end
 * @param array $fields 
 * @param boolean $is_details_table 
 * @return string
 */
function buildInsertQuery(array $fields, $is_details_table = false){
	$result = "";
	$field_separator = '';
	foreach($fields as $field => $value){
		if(is_array($value) || strlen($value) > 0){
			$result .= $field_separator.$field;
			$field_separator = ", ";
		}
	}
	
	return $result.($is_details_table? "" : $field_separator." created, created_by, modified, modified_by");
}

/**
 * Takes the fields array and the values array in order to build the values part of the query.
 * The value fields starting with @ will be put directly into the query without beign replaced (minus the first @)
 * @param Model $model The model to build the values query on
 * @param array $fields The fields key to use
 * @param array $schema The schema into which the insert will occur
 * @param boolean $is_details_table 
 * @return string
 */
function buildValuesQuery(Model $model, array $fields, array $schema, $is_details_table = false){
	$result = "";
	$field_separator = '';
	foreach($fields as $field => $value){
		if(is_array($value)){
			$possible_values = current($value);
			$tmp = trim($model->values[key($value)]);
			if(is_a($possible_values, 'ValueDomain')){
				if(($val = $possible_values->isValidValue($tmp)) === null){
					echo "WARNING: value [",$tmp,"] is unmatched for ValueDomain field [",$field,"] in file [",$model->file,"] at line [".$model->line."]".Config::$line_break_tag;
					$val = "";
				}
				$result .= $field_separator."'".str_replace("'", "\\'", $val)."'";
			}else if(isset($possible_values[$tmp])){
				$result .= $field_separator."'".str_replace("'", "\\'", $possible_values[$tmp])."'";
			}else{
				$result .= $field_separator."''";
				echo "WARNING: value [",$tmp,"] is unmatched for field [",$field,"] in file [",$model->file,"] at line [".$model->line."]".Config::$line_break_tag;
			}
		}else if(strpos($value, "@") === 0){
			$result .= $field_separator."'".str_replace("'", "\\'", substr($value, 1))."'";
		}else if(strpos($value, "#") === 0){
			$tmp = substr($value, 1);
			if(array_key_exists($tmp, $model->values)){
				if($model->values[$tmp] == null){
					$result .= $field_separator.'NULL';
				}else{
					$result .= $field_separator."'".str_replace("'", "\\'", $model->values[$tmp])."'";
				}
			}else{
				$result .= $field_separator."''";
				echo "WARNING: custom value [",$tmp,"] is unmatched for field [",$field,"] in file [",$model->file,"] at line [".$model->line."]".Config::$line_break_tag;
			}	
		}else if(strlen($value) > 0){
			if(strlen($model->values[$value]) > 0){
				$result .= $field_separator."'".str_replace("'", "\\'", $model->values[$value])."'";
			}else if(isDbNumericType($schema[$field]['type']) && $schema[$field]){
				$result .= $field_separator."NULL";
			}else{
				$result .= $field_separator."''";
			}
		}
		$field_separator = ", ";
	}
	return $result.($is_details_table? "" : $field_separator."NOW(), ".Config::$db_created_id.", NOW(), ".Config::$db_created_id);	
}

/**
 * Takes the fields array and the values array in order to build the values part of the query.
 * The value fields starting with @ will be put directly into the query without beign replaced (minus the first @)
 * @param Model $model The model to build the values query on
 * @param array $fields The fields key to use
 * @param array $schema The schema into which the insert will occur
 * @param boolean $is_details_table 
 * @return string
 */
function formatQueryForRevs($sql_statement){
	$sql_statement = str_replace(" created, created_by, modified, modified_by", " modified_by", $sql_statement);
	$sql_statement = str_replace("NOW(), ".Config::$db_created_id.", NOW(), ".Config::$db_created_id, Config::$db_created_id, $sql_statement);
	return $sql_statement;
}

function isDbNumericType($field_type){
	return strpos($field_type, "int(") === 0
		|| strpos($field_type, "float") === 0
		|| strpos($field_type, "decimal(") === 0
		|| strpos($field_type, "tinyint(") === 0
		|| strpos($field_type, "smallint(") === 0
		|| strpos($field_type, "mediumint(") === 0
		|| strpos($field_type, "double") === 0;
}


/**
 * Inserts a given table data into the database. For each row, there is a verification to see if children exist to call this
 * function recursively
 * @param unknown_type $ref_name The name of the table to work on
 * @param Model $parent_model The parent model
 */
//table_name -> ref_name
//table -> Config::$models
function insertTable($ref_name, $parent_model = null){
	global $insert;
	if(!isset(Config::$models[$ref_name])){
		$insert = false;
		echo "ERROR: model [".$ref_name."] not found".Config::$line_break_tag;
		return ;
	}
	$current_model = &Config::$models[$ref_name];
	$current_model->parent_model = $parent_model;
	
	if($current_model->first_read){
		$current_model->first_read = false;
		readFileLine($current_model);
	}
	
	$i = 0;
	$insert_condition_function = $current_model->insert_condition_function;

	//debug info
// 	echo($ref_name.Config::$line_break_tag);
	
// 	if($ref_name == "qc_tf_dxd_progression_site_ca125"){
// 		echo $current_model->line;
// 		print_r($current_model->values);
// 		echo "Current parent csv ref: ", $current_model->values[$current_model->parent_csv_key],Config::$line_break_tag;
// 		echo "Current parent csv key: ", $current_model->parent_model->csv_key_value,Config::$line_break_tag;
		
// 		if(!empty($current_model->values) &&
// 			($current_model->parent_model == null || $current_model->values[$current_model->parent_csv_key] == $current_model->parent_model->csv_key_value)
// 			&& ($insert_condition_function == null || $insert_condition_function($current_model))
// 		){
// 			echo "WILL GO IN";
// 		}else{
// 			echo "WONT GO IN";
// 		}
// 		exit;
// 	}
	
	while(!empty($current_model->values) && 
		($current_model->parent_model == null || $current_model->values[$current_model->parent_csv_key] == $current_model->parent_model->csv_key_value)
		&& ($insert_condition_function == null || $insert_condition_function($current_model))
	){
		//replace parent value.
		if($parent_model != null && $current_model->parent_sql_key != null){
			$current_model->values[$current_model->fields[$current_model->parent_sql_key]] = $parent_model->last_id;
		}
		
		//master main
		$queryValues = buildValuesQuery($current_model, $current_model->fields, $current_model->schema);
		$query = $current_model->query_insert.$queryValues.")";
	
		mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".Config::$line_break_tag);
		$current_model->last_id = mysqli_insert_id(Config::$db_connection);
		if(Config::$print_queries){
			echo $query.";".Config::$line_break_tag;
		}
		
		if(Config::$insert_revs){
			//master revs
			$query = formatQueryForRevs($current_model->query_insert_revs.$queryValues.", '".$current_model->last_id."')");
			mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."_revs][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);
			if(Config::$print_queries){
				echo $query.";".Config::$line_break_tag;
			}
		}
		if(is_a($current_model, "MasterDetailModel")){
			//detail level
			if(is_array($current_model->query_detail_insert)){
				//insert into multi detail tables
				foreach($current_model->query_detail_insert as $key => $value){
					//detail main
					$current_model->detail_fields[$key][$current_model->detail_master_fkey] = "@".$current_model->last_id;
					echo $current_model->detail_master_fkey,Config::$line_break_tag;
					
					$queryValues = buildValuesQuery($current_model, $current_model->detail_fields[$key], $current_model->detail_schema, true);
					$query = $current_model->query_detail_insert[$key].$queryValues.")";
					mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);
					$last_detail_id = mysqli_insert_id(Config::$db_connection);
					if(Config::$print_queries){
						echo $query.";".Config::$line_break_tag;
					}
					
					if(Config::$insert_revs){
						//detail revs
						$query = formatQueryForRevs($current_model->query_detail_insert_revs[$key].$queryValues.")");
						mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."_revs][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);
						if(Config::$print_queries){
							echo $query.";".Config::$line_break_tag;
						}
					}
				}
			}else{
				
				//insert insto single detail table
				//detail main
				$current_model->detail_fields[$current_model->detail_master_fkey] = "@".$current_model->last_id;
				$queryValues = buildValuesQuery($current_model, $current_model->detail_fields, $current_model->detail_schema, true);
				$query = $current_model->query_detail_insert.$queryValues.")";
				mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);
				$last_detail_id = mysqli_insert_id(Config::$db_connection);
				if(Config::$print_queries){
					echo $query.";".Config::$line_break_tag;
				}
				
				if(Config::$insert_revs){
					//detail revs
					$query = formatQueryForRevs($current_model->query_detail_insert_revs.$queryValues.")");
					mysqli_query(Config::$db_connection, $query) or die("query failed[".$ref_name."][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);
					if(Config::$print_queries){
						echo $query.";".Config::$line_break_tag;
					}
				}
				
			}
		}

		if($current_model->post_write_function != NULL){
			$func = $current_model->post_write_function;
			$func($current_model);
		}
		
		//saving id if required
		if($current_model->save_id){
			$query = "INSERT INTO id_linking (csv_id, csv_reference, mysql_id, model) VALUES('"
					.$current_model->values[$current_model->csv_pkey]."', "
					."'".$ref_name."', " 
					.$current_model->last_id.", '"
					.$current_model->table."')";
			if(Config::$print_queries){
				echo $query.";".Config::$line_break_tag;
			}
			mysqli_query(Config::$db_connection, $query) or die("tmp id query failed[".$ref_name."][".$query."][".mysqli_errno(Config::$db_connection) . ": " . mysqli_error(Config::$db_connection)."]".print_r($current_model->values).Config::$line_break_tag);	
		}
		
		
		if(is_array($current_model->child)){
			//treat child
			foreach($current_model->child as $child_model_ref){
				insertTable($child_model_ref, $current_model);
			}
		}
		flush();
		readFileLine($current_model);
	}
}

function readFileLine(&$current_model, $do_post_read = true){
	$end_of_file_eval = NULL;
	if(IS_XLS){
		$end_of_file_eval = 'return $current_model->line > $current_model->file_handler["numRows"];';
	}else if(feof($current_model->file_handler)){
		$end_of_file_eval = 'return feof($current_model->file_handler);';
	}
	if(eval($end_of_file_eval)){
		$current_model->values = array();
	}else{
		$func = $current_model->post_read_function;
		do{
			//read line, skip empty lines
			$current_model->line ++;
			if(IS_XLS){
				$current_model->values = isset($current_model->file_handler['cells'][$current_model->line]) ? $current_model->file_handler['cells'][$current_model->line] : array();
			}else{
				$line = fgets($current_model->file_handler, 4096);
				$current_model->values = lineToArray($line);
			}
			associate($current_model->keys, $current_model->values);
			$current_model->csv_key_value = $current_model->values[$current_model->csv_pkey];
			
			if(eval($end_of_file_eval)){
				break;
			}
			$go_on = sizeof($current_model->values) <= (sizeof($current_model->keys) + 1) ||
				(strlen($current_model->csv_pkey) > 0 && strlen($current_model->values[$current_model->csv_pkey]) == 0);
			if($do_post_read && !$go_on && $func != null){
				$go_on = !$func($current_model);
			}
		}while($go_on);
		
		if(eval($end_of_file_eval)){
			$current_model->values = array();
		}
	}
}
