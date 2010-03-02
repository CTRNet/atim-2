<?php
$consent_masters["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_consent.csv";
$consent_masters["app_data"]["pkey"] = "consent_code";

$consent_masters["master"]["date_of_referral"] = "";
$consent_masters["master"]["route_of_referral"] = "";
$consent_masters["master"]["date_first_contact"] = "";
$consent_masters["master"]["consent_signed_date"] = "";
$consent_masters["master"]["form_version"] = "consent_version_date";
$consent_masters["master"]["reason_denied"] = "";
$consent_masters["master"]["consent_status"] = "";
$consent_masters["master"]["process_status"] = "";
$consent_masters["master"]["status_date"] = "status_date";
$consent_masters["master"]["surgeon"] = "";
$consent_masters["master"]["operation_date"] = "";
$consent_masters["master"]["facility"] = "";
$consent_masters["master"]["notes"] = "";
$consent_masters["master"]["consent_method"] = "";
$consent_masters["master"]["translator_indicator"] = "";
$consent_masters["master"]["translator_signature"] = "";
$consent_masters["master"]["consent_person"] = "";
$consent_masters["master"]["facility_other"] = "";
$consent_masters["master"]["consent_master_id"] = "";
$consent_masters["master"]["acquisition_id"] = "";
$consent_masters["master"]["participant_id"] = "no_labo";
$consent_masters["master"]["consent_control_id"] = "control_id";
$consent_masters["master"]["type"] = "consent_type";

//do not modify this section
$consent_masters["app_data"]['parent_key'] = "participant_id";
$consent_masters["app_data"]['child'] = array();
$consent_masters["app_data"]['master_table_name'] = "consents";
$consent_masters["app_data"]['save_id'] = true;
$tables['consent_masters'] = $consent_masters;
//-------------------------------
