<?php
$sd_der_plasmas["app_data"]["pkey"] = "sample_code_plasma";
$sd_der_plasmas["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_blood_out.csv";

$sd_der_plasmas["detail"]["hemolyze_signs"] = "";

$sd_der_plasmas["master"]["sample_code"] = "@plasma_tmp";//Required
$sd_der_plasmas["master"]["sop_master_id"] = "";
$sd_der_plasmas["master"]["product_code"] = "";
$sd_der_plasmas["master"]["is_problematic"] = "";
$sd_der_plasmas["master"]["notes"] = "";


//do not modify this section
$sd_der_plasmas["detail"]["sample_master_id"] = "sample_master_id";

$sd_der_plasmas["master"]["sample_type"] = "@plasma";
$sd_der_plasmas["master"]["sample_control_id"] = "@9";
$sd_der_plasmas["master"]["sample_category"] = "@derivative";
$sd_der_plasmas["master"]["initial_specimen_sample_type"] = "@blood";
$sd_der_plasmas["master"]["parent_id"] = "sample_code";
$sd_der_plasmas["master"]["collection_id"] = "collection_id";
$sd_der_plasmas["master"]["initial_specimen_sample_id"] = "initial_specimen_sample_id";

$sd_der_plasmas["app_data"]['child'][] = "ad_tubes_plasma";
$sd_der_plasmas["app_data"]['master_table_name'] = "sample_masters";
$sd_der_plasmas["app_data"]['detail_table_name'] = "sd_der_plasmas";
$sd_der_plasmas["app_data"]['detail_parent_key'] = "sample_master_id";
$sd_der_plasmas["app_data"]['parent_key'] = "parent_id";
$sd_der_plasmas["app_data"]['ask_parent']["collection_id"] = "collection_id";
$sd_der_plasmas["app_data"]['ask_parent']["id"] = "initial_specimen_sample_id";
$sd_der_plasmas["app_data"]['additional_queries'][] = "UPDATE sample_masters SET sample_code='P - %%last_master_insert_id%%' WHERE id=%%last_master_insert_id%%";
$tables['sd_der_plasmas'] = $sd_der_plasmas;
//-------------------------------
?>