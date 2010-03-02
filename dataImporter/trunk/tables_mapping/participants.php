<?php
$participants["app_data"]["pkey"] = "no_labo";
$participants["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_patient.csv";

$participants["master"]["title"] = "";
$participants["master"]["first_name"] = "first_name";
$participants["master"]["middle_name"] = "";
$participants["master"]["last_name"] = "last_name";
$participants["master"]["date_of_birth"] = "date_of_birth";
$participants["master"]["dob_date_accuracy"] = "";
$participants["master"]["marital_status"] = "";
$participants["master"]["language_preferred"] = "";
$participants["master"]["sex"] = "sex";
$participants["master"]["race"] = "";
$participants["master"]["vital_status"] = "";
$participants["master"]["notes"] = "memo";
$participants["master"]["date_of_death"] = "";
$participants["master"]["dod_date_accuracy"] = "";
$participants["master"]["cod_icd10_code"] = "";
$participants["master"]["secondary_cod_icd10_code"] = "";
$participants["master"]["cod_confirmation_source"] = "";
$participants["master"]["participant_identifier"] = "";
$participants["master"]["last_chart_checked_date"] = "";



//do not modify this section
$participants["app_data"]['child'][] = "consent_masters";
$participants["app_data"]['child'][] = "identifiers";
$participants["app_data"]['save_id'] = true;
$participants["app_data"]['master_table_name'] = "participants";
$tables['participants'] = $participants;
//-------------------------------
?>