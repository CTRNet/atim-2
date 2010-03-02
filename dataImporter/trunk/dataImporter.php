<?php
//TODO: Die if a file pkey column doesnt exist
require_once("commonFunctions.php");
require_once("config.php");
require_once("dataImporter.php");
//require_once("tables_mapping/collections.php");
require_once("tables_mapping/consents.php");
require_once("tables_mapping/identifiers.php");
require_once("tables_mapping/participants.php");
//require_once("tables_mapping/sd_der_pbmcs.php");
//require_once("tables_mapping/sd_der_plasmas.php");
//require_once("tables_mapping/sd_der_serums.php");
//require_once("tables_mapping/sd_spe_bloods.php");
//require_once("tables_mapping/sd_spe_tissues.php");
//require_once("tables_mapping/tubes/ad_tubes_plasma.php");
//require_once("tables_mapping/tubes/ad_tubes_pbmc.php");
//require_once("tables_mapping/tubes/ad_tubes_serum.php");

//validate each file exists and prep them
foreach($tables as $ref_name => &$table){
	if(strlen($table['app_data']['file']) > 0){
		if(!is_file($table['app_data']['file'])){
			die("File for [".$ref_name."] does not exist. [".$table['app_data']['file']."]\n");
		}
		$table['app_data']['file_handler'] = fopen($table['app_data']['file'], 'r');
		if(!$table['app_data']['file_handler']){
			die("fopen failed on ".$ref_name);
		}
		$table['app_data']['query_master_insert'] = "INSERT INTO ".$table["app_data"]['master_table_name']." (".buildInsertQuery($tables[$ref_name]['master']).") VALUES(";
		if(isset($table["app_data"]['detail_table_name'])){
			$table['app_data']['query_detail_insert'] = "INSERT INTO ".$table["app_data"]['detail_table_name']." (".buildInsertQuery($tables[$ref_name]['detail']).") VALUES(";
		}
		$table['app_data']['keys'] = lineToArray(fgets($table['app_data']['file_handler'], 4096));
		readLine($table);
	}
}
unset($table);//weird bug otherwise

//init database connection
global $connection;
$connection = @mysqli_connect($database['ip'].":".$database['port'], $database['user'], $database['pwd']) or die("Could not connect to MySQL");
if(!mysqli_set_charset($connection, $database['charset'])){
	die("Invalid charset");
}
@mysqli_select_db($connection, $database['schema']) or die("db selection failed");
mysqli_autocommit($connection, false);

//create the temporary id linking table
mysqli_query($connection, "DROP TABLE IF EXISTS id_linking ") or die("DROP tmp failed");
$query = "CREATE TABLE id_linking(
	csv_id varchar(10) not null,
	mysql_id int unsigned not null, 
	model varchar(15) not null
	)Engine=InnoDB";
mysqli_query($connection, $query) or die("temporary table query failed[".mysqli_errno($connection) . ": " . mysqli_error($connection)."]\n");

//define the primary tables (collection links is considered to be a special table)
$primary_tables = array("participants" => $participants,
						"collections" => $collections);

//iteratover the primary tables who will, in turn, iterate over their children
foreach($primary_tables as $table_name => $table){
	insertTable($table_name, $tables);
}

//TODO: treat special tables such as collection links

//validate that each file handler has reached the end of it's file so that no data is left behind
$insert = true;
foreach($tables as $ref_name => &$table){
	if(strlen($table['app_data']['file']) > 0){
		if(!feof($table['app_data']['file_handler'])){
			echo("ERROR: Data was not all fetched from [".$ref_name."]\n");
			$insert = false;
		}
	}
}

if($insert){
	mysqli_commit($connection);
	echo("Insertions commited\n");
	echo("*************************\n"
		."********VictWare*********\n"
		."* Integration completed *\n"
		."*************************\n");
}else{
	echo("Insertions cancelled\n");
}


/**
 * Takes an array of field and returns a string contaning those who have a non empty string value
 * and adds the default created, created_by, modified, modified_by fields at the end
 * @param array $fields 
 * @return string
 */
function buildInsertQuery($fields){
	$result = "";
	foreach($fields as $field => $value){
		if($field != "app_data" && strlen($value) > 0){
			$result .= $field.", ";
		}
	}
	
	return $result."created, created_by, modified, modified_by";
}

/**
 * Takes the fields array and the values array in order to build the values part of the query.
 * The value fields starting with @Êwill be put directly into the query without beign replaced (minus the first @)
 * @param unknown_type $fields The array of the fields configuration
 * @param unknown_type $values The array of values read from the csv
 * @return string
 */
