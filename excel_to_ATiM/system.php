<?php

set_time_limit('3600');

//-- Initiate config file variables ------------------------------------------------------------------------------------------------------------------------------------------------

global $migration_process_version;
$migration_process_version = 'v0.1';

$db_ip			= "";
$db_port 		= "";
$db_user 		= "";
$db_pwd			= "";
$db_schema		= "";
$db_charset		= "";

$migration_user_id = null;

global $excel_files_paths;
$excel_files_paths = "";
global $excel_files_names;
$excel_files_names = array();

// Serial number of 1 January 2001 in Excel: 36526 (Windows) / 35064 (MAC)
$windows_xls_offset = 36526;
$mac_xls_offset = 35064;

//==================================================================================================================================================================================
// SYSTEM REQUIRE SECTION
//==================================================================================================================================================================================

require_once 'config.php';
require_once 'Excel/reader.php';

//==================================================================================================================================================================================
// DATABSE CONNECTION
//==================================================================================================================================================================================

global $db_connection;
$db_connection = @mysqli_connect(
		$db_ip.(!empty($db_port)? ":".$db_port : ''),
		$db_user,
		$db_pwd
) or migrationDie("ERR_DATABASE_CONNECTION: Could not connect to MySQL");
if(!mysqli_set_charset($db_connection, $db_charset)){
	die("ERR_DATABASE_CONNECTION: Invalid charset");
}
@mysqli_select_db($db_connection, $db_schema) or migrationDie("ERR_DATABASE_CONNECTION: Unable to connect to $db_schema for user $db_user");
mysqli_autocommit($db_connection, false);

//==================================================================================================================================================================================
// SYSTEM VARIABLES
//==================================================================================================================================================================================

global $import_date;
global $imported_by;
$query_result = getSelectQueryResult("SELECT NOW() AS import_date, id FROM users WHERE id = '$migration_user_id';");
if(empty($query_result)) {
	migrationDie("ERR_DATABASE_CONNECTION: Migration user id '$migration_user_id' does not exist into $db_schema");
}
$import_date = $query_result[0]['import_date'];
$imported_by = $query_result[0]['id'];

global $atim_controls;
$atim_controls = array();
//*** Control : sample_controls ***
$atim_controls['sample_controls'] = array();
$query_result = getSelectQueryResult("SELECT parent_sample_control_id, derivative_sample_control_id FROM parent_to_derivative_sample_controls WHERE flag_active = 1");
foreach($query_result as $unit){
	$key = $unit["parent_sample_control_id"];
	$value = $unit["derivative_sample_control_id"];
	if(!isset($relations[$key])){
		$relations[$key] = array();
	}
	$relations[$key][] = $value;
}
$active_sample_control_ids = getActiveSampleControlIds($relations, "");
function getActiveSampleControlIds($relations, $current_check){
	$active_ids = array('-1');	//If no sample
	if(array_key_exists($current_check, $relations)) {
		foreach($relations[$current_check] as $sample_id){
			if($current_check != $sample_id && $sample_id != 'already_parsed'){
				$active_ids[] = $sample_id;
				if(isset($relations[$sample_id]) && !in_array('already_parsed', $relations[$sample_id])){
					$relations[$sample_id][] = 'already_parsed';
					$active_ids = array_merge($active_ids, getActiveSampleControlIds($relations, $sample_id));
				}
			}
		}
	}
	return array_unique($active_ids);
}
$ids_to_types = array();
foreach(getSelectQueryResult("SELECT id, sample_type, sample_category, detail_tablename FROM sample_controls WHERE id IN (".implode(',', $active_sample_control_ids).")") as $new_sample_control) {
	$atim_controls['sample_controls'][$new_sample_control['sample_type']] = $new_sample_control;
	$ids_to_types[$new_sample_control['id']] = $new_sample_control['sample_type'];
}
$atim_controls['sample_controls']['***id_to_type***'] = $ids_to_types;

//*** Control : aliquot_controls ***
$atim_controls['aliquot_controls'] = array();
$query = "SELECT ac.id, sample_type, aliquot_type, ac.detail_tablename, volume_unit 
	FROM aliquot_controls ac INNER JOIN sample_controls sc ON sc.id = ac.sample_control_id 
	WHERE ac.flag_active = '1' AND ac.sample_control_id IN (".implode(',', $active_sample_control_ids).")";
foreach(getSelectQueryResult($query) as $new_aliquot_control) $atim_controls['aliquot_controls'][$new_aliquot_control['sample_type'].'-'.$new_aliquot_control['aliquot_type']] = $new_aliquot_control;
//*** Control : consent_controls ***
$atim_controls['consent_controls'] = array();
foreach(getSelectQueryResult("SELECT id, controls_type, detail_tablename FROM consent_controls WHERE flag_active = 1") as $new_control) $atim_controls['consent_controls'][$new_control['controls_type']] = $new_control;
//*** Control : event_controls ***
$atim_controls['event_controls'] = array();
foreach(getSelectQueryResult("SELECT id, disease_site, event_type, detail_tablename FROM event_controls WHERE flag_active = 1") as $new_control) $atim_controls['event_controls'][(strlen($new_control['disease_site'])? $new_control['disease_site'].'-': '').$new_control['event_type']] = $new_control;
//*** Control : diagnosis_controls ***
$atim_controls['diagnosis_controls'] = array();
$primary_control_ids = array();
foreach(getSelectQueryResult("SELECT id, category, controls_type, detail_tablename FROM diagnosis_controls WHERE flag_active = 1") as $new_control) {
	$atim_controls['diagnosis_controls'][$new_control['category'].'-'.$new_control['controls_type']] = $new_control;
	if($new_control['category'] == 'primary') $primary_control_ids[] = $new_control['id']; 
}
$atim_controls['diagnosis_controls']['***primary_control_ids***'] = $primary_control_ids;
//*** Control : misc_identifier_controls ***
$atim_controls['misc_identifier_controls'] = array();
foreach(getSelectQueryResult("SELECT id, misc_identifier_name, flag_active, autoincrement_name, misc_identifier_format, flag_once_per_participant, flag_unique FROM misc_identifier_controls WHERE flag_active = 1") as $new_control) $atim_controls['misc_identifier_controls'][$new_control['misc_identifier_name']] = $new_control;
//*** Control : sop_controls ***
$atim_controls['sop_controls'] = array();
foreach(getSelectQueryResult("SELECT id, sop_group, type, detail_tablename, extend_tablename, extend_form_alias FROM sop_controls WHERE flag_active = 1") as $new_control) $atim_controls['sop_controls'][$new_control['sop_group'].'-'.$new_control['type']] = $new_control;
//*** Control : storage_controls ***
$atim_controls['storage_controls'] = array();
foreach(getSelectQueryResult("SELECT id, storage_type, coord_x_title, coord_x_type, coord_x_size, coord_y_title, coord_y_type, coord_y_size, display_x_size, display_y_size , set_temperature, is_tma_block, detail_tablename FROM storage_controls WHERE flag_active = 1") as $new_control) $atim_controls['storage_controls'][$new_control['storage_type']] = $new_control;
//*** Control : protocol_controls ***
$atim_controls['protocol_controls'] = array();
$query  = "SELECT pc.id, tumour_group, type, pc.detail_tablename, pec.id AS protocol_extend_control_id, pec.detail_tablename AS protocol_extend_detail_tablename
	FROM protocol_controls pc LEFT JOIN protocol_extend_controls pec ON pec.id = pc.protocol_extend_control_id AND pec.flag_active = 1
	WHERE pc.flag_active = 1";
