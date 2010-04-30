<?php
$sd_der_serums["app_data"]["pkey"] = "serum_group_id";
$sd_der_serums["app_data"]["file"] = "/Users/francois-michellheureux/Documents/jewish/newData/bloodsOut.csv";

$sd_der_serums["detail"]["hemolyze_signs"] = "";

$sd_der_serums["master"]["sample_code"] = "@serum_tmp";//Required
$sd_der_serums["master"]["sop_master_id"] = "";
$sd_der_serums["master"]["product_code"] = "";
$sd_der_serums["master"]["is_problematic"] = "";
$sd_der_serums["master"]["notes"] = "";
$sd_der_serums["master"]["parent_id"] = "blood_id";


//do not modify this section
$sd_der_serums["detail"][0]["sample_master_id"] = "sample_master_id";
$sd_der_serums["detail"][1]["sample_master_id"] = "sample_master_id";

$sd_der_serums["master"]["sample_type"] = "@serum";
$sd_der_serums["master"]["sample_control_id"] = "@10";
$sd_der_serums["master"]["sample_category"] = "@derivative";
$sd_der_serums["master"]["initial_specimen_sample_type"] = "@blood";
$sd_der_serums["master"]["collection_id"] = "collection_id";
$sd_der_serums["master"]["initial_specimen_sample_id"] = "initial_specimen_sample_id";

$sd_der_serums["app_data"]['child'][] = "ad_tubes_serum";
$sd_der_serums["app_data"]['master_table_name'] = "sample_masters";
$sd_der_serums["app_data"]['detail_table_name'][0] = "sd_der_serums";
$sd_der_serums["app_data"]['detail_table_name'][1] = "derivative_details";
$sd_der_serums["app_data"]['detail_parent_key'] = "sample_master_id";
$sd_der_serums["app_data"]['parent_key'] = "parent_id";
$sd_der_serums["app_data"]['ask_parent']["collection_id"] = "collection_id";
$sd_der_serums["app_data"]['ask_parent']["id"] = "initial_specimen_sample_id";
$sd_der_serums["app_data"]['additional_queries'][] = "UPDATE sample_masters SET sample_code='SER - %%last_master_insert_id%%' WHERE id=%%last_master_insert_id%%";
$tables['sd_der_serums'] = $sd_der_serums;
//-------------------------------
?>