function buildValuesQuery($fields, $values){
	global $created_id;
	$result = "";
	foreach($fields as $field => $value){
		if(strpos($value, "@") === 0){
			$result .= "'".substr($value, 1)."', ";
		}else if(strlen($value) > 0){
			$result .= "'".str_replace("'", "\\'", $values[$value])."', ";
		}
	}
	return $result."NOW(), ".$created_id.", NOW(), ".$created_id;	
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
	$current_table = &$tables[$table_name];
	$i = 0;
	//debug info
//	if($table_name == "ad_tubes_pbmc"){
//		echo("Size: ".sizeof($current_table['app_data']['values'])."\n");
//		echo($current_table['app_data']['values'][$current_table['master'][$current_table['app_data']['parent_key']]]."  -  ".$csv_parent_key."\n");
//		print_r($current_table['app_data']['values']);
//		echo($current_table['app_data']['values'][$current_table['master'][$current_table['app_data']['parent_key']]]."\n");
//		exit;
//	}
	while(sizeof($current_table['app_data']['values']) > 0 && 
		($csv_parent_key == null || $current_table['app_data']['values'][$current_table['master'][$current_table['app_data']['parent_key']]] == $csv_parent_key)){
		//replace parent value.
		if($mysql_parent_id != null){
			$current_table['app_data']['key before replace'] = $current_table['app_data']['values'][$current_table['master'][$current_table['app_data']['parent_key']]];
			$current_table['app_data']['values'][$current_table['master'][$current_table['app_data']['parent_key']]] = $mysql_parent_id;
		}
		if(isset($parent_data)){
			//put answers in place
			foreach($parent_data as $question => $answer){
				$current_table['app_data']['values'][$question] = $answer;
			}
		}
		
		$query = $current_table['app_data']['query_master_insert'].buildValuesQuery($current_table["master"], $current_table['app_data']['values']).")";
		mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
		$last_id = mysqli_insert_id($connection);
		$last_detail_id = 0;
		echo $query.";\n";
		if(isset($current_table['app_data']['query_detail_insert'])){
			//detail level
			$current_table['detail'][$current_table['app_data']['detail_parent_key']] = "@".$last_id;
			$query = $current_table['app_data']['query_detail_insert'].buildValuesQuery($current_table["detail"], $current_table['app_data']['values']).")";
			mysqli_query($connection, $query) or die("query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
			$last_detail_id = mysqli_insert_id($connection);
			echo $query.";\n";
		}
		
		
		//treat additional querries
		if(isset($current_table["app_data"]['additional_queries'])){
			foreach($current_table["app_data"]['additional_queries'] as $ad_query){
				$ad_query = str_replace("%%last_master_insert_id%%", $last_id, str_replace("%%last_detail_insert_id%%", $last_detail_id, $ad_query));
				mysqli_query($connection, $ad_query) or die("ad query failed[".$table_name."][".$ad_query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");
				echo $ad_query.";\n";
			}
		}
		
		//saving id if required
		if(isset($current_table['app_data']['save_id']) && $current_table['app_data']['save_id']){
			$query = "INSERT INTO id_linking (csv_id, mysql_id, model) VALUES('"
					.$current_table['app_data']['values'][$current_table['app_data']['pkey']]."', "
					.$last_id.", '".$table_name."')";
			//echo $query.";\n";
			mysqli_query($connection, $query) or die("tmp id query failed[".$table_name."][".$query."][".mysqli_errno($connection) . ": " . mysqli_error($connection)."]".print_r($current_table)."\n");	
		}
		
		//treat child
		foreach($current_table["app_data"]['child'] as $child_table_name){
			$child_required_data = array();
			if(isset($tables[$child_table_name]['app_data']['ask_parent'])){
				foreach($tables[$child_table_name]['app_data']['ask_parent'] as $question => $where_to_answer){
//					echo("child asking for: ".$question."\n");
					if($question == "id"){
						$child_required_data[$where_to_answer] = $last_id;
					}else{
						$child_required_data[$where_to_answer] = $current_table['app_data']['values'][$current_table['master'][$question]];
					}
				}
			}
//			if($child_table_name == "ad_tubes_plasma"){
//				print_r($current_table['app_data']);
//				echo($current_table['app_data']['key before replace']."\n");
//				exit;
//			}
			insertTable($child_table_name, 
							$tables, 
							$current_table['app_data']['values'][$current_table["app_data"]["pkey"]], 
							$last_id, 
							$child_required_data);
		}
		flush();
		readLine($current_table);
//		++ $i;
//		if($i == 3){
//			exit;
//		}
	}
}

function readLine(&$current_table){
	if(feof($current_table['app_data']['file_handler'])){
		$current_table['app_data']['values'] = array();
	}else{
		do{
			//read line, skip empty lines
			$line = fgets($current_table['app_data']['file_handler'], 4096);
			//				echo($line."\n");
			$current_table['app_data']['values'] = lineToArray($line);
			associate($current_table['app_data']['keys'], $current_table['app_data']['values']);
		}while(!feof($current_table['app_data']['file_handler']) && (sizeof($current_table['app_data']['values']) <= (sizeof($current_table['app_data']['keys']) + 1) ||
		(strlen($current_table["app_data"]["pkey"]) > 0 && strlen($current_table['app_data']['values'][$current_table["app_data"]["pkey"]]) == 0)));
		
		if(feof($current_table['app_data']['file_handler'])){
			$current_table['app_data']['values'] = array();
		}
	}
}