foreach(getSelectQueryResult($query) as $new_control) $atim_controls['protocol_controls'][$new_control['tumour_group'].'-'.$new_control['type']] = $new_control;
//*** Control : treatment_controls ***
$atim_controls['treatment_controls'] = array();
$query  = "SELECT tc.id, disease_site, tx_method, tc.detail_tablename, applied_protocol_control_id, tec.id AS treatment_extend_control_id, tec.detail_tablename AS treatment_extend_detail_tablename
	FROM treatment_controls tc LEFT JOIN treatment_extend_controls tec ON tc.treatment_extend_control_id = tec.id AND tec.flag_active = 1
	WHERE tc.flag_active = 1";
foreach(getSelectQueryResult($query) as $new_control) $atim_controls['treatment_controls'][(strlen($new_control['disease_site'])?$new_control['disease_site'].'-':'').$new_control['tx_method']] = $new_control;
//*** Control : specimen_review_controls ***
$atim_controls['specimen_review_controls'] = array();
$query  = "SELECT src.id, src.review_type, sample_control_id, src.detail_tablename, arc.id AS aliquot_review_control_id, arc.review_type AS aliquot_review_type, arc.detail_tablename AS aliquot_review_detail_tablename, aliquot_type_restriction
	FROM specimen_review_controls src LEFT JOIN aliquot_review_controls arc ON src.aliquot_review_control_id = arc.id AND arc.flag_active = 1
	WHERE src.flag_active = 1";
foreach(getSelectQueryResult($query) as $new_control) $atim_controls['specimen_review_controls'][$new_control['review_type']] = $new_control;
ksort($atim_controls);

global $XlsReader;
$XlsReader = null;
global $studied_excel_file_name_properties;
$studied_excel_file_name_properties = null;

global $modified_database_tables_list;
$modified_database_tables_list = array();

//==================================================================================================================================================================================
// SYSTEM FUNCTION
//==================================================================================================================================================================================

function migrationDie($error_messages) {
	if(is_array($error_messages)) {
		foreach($error_messages as $msg) pr($msg);
	} else {
		pr($error_messages);
	}
	pr('-------------------------------------------------------------------------------------------------------------------------------------');
	$counter = 0;
	foreach(debug_backtrace() as $debug_data) {
		$counter++;
		pr("$counter- Function ".$debug_data['function']."() [File: ".$debug_data['file']." - Line: ".$debug_data['line']."]");
	}
	die('Please contact your administrator');	
}

