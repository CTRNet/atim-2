<?php

//First Line of any main.php file
require_once 'system.php';

//==============================================================================================
// Custom Require Section
//==============================================================================================

//TODO: include custom files if required
//require_once 'CustomFiles/cutsom_file.php';

//==============================================================================================
// Custom Variables
//==============================================================================================

//TODO: section to declare custom global variables if required
//global $custom_var;
//$custom_var = '';

//==============================================================================================
// Main Code
//==============================================================================================

$excel_file_name = 'file_atim_data.xls';

// ***********************************************************************
// Example: Display variable content (html format)
// ***********************************************************************
pr($excel_file_name);

// ***********************************************************************
// Example: Display the title of the migration in html format
// ***********************************************************************
displayMigrationTitle('Migration Example', array($excel_file_name));
// ***********************************************************************
// Example: Record an error or message, etc
// ***********************************************************************
recordErrorAndMessage('Patient Profile', '@@ERROR@@', "Bank patient unknown", "No ATim Patient matches excel patient ".$expecl_patient_identifier);
recordErrorAndMessage('Patient Profile', '@@WARNING@@', "Patient Vital Status Unsupported", "The vital status value '".$excel_patient_vital_status."' is not supported.", $excel_patient_vital_status);
// ***********************************************************************
// Example: Dispaly error and message
// ***********************************************************************
dislayErrorAndMessage(true);

// ***********************************************************************
// Example: Test excel file
// ***********************************************************************
if(!testExcelFile(array($excel_file_name))) {
	dislayErrorAndMessage();
	exit;
}
// ***********************************************************************
// Example: Parse excel file
// ***********************************************************************
$worksheet_name = 'Profile';
while(list($line_number, $excel_line_data) = getNextExcelLineData($excel_file_name, $worksheet_name, 2)) {
	//.....
}

// ***********************************************************************
// Example: Execute a specific query
// ***********************************************************************
customQuery("UPDATE drugs SET deleted = 1, modified = '$imported', modified_by = '$imported_by';");
// ***********************************************************************
// Example: Execute a select query
// ***********************************************************************
$atim_patient_data = getSelectQueryResult("SELECT p.* FROM participants p WHERE ....;");
// ***********************************************************************
// Example: Create a new diagnosis
// ***********************************************************************
$secondary_data = array(
	'diagnosis_masters' => array(
			'participant_id' => $atim_participant_id,
			'diagnosis_control_id' => $atim_controls['diagnosis_controls']['secondary-other']['id'],
			'primary_id' => $diagnosis_master_id,
			'parent_id' => $diagnosis_master_id,
			'dx_date' => $excel_metastasis_data['dx_date'],
			'dx_date_accuracy' => $excel_metastasis_data['dx_date_accuracy']),
	$atim_controls['diagnosis_controls']['secondary-other']['detail_tablename'] => array(
			'site' => $excel_metastasis_data['site']));
customInsertRecord($secondary_data);
// ***********************************************************************
// Example: Update a diagnosis
// ***********************************************************************
$diagnosis_data_to_update = array(
	'diagnosis_masters' => array(), //Nothing to update
	$atim_controls['diagnosis_controls']['primary-blood']['detail_tablename'] => array('text_field' => '...'));
updateTableData($atim_diagnosis_master_id, $diagnosis_data_to_update);
// ***********************************************************************
// Example: Insert into revs
// ***********************************************************************
insertIntoRevsBasedOnModifiedValues('drugs');
insertIntoRevsBasedOnModifiedValues();

// ***********************************************************************
// Example: Functions to validate values to created
// ***********************************************************************
//Value
$excel_patient_data['vital_status'] = validateAndGetStructureDomainValue(str_replace(array('dead'), array('deceased')), $excel_vital_status, 'health_status', 'Participant profile', "$worksheet_name::$excel_field:", "See Line:$line_number");
$yes_no_excel_values_list = array('unknown' => '', '?' => '', 'yes' => 'y', 'y' => 'y', 'oui' => 'y', 'no' => 'n', 'non' => 'n');
//Value
$excel_patient_data['qc_tf_death_from_prostate_cancer'] = validateAndGetExcelValueFromList($excel_line_data[$excel_field], $yes_no_excel_values_list, true, 'Participant profile', "$worksheet_name::$excel_field:", "See Line:$line_number");
//Date
$empty_date_time_values = array('-', 'n/a', 'x', '??', 'nd');
list($date_of_death, $date_of_death_accuracy) = validateAndGetDateAndAccuracy($excel_date_of_death, 'Participant profile', "$worksheet_name::$excel_field:", "See Line:$line_number");
list($date_of_collection, $date_of_collection_accuracy) =validateAndGetDatetimeAndAccuracy($excel_date, $excel_time, 'Collection', "$worksheet_name::$excel_field:", "See Line:$line_number");
//Number
$empty_number_values = array('-', 'n/a', 'x', '??', 'nd');
$height = validateAndGetDecimal($excel_height, 'Participant profile', "$worksheet_name::$excel_field:", "See Line:$line_number");
$age = validateAndGetInteger($excel_age, 'Participant profile', "$worksheet_name::$excel_field:", "See Line:$line_number");

//==================================================================================================================================================================================
// CUSTOM FUNCTIONS
//==================================================================================================================================================================================

//TODO: Section for custom functions if required
	
?>
		