function pr($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

// ---- TITLE, ERROR AND MESSAGE DISPLAY  ------------------------------------------------------------------------------------------------------------------------------------------

global $import_summary;
$import_summary = array();

/**
 * Display the title of the migration (html format).
 * 
 * @param string $title Title of the migration process
 * @param array $display_file_names List of files names
 */
function displayMigrationTitle($title, $display_file_names = false) {
	global $import_date;
	global $migration_process_version;
	global $excel_files_paths;
	echo "<br><FONT COLOR=\"blue\">=====================================================================<br>
		<b>ATiM DATA MIGRATION PROCESS</b></FONT><br>
		<b><FONT COLOR=\"blue\">Processus : </FONT>$title</b><br>
		<b><FONT COLOR=\"blue\">Version : </FONT>$migration_process_version</b><br>
		<b><FONT COLOR=\"blue\">Date : </FONT>$import_date</b><br>
		<FONT COLOR=\"blue\">=====================================================================</FONT><br>";
	if($display_file_names && !empty($excel_files_paths)) {
		foreach($display_file_names as $file_name) {
			echo "<FONT COLOR=\"blue\" >File : </FONT>".$file_name."<br>";		
		}
		echo "<FONT COLOR=\"blue\" >=====================================================================</FONT><br>";
	}
}

/**
 * Record an error or message that could be dispalyed in html format using function dislayErrorAndMessage().
 * The format of the display will be similar than the example below
 * 
 * =====================================================================
 * $summary_section_title
 * =====================================================================
 * 
 * $summary_title
 * - $summary_details 1
 * - $summary_details 2
 * - $summary_details 3
 * - $summary_details ...
 * 
 * @param string $summary_section_title Title of a section gathering error or message linked to the same task, data type, etc.
 * @param string $summary_type @@ERROR@@ or @@WARNING@@ or @@MESSAGE@@
 * @param string $summary_title Error type
 * @param string $summary_details Details of the error (to inlude excel line number or a patient identifier, etc)
 * @param string $message_detail_key Key to not duplicate summary details for a same error.
 */
function recordErrorAndMessage($summary_section_title, $summary_type, $summary_title, $summary_details, $message_detail_key = null) {
	global $import_summary;
	if(is_null($message_detail_key)) {
		$import_summary[$summary_section_title][$summary_type][$summary_title][] = $summary_details;
	} else {
		$import_summary[$summary_section_title][$summary_type][$summary_title][$message_detail_key] = $summary_details;
	}
}

/**
 * Display any errors or messages recorded by function recordErrorAndMessage() in html forma.
 * 
 * @param boolean $commit Commit all sql statements.
 */
function dislayErrorAndMessage($commit = false, $title = 'Migration Summary') {
	global $import_summary;
	global $db_connection;
	echo "<br><FONT COLOR=\"blue\">
		=====================================================================<br>
		<b>$title</b><br>
		=====================================================================</FONT><br>";
	$err_counter = 0;
	foreach($import_summary as $summary_section_title => $data1) {
		echo "<br><br><FONT COLOR=\"0066FF\" >
		=====================================================================<br>
		$summary_section_title<br>
		=====================================================================</FONT><br>";
		foreach($data1 as $message_type => $data2) {
			$color = 'black';
			$code = 'ER';
			switch($message_type) {
				case '@@ERROR@@':
					$color = 'red';
					$code = 'ER';
					break;
				case '@@WARNING@@':
					$color = 'orange';
					$code = 'WAR';
					break;
				case '@@MESSAGE@@':
					$color = 'green';
					$code = 'MSG';
					break;
				default:
					echo '<br><br><br>UNSUPORTED message_type : '.$message_type.'<br><br><br>';
			}
			foreach($data2 as $error => $details) {
				$err_counter++;
				$error = str_replace("\n", ' ', utf8_decode("[$code#$err_counter] $error"));
				echo "<br><br><FONT COLOR=\"$color\" ><b>$error</b></FONT><br>";
				foreach($details as $detail) {
					$detail = str_replace("\n", ' ', $detail);
					echo ' - '.utf8_decode($detail)."<br>";
				}
			}
		}
	}
	if($commit) {
		$query = "UPDATE versions SET permissions_regenerated = 0;";
		customQuery($query);
		mysqli_commit($db_connection);
		$ccl = '& Commited';
	} else {
		$ccl = 'But Not Commited';
	}
	echo "<br><FONT COLOR=\"blue\">
		=====================================================================<br>
		<b>$title Done $ccl</b><br>
		=====================================================================</FONT><br>";
}

// ---- QUERY FUNCTIONS ------------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Exeute an sql statement.
 * 
 * @param string $query SQL statement
 * @param boolean $insert True for any $query being an INSERT statement
 * 
 * @return multitype Id of the insert when $insert set to TRUE else the mysqli_result object
 */
function customQuery($query, $insert = false) {
	global $db_connection;
	$query_res = mysqli_query($db_connection, $query) or migrationDie(array("ERR_QUERY", mysqli_error($db_connection), $query));
	return ($insert)? mysqli_insert_id($db_connection) : $query_res;
}

/**
 * Execute an sql SELECT statement and return results into an array.
 * 
 * @param string $query SQL statement
 * 
 * @return array Query results in an array
 */
function getSelectQueryResult($query) {
	if(!preg_match('/^[\ ]*SELECT/i', $query))  migrationDie(array("ERR_QUERY", "'SELECT' query expected", $query));
	$select_result = array();
	$query_result = customQuery($query);
	while($row = $query_result->fetch_assoc()) {
		$select_result[] = $row;
	}
	return $select_result;
}

/**
 * Record new data into ATiM table.
 * 
 * Submitted data format:
 * 
 * 1- Simple Table
 * 
 * array(table_name => array(field1 => value, field2 => value, etc)
 * 
 * array('participants' => array('first_name' => 'James', 'last_name' => 'Bond))
 * 
 * 2- Master/Detail Tables
 * 
 * array(master_table_name => array(master_control_id => value, field1 => value, field2 => value, etc)
 * 		detail_table_name => array(field1 => value, field2 => value, etc))
 * 
 * array('aliquot_masters' => array(aliquot_control_id => 12, field1 => value, field2 => value, etc)
 * 		'ad_tubes' => array(field1 => value, field2 => value, etc))
 * 
 * 3- Sample Master/Detail Tables
 * 
 * array(master_table_name => array(master_control_id => value, field1 => value, field2 => value, etc)
 * 		specimen_details/derivativ_details => array(field1 => value, field2 => value, etc)
 * 		detail_table_name => array(field1 => value, field2 => value, etc))
 * 
 * @param array $tables_data Data to insert. See format above.
 * 
 * @return string Id of the created record
 */
function customInsertRecord($tables_data) {
	global $import_date;
	global $imported_by;
	global $atim_controls;
	$record_id = null;
	$main_table_data = array();
	$details_tables_data = array();
//TODO: Add control on detail table based on _control_id
	if($tables_data) {
		$tables_data_keys = array_keys($tables_data);
		//Flush empty field
		foreach($tables_data as $table_name => $table_fields_and_data) {
			foreach($table_fields_and_data as $field => $data) {
				if(!strlen($data)) unset($tables_data[$table_name][$field]);
			}
		}
		//--1-- Check data
		switch(sizeof($tables_data)) {
			case '1':
				$tmp_tables_data_keys = $tables_data_keys;
				$table_name = array_shift($tmp_tables_data_keys);
				if(preg_match('/_masters$/', $table_name)) migrationDie("ERR_FUNCTION_customInsertRecord(): Detail table is missing to record data into $table_name");
				$main_table_data = array('name' => $table_name, 'data' => $tables_data[$table_name]);
				break;
			case '3':
				$details_table_name = '';
				foreach($tables_data_keys as $table_name) {
					if(in_array($table_name, array('specimen_details', 'derivative_details'))) {
						$details_tables_data[] = array('name' => $table_name, 'data' => $tables_data[$table_name]);
						unset($tables_data[$table_name]);
					} else if($table_name == 'sample_masters') {
						$main_table_data = array('name' => $table_name, 'data' => $tables_data[$table_name]);
						unset($tables_data[$table_name]);
					} else {
						$details_table_name = $table_name;
					}
				}
				if(empty($main_table_data)) migrationDie("ERR_FUNCTION_customInsertRecord(): Table sample_masters is missing (See table names: ".implode(' & ', $tables_data_keys).")");
				if(empty($details_tables_data)) migrationDie("ERR_FUNCTION_customInsertRecord(): Table 'specimen_details' or 'derivative_details' is missing (See table names: ".implode(' & ', $tables_data_keys).")");
				if(sizeof($tables_data) != 1) migrationDie("ERR_FUNCTION_customInsertRecord(): Wrong 3 tables names for a new sample (See table names: ".implode(' & ', $tables_data_keys).")");
				$details_tables_data[] = array('name' => $details_table_name, 'data' => $tables_data[$details_table_name]);
				break;
			case '2':
				$details_table_name = '';
				foreach($tables_data_keys as $table_name) {
					if(in_array($table_name, array('specimen_details', 'derivative_details', 'sample_masters'))) {
						migrationDie("ERR_FUNCTION_customInsertRecord(): Table 'sample_masters', 'specimen_details' or 'derivative_details' defined for a record different than Sample or wrong tables definition for a sample creation (See table names: ".implode(' & ', $tables_data_keys).")");
						exit;
					} else if(preg_match('/_masters$/', $table_name)) {
						$main_table_data = array('name' => $table_name, 'data' => $tables_data[$table_name]);
						unset($tables_data[$table_name]);
					} else {
						$details_table_name = $table_name;
					}
				}
				if(empty($main_table_data)) migrationDie("ERR_FUNCTION_customInsertRecord(): Table %%_masters is missing (See table names: ".implode(' & ', $tables_data_keys).")");
				if(sizeof($tables_data) != 1) migrationDie("ERR_FUNCTION_customInsertRecord(): Wrong 2 tables names for a master/detail model record (See table names: ".implode(' & ', $tables_data_keys).")");
				$details_tables_data[] = array('name' => $details_table_name, 'data' => $tables_data[$details_table_name]);
				break;
			default:
				migrationDie("ERR_FUNCTION_customInsertRecord(): Too many tables passed in arguments: ".implode(', ',$tables_data_keys).".");
		}
		//-- 2 -- Main or master table record
		if(isset($main_table_data['data']['sample_control_id'])) {
			if(!isset($atim_controls['sample_controls']['***id_to_type***'][$main_table_data['data']['sample_control_id']])) migrationDie('ERR_FUNCTION_customInsertRecord(): Unsupported sample control id.');
			$sample_type = $atim_controls['sample_controls']['***id_to_type***'][$main_table_data['data']['sample_control_id']];
			if($atim_controls['sample_controls'][$sample_type]['sample_category'] == 'specimen') {
				if(isset($main_table_data['data']['initial_specimen_sample_id'])) unset($main_table_data['data']['initial_specimen_sample_id']);
				$main_table_data['data']['initial_specimen_sample_type'] = $sample_type;
				if(isset($main_table_data['data']['parent_id'])) unset($main_table_data['data']['parent_id']);
				if(isset($main_table_data['data']['parent_sample_type'])) unset($main_table_data['data']['parent_sample_type']);
				if(!isset($main_table_data['data']['sample_code'])) $main_table_data['data']['sample_code'] = "@@@TO_GENERATE@@";
			} else {
				if(!isset($main_table_data['data']['initial_specimen_sample_id'])) migrationDie('ERR_FUNCTION_customInsertRecord(): Missing sample information : initial_specimen_sample_id.');
				if(!isset($main_table_data['data']['initial_specimen_sample_type'])) migrationDie('ERR_FUNCTION_customInsertRecord(): Missing sample information : initial_specimen_sample_type.');
				if(!isset($main_table_data['data']['parent_id'])) migrationDie('ERR_FUNCTION_customInsertRecord(): Missing sample information : parent_id.');
				if(!isset($main_table_data['data']['parent_sample_type'])) migrationDie('ERR_FUNCTION_customInsertRecord(): Missing sample information : parent_sample_type.');
				if(!isset($main_table_data['data']['sample_code'])) $main_table_data['data']['sample_code'] = "@@@TO_GENERATE@@";
			}
		}
		$main_table_data['data'] = array_merge($main_table_data['data'], array("created" => $import_date, "created_by" => $imported_by, "modified" => "$import_date", "modified_by" => $imported_by));
		$query = "INSERT INTO `".$main_table_data['name']."` (`".implode("`, `", array_keys($main_table_data['data']))."`) VALUES (\"".implode("\", \"", array_values($main_table_data['data']))."\")";
		$record_id = customQuery($query, true);
		if(isset($main_table_data['data']['diagnosis_control_id'])) {
			if(in_array($main_table_data['data']['diagnosis_control_id'], $atim_controls['diagnosis_controls']['***primary_control_ids***'])) {
				customQuery("UPDATE diagnosis_masters SET primary_id=id WHERE id = $record_id;", true);
			} else {
				if(!isset($main_table_data['data']['primary_id']) || !isset($main_table_data['data']['parent_id']))
					migrationDie('ERR_FUNCTION_customInsertRecord(): Missing diagnosis_masters primary_id or parent_id key.');
			}
		} else if(isset($main_table_data['data']['sample_control_id'])) {
			$sample_type = $atim_controls['sample_controls']['***id_to_type***'][$main_table_data['data']['sample_control_id']];
			$set_strings = array();
			if($atim_controls['sample_controls'][$sample_type]['sample_category'] == 'specimen') $set_strings[] = "initial_specimen_sample_id=id";
			if($main_table_data['data']['sample_code'] == "@@@TO_GENERATE@@") $set_strings[] = "sample_code=id";
			if($set_strings) {
				customQuery("UPDATE sample_masters SET ".implode(',',$set_strings)." WHERE id = $record_id;", true);
			}
		}			
		//-- 3 -- Details tables record
		if(isset($main_table_data['data']['sample_control_id'])) {
			if(sizeof($details_tables_data) != 2) migrationDie("ERR_FUNCTION_customInsertRecord(): Table 'specimen_details', 'derivative_details' or 'SampleDetail' is missing (See table names: ".implode(' & ', $tables_data_keys).")");
		} else {
			if(sizeof($details_tables_data) > 2) migrationDie("ERR_FUNCTION_customInsertRecord(): Too many tables are declared (>2) (See table names: ".implode(' & ', $tables_data_keys).")");
		}
		$tmp_detail_tablename = null;
		if($details_tables_data) {
			$foreign_key = str_replace('_masters', '_master_id', $main_table_data['name']);
			foreach($details_tables_data as $detail_table) {
				$detail_table['data'] = array_merge($detail_table['data'], array($foreign_key => $record_id));
				$query = "INSERT INTO `".$detail_table['name']."` (`".implode("`, `", array_keys($detail_table['data']))."`) VALUES (\"".implode("\", \"", array_values($detail_table['data']))."\")";
				customQuery($query, true);
				if(!in_array($detail_table['name'], array('specimen_details', 'derivative_details'))) $tmp_detail_tablename = $detail_table['name'];
			}
		}
		//-- 4 -- Keep updated tables in memory
		addToModifiedDatabaseTablesList($main_table_data['name'], $tmp_detail_tablename);
	}
	return $record_id;
}

/**
 * Update an ATim table record.
 * 
 * @param string $id Id of the record to update
 * @param array $tables_data Data to update (see format above)
 */
function updateTableData($id, $tables_data) {
	global $import_date;
	global $imported_by;
	if($tables_data) {
		$tables_data_keys = array_keys($tables_data);
		$to_update = false;
		//Flush empty field
		foreach($tables_data as $table_name => $table_fields_and_data) {
			foreach($table_fields_and_data as $field => $data) {
				if(!strlen($data)) unset($tables_data[$table_name][$field]);
			}
		}
		//Check data passed in args
		$main_or_master_tablename = null;
		switch(sizeof($tables_data)) {
			case '1':
				$tmp_tables_data_keys = $tables_data_keys;
				$main_or_master_tablename = array_shift($tmp_tables_data_keys);
				if(!empty($tables_data[$main_or_master_tablename])) $to_update = true;
				break;
			case '2':
			case '3':
				foreach($tables_data_keys as $table_name) {
					if(preg_match('/_masters$/', $table_name)) {
						if(!is_null($main_or_master_tablename)) migrationDie("ERR_FUNCTION_updateTableData(): 2 Master tables passed in arguments: ".implode(', ',$tables_data_keys).".");
						$main_or_master_tablename = $table_name;
					}
					if(!empty($tables_data[$table_name])) $to_update = true;
				}
				if(is_null($main_or_master_tablename)) migrationDie("ERR_FUNCTION_updateTableData(): Master table not passed in arguments: ".implode(', ',$tables_data_keys).".");
				break;
			default:
				migrationDie("ERR_FUNCTION_updateTableData(): Too many tables passed in arguments: ".implode(', ',$tables_data_keys).".");
		}
		if($to_update) {
			//Master/Main Table Update
			$table_name = $main_or_master_tablename;
			$table_data = $tables_data[$main_or_master_tablename];
			unset($tables_data[$main_or_master_tablename]);
			$set_sql_strings = array();
			foreach(array_merge($table_data, array('modified' => $import_date, 'modified_by' => $imported_by))  as $key => $value) $set_sql_strings[] = "`$key` = \"$value\"";
			$query = "UPDATE `$table_name` SET ".implode(', ', $set_sql_strings)." WHERE `id` = $id;";
			customQuery($query);
			//Detail or SpecimenDetail/DerivativeDetail Table Update
			$foreaign_key = str_replace('_masters', '_master_id', $main_or_master_tablename);
			$tmp_detail_tablename = null;
			foreach($tables_data as $table_name => $table_data) {
				if(!empty($table_data)) {
					$set_sql_strings = array();
					foreach($table_data  as $key => $value) $set_sql_strings[] = "`$key` = \"$value\"";
					$query = "UPDATE `$table_name` SET ".implode(', ', $set_sql_strings)." WHERE `$foreaign_key` = $id;";
					customQuery($query);
					if(!in_array($table_name, array('specimen_details', 'derivative_details'))) $tmp_detail_tablename = $table_name;
				}
			}
			//Keep updated tables in memory
			addToModifiedDatabaseTablesList($main_or_master_tablename, $tmp_detail_tablename);
		}
	}	
}

function addToModifiedDatabaseTablesList($main_table_name, $detail_table_name) {
	global $modified_database_tables_list;
	$key = $main_table_name.'-'.(is_null($detail_table_name)? '' : $detail_table_name);
	$modified_database_tables_list[$key] = array($main_table_name, $detail_table_name);
}

/**
 * Insert into revs table data created or updated . 
 * When no parameter is set, the system will insert any record created or modified by 
 * follwing functions:
 * 		- customInsertRecord()
 * 		- updateTableData()
 *
 * @param unknown $main_table_name Name of the main table
 * @param unknown $detail_table_name Name of the detail table name
 */
function insertIntoRevsBasedOnModifiedValues($main_tablename = null, $detail_tablename = null) {
	global $import_date;
	global $imported_by;
	global $atim_controls;
	global $modified_database_tables_list;
	
	$tables_sets_to_update = $modified_database_tables_list;
	if(!is_null($main_tablename)) {
		$key = $main_tablename.'-'.(is_null($detail_tablename)? '' : $detail_tablename);
		$tables_sets_to_update = array($key => array($main_tablename, $detail_tablename));
	}
	
	//Check masters model alone
	$initial_tables_sets_to_update = $tables_sets_to_update;
	foreach($initial_tables_sets_to_update as $key => $tmp) {
		if(preg_match('/^([a-z]+_masters)\-$/', $key, $matches)) {
			$master_table_name = $matches[1];
			$control_table_name = str_replace('_masters', '_controls', $master_table_name);
			if(!isset($atim_controls[$control_table_name])) migrationDie("ERR 778894003 #$master_table_name.$control_table_name");
			foreach($atim_controls[$control_table_name] as $control_data) {
				if(is_array($control_data) && isset($control_data['detail_tablename'])) {
					$detail_table_name = $control_data['detail_tablename'];
					$new_key = "$master_table_name-$detail_table_name";
					if(!isset($tables_sets_to_update[$new_key])) $tables_sets_to_update[$new_key] = array("$master_table_name", "$detail_table_name");
				}
			}
			unset($tables_sets_to_update[$key]);
		}
	}
	
	//Insert Into Revs
	$insert_queries = array();
	$set_key_done = array();
	foreach($tables_sets_to_update as $new_tables_set) {
		list($main_tablename, $detail_tablename) = $new_tables_set;
		if(!$detail_tablename) {
			// *** CLASSICAL MODEL ***
			$query = "DESCRIBE $main_tablename;";
			$results = customQuery($query);
			$table_fields = array();
			while($row = $results->fetch_assoc()) {
				if(!in_array($row['Field'], array('created','created_by','modified','modified_by','deleted'))) $table_fields[] = $row['Field'];
			}
			$source_table_fields = (empty($table_fields)? '' : '`'.implode('`, `',$table_fields).'`, ')."`modified_by`, `modified`";
			$revs_table_fields = (empty($table_fields)? '' : '`'.implode('`, `',$table_fields).'`, ').'`modified_by`, `version_created`';
			$insert_queries[] = "INSERT INTO `".$main_tablename."_revs` ($revs_table_fields)
				(SELECT $source_table_fields FROM `$main_tablename` WHERE `modified_by` = '$imported_by' AND `modified` = '$import_date');";
		} else {
			// *** MASTER DETAIL MODEL ***
			if(!preg_match('/^.+\_masters$/', $main_tablename)) migrationDie("ERR_FUNCTION_insertIntoRevsBasedOnModifiedValues(): '$main_tablename' is not a 'Master' table of a MasterDetail model.");
			$foreign_key = str_replace('_masters', '_master_id', $main_tablename);
			//Master table
			$query = "DESCRIBE $main_tablename;";
			$results = customQuery($query);
			$table_fields = array();
			while($row = $results->fetch_assoc()) {
				if(!in_array($row['Field'], array('created','created_by','modified','modified_by','deleted'))) $table_fields[] = $row['Field'];
			}
			$source_table_fields = (empty($table_fields)? '' : 'Master.`'.implode('`, Master.`',$table_fields).'`, ')."Master.`modified_by`, Master.`modified`";
			$revs_table_fields = (empty($table_fields)? '' : '`'.implode('`, `',$table_fields).'`, ').'`modified_by`, `version_created`';
			$insert_queries[] = "INSERT INTO `".$main_tablename."_revs` ($revs_table_fields)
				(SELECT $source_table_fields FROM `$main_tablename` AS Master INNER JOIN $detail_tablename AS Detail ON Master.`id` = Detail.`$foreign_key` WHERE Master.`modified_by` = '$imported_by' AND Master.`modified` = '$import_date');";
			//Detail table
			$all_detail_tablenames = ($main_tablename != 'sample_masters')? array($detail_tablename) : array($detail_tablename, 'specimen_details', 'derivative_details');
			foreach($all_detail_tablenames as $detail_tablename) {
				$query = "DESCRIBE $detail_tablename;";
				$results = customQuery($query);
				$table_fields = array();
				while($row = $results->fetch_assoc()) $table_fields[] = $row['Field'];
				if(!in_array($foreign_key, $table_fields)) migrationDie("ERR_FUNCTION_insertIntoRevsBasedOnModifiedValues(): Foreign Key '$foreign_key' defined based on 'Master' table name '$main_tablename' is not a field of the 'Detail' table '$detail_tablename'.");
				$source_table_fields = (empty($table_fields)? '' : 'Detail.`'.implode('`, Detail.`',$table_fields).'`, ')."Master.`modified`";
				$revs_table_fields = (empty($table_fields)? '' : '`'.implode('`, `',$table_fields).'`, ').'`version_created`';
				$insert_queries[] = "INSERT INTO `".$detail_tablename."_revs` ($revs_table_fields)
					(SELECT $source_table_fields FROM `$main_tablename` AS Master INNER JOIN `$detail_tablename` AS Detail ON Master.`id` = Detail.`$foreign_key` WHERE Master.`modified_by` = '$imported_by' AND Master.`modified` = '$import_date');";
			}
		}
	}
	foreach($insert_queries as $query) {
		customQuery($query, true);
	}
}

// ---- VALUE DOMAIN FUNCTIONS & EXCEL LIST VALIDATION FUNCTION --------------------------------------------------------------------------------------------------------------------

global $domains_values;
$domains_values = array();

/**
 * Validate a value is a value of a system list or a custom drop down list and return
 * the formatted value as it exists into database (when cases are different).
 * 
 * @param unknown $value Value to validate
 * @param unknown $domain_name Domain name of the list
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 * 
 * @return string Formatted value to use to match value in database. 
 */
function validateAndGetStructureDomainValue($value, $domain_name, $summary_section_title, $summary_title_add_in = '', $summary_details_add_in = '') {
	global $import_summary;
	global $domains_values;
	if(!array_key_exists($domain_name, $domains_values)) {
		$domains_values[$domain_name] = array();
		$domain_data_results = getSelectQueryResult("SELECT id, source FROM structure_value_domains WHERE domain_name = '$domain_name'");
		if(!empty($domain_data_results)) {
			$domain_data_results = $domain_data_results[0];
			if($domain_data_results['source']) {
				if(preg_match('/getCustomDropdown\(\'(.*)\'\)/', $domain_data_results['source'], $matches)) {
					$query = "SELECT val.value
						FROM structure_permissible_values_custom_controls AS ct
						INNER JOIN structure_permissible_values_customs val ON val.control_id = ct.id
						WHERE ct.name = '".$matches[1]."';";
					foreach(getSelectQueryResult($query) as $domain_value)  $domains_values[$domain_name][strtolower($domain_value['value'])] = $domain_value['value'];
				} else {
					migrationDie("ERR_STRUCTURE_DOMAIN: Source value format for domain_name '$domain_name' is not supported by the migration process.");
				}
			} else {
				$query = "SELECT val.value
					FROM structure_permissible_values val
					INNER JOIN structure_value_domains_permissible_values link ON link.structure_permissible_value_id = val.id
					WHERE link.structure_value_domain_id = ".$domain_data_results['id']." AND link.flag_active = '1';";
				foreach(getSelectQueryResult($query) as $domain_value) $domains_values[$domain_name][strtolower($domain_value['value'])] = $domain_value['value'];
			}
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', "Wrong '$domain_name'".(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "The '$domain_name' Structure Domain (defined as domain of value '$value') does not exist. The value will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
			$value = '';
		}
	}
	if(strlen($value)) {
		if(array_key_exists(strtolower($value), $domains_values[$domain_name])) {
			$value = $domains_values[$domain_name][strtolower($value)];	//To set the right case
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', "Wrong '$domain_name' Value".(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Value '$value' is not a value of the '$domain_name' Structure Domain. The value will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]")); 
			$value = '';
		}
	}
	return $value;
}

/**
 * Test an excel value match a predefined list of values and return the value to record in database.
 * 
 * @param string $value Value to test
 * @param array $values_matches List of good values (key being a value you can find in excel and value being the value to record in database (ex: array('yes' => 'y', 'y' => 'y', 'unknown' => 'u', '?' => 'u', etc)
 * @param string $str_to_lower Should the case of the value to test change to lower for test
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 * 
 * @return string Tested value (in lower case if expected)
 */
function validateAndGetExcelValueFromList($value, $values_matches, $str_to_lower = false, $summary_section_title = '', $summary_title_add_in = '', $summary_details_add_in = '') {
	if(strlen($value)) {
		if($str_to_lower) $value = strtolower($value);
		if(array_key_exists($value, $values_matches)) {
			$value = $values_matches[$value];
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', "Wrong Excel Value".(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Value '$value' is not a supported excel value. The value will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]")); 
			$value = '';
		}
	}
	return $value;
}

// ---- DATE & DATETIME ------------------------------------------------------------------------------------------------------------------------------------------------------------

global $empty_date_time_values;
$empty_date_time_values = array('-', 'n/a', 'x', '??', 'nd');

/**
 * Test excel value is a date and return the formatted date for database and the accuracy.
 *
 * @param string $date Value of the excel date
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 *
 * @return array array($formatted_date, $accuracy)
 */
function validateAndGetDateAndAccuracy($date, $summary_section_title, $summary_title_add_in, $summary_details_add_in) {
	global $import_summary;
	global $empty_date_time_values;
	global $xls_offset;
	$date = str_replace(' ', '', $date);
	if(empty($date) || in_array(strtolower($date), $empty_date_time_values)) {
		return array('', '');
	} else if(preg_match('/^([0-9]+)$/', $date, $matches)) {
		//format excel date integer representation
		$php_offset = 946746000;//2000-01-01 (12h00 to avoid daylight problems)
		$formated_date = date("Y-m-d", $php_offset + (($date - $xls_offset) * 86400));
		if(preg_match('/^(19|20)([0-9]{2})$/',$date)) {
			recordErrorAndMessage($summary_section_title, '@@WARNING@@', 'Date Format Warning'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "The excel date value '$date' is considered by the migration process as an Excel formated date 'xxxx-xx-xx' but please validate it's not just the four digits of a year. Migrated date will be '$formated_date'.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
		}
		return array($formated_date, 'c');
	} else if(preg_match('/^(19|20)([0-9]{2})\-([01][0-9])\-([0-3][0-9])$/',$date,$matches)) {
		return array($date, 'c');
	} else if(preg_match('/^(19|20)([0-9]{2})\-([01][0-9])$/',$date,$matches)) {
		return array($date.'-01', 'd');
	} else if(preg_match('/^((19|20)([0-9]{2})\-([01][0-9]))\-((xx)|(unk))$/',$date,$matches)) {
		return array($matches[1].'-01', 'd');
	} else if(preg_match('/^((19|20)([0-9]{2}))\-((xx)|(unk))\-((xx)|(unk))$/',$date,$matches)) {
		return array($matches[1].'-01-01', 'm');
	} else if(preg_match('/^([0-3][0-9])\/([01][0-9])\/(19|20)([0-9]{2})$/',$date,$matches)) {
		return array($matches[3].$matches[4].'-'.$matches[2].'-'.$matches[1], 'c');
	} else if(preg_match('/^([0-3][0-9])\-([01][0-9])\-(19|20)([0-9]{2})$/',$date,$matches)) {
		return array($matches[3].$matches[4].'-'.$matches[2].'-'.$matches[1], 'c');
	} else {
		recordErrorAndMessage($summary_section_title, '@@ERROR@@', 'Date Format Error'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of the date '$date' is not supported! The date will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
		return array('', '');
	}
}

/**
 * Test excel values are component of a datetime and return the formatted datetime for database and the accuracy.
 *
 * @param string $date Value of the excel date
 * @param string $time Value of the excel time
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 *
 * @return array array($formatted_datetime, $accuracy)
 */
function validateAndGetDatetimeAndAccuracy($date, $time, $summary_section_title, $summary_title_add_in, $summary_details_add_in) {
	global $import_summary;
	global $empty_date_time_values;
	$date = str_replace(' ', '', $date);
	$time = str_replace(' ', '', $time);
	//** Get Date **
	list($formatted_date, $formatted_date_accuracy) = validateAndGetDateAndAccuracy($date, $summary_section_title, $summary_title_add_in, $summary_details_add_in);
	if(!$formatted_date) {
		if(!empty($time) && !in_array(strtolower($time), $empty_date_time_values)) {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', 'DateTime Format Error: Date Is Missing'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of the datetime '$date $time' is not supported! The datetime will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
		}
		return array('', '');
	} else {
		//Combine date and time
		if(empty($time) || in_array(strtolower($time), $empty_date_time_values)) {
			return array($formatted_date.' 00:00', str_replace('c', 'h', $formatted_date_accuracy));
		} else {
			if($formatted_date_accuracy != 'c') {
				recordErrorAndMessage($summary_section_title, '@@ERROR@@', 'Time Set for an Inaccurate Date'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Date and time are set but date is inaccurate. The datetime will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
				return array('', '');
			} else if(preg_match('/^(0{0,1}[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/',$time, $matches)) {
				return array($formatted_date.' '.((strlen($time) == 5)? $time : '0'.$time), 'c');
			} else if(preg_match('/^0\.[0-9]+$/', $time)) {
				$hour = floor(24*$time);
				$mn = round((24*$time - $hour)*60);
				$mn = (strlen($mn) == 1)? '0'.$mn  : $mn ;
				if($mn == '60') {
					$mn = '00';
					$hour += 1;
				}
				if($hour > 23) migrationDie("ERR_GET_DATETIME: Hour value > 24.");
				$time=$hour.':'.$mn;
				return array($formatted_date.' '.((strlen($time) == 5)? $time : '0'.$time), 'c');
			} else {
				recordErrorAndMessage($summary_section_title, '@@ERROR@@', 'Time Format Error'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of the datetime '$date $time' is not supported! The datetime will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
				return array('', '');
			}
		}
	}
}

/**
 * Test excel values are time and return the formatted time for database and the accuracy.
 *
 * @param string $time Value of the excel time
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 *
 * @return string $formatted_time
 */
function validateAndGetTime($time, $summary_section_title, $summary_title_add_in, $summary_details_add_in) {
	global $import_summary;
	global $empty_date_time_values;
	$time = str_replace(' ', '', $time);
	if(empty($time) || in_array(strtolower($time), $empty_date_time_values)) {
		return '';
	} else {
		if(preg_match('/^(0{0,1}[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/',$time, $matches)) {
			return (strlen($time) == 5)? $time : '0'.$time;
		} else if(preg_match('/^0\.[0-9]+$/', $time)) {
			$hour = floor(24*$time);
			$mn = round((24*$time - $hour)*60);
			$mn = (strlen($mn) == 1)? '0'.$mn  : $mn ;
			if($mn == '60') {
				$mn = '00';
				$hour += 1;
			}
			if($hour > 23) die('ERR time >= 24 79904044--4-44');
			$time=$hour.':'.$mn;
			return (strlen($time) == 5)? $time : '0'.$time;
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', 'Time Format Error'.(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of time '$time' is not supported! The time will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
			return '';
		}
	}
}

// ---- NUMBER ---------------------------------------------------------------------------------------------------------------------------------------------------------------------

global $empty_number_values;
$empty_number_values = array('-', 'n/a', 'x', '??', 'nd');

/**
 * Test excel value is a decimal
 *
 * @param string $decimal_value Exel value
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 *
 * @return string $formatted_value
 */
function validateAndGetDecimal($decimal_value, $summary_section_title, $summary_title_add_in, $summary_details_add_in) {
	global $import_summary;
	global $empty_number_values;
	$decimal_value = str_replace(array(' ', ','), array('', '.'), $decimal_value);
	if(strlen($decimal_value)) {
		if(preg_match('/^[0-9]+([\.,][0-9]+){0,1}$/', $decimal_value)) {
			return $decimal_value;
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', "Wrong Decimal Format".(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of decimal '$decimal_value' is not supported! The value will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
			return '';
		}
	} else {
		return '';
	}
}

/**
 * Test excel value is an integer
 *
 * @param string $integer_value Exel value
 * @param unknown $summary_section_title Section title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_title_add_in Additional information to add to summary title if an error is generated by the function (See recordErrorAndMessage() description)
 * @param string $summary_details_add_in Additional information to add to seummary detail if an error is generated by the function (See recordErrorAndMessage() description)
 *
 * @return string $formatted_value
 */
function validateAndGetInteger($integer_value, $summary_section_title, $summary_title_add_in, $summary_details_add_in) {
	global $import_summary;
	global $empty_number_values;
	$integer_value = str_replace(array(' '), array(''), $integer_value);
	if(strlen($integer_value)) {
		if(preg_match('/^[0-9]+$/', $integer_value)) {
			return $integer_value;
		} else {
			recordErrorAndMessage($summary_section_title, '@@ERROR@@', "Wrong Integer Format".(empty($summary_title_add_in)? '' : ' - '.$summary_title_add_in), "Format of integer '$integer_value' is not supported! The value will be erased.".(empty($summary_details_add_in)? '' : " [$summary_details_add_in]"));
			return '';
		}
	} else {
		return '';
	}
}

// ---- EXCEL FILE -----------------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Test excel files exist
 * 
 * @param array $file_names List of excel files names to test
 * 
 * @return boolean TRUE if all files have been found / FALSE if not
 */
function testExcelFile($file_names) {
	global $import_summary;
	global $excel_files_paths;
	$validated = true;
	foreach($file_names as $excel_file_name) {	
		if(!file_exists($excel_files_paths.$excel_file_name)) {
			recordErrorAndMessage('Excel Data Reading', '@@ERROR@@', "Non-Existent File", "File '$excel_file_name' in directory ($excel_files_paths) does not exist. File won't be parsed.", $excel_file_name);
			$validated = false;
		}
		if(!preg_match('/\.xls$/', $excel_file_name)) {
			recordErrorAndMessage('Excel Data Reading', '@@ERROR@@', "Wrong File Extension", "File '$excel_file_name' in directory ($excel_files_paths) is not a '.xls' file. File won't be parsed.", $excel_file_name);
			$validated = false;
		}
	}
	return $validated;
}

/*
 * @$file_xls_offset Serial number $windows_xls_offset = 36526 & $mac_xls_offset = 35064;
 */
/**
 * Get the next excel line data. The process will restart at the begining of a file worksheet
 * anytime the file name or the worksheet changes.
 *
 * @param string $excel_file_name Name of the excel file
 * @param string $worksheet_name Studied worksheet
 * @param string $header_lines_nbr The number of header lines
 * @param string $file_xls_offset Serial number $windows_xls_offset = 36526 & $mac_xls_offset = 35064 (Use for excel date parsing)
 * 
 * @return array New excel line data gathered in array
 */
function getNextExcelLineData($excel_file_name, $worksheet_name, $header_lines_nbr = 1, $file_xls_offset = '36526') {
	global $import_summary;
	global $XlsReader;
	global $excel_files_paths;
	global $studied_excel_file_name_properties;
	global $xls_offset;
	
	$xls_offset = $file_xls_offset;

	// ** LOAD NEW EXCEL FILE **

	if(is_null($studied_excel_file_name_properties) || $studied_excel_file_name_properties['file_name'] != $excel_file_name) {
		if(!testExcelFile(array($excel_file_name))) return false;
		//Load Excel Data
		$XlsReader = new Spreadsheet_Excel_Reader();	
		$XlsReader->read($excel_files_paths.$excel_file_name);
		//Set studied_excel_file_name_properties
		$studied_excel_file_name_properties = array('file_name' => $excel_file_name, 'file_worksheets' => array());
		foreach($XlsReader->boundsheets as $key => $tmp) $studied_excel_file_name_properties['file_worksheets'][$tmp['name']] = $key;
	}

	// ** SET NEW WOKRSHEET DATA **

	if(!array_key_exists('worksheet_name', $studied_excel_file_name_properties) || $studied_excel_file_name_properties['worksheet_name'] != $worksheet_name) {
		if(!array_key_exists($worksheet_name, $studied_excel_file_name_properties['file_worksheets'])) {
			recordErrorAndMessage('Excel Data Reading', '@@ERROR@@', "Non-Existent Worksheet", "Worksheet '$worksheet_name' is not a worksheet of file '$excel_file_name'. Worksheet won't be parsed.");
			return false;
		} else if(!isset($XlsReader->sheets[$studied_excel_file_name_properties['file_worksheets'][$worksheet_name]]['cells']) || empty($XlsReader->sheets[$studied_excel_file_name_properties['file_worksheets'][$worksheet_name]]['cells'])) {
			//Empty worksheet
			recordErrorAndMessage('Excel Data Reading', '@@WARNING@@', "EMpty Worksheet", "Worksheet '$worksheet_name' of file '$excel_file_name' is empty. Worksheet won't be parsed.");
			return false;
		} else {
			$studied_excel_file_name_properties['worksheet_name'] = $worksheet_name;
			$studied_excel_file_name_properties['headers'] = array();
			$tmp_worksheet_line_counters = array_keys($XlsReader->sheets[$studied_excel_file_name_properties['file_worksheets'][$worksheet_name]]['cells']);
			$studied_excel_file_name_properties['last_worksheet_line_counter'] = end($tmp_worksheet_line_counters);
			$studied_excel_file_name_properties['last_studied_line_counter'] = '0';
		}
	}
	$excel_file_handler = $XlsReader->sheets[$studied_excel_file_name_properties['file_worksheets'][$worksheet_name]];
	
	// ** LOAD EXCEL HEADER **

	if($studied_excel_file_name_properties['headers'] == '-1') {
		return false;
	} else if(empty($studied_excel_file_name_properties['headers'])) {
		if(!preg_match('/^[1-9][0-9]*$/', $header_lines_nbr)) {
			recordErrorAndMessage('Excel Data Reading', '@@ERROR@@', "Excel Headers Definition", "The number of header lines [$header_lines_nbr] passed in arguments to the function getNextExcelLineData() is not supported. No data will be parsed. (Defintion used for worksheet '$worksheet_name' of file '$excel_file_name')");
			return false;
		} else {
			//Get Header Excel Line Numbers (in case there are some empty line before or between headers)
			$header_excel_line_numbers = array();
			$detected_header_lines_counter = 0;
			while($detected_header_lines_counter < $header_lines_nbr && $studied_excel_file_name_properties['last_studied_line_counter'] <= $studied_excel_file_name_properties['last_worksheet_line_counter']) {
				$studied_excel_file_name_properties['last_studied_line_counter']++;
				if(array_key_exists($studied_excel_file_name_properties['last_studied_line_counter'], $excel_file_handler['cells'])) {
					$header_excel_line_numbers[] = $studied_excel_file_name_properties['last_studied_line_counter'];
					$detected_header_lines_counter++;
				}
			}
			if(empty($header_excel_line_numbers) || sizeof($header_excel_line_numbers) != $header_lines_nbr) {
				recordErrorAndMessage('Excel Data Reading', '@@ERROR@@', "Missing or Incomplete Header", "No header (or incomplete header) exsists into worksheet '$worksheet_name' of file '$excel_file_name'. No data will be parsed.");
				$studied_excel_file_name_properties['headers'] = '-1';
				return false;
			}
			//Build Header
			$header_excel_line_numbers = array_reverse($header_excel_line_numbers);
			$headers = array();
			foreach($header_excel_line_numbers as $line_number) {
				if(empty($headers)) {
					$headers = $excel_file_handler['cells'][$line_number];
				} else {				
					foreach($excel_file_handler['cells'][$line_number] as $key => $title){
						if(isset($headers[$key])){
							$colspan = isset($excel_file_handler['cellsInfo'][$line_number]) && isset($excel_file_handler['cellsInfo'][$line_number][$key]) && isset($excel_file_handler['cellsInfo'][$line_number][$key]['colspan']) && is_numeric($excel_file_handler['cellsInfo'][$line_number][$key]['colspan']) ? $excel_file_handler['cellsInfo'][$line_number][$key]['colspan'] : 1;
							for($i = $colspan - 1; $i >= 0; -- $i){
								$headers[$key + $i] = $title." ".$headers[$key + $i];
							}
						}else{
							$headers[$key] = $title;
						}
					}
				}
			}
			ksort($headers);
			foreach($headers as $key => $title) {
				$title = trim(str_replace(array("\n", "  "), array(' ', ' '), $title));
				$studied_excel_file_name_properties['headers'][$key] = $title;
			}		
		}
	}

	// ** LOAD NEW EXCEL LINE DATA **

	while($studied_excel_file_name_properties['last_studied_line_counter'] <= $studied_excel_file_name_properties['last_worksheet_line_counter']) {
		$studied_excel_file_name_properties['last_studied_line_counter']++;
		if(array_key_exists($studied_excel_file_name_properties['last_studied_line_counter'], $excel_file_handler['cells'])) {
			$formatted_new_line_data = array();
			$new_excel_line_data = $excel_file_handler['cells'][$studied_excel_file_name_properties['last_studied_line_counter']];
			$data_found = false;
			foreach($studied_excel_file_name_properties['headers'] as $key => $field) {
				if(isset($new_excel_line_data[$key])) {
					$formatted_new_line_data[trim(utf8_encode($field))] = trim(utf8_encode(str_replace('"', "'", $new_excel_line_data[$key])));
					$data_found = true;
				} else {
					$formatted_new_line_data[trim(utf8_encode($field))] = '';
				}
			}
			if($data_found) return array($studied_excel_file_name_properties['last_studied_line_counter'], $formatted_new_line_data);
		}
	}
	
	// ** END OF FILE **
	
	return false;
}	
	
?